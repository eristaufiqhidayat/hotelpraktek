<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/** Formatting helpers shared by controllers and views (Indonesian locale). */
class Fmt
{
    public static function rp(int|float|null $n): string
    {
        $v = (int) round($n ?? 0);

        return ($v < 0 ? '-Rp ' : 'Rp ').number_format(abs($v), 0, ',', '.');
    }

    public static function num(mixed $v): int
    {
        return (int) preg_replace('/[^0-9]/', '', (string) $v);
    }

    public static function addDays(string $date, int $n): string
    {
        return Carbon::parse($date)->addDays($n)->toDateString();
    }

    public static function nights(string $a, string $b): int
    {
        return (int) round(Carbon::parse($a)->diffInDays(Carbon::parse($b), false));
    }

    /** "30 Sep 2026" or, long, "Rabu, 30 September 2026". */
    public static function date(?string $d, bool $long = false): string
    {
        if (! $d) {
            return '-';
        }

        return Carbon::parse($d)->locale('id')->translatedFormat($long ? 'l, j F Y' : 'j M Y');
    }

    /** "Rab, 30 Sep". */
    public static function short(?string $d): string
    {
        return $d ? Carbon::parse($d)->locale('id')->translatedFormat('D, j M') : '-';
    }

    public static function clock($t): string
    {
        return $t ? Carbon::parse($t)->format('H.i') : '';
    }

    public static function initials(?string $name): string
    {
        $w = array_values(array_filter(preg_split('/\s+/', (string) $name)));

        return strtoupper(implode('', array_map(fn ($x) => mb_substr($x, 0, 1), array_slice($w, 0, 2)))) ?: '?';
    }
}
