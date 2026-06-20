<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use Illuminate\Http\Request;

class AlumniController extends Controller
{
    public function index()
    {
        $alumni = Alumni::orderByDesc('graduation_year')->get();
        return view('alumni.index', compact('alumni'));
    }

    // Register a new alumnus
    public function register(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:alumni',
            'graduation_year' => 'required|integer',
            'program' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        Alumni::create($data);

        return back()->with('success', 'Alumni record registered successfully.');
    }

    // Search alumni by graduation year
    public function search(Request $request)
    {
        $alumni = Alumni::where('graduation_year', $request->year)->get();
        return view('alumni.index', compact('alumni'));
    }
}
