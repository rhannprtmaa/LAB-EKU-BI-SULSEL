<div class="rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-5"
     style="background-color:#EAF1F8;">
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" style="background-color:#054177;">
                <x-heroicon-o-document-arrow-down class="w-5 h-5 text-white" />
            </div>

            <div>
                <p class="font-semibold text-gray-800">Template Kerja EKU</p>

                @php($template = $this->getTemplate())

                @if ($template)
                    <p class="text-sm text-gray-500">
                        {{ $template->nama_file }} &middot; diupload {{ $template->created_at->diffForHumans() }}
                    </p>
                @else
                    <p class="text-sm text-gray-500">Belum ada template yang diupload Admin BI.</p>
                @endif
            </div>
        </div>

        @if ($template)
            <a href="{{ Storage::disk('public')->url($template->file_path) }}" target="_blank"
               class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90 shrink-0"
               style="background-color:#054177;">
                <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                Unduh Template
            </a>
        @endif
    </div>
</div>
