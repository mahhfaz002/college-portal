<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Subject;
use Illuminate\Support\Str;

/**
 * Exam Officer: HOD-approved question sets. Print the full paper (objectives +
 * theory) or download a CSV of the OBJECTIVES ONLY for the offline exam portal.
 */
class ExamPaperController extends Controller
{
    /** Subject ids in the officer's college (Subject is college-scoped). */
    private function collegeSubjectIds()
    {
        return Subject::pluck('id');
    }

    /** Paper must be in this college and at least submitted by the lecturer. */
    private function assertExportable(Exam $exam): void
    {
        abort_unless(
            in_array($exam->status, ['submitted', 'approved'], true) && Subject::whereKey($exam->subject_id)->exists(),
            404, 'No submitted paper found.'
        );
    }

    public function index()
    {
        $exams = Exam::whereIn('subject_id', $this->collegeSubjectIds())
            ->whereIn('status', ['submitted', 'approved'])
            ->with('subject.program', 'examCycle')
            ->withCount([
                'questions as objective_count' => fn ($q) => $q->where('type', 'objective'),
                'questions as theory_count' => fn ($q) => $q->where('type', 'theory'),
            ])
            ->latest('submitted_at')->get();

        return view('exams.papers', compact('exams'));
    }

    /** Printable paper (objectives + theory). */
    public function print(Exam $exam)
    {
        $this->assertExportable($exam);
        $exam->load([
            'subject.program', 'subject.department', 'examCycle',
            'questions' => fn ($q) => $q->orderBy('type')->orderBy('theory_number')->orderBy('id'),
        ]);

        return view('exams.paper_print', [
            'exam'    => $exam,
            'college' => current_college(),
        ]);
    }

    /** CSV of objectives only, for the offline exam portal. */
    public function csv(Exam $exam)
    {
        $this->assertExportable($exam);

        $objectives = $exam->questions()->where('type', 'objective')->orderBy('id')->get();

        $map   = ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'];
        $lines = [implode(',', ['QUESTION', 'OPTION A', 'OPTION B', 'OPTION C', 'OPTION D', 'CORRECT ANSWER'])];

        foreach ($objectives as $q) {
            $cells = [$q->question_text, $q->option_a, $q->option_b, $q->option_c, $q->option_d, $map[$q->correct_option] ?? 'A'];
            $lines[] = implode(',', array_map(fn ($c) => '"'.str_replace('"', '""', (string) $c).'"', $cells));
        }

        $csv  = implode("\n", $lines)."\n";
        $name = 'paper_'.Str::slug(optional($exam->subject)->course_code ?: ('exam-'.$exam->id)).'_objectives.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
        ]);
    }
}
