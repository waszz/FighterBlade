<x-app-layout>
    <section class="bg-white py-16 px-4">
        <div class="max-w-2xl mx-auto text-center">
            <img src="{{ asset('images/mercadopago.png') }}" alt="MercadoPago" class="mx-auto h-16 mb-6">

            <h1 class="text-3xl font-bold text-gray-800 mb-4">Gracias por tu generosidad 🐾</h1>
            <p class="text-gray-600 mb-6">Serás redirigido al sitio de MercadoPago para completar tu donación de <strong class="text-indigo-600" id="donationAmount">Seleccione un monto</strong>.</p>

            <!-- Selección de monto -->
            <div class="mb-6">
                <label for="monto" class="block text-sm font-medium text-gray-700 mb-1">Monto de donación (UYU)</label>
                <select id="monto" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    onchange="actualizarMonto()">
                    <option value="">Selecciona un monto</option>
                    <option value="50">💙 $50</option>
                    <option value="100">💙 $100</option>
                    <option value="200">💙 $200</option>
                </select>
            </div>

            {{-- Simulación de botón de pago --}}
            <a href="#" target="_blank" id="paymentLink" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-6 rounded-lg transition">
                Donar con MercadoPago
            </a>

            <p class="text-sm text-gray-400 mt-6">Tu ayuda permite alimentar, cuidar y salvar a cientos de mascotas. ¡Gracias!</p>
        </div>
    </section>

    <script>
        function actualizarMonto() {
            const monto = document.getElementById('monto').value;
            const donationAmount = document.getElementById('donationAmount');
            const paymentLink = document.getElementById('paymentLink');

            // Actualizar el monto que se muestra
            donationAmount.innerText = monto ? `$${monto}` : 'Seleccione un monto';

            // Definir los enlaces según el monto
            const enlacesDePago = {
                "50": "https://mpago.la/33fQbB2",  // Enlace para $50
                "100": "https://mpago.la/33fQbB3", // Enlace para $100
                "200": "https://mpago.la/33fQbB4", // Enlace para $200
            };

            // Si hay un monto seleccionado, actualizar el enlace de pago
            if (monto && enlacesDePago[monto]) {
                paymentLink.href = enlacesDePago[monto];
            } else {
                paymentLink.href = "#";  // Evitar redirección si no se ha seleccionado un monto
            }
        }
    </script>
</x-app-layout>
@include('footer.index')