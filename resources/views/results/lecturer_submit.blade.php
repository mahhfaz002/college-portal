<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Submit Results — {{ $subject->name }} ({{ $subject->course_code }})
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('error'))
                <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg">
                    <ul class="list-disc ml-5 text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <form action="{{ route('results.submit.store', $subject) }}" method="POST" enctype="multipart/form-data" id="resultForm"
                  class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-200">
                @csrf

                <div class="p-6 bg-indigo-900 text-white flex flex-wrap justify-between items-center gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase text-indigo-300">Course</p>
                        <p class="mt-1 text-lg font-black">{{ $subject->name }}</p>
                        <p class="text-xs text-indigo-300">{{ $subject->course_code }} {{ optional($subject->program)->name }}{{ $subject->level ? ' · L'.$subject->level : '' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold">Max CA: {{ setting('ca_max_score', 40) }} | Max Exam: {{ setting('exam_max_score', 60) }}</p>
                        <p class="text-xs text-indigo-300 italic">{{ $term }} &bull; {{ $session }}</p>
                    </div>
                </div>

                <div class="p-6">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b-2 border-gray-100">
                                <th class="py-3 px-4 text-sm font-bold text-gray-600">#</th>
                                <th class="py-3 px-4 text-sm font-bold text-gray-600">Student Name</th>
                                <th class="py-3 px-4 text-sm font-bold text-gray-600">Reg. Number</th>
                                <th class="py-3 px-4 text-sm font-bold text-gray-600 text-center">CA ({{ setting('ca_max_score', 40) }})</th>
                                <th class="py-3 px-4 text-sm font-bold text-gray-600 text-center">Exam ({{ setting('exam_max_score', 60) }})</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($students as $i => $student)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-4 px-4 text-sm text-gray-400">{{ $i + 1 }}</td>
                                <td class="py-4 px-4 font-bold text-gray-800">{{ $student->full_name }}</td>
                                <td class="py-4 px-4 text-sm text-gray-500">{{ $student->registration_number ?? '—' }}</td>
                                <td class="py-4 px-4">
                                    <input type="number" name="scores[{{ $student->id }}][ca]"
                                           value="{{ optional($scores->get($student->id))->ca_score }}"
                                           class="w-24 mx-auto block border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-center font-bold"
                                           placeholder="0" min="0" max="{{ setting('ca_max_score', 40) }}">
                                </td>
                                <td class="py-4 px-4">
                                    <input type="number" name="scores[{{ $student->id }}][exam]"
                                           value="{{ optional($scores->get($student->id))->exam_score }}"
                                           class="w-24 mx-auto block border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-center font-bold"
                                           placeholder="0" min="0" max="{{ setting('exam_max_score', 60) }}">
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="py-8 text-center text-gray-400 italic">No students enrolled in this programme & level yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    {{-- Scanned result upload --}}
                    <div class="mt-8 p-4 bg-gray-50 rounded-xl border border-gray-200">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Upload Scanned Copy of Result (JPEG/JPG only, max 5MB) <span class="text-red-500">*</span></label>
                        <input type="file" name="scan" accept=".jpeg,.jpg" required
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="text-xs text-gray-400 mt-1">Upload the signed, scanned copy of the result sheet in JPEG or JPG format.</p>
                    </div>

                    <div class="mt-8">
                        <button type="button" onclick="showConfirmModal()" @disabled($students->isEmpty())
                                class="w-full bg-indigo-600 text-white py-4 rounded-xl font-black uppercase tracking-widest hover:bg-indigo-700 shadow-lg transition disabled:opacity-40">
                            Submit Results
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Confirmation Modal --}}
    <div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 p-8">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-black text-gray-900">Confirm Result Submission</h3>
            </div>
            <div class="space-y-3 text-sm text-gray-600">
                <p>Please confirm that <strong>all results are correct</strong> before submitting.</p>
                <p class="font-bold text-red-600">Once submitted, you will no longer be able to edit or view these results.</p>
                <p>You are required to submit the <strong>physical copy</strong> of the result to the Exam Officer <strong>within 72 hours</strong> of submitting this digital result.</p>
            </div>
            <div class="mt-8 flex gap-3">
                <button type="button" onclick="hideConfirmModal()"
                        class="flex-1 bg-green-600 text-white py-3 rounded-xl font-bold hover:bg-green-700 transition">
                    Go Back
                </button>
                <button type="button" onclick="submitForm()"
                        class="flex-1 bg-red-600 text-white py-3 rounded-xl font-bold hover:bg-red-700 transition">
                    Submit Results
                </button>
            </div>
        </div>
    </div>

    <script>
        function showConfirmModal() {
            const form = document.getElementById('resultForm');
            const scanInput = form.querySelector('input[name="scan"]');
            if (!scanInput.files.length) {
                alert('Please upload the scanned copy of the result before submitting.');
                return;
            }
            document.getElementById('confirmModal').classList.remove('hidden');
            document.getElementById('confirmModal').classList.add('flex');
        }
        function hideConfirmModal() {
            document.getElementById('confirmModal').classList.add('hidden');
            document.getElementById('confirmModal').classList.remove('flex');
        }
        function submitForm() {
            document.getElementById('resultForm').submit();
        }
    </script>
</x-app-layout>
