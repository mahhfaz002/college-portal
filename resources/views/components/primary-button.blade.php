<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-brand inline-flex items-center justify-center px-4 py-2 rounded-lg text-xs uppercase tracking-widest focus:outline-none focus:ring-2 ring-brand focus:ring-offset-2 transition']) }}>
    {{ $slot }}
</button>
