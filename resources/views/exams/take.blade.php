<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $exam->title }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @if(session('error'))<div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif

            @if(!$unlocked)
                <!-- Password gate -->
                <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-200 text-center">
                    <div class="text-5xl mb-3">🔒</div>
                    <h3 class="font-bold text-gray-800 mb-1">Enter Exam Password</h3>
                    <p class="text-sm text-gray-500 mb-5">The Exam Officer will provide the password to start.</p>
                    <form action="{{ route('myexams.unlock', $exam) }}" method="POST" class="flex flex-col gap-3 max-w-xs mx-auto">
                        @csrf
                        <input type="text" name="access_password" placeholder="Exam password" class="border-gray-300 rounded-md shadow-sm text-center" required autofocus>
                        <button class="bg-indigo-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-indigo-700">Unlock Exam</button>
                    </form>
                </div>
            @else
                <!-- Question paper -->
                <form action="{{ route('myexams.submit', $exam) }}" method="POST" class="space-y-5">
                    @csrf
                    <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-lg p-3 text-sm">
                        {{ $questions->count() }} questions · {{ $exam->totalMarks() }} marks · {{ $exam->duration_minutes }} minutes
                    </div>

                    @foreach($questions as $i => $q)
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200">
                        <p class="font-bold text-gray-800 mb-3">{{ $i+1 }}. {{ $q->question_text }} <span class="text-xs text-gray-400">({{ $q->marks }} mk)</span></p>
                        <div class="space-y-2">
                            @foreach(['a'=>$q->option_a,'b'=>$q->option_b,'c'=>$q->option_c,'d'=>$q->option_d] as $key => $opt)
                                @if($opt)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="radio" name="answers[{{ $q->id }}]" value="{{ $key }}"> {{ strtoupper($key) }}. {{ $opt }}
                                </label>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endforeach

                    <div class="text-right">
                        <button class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700" onclick="return confirm('Submit your answers? You cannot change them after.')">Submit Exam</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
