<x-shop::layouts.account>
    <x-slot:title>Mis recompensas</x-slot>

    <div class="container mt-8 max-lg:px-8">
        <h1 class="text-2xl font-semibold">Mis recompensas</h1>
        <p class="mt-2 text-gray-600">Tus cupones por compras acumuladas aparecen aquí.</p>

        <div class="mt-6 grid gap-4">
            @forelse ($rewards as $reward)
                <div class="rounded-lg border border-gray-200 p-5">
                    <div class="text-lg font-semibold">10% de descuento</div>
                    <div class="mt-1 text-sm text-gray-600">Recompensa por acumular L {{ number_format($reward->threshold, 2) }}.</div>
                    <code class="mt-4 inline-block rounded bg-gray-100 px-3 py-2 font-mono">{{ $reward->coupon->code }}</code>
                    <div class="mt-2 text-xs text-gray-500">Úsalo una vez en tu próxima compra.</div>
                </div>
            @empty
                <p class="mt-4 text-gray-600">Aún no tienes recompensas disponibles.</p>
            @endforelse
        </div>
    </div>
</x-shop::layouts.account>
