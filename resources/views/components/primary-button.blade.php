<button {{ $attributes->merge(['type' => 'submit', 'class' => 'uppercase bg-gradient-to-r from-yellow-600 to-red-500 hover:from-red-500 hover:to-yellow-600
                text-white font-bold py-2 px-6 rounded-lg text-sm shadow-[0_0_15px_rgba(255,69,0,0.8)]
                transition transform hover:scale-105 animate-fade-in delay-300']) }}>
    {{ $slot }}
</button>
