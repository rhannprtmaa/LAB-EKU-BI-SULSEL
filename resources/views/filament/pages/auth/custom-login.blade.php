<x-filament-panels::page.simple>
    {{-- 'fixed inset-0' sengaja dipakai supaya layout split-screen ini
         menembus/mengabaikan container tengah bawaan Filament (yang biasanya
         membuat card kecil di tengah layar), sehingga benar-benar full-screen
         seperti wireframe. --}}
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
        <div class="flex-1 flex items-center justify-center p-8 sm:p-12 lg:w-1/2 bg-white dark:bg-gray-800">
            <div class="w-full max-w-md space-y-8">
                <div class="text-center lg:text-left">
                    <div class="flex items-center gap-3 justify-center lg:justify-start mb-2">
                        <img src="{{ asset('images/logo-lab-eku.png') }}" alt="Logo" class="h-10 lg:hidden">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Sign In Akun</h2>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Masukkan email dan password terdaftar kamu</p>
                </div>

                {{-- Form bawaan Filament (validasi, rate-limit, session error tetap jalan normal).
                     Sengaja pakai <form> polos + tombol manual, karena component
                     x-filament-panels::form.actions tidak tersedia di versi ini. --}}
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
