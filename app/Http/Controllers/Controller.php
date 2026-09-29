<?php

namespace App\Http\Controllers;

use App\Services\Hotel;
use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    public function __construct(protected Hotel $hotel) {}

    /**
     * Transaksi ditolak: catat sebagai kesalahan siswa (untuk penilaian guru),
     * lalu kembali ke form yang sama dengan pesan error.
     */
    protected function fail(string $action, string $message): RedirectResponse
    {
        $this->hotel->log($action, $message, false);

        return back()->withInput()->withErrors(['msg' => $message])->with('toast', [$message, 'err']);
    }

    /** Transaksi berhasil: catat di log dan tampilkan notifikasi. */
    protected function done(string $action, string $detail, ?string $message = null): void
    {
        $this->hotel->log($action, $detail, true);
        session()->flash('toast', [$message ?? $detail, 'ok']);
    }

    /** URL tujuan setelah modal selesai (halaman asal tanpa parameter modal). */
    protected function backUrl(string $fallback): string
    {
        $u = (string) request('_back');

        return $u !== '' && str_starts_with($u, url('/')) ? $u : $fallback;
    }

    /** Tampilkan modal hasil (struk / bukti) setelah redirect. */
    protected function receipt(string $title, string $sub, string $body, array $opt = []): void
    {
        session()->flash('receipt', array_merge(['title' => $title, 'sub' => $sub, 'body' => $body], $opt));
    }
}
