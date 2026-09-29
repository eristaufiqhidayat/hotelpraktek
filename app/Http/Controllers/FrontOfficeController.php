<?php

namespace App\Http\Controllers;

use App\Models\FolioLine;
use App\Models\Reservation;
use App\Models\Room;
use App\Support\Fmt;
use Illuminate\Http\Request;

class FrontOfficeController extends Controller
{
    public const PAY_METHODS = ['Tunai', 'Kartu debit/kredit', 'Transfer / QRIS'];
    public const CITY_LEDGER = 'Tagihan perusahaan (city ledger)';

    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), ['arrival', 'inhouse', 'departure'], true) ? $request->query('tab') : 'arrival';

        return view('frontoffice.index', [
            'tab' => $tab,
            'arrivals' => $this->hotel->arrivals(),
            'inhouse' => $this->hotel->inhouse(),
            'departures' => $this->hotel->departures(),
        ]);
    }

    public function checkin(Request $request, Reservation $reservation)
    {
        $h = $this->hotel;
        $r = $reservation;
        $no = (string) $request->input('room');
        $idno = trim((string) $request->input('idno'));

        if ($r->status !== Reservation::CONFIRMED) {
            return $this->fail('Check-in', 'Reservasi ini tidak berstatus terkonfirmasi.');
        }
        if ($r->arrival !== $h->bizDate()) {
            return $this->fail('Check-in', 'Reservasi ini dijadwalkan datang '.Fmt::date($r->arrival).', bukan hari ini.');
        }
        if (mb_strlen($idno) < 6) {
            return $this->fail('Check-in', 'Nomor identitas tamu wajib dicatat sebelum check-in.');
        }
        $rm = $no !== '' ? Room::with('type')->find($no) : null;
        if (! $rm) {
            return $this->fail('Check-in', 'Pilih kamar untuk tamu.');
        }
        if ($rm->status === 'VD') {
            return $this->fail('Check-in', 'Kamar '.$no.' masih kotor (VD). Tamu tidak boleh masuk sebelum kamar dibersihkan.');
        }
        if ($rm->status === 'OOO') {
            return $this->fail('Check-in', 'Kamar '.$no.' sedang rusak (OOO) dan tidak bisa dijual.');
        }
        if ($rm->isOccupied() || $h->guestIn($no)) {
            return $this->fail('Check-in', 'Kamar '.$no.' sedang ditempati tamu lain.');
        }

        $deposit = Fmt::num($request->input('deposit'));
        $method = in_array($request->input('method'), self::PAY_METHODS, true) ? $request->input('method') : 'Tunai';
        $keys = max(1, min(4, (int) $request->input('keys', 2)));
        $up = $rm->room_type_code !== $r->room_type_code ? ' (upgrade/pindah tipe ke '.$rm->type->name.', tarif tetap)' : '';

        $r->update(['idno' => $idno, 'phone' => (string) ($request->input('phone') ?: $r->phone), 'room_no' => $no, 'status' => Reservation::IN_HOUSE]);
        if ($deposit > 0) {
            $r->folio()->create(['date' => $h->bizDate(), 'dept' => 'Deposit', 'description' => 'Deposit check-in ('.$method.')', 'amount' => -$deposit, 'created_by' => $h->who()]);
        }
        $rm->update(['status' => 'OC']);

        $this->done('Check-in', $r->guest.' ke kamar '.$no.$up, $r->guest.' check-in di kamar '.$no.'.');
        $this->receipt('Check-in berhasil', $r->code, view('receipts.checkin', compact('r', 'no', 'up', 'deposit', 'keys'))->render());

        return redirect()->to($this->backUrl(route('fo.index')));
    }

    public function post(Request $request, Reservation $reservation)
    {
        $h = $this->hotel;
        $r = $reservation;
        $dept = (string) $request->input('dept');
        $amt = Fmt::num($request->input('amount'));
        $desc = trim((string) $request->input('desc'));

        if ($r->status !== Reservation::IN_HOUSE) {
            return $this->fail('Posting tagihan', 'Tagihan hanya bisa diposting ke tamu yang sedang menginap.');
        }
        if (! in_array($dept, ['Laundry', 'Minibar', 'Telepon', 'Lain-lain', 'Pembayaran'], true)) {
            return $this->fail('Posting tagihan', 'Pilih jenis tagihan.');
        }
        if (! $amt) {
            return $this->fail('Posting tagihan', 'Isi jumlah yang akan diposting.');
        }
        if ($dept === 'Pembayaran') {
            $bal = $r->balance();
            if ($amt > $bal) {
                return $this->fail('Posting pembayaran', 'Pembayaran melebihi saldo tagihan ('.Fmt::rp($bal).').');
            }
            $r->folio()->create(['date' => $h->bizDate(), 'dept' => 'Pembayaran', 'description' => $desc ?: 'Pembayaran tamu', 'amount' => -$amt, 'created_by' => $h->who()]);
        } else {
            $r->folio()->create(['date' => $h->bizDate(), 'dept' => $dept, 'description' => $desc ?: $dept, 'amount' => $amt, 'created_by' => $h->who()]);
        }
        $this->done('Posting tagihan', $dept.' '.Fmt::rp($amt).' ke kamar '.$r->room_no, 'Tercatat di folio kamar '.$r->room_no.'.');

        return back();
    }

    public function checkout(Request $request, Reservation $reservation)
    {
        $h = $this->hotel;
        $r = $reservation;
        if ($r->status !== Reservation::IN_HOUSE) {
            return $this->fail('Check-out', 'Tamu ini tidak sedang menginap.');
        }
        $bal = $r->balance();
        $pay = Fmt::num($request->input('pay'));
        $method = (string) $request->input('method');
        $methods = $bal > 0 ? array_merge(self::PAY_METHODS, [self::CITY_LEDGER]) : self::PAY_METHODS;
        if (! in_array($method, $methods, true)) {
            $method = 'Tunai';
        }

        if ($bal > 0 && $pay !== $bal) {
            return $this->fail('Check-out', 'Saldo belum lunas. Tamu harus membayar tepat '.Fmt::rp($bal).'.');
        }
        if ($bal < 0 && $pay !== -$bal) {
            return $this->fail('Check-out', 'Kembalikan sisa deposit tepat '.Fmt::rp(-$bal).'.');
        }
        if (! $request->boolean('key')) {
            return $this->fail('Check-out', 'Pastikan kartu kunci sudah dikembalikan sebelum check-out.');
        }

        if ($bal > 0) {
            $r->folio()->create(['date' => $h->bizDate(), 'dept' => 'Pembayaran', 'description' => 'Pelunasan ('.$method.')', 'amount' => -$bal, 'created_by' => $h->who()]);
        }
        if ($bal < 0) {
            $r->folio()->create(['date' => $h->bizDate(), 'dept' => 'Refund', 'description' => 'Pengembalian deposit ('.$method.')', 'amount' => -$bal, 'created_by' => $h->who()]);
        }
        $early = $r->departure > $h->bizDate();
        $r->update(['status' => Reservation::CHECKED_OUT, 'departure' => $early ? $h->bizDate() : $r->departure]);
        Room::where('no', $r->room_no)->update(['status' => 'VD']);

        $this->done('Check-out', $r->guest.' kamar '.$r->room_no.($bal ? ' · '.Fmt::rp(abs($bal)) : '').($early ? ' · early departure' : ''), $r->guest.' check-out. Kamar '.$r->room_no.' menjadi VD.');
        $r->load('folio');
        $this->receipt('Check-out selesai', $r->code, view('receipts.checkout', compact('r'))->render(), ['wide' => true, 'print' => route('fo.print', $r)]);

        return redirect()->to($this->backUrl(route('fo.index', ['tab' => 'departure'])));
    }

    public function extend(Reservation $reservation)
    {
        $x = $reservation;
        if ($x->status !== Reservation::IN_HOUSE) {
            return $this->fail('Perpanjang', 'Hanya tamu yang menginap yang bisa diperpanjang.');
        }
        $next = Fmt::addDays($x->departure, 1);
        if ($this->hotel->available($x->room_type_code, $x->departure, $next, $x->id) <= 0) {
            return $this->fail('Perpanjang', 'Tidak ada kamar '.$x->type->name.' tersisa untuk perpanjangan.');
        }
        $x->update(['departure' => $next]);
        $this->done('Perpanjang', $x->guest.' kamar '.$x->room_no.' s.d. '.Fmt::date($next), 'Masa inap diperpanjang sampai '.Fmt::date($next).'.');

        return back();
    }

    public function printFolio(Reservation $reservation)
    {
        $reservation->load(['folio', 'type']);

        return view('frontoffice.print', ['r' => $reservation]);
    }
}
