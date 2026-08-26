<?php

namespace App\Support;

class Rupiah
{
    /**
     * Format nominal ke format Rupiah baku aplikasi, contoh: Rp1.000.000,-
     * - Tanpa spasi setelah "Rp"
     * - Selalu diakhiri ",-"
     * - Nilai negatif diberi prefix "-" sebelum "Rp" (mis. -Rp1.000.000,-)
     */
    public static function format(int|float $value): string
    {
        $prefix = $value < 0 ? '-Rp' : 'Rp';

        return $prefix . number_format(abs($value), 0, ',', '.') . ',-';
    }

    /**
     * Sama seperti format(), tetapi untuk kasus deviasi/selisih yang nilai
     * negatifnya perlu ditandai dengan label "(Mines)" alih-alih tanda minus.
     */
    public static function formatMines(int|float $value): string
    {
        $prefix = $value < 0 ? '(Mines) Rp' : 'Rp';

        return $prefix . number_format(abs($value), 0, ',', '.') . ',-';
    }
}
