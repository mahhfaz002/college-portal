<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">📊 Grading Scheme — {{ $department->name ?? 'Department' }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif

            <div class="bg-white rounded-2xl shadow-sm border p-6"
                 x-data="{ rows: {{ Illuminate\Support\Js::from($bands->isEmpty()
                    ? [['grade'=>'A','min_score'=>70,'max_score'=>100,'remark'=>'Excellent'],['grade'=>'B','min_score'=>60,'max_score'=>69,'remark'=>'Very Good'],['grade'=>'C','min_score'=>50,'max_score'=>59,'remark'=>'Good'],['grade'=>'D','min_score'=>45,'max_score'=>49,'remark'=>'Pass'],['grade'=>'F','min_score'=>0,'max_score'=>44,'remark'=>'Fail']]
                    : $bands->map(fn($b)=>['grade'=>$b->grade,'min_score'=>$b->min_score,'max_score'=>$b->max_score,'remark'=>$b->remark])) }} }">
                <p class="text-sm text-gray-500 mb-4">Define the score bands for your department. This scheme is applied to every student in {{ $department->name ?? 'your department' }} when results are computed.</p>
                <form method="POST" action="{{ route('hod.grading.save') }}">
                    @csrf
                    <table class="w-full text-sm mb-4">
                        <thead class="text-xs uppercase text-gray-400">
                            <tr><th class="text-left py-2">Grade</th><th class="text-left">Min %</th><th class="text-left">Max %</th><th class="text-left">Remark</th><th></th></tr>
                        </thead>
                        <tbody>
                            <template x-for="(r,i) in rows" :key="i">
                                <tr>
                                    <td class="py-1 pr-2"><input x-model="r.grade" :name="`grade[${i}]`" required class="w-16 border-gray-300 rounded text-sm"></td>
                                    <td class="pr-2"><input x-model="r.min_score" :name="`min_score[${i}]`" type="number" min="0" max="100" required class="w-20 border-gray-300 rounded text-sm"></td>
                                    <td class="pr-2"><input x-model="r.max_score" :name="`max_score[${i}]`" type="number" min="0" max="100" required class="w-20 border-gray-300 rounded text-sm"></td>
                                    <td class="pr-2"><input x-model="r.remark" :name="`remark[${i}]`" class="w-full border-gray-300 rounded text-sm"></td>
                                    <td><button type="button" @click="rows.splice(i,1)" x-show="rows.length>1" class="text-red-500 text-xs font-bold">✕</button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div class="flex items-center justify-between">
                        <button type="button" @click="rows.push({grade:'',min_score:0,max_score:0,remark:''})" class="text-indigo-600 text-sm font-bold hover:underline">+ Add band</button>
                        <button class="bg-emerald-600 text-white px-8 py-2.5 rounded-full font-bold hover:bg-emerald-700">Save Scheme</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
