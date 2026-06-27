<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * E-signature management for the Registrar and Provost. The stored PNG is later
 * embedded on official documents — the Registrar's on admission letters now,
 * and (when built) the Provost's on transcripts, testimonials and statements
 * of result. Only these two roles may manage a signature.
 */
class SignatureController extends Controller
{
    private const ALLOWED_ROLES = ['registrar', 'provost'];

    public function edit()
    {
        $user = auth()->user();
        abort_unless(in_array($user->role, self::ALLOWED_ROLES, true), 403);

        return view('profile.signature', ['user' => $user]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        abort_unless(in_array($user->role, self::ALLOWED_ROLES, true), 403);

        $request->validate([
            'signature_file' => 'nullable|file|mimes:png|max:2048',
            'signature_data' => 'nullable|string',
        ]);

        $disk = config('filesystems.documents', 'local');
        $path = null;

        if ($request->hasFile('signature_file')) {
            $path = $request->file('signature_file')->store('signatures', $disk);
        } elseif ($request->filled('signature_data')) {
            // Drawn signature: a "data:image/png;base64,...." payload from the pad.
            $data = $request->input('signature_data');
            if (preg_match('/^data:image\/png;base64,/', $data)) {
                $binary = base64_decode(substr($data, strlen('data:image/png;base64,')), true);
                if ($binary !== false) {
                    $path = 'signatures/'.Str::uuid().'.png';
                    Storage::disk($disk)->put($path, $binary);
                }
            }
        }

        if (!$path) {
            return back()->with('error', 'Please upload a PNG signature or draw one before saving.');
        }

        // Replace any previous signature file.
        if ($user->signature_path && Storage::disk($disk)->exists($user->signature_path)) {
            Storage::disk($disk)->delete($user->signature_path);
        }

        $user->update(['signature_path' => $path]);
        ActivityLog::record('Updated e-signature', 'signature.update');

        return back()->with('success', 'Your e-signature has been saved. It will appear on the documents you authorise.');
    }

    public function destroy()
    {
        $user = auth()->user();
        abort_unless(in_array($user->role, self::ALLOWED_ROLES, true), 403);

        $disk = config('filesystems.documents', 'local');
        if ($user->signature_path && Storage::disk($disk)->exists($user->signature_path)) {
            Storage::disk($disk)->delete($user->signature_path);
        }
        $user->update(['signature_path' => null]);

        return back()->with('success', 'Signature removed.');
    }

    /** Stream the signature image for an authorised viewer (owner or document render). */
    public function show(\App\Models\User $user)
    {
        abort_unless($user->signature_path, 404);
        // Only same-college staff (or the owner) may fetch a signature image.
        $viewer = auth()->user();
        abort_unless(
            $viewer->id === $user->id
            || ($viewer->college_id && $viewer->college_id === $user->college_id),
            403
        );

        $disk = config('filesystems.documents', 'local');
        abort_unless(Storage::disk($disk)->exists($user->signature_path), 404);

        return Storage::disk($disk)->response($user->signature_path);
    }
}
