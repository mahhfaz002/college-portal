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

        // The signature is stored in the DATABASE (as a base64 data URI), not on
        // the object-storage disk — that disk write was the cause of the save
        // failures on production. A DB write has no external dependency. Wrapped
        // defensively so it can never 500.
        try {
            $dataUri = null;

            if ($request->hasFile('signature_file')) {
                $file = $request->file('signature_file');
                $bytes = @file_get_contents($file->getRealPath());
                if ($bytes !== false) {
                    $dataUri = 'data:'.($file->getMimeType() ?: 'image/png').';base64,'.base64_encode($bytes);
                }
            } elseif ($request->filled('signature_data')) {
                // Drawn signature: a "data:image/png;base64,...." payload from the pad.
                $data = $request->input('signature_data');
                if (preg_match('#^data:image/(png|jpe?g);base64,#i', $data)) {
                    $dataUri = $data;
                }
            }

            if (! $dataUri) {
                return back()->with('error', 'Please upload a PNG signature or draw one before saving.');
            }

            \App\Models\UserSignature::updateOrCreate(['user_id' => $user->id], ['data' => $dataUri]);
            // Keep signature_path as a non-null marker so existing existence
            // checks (@if($user->signature_path)) still light up.
            $user->forceFill(['signature_path' => 'db'])->save();

            ActivityLog::record('Updated e-signature', 'signature.update');

            return back()->with('success', 'Your e-signature has been saved. It will appear on the documents you authorise.');
        } catch (\Throwable $e) {
            \Log::error('Signature save failed', [
                'user' => $user->id, 'role' => $user->role,
                'exception' => get_class($e), 'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'We could not save your signature just now. Please try again — if it keeps failing, upload a small PNG instead of drawing.');
        }
    }

    public function destroy()
    {
        $user = auth()->user();
        abort_unless(in_array($user->role, self::ALLOWED_ROLES, true), 403);

        \App\Models\UserSignature::where('user_id', $user->id)->delete();

        // Best-effort cleanup of any legacy disk file.
        try {
            $disk = config('filesystems.documents', 'local');
            if ($user->signature_path && $user->signature_path !== 'db' && Storage::disk($disk)->exists($user->signature_path)) {
                Storage::disk($disk)->delete($user->signature_path);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $user->update(['signature_path' => null]);

        return back()->with('success', 'Signature removed.');
    }

    /** Stream the signature image for an authorised viewer (owner or document render). */
    public function show(\App\Models\User $user)
    {
        // A signature PNG is a document-forgery vector (it is embedded on
        // admission letters, transcripts and results). Only the OWNER, or the
        // same-college leadership who render those official documents, may fetch
        // it — never ordinary staff or students.
        $viewer = auth()->user();
        $sameCollegeLeadership = $viewer->college_id
            && $viewer->college_id === $user->college_id
            && $viewer->hasRole('registrar', 'provost', 'proprietor', 'mis');
        abort_unless($viewer->id === $user->id || $sameCollegeLeadership, 403);

        $dataUri = $user->signatureDataUri();
        abort_unless($dataUri, 404);

        // Decode the data URI to raw image bytes.
        [$meta, $b64] = array_pad(explode(',', $dataUri, 2), 2, '');
        preg_match('#data:(image/[a-z]+)#i', $meta, $m);
        $mime = $m[1] ?? 'image/png';
        $bytes = base64_decode($b64, true);
        abort_unless($bytes !== false, 404);

        return response($bytes)->header('Content-Type', $mime)->header('Cache-Control', 'private, max-age=300');
    }
}
