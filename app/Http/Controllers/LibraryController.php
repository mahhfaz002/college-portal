<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BorrowRecord;
use App\Models\Student;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LibraryController extends Controller
{
    public function index()
    {
        // Only the librarian manages loans, so the student picker (for issuing)
        // is loaded for them alone — not exposed to read-only browsers.
        $canManage = auth()->user()->canManage('manage_library');

        $books = Book::orderBy('title')->get();
        $students = $canManage ? Student::orderBy('full_name')->get() : collect();
        $records = BorrowRecord::with(['book', 'student'])->whereNull('returned_at')->latest()->get();

        $stats = [
            'titles'  => $books->count(),
            'copies'  => $books->sum('total_copies'),
            'on_loan' => $records->count(),
            'overdue' => $records->filter(fn ($r) => $r->due_at && $r->due_at->isPast())->count(),
        ];

        return view('library.index', compact('books', 'students', 'records', 'stats'));
    }

    /** Add a new title to the catalogue. */
    public function storeBook(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:200',
            'author'       => 'nullable|string|max:150',
            'isbn'         => 'nullable|string|max:50',
            'category'     => 'nullable|string|max:100',
            'total_copies' => 'required|integer|min:1',
        ]);
        $data['available_copies'] = $data['total_copies'];
        $data['college_id'] = current_college_id();
        $data['isbn'] = $data['isbn'] ?? '';   // legacy column is NOT NULL
        Book::create($data);

        return back()->with('success', 'Book added to the catalogue.');
    }

    public function destroyBook(Book $book)
    {
        $book->delete();
        return back()->with('success', 'Book removed.');
    }

    // Issue a book to a student
    public function issueBook(Request $request)
    {
        $book = Book::findOrFail($request->book_id);

        // 1. Check availability
        if ($book->available_copies <= 0) {
            return back()->with('error', 'Book is currently unavailable.');
        }

        // 2. Create borrow record
        BorrowRecord::create([
            'student_id' => $request->student_id,
            'book_id' => $book->id,
            'college_id' => current_college_id(),
            'borrowed_at' => Carbon::now(),
            'due_at' => Carbon::now()->addWeeks(2), // 2-week loan period
        ]);

        // 3. Decrease available copies
        $book->decrement('available_copies');

        return back()->with('success', 'Book issued successfully.');
    }

    // Return a book
    public function returnBook($recordId)
    {
        $record = BorrowRecord::findOrFail($recordId);
        $book = Book::find($record->book_id);

        // 1. Mark as returned
        $record->update(['returned_at' => Carbon::now()]);

        // 2. Increase available copies
        $book->increment('available_copies');

        return back()->with('success', 'Book returned successfully.');
    }
}
