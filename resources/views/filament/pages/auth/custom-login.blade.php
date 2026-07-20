<x-filament-panels::page.simple>
    <div class="fixed inset-0 z-40 flex min-h-screen bg-gray-100 dark:bg-gray-900 overflow-y-auto">

        {{-- SISI KIRI: Branding Bank Indonesia --}}
        <div class="hidden lg:flex lg:w-1/2 relative bg-cover bg-center items-center justify-center"
             style="background-image: url('{{ asset('images/bg-bi.jpg') }}');">
            <div class="absolute inset-0 bg-[#054177]/85 backdrop-blur-sm"></div>

            <div class="relative z-10 p-12 text-white max-w-lg text-center">
                <img src="{{ asset('images/logo-bi.png') }}" alt="Bank Indonesia" class="h-16 mx-auto mb-6">
                <h1 class="text-4xl font-bold tracking-tight mb-3">EKU</h1>
                <p class="text-blue-100 text-base leading-relaxed">
                    Estimasi Kebutuhan Uang<br>
                    Bank Indonesia Sulawesi Selatan
                </p>
            </div>

            <div class="absolute bottom-6 inset-x-0 text-center text-xs text-blue-200/80 z-10">
                Bank Indonesia | EKU | &copy; {{ date('Y') }}
            </div>
        </div>

    {{-- SISI KANAN: Form Login --}}
    <div class="flex-1 flex items-center justify-center p-8 sm:p-12 lg:w-1/2 bg-gray-50 dark:bg-gray-900">
        <div class="w-full max-w-md space-y-8">
            <div class="text-center">
                <img src="{{ asset('images/logo-lab-eku.png') }}" alt="LAB EKU SULSEL" class="h-24 w-auto mx-auto mb-2">
            </div>

        {{-- Card putih dengan shadow membungkus form login --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 p-8 sm:p-10">
            <form wire:submit="authenticate" class="space-y-6">
                {{ $this->form }}

                <button type="submit"
                    class="w-full rounded-lg py-3 font-semibold text-white transition hover:opacity-90"
                    style="background-color:#054177;">
                    Sign In
                </button>
            </form>
        </div>
    </div>
</div>
</x-filament-panels::page.simple>
