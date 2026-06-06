@extends('layouts.school')

@section('content')
<div class="max-w-4xl mx-auto py-10 px-4">
    <h1 class="text-3xl font-bold text-indigo-900 mb-6">Prospective Student Admission</h1>

    <div class="bg-indigo-50 p-6 rounded-lg mb-8 border border-indigo-200">
        <h2 class="text-xl font-semibold text-indigo-950">Application Guidelines & Requirements</h2>
        <p class="mt-2 text-gray-700">Welcome to {{ $school['name'] ?? 'our school' }}. Please review our programs and requirements below before applying.</p>
        <ul class="list-disc pl-5 mt-4 text-gray-700 space-y-1">
            <li><strong>Programs:</strong> Nursery, Primary, and Junior/Senior Secondary School.</li>
            <li><strong>Entry Requirement:</strong> Students must meet the age requirement for their desired class.</li>
            <li><strong>Documents to Upload:</strong>
                <ul class="list-circle pl-5 mt-1 text-sm">
                    <li>Applicant Passport Photograph</li>
                    <li>Birth Certificate</li>
                    <li>Letter of Indigene</li>
                </ul>
            </li>
        </ul>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admission.submit') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="bg-white p-6 rounded-lg shadow-sm border mb-6">
            <h3 class="text-lg font-bold mb-4 text-indigo-900">Student Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" name="full_name" class="mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Gender</label>
                    <select name="gender" class="mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Desired Class</label>
                    <input type="text" name="desired_class" placeholder="e.g. JSS 1" class="mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm border mb-6">
            <h3 class="text-lg font-bold mb-4 text-indigo-900">Parent/Guardian Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Parent/Guardian Name</label>
                    <input type="text" name="parent_name" class="mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                    <input type="text" name="parent_phone" class="mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input type="email" name="parent_email" class="mt-1 w-full border-gray-300 rounded-md shadow-sm" required>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm border mb-6">
            <h3 class="text-lg font-bold mb-4 text-indigo-900">Upload Required Documents</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Applicant Passport</label>
                    <input type="file" name="passport" class="mt-1 w-full text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Birth Certificate</label>
                    <input type="file" name="birth_certificate" class="mt-1 w-full text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Indigene Letter</label>
                    <input type="file" name="indigene_letter" class="mt-1 w-full text-sm" required>
                </div>
            </div>
        </div>

        <button type="submit" class="w-full bg-indigo-900 text-white py-3 rounded-lg font-bold text-lg hover:bg-indigo-800 transition">
            Submit Application
        </button>
    </form>
</div>
@endsection
