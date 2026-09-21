<?php

if (! function_exists('fmt_qty')) {
    /**
     * Format angka qty tanpa titik desimal kalau bilangan bulat (mis. 10, bukan
     * 10.00) — sebagian besar qty di gudang ini satuan bulat (pcs/pcc/box).
     * Kalau memang pecahan (mis. 10.5), tetap tampil sampai 2 desimal.
     */
    function fmt_qty(float|int|string $value): string
    {
        $value = (float) $value;

        return rtrim(rtrim(number_format($value, 2), '0'), '.') ?: '0';
    }
}
