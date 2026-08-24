<x-filament-panels::page>
    @php
        $user = \App\Support\CurrentUser::get();

        $labelRole = match ($user?->role) {
            'admin_bi' => 'Admin BI',
            'user_bi' => 'User BI',
            'user_perbankan' => $user->bank->name ?? 'User Bank',
            default => '-',
        };
    @endphp

    <div class="max-w-4xl mx-auto space-y-6">

        {{-- RINGKASAN AKUN --}}
        <div class="flex items-center gap-4 p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="shrink-0">
                @if ($user?->avatar_url)
                    <img
                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar_url) }}"
                        alt="{{ $user->name }}"
                        class="w-16 h-16 rounded-full object-cover border border-gray-200 dark:border-gray-700"
                    >
                @else
                    <div class="w-16 h-16 rounded-full bg-[#054177] flex items-center justify-center text-white text-xl font-bold">
                        {{ strtoupper(substr($user?->name ?? '?', 0, 1)) }}
                    </div>
                @endif
            </div>

            <div class="min-w-0">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white truncate">{{ $user?->name }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $user?->email }}</p>
                <span class="inline-flex items-center mt-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#054177]/10 text-[#054177] dark:bg-[#054177]/30 dark:text-blue-200">
                    {{ $labelRole }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

            {{-- FOTO PROFIL --}}
            <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-md font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-camera class="w-5 h-5 text-[#054177]" />
                        <span>Foto Profil</span>
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Gunakan foto berukuran kecil agar halaman tetap ringan saat dimuat.
                    </p>
                </div>

                <form wire:submit.prevent="updateAvatar" class="space-y-4">
                    {{ $this->avatarForm }}

                    <div class="flex justify-end pt-2">
                        <button
                            type="submit"
                            class="bg-[#054177] hover:bg-[#04345f] text-white font-medium py-2.5 px-5 rounded-lg transition duration-200 shadow-sm flex items-center space-x-2 text-sm"
                        >
                            <x-heroicon-m-check-circle class="w-4 h-4" />
                            <span>Simpan Foto</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- GANTI SANDI --}}
            <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="mb-4 pb-3 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-md font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-lock-closed class="w-5 h-5 text-[#054177]" />
                        <span>Ganti Sandi</span>
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Ganti sandi yang dibuatkan Admin dengan sandi pribadi Anda sendiri.
                    </p>
                </div>

                <form wire:submit.prevent="updatePassword" class="space-y-4">
                    {{ $this->passwordForm }}

                    <div class="flex justify-end pt-2">
                        <button
                            type="submit"
                            class="bg-[#054177] hover:bg-[#04345f] text-white font-medium py-2.5 px-5 rounded-lg transition duration-200 shadow-sm flex items-center space-x-2 text-sm"
                        >
                            <x-heroicon-m-check-circle class="w-4 h-4" />
                            <span>Simpan Sandi</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-filament-panels::page>
