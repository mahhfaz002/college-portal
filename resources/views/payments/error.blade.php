@extends('layouts.school')

@section('content')
<div class="max-w-xl mx-auto py-16 px-4 text-center">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
        <div class="mx-auto w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center mb-5">
            <span class="text-3xl">⚠️</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Payment could not start</h1>
        <p class="text-slate-600 mb-6">{{ $message ?? 'We could not connect to the payment gateway. Your details are saved — please try again.' }}</p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            @isset($invoice)
                <a href="{{ route('payments.initialize', $invoice) }}"
                   class="btn-brand px-6 py-3 rounded-full shadow font-semibold">Try Payment Again</a>
            @endisset
            <a href="{{ url()->previous() === url()->current() ? route('home') : url()->previous() }}"
               class="px-6 py-3 rounded-full border border-slate-300 text-slate-700 font-semibold hover:bg-slate-50">Go Back</a>
        </div>

        @isset($invoice)
            <p class="text-xs text-slate-400 mt-6">Reference: {{ $invoice->reference }} · Amount: ₦{{ number_format($invoice->chargeable(), 2) }}</p>
        @endisset
    </div>
</div>
@endsection
