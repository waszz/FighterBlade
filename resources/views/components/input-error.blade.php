@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'bg-red-100 border-l-4 border-red-600 p-3 font-bold text-sm text-red-600 space-y-1']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
