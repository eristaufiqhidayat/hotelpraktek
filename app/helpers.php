<?php

use App\Support\Fmt;
use App\Support\Icons;

if (! function_exists('rp')) {
    function rp($n): string
    {
        return Fmt::rp($n);
    }
}

if (! function_exists('fdate')) {
    function fdate(?string $d, bool $long = false): string
    {
        return Fmt::date($d, $long);
    }
}

if (! function_exists('fshort')) {
    function fshort(?string $d): string
    {
        return Fmt::short($d);
    }
}

if (! function_exists('pct')) {
    function pct(float $v): string
    {
        return number_format($v, 1, ',', '.').'%';
    }
}

if (! function_exists('ic')) {
    /** Ikon SVG garis (sama dengan mockup). */
    function ic(string $name, int $size = 18): \Illuminate\Support\HtmlString
    {
        return Icons::svg($name, $size);
    }
}

if (! function_exists('modal_url')) {
    /** URL halaman saat ini dengan modal terbuka. */
    function modal_url(string $modal, $id = null, array $extra = []): string
    {
        return request()->fullUrlWithQuery(array_merge(['modal' => $modal, 'id' => $id], $extra));
    }
}

if (! function_exists('close_url')) {
    /** URL halaman saat ini tanpa modal. */
    function close_url(): string
    {
        return request()->fullUrlWithoutQuery(['modal', 'id']);
    }
}
