<?php

namespace App\Support;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class UploadedFileNaming
{
    public static function bersih(): Closure
    {
        return function (UploadedFile $file): string {
            $namaAsli = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $ekstensi = $file->getClientOriginalExtension() ?: $file->extension();

            $namaBersih = Str::slug($namaAsli);

            if ($namaBersih === '') {
                $namaBersih = 'file';
            }

            return now()->format('Ymd_His') . '_' . $namaBersih . '.' . $ekstensi;
        };
    }
}
