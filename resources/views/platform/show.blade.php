<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🏫 {{ $college->name }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
            <a href="{{ route('platform.colleges') }}" class="text-sm text-indigo-600 hover:underline">← All colleges</a>

            <div class="grid sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border p-4"><p class="text-xs uppercase text-gray-400 font-bold">Students</p><p class="text-2xl font-black text-gray-800">{{ number_format($students) }}</p></div>
                <div class="bg-white rounded-xl border p-4"><p class="text-xs uppercase text-gray-400 font-bold">Staff</p><p class="text-2xl font-black text-gray-800">{{ number_format($staff) }}</p></div>
                <div class="bg-white rounded-xl border p-4"><p class="text-xs uppercase text-gray-400 font-bold">Applicants</p><p class="text-2xl font-black text-gray-800">{{ number_format($applicants) }}</p></div>
                <div class="bg-white rounded-xl border p-4"><p class="text-xs uppercase text-gray-400 font-bold">Revenue</p><p class="text-2xl font-black text-emerald-600">{{ money($revenue) }}</p></div>
            </div>

            {{-- Registered students (CSV upload + list/search/edit) --}}
            <div class="bg-white rounded-2xl shadow-sm border p-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="font-bold text-gray-800">🎓 Registered Students</h3>
                    <p class="text-sm text-gray-500">Upload the admitted-students CSV, view the list with registration status, search and fix records.</p>
                </div>
                <a href="{{ route('platform.colleges.students', $college) }}" class="bg-emerald-600 text-white px-5 py-2 rounded-lg text-sm font-bold hover:bg-emerald-700 whitespace-nowrap">Manage registered students →</a>
            </div>

            {{-- Leadership accounts --}}
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Leadership Accounts</div>
                <table class="w-full text-sm">
                    <tbody class="divide-y">
                        @forelse($admins as $a)
                            <tr>
                                <td class="px-6 py-3 font-semibold text-gray-800">{{ $a->name }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ $a->email }}</td>
                                <td class="px-6 py-3"><span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-bold">{{ str_replace('_',' ',$a->role) }}</span></td>
                                <td class="px-6 py-3 text-right whitespace-nowrap">
                                    <form method="POST" action="{{ route('platform.colleges.admins.reset', [$college, $a]) }}" class="inline" onsubmit="return confirm('Reset this admin password? A new temporary password will be shown.')">
                                        @csrf<button class="text-indigo-600 text-xs font-bold hover:underline">Reset password</button>
                                    </form>
                                    <form method="POST" action="{{ route('platform.colleges.admins.remove', [$college, $a]) }}" class="inline ml-3" onsubmit="return confirm('Remove this admin account?')">
                                        @csrf @method('DELETE')<button class="text-red-500 text-xs font-bold hover:underline">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-4 text-center text-gray-400">No leadership accounts yet — create them below.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <form method="POST" action="{{ route('platform.colleges.admins.add', $college) }}" class="p-6 border-t flex flex-wrap gap-2 items-end bg-gray-50">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Role</label>
                        <select name="role" required class="border-gray-300 rounded-lg text-sm">
                            @foreach($adminRoles as $r)<option value="{{ $r }}">{{ ucwords(str_replace('_',' ',$r)) }}</option>@endforeach
                        </select>
                    </div>
                    <input name="name" required placeholder="Full name *" class="border-gray-300 rounded-lg text-sm">
                    <input name="email" type="email" required placeholder="Email *" class="border-gray-300 rounded-lg text-sm">
                    <input name="password" required placeholder="Temp password *" class="border-gray-300 rounded-lg text-sm">
                    <button class="bg-emerald-600 text-white px-5 py-2 rounded-lg text-sm font-bold hover:bg-emerald-700">Add Admin</button>
                </form>
            </div>

            {{-- Paystack subaccount & settlement (marketplace split) --}}
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b flex items-center justify-between">
                    <h3 class="font-bold text-gray-700">💳 Payments &amp; Settlement</h3>
                    <a href="{{ route('platform.colleges.transactions', $college) }}" class="text-sm font-bold text-indigo-600 hover:underline">View transactions →</a>
                </div>
                <div class="p-6 space-y-5">
                    {{-- Split status banner: the whole marketplace model depends on a linked subaccount code. --}}
                    @php
                        $hasAccount = !empty($college->settlement_account_number);
                        $hasCode    = !empty($college->paystack_subaccount_code);
                    @endphp
                    @if($hasCode)
                        <div class="p-3 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm flex items-center gap-2">
                            <span>✅</span><span><strong>Auto-split active.</strong> Payments on this college's domain split natively to its subaccount; the platform keeps {{ (float) ($college->commission_percentage ?? 0) }}%.</span>
                        </div>
                    @elseif($hasAccount)
                        <div class="p-3 rounded-lg bg-amber-50 border border-amber-300 text-amber-800 text-sm">
                            <strong>⚠️ Auto-split is INACTIVE.</strong> Settlement details are saved but no Paystack subaccount is linked, so payments currently settle 100% to the platform with no split.
                            Click <strong>Create Subaccount</strong> below, or <strong>Recover from Paystack</strong> if you already created one there.
                            <form method="POST" action="{{ route('platform.colleges.subaccount.sync', $college) }}" class="mt-2">@csrf
                                <button class="bg-amber-600 text-white px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-amber-700">Recover from Paystack</button>
                            </form>
                        </div>
                    @else
                        <div class="p-3 rounded-lg bg-gray-50 border border-gray-200 text-gray-600 text-sm">
                            <strong>Split not set up.</strong> Add this college's settlement bank &amp; account below and create its subaccount to enable native payment splitting.
                        </div>
                    @endif

                    <div class="grid sm:grid-cols-3 gap-4 text-sm">
                        <div><p class="text-[10px] uppercase font-bold text-gray-400">Subaccount Code</p>
                            <p class="font-mono text-gray-800 break-all">{{ $college->paystack_subaccount_code ?? '— not set up —' }}</p></div>
                        <div><p class="text-[10px] uppercase font-bold text-gray-400">Status</p>
                            @php $st = $college->paystack_subaccount_status; @endphp
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $st==='active' ? 'bg-green-100 text-green-700' : ($st==='inactive' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">{{ ucfirst($st ?? 'pending') }}</span></div>
                        <div><p class="text-[10px] uppercase font-bold text-gray-400">Commission</p>
                            <p class="font-bold text-gray-800">{{ (float) ($college->commission_percentage ?? 0) }}%</p></div>
                        <div><p class="text-[10px] uppercase font-bold text-gray-400">Settlement Account</p>
                            <p class="text-gray-800">{{ $college->settlement_account_name ?? '—' }}<br><span class="text-xs text-gray-500">{{ $college->settlement_account_number }} {{ $college->settlement_bank ? '· bank '.$college->settlement_bank : '' }}</span></p></div>
                        <div><p class="text-[10px] uppercase font-bold text-gray-400">Commission Earned</p>
                            <p class="font-bold text-emerald-600">{{ money($commissionEarned) }}</p></div>
                        <div><p class="text-[10px] uppercase font-bold text-gray-400">Last Settlement
                            @if(!is_null($settlement ?? null))<span class="text-emerald-500">· live</span>@endif</p>
                            <p class="text-gray-800">{{ $lastSettlement ? \Illuminate\Support\Carbon::parse($lastSettlement)->format('d M Y') : '—' }}</p>
                            @if(!empty($settlement['last_amount']))
                                <p class="text-xs text-gray-500">{{ money($settlement['last_amount']) }} · {{ ucfirst($settlement['last_status'] ?? 'settled') }}</p>
                            @endif
                        </div>
                    </div>
                    @if(is_null($settlement ?? null) && $college->paystack_subaccount_code)
                        <p class="mt-2 text-[11px] text-amber-600">Live settlement data is unavailable from Paystack right now — showing the last recorded value.</p>
                    @endif

                    <form method="POST" action="{{ route('platform.colleges.settlement', $college) }}" class="border-t pt-4 grid sm:grid-cols-2 gap-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Settlement Bank</label>
                            @if(!empty($banks))
                                <select name="settlement_bank" required class="w-full border-gray-300 rounded-lg text-sm">
                                    <option value="">— Select bank —</option>
                                    @foreach($banks as $b)
                                        <option value="{{ $b['code'] }}" {{ $college->settlement_bank == $b['code'] ? 'selected' : '' }}>{{ $b['name'] }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input name="settlement_bank" value="{{ $college->settlement_bank }}" required placeholder="Paystack bank code (e.g. 058)" class="w-full border-gray-300 rounded-lg text-sm">
                            @endif
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Account Number</label>
                            <input name="settlement_account_number" value="{{ $college->settlement_account_number }}" required placeholder="0123456789" class="w-full border-gray-300 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Account Name <span class="text-gray-300">(auto-verified if blank)</span></label>
                            <input name="settlement_account_name" value="{{ $college->settlement_account_name }}" placeholder="Resolved from Paystack" class="w-full border-gray-300 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Platform Commission (%)</label>
                            <input name="commission_percentage" type="number" step="0.01" min="0" max="100" value="{{ $college->commission_percentage ?? config('services.paystack.default_commission_percentage', 2) }}" required class="w-full border-gray-300 rounded-lg text-sm">
                        </div>
                        <div class="sm:col-span-2 flex flex-wrap gap-2">
                            <button class="bg-emerald-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-emerald-700 text-sm">
                                {{ $college->paystack_subaccount_code ? 'Update Subaccount' : 'Create Subaccount' }}
                            </button>
                            @if($college->paystack_subaccount_code)
                                <button formaction="{{ route('platform.colleges.subaccount.sync', $college) }}" formmethod="POST" class="bg-gray-100 text-gray-700 px-5 py-2 rounded-lg font-bold hover:bg-gray-200 text-sm">Sync from Paystack</button>
                            @endif
                        </div>
                    </form>
                    <p class="text-[11px] text-gray-400">The platform's master Paystack account collects every payment; each payment made on this college's domain is split natively by Paystack so the institution's share settles to the account above and the platform keeps its commission.</p>
                </div>
            </div>

            {{-- Edit college branding / domain --}}
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-800">College Details &amp; Branding</h3>
                    <form method="POST" action="{{ route('platform.colleges.toggle', $college) }}">@csrf
                        <button class="px-4 py-1.5 rounded-lg text-xs font-bold {{ $college->is_active ? 'bg-red-100 text-red-700 hover:bg-red-200':'bg-green-100 text-green-700 hover:bg-green-200' }}">{{ $college->is_active ? 'Suspend' : 'Reactivate' }}</button>
                    </form>
                </div>
                <form method="POST" action="{{ route('platform.colleges.update', $college) }}" class="space-y-3">
                    @csrf @method('PUT')
                    <div class="grid md:grid-cols-2 gap-3">
                        <input name="name" value="{{ $college->name }}" required placeholder="Name" class="border-gray-300 rounded-lg md:col-span-2">
                        <input name="acronym" value="{{ $college->acronym }}" required placeholder="Acronym" class="border-gray-300 rounded-lg">
                        <input name="domain" value="{{ $college->domain }}" placeholder="Domain (e.g. albazchst.edu.ng)" class="border-gray-300 rounded-lg">
                        <input name="email" value="{{ $college->email }}" placeholder="Email" class="border-gray-300 rounded-lg">
                        <input name="phone" value="{{ $college->phone }}" placeholder="Phone" class="border-gray-300 rounded-lg">
                        <input name="address" value="{{ $college->address }}" placeholder="Address" class="border-gray-300 rounded-lg md:col-span-2">
                        <input name="tagline" value="{{ $college->tagline }}" placeholder="Tagline" class="border-gray-300 rounded-lg">
                        <input name="motto" value="{{ $college->motto }}" placeholder="Motto" class="border-gray-300 rounded-lg">
                        <textarea name="about" placeholder="About the college (homepage)" class="border-gray-300 rounded-lg md:col-span-2" rows="2">{{ $college->about }}</textarea>
                        <input name="provost_name" value="{{ $college->provost_name }}" placeholder="Provost name" class="border-gray-300 rounded-lg">
                        <input name="primary_color" type="color" value="{{ $college->primary_color ?? '#1d4ed8' }}" class="h-10 w-20 border-gray-300 rounded">
                        <textarea name="provost_message" placeholder="Provost welcome message (homepage)" class="border-gray-300 rounded-lg md:col-span-2" rows="2">{{ $college->provost_message }}</textarea>
                    </div>
                    <button class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-indigo-700">Save Details</button>
                </form>
            </div>

            {{-- Danger zone --}}
            <div class="bg-white rounded-2xl border border-red-200 p-6">
                <h3 class="font-bold text-red-700 mb-1">Danger Zone</h3>
                <p class="text-sm text-gray-500 mb-3">Permanently delete this college and <strong>all</strong> its students, staff, applications and finance records. This cannot be undone.</p>
                <form method="POST" action="{{ route('platform.colleges.destroy', $college) }}" onsubmit="return confirm('Permanently delete {{ $college->name }} and ALL its data?')">
                    @csrf @method('DELETE')
                    <button class="bg-red-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-red-700">Delete College</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
