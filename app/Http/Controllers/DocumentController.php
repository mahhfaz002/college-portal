<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Support\Facades\Storage;

/**
 * Streams a student's uploaded registration document AFTER an authorization
 * check, so sensitive PII (certificates, results, birth certs) is never exposed
 * via a public URL. StudentDocument is college-scoped, so route-model binding
 * already prevents cross-college access; this adds per-record authorization.
 */
class DocumentController extends Controller
{
    public function show(StudentDocument $document)
    {
        $user    = auth()->user();
        $student = Student::find($document->student_id);

        $owns   = $user->role === 'student' && $student && $student->email && $student->email === $user->email;
        $staff  = $user->canManage('view_students')
            || $user->hasRole('registrar', 'proprietor', 'provost', 'mis', 'admission_officer');
        $isHod  = $user->hasRole('hod', 'assistant_hod')
            && $student && $student->department_id && $student->department_id === $user->department_id;

        abort_unless($owns || $staff || $isHod, 403);

        // New uploads live on the private documents disk; fall back to the legacy
        // public disk for any document stored before this change.
        $disk = config('filesystems.documents', 'local');
        if (! Storage::disk($disk)->exists($document->path)) {
            $disk = 'public';
        }
        abort_unless(Storage::disk($disk)->exists($document->path), 404);

        return Storage::disk($disk)->download($document->path, $document->original_name ?: basename($document->path));
    }
}
