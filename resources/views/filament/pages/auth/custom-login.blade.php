<div class="flex min-h-screen bg-gray-100">
    <!-- SISI KIRI: Gambar & Branding BI -->
    <div class="hidden lg:flex lg:w-1/2 relative bg-cover bg-center items-center justify-center"
         style="background-image: url('{{ asset('images/bg-bi.jpg') }}');">
        <div class="absolute inset-0 bg-[#054177]/85 backdrop-blur-sm"></div>

        <div class="relative z-10 p-12 text-white max-w-lg text-center">
            <img src="{{ asset('images/logo-lab-eku.png') }}" alt="Logo LAB EKU" class="h-20 mx-auto mb-6">
            <h1 class="text-3xl font-bold tracking-tight mb-2">LAB EKU SULSEL</h1>
            <p class="text-blue-100 text-sm leading-relaxed">
                Layanan Aset Bank & Edukasi Kelayakan Uang<br>
                Kantor Perwakilan Bank Indonesia Provinsi Sulawesi Selatan
            </p>
        </div>
    </div>

    <!-- SISI KANAN: Form Login -->
    <div class="flex-1 flex items-center justify-center p-8 sm:p-12 lg:w-1/2 bg-white">
        <div class="w-full max-w-md space-y-8">
            <div class="text-center lg:text-left">
                <div class="flex items-center gap-3 justify-center lg:justify-start mb-2">
                    <img src="{{ asset('images/logo-lab-eku.png') }}" alt="Logo" class="h-10 lg:hidden">
                    <h2 class="text-2xl font-bold text-gray-900">Sign In Akun</h2>
                </div>
                <p class="text-sm text-gray-500">Masukkan email dan password terdaftar kamu</p>
            </div>

            <!-- Form Filament -->
            <x-filament-panels::form wire:submit="authenticate">
                {{ $this->form }}

                <x-filament-panels::form.actions
                    :actions="$this->getCachedFormActions()"
                    :full-width="$this->hasFullWidthFormActions()"
                />
            </x-filament-panels::form>
        </div>
    </div>
</div>
