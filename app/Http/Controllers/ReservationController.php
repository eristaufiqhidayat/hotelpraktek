<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Support\Fmt;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public const TABS = ['aktif' => 'Aktif', 'hariini' => 'Datang hari ini', 'mendatang' => 'Mendatang', 'selesai' => 'Selesai / batal', 'semua' => 'Semua'];

    public function index(Request $request)
    {
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'aktif';
        $q = trim((string) $request->query('q'));
        $b = $this->hotel->bizDate();

        $list = Reservation::with('type')
            ->when($tab === 'aktif', fn ($x) => $x->whereIn('status', [Reservation::CONFIRMED, Reservation::IN_HOUSE]))
            ->when($tab === 'hariini', fn ($x) => $x->where('status', Reservation::CONFIRMED)->where('arrival', $b))
            ->when($tab === 'mendatang', fn ($x) => $x->where('status', Reservation::CONFIRMED)->where('arrival', '>', $b))
            ->when($tab === 'selesai', fn ($x) => $x->whereIn('status', [Reservation::CHECKED_OUT, Reservation::CANCELLED, Reservation::NO_SHOW]))
            ->when($q !== '', function ($x) use ($q) {
                $id = preg_match('/(\d{4,})/', $q, $m) ? (int) $m[1] - 1000 : 0;
                $x->where(fn ($w) => $w->where('guest', 'like', "%$q%")->orWhere('room_no', $q)->orWhere('id', $id));
            })
            ->orderBy('arrival')->orderBy('id')->get();

        $days = array_map(fn ($i) => Fmt::addDays($b, $i), range(0, 6));
        $avail = [];
        foreach ($this->hotel->types() as $code => $t) {
            foreach ($days as $d) {
                $avail[$code][$d] = $this->hotel->available($code, $d, Fmt::addDays($d, 1));
            }
        }

        return view('reservations.index', compact('tab', 'q', 'list', 'days', 'avail'));
    }

    public function store(Request $request)
    {
        $h = $this->hotel;
        $walk = $request->boolean('walkin');
        $act = $walk ? 'Walk-in' : 'Reservasi baru';
        $g = trim((string) $request->input('guest'));
        $arr = (string) $request->input('arr');
        $dep = (string) $request->input('dep');
        $type = (string) $request->input('type');
        $types = $h->types();

        if ($walk) {
            $arr = $h->bizDate();
        }
        if (mb_strlen($g) < 3) {
            return $this->fail($act, 'Nama tamu wajib diisi lengkap.');
        }
        if (! $types->has($type)) {
            return $this->fail($act, 'Pilih tipe kamar.');
        }
        if (! strtotime($arr) || ! strtotime($dep) || $dep <= $arr) {
            return $this->fail($act, 'Tanggal pergi harus setelah tanggal datang.');
        }
        if ($arr < $h->bizDate()) {
            return $this->fail($act, 'Tanggal datang tidak boleh sebelum tanggal hotel ('.Fmt::date($h->bizDate()).').');
        }
        if ($h->available($type, $arr, $dep) <= 0) {
            return $this->fail($act, 'Kamar '.$types[$type]->name.' penuh pada tanggal tersebut. Tawarkan tipe lain atau tanggal lain.');
        }

        $source = in_array($request->input('source'), Reservation::SOURCES, true) ? $request->input('source') : 'Telepon';
        $r = Reservation::create([
            'guest' => $g, 'phone' => (string) $request->input('phone'), 'nationality' => trim((string) $request->input('nat')) ?: 'Indonesia',
            'room_type_code' => $type, 'arrival' => $arr, 'departure' => $dep,
            'adults' => max(1, min(4, (int) $request->input('adults', 1))), 'rate' => $types[$type]->rate,
            'source' => $walk ? 'Walk-in' : $source, 'status' => Reservation::CONFIRMED,
            'note' => trim((string) $request->input('note')) ?: null, 'created_by' => $h->who(),
        ]);
        $this->done($act, $g.' · '.$types[$type]->name.' · '.Fmt::nights($arr, $dep).' malam', 'Reservasi '.$r->code.' tersimpan.');

        $back = $this->backUrl(route('res.index'));
        if ($walk) {
            return redirect()->to($this->withModal($back, 'checkin', $r->id));
        }

        return redirect()->to($back);
    }

    public function cancel(Reservation $reservation)
    {
        if ($reservation->status !== Reservation::CONFIRMED) {
            return $this->fail('Pembatalan', 'Hanya reservasi terkonfirmasi yang bisa dibatalkan.');
        }
        $reservation->update(['status' => Reservation::CANCELLED]);
        $this->done('Pembatalan', $reservation->guest.' · '.$reservation->code, 'Reservasi dibatalkan.');

        return back();
    }

    private function withModal(string $url, string $modal, int $id): string
    {
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $qs);
        $qs['modal'] = $modal;
        $qs['id'] = $id;

        return strtok($url, '?').'?'.http_build_query($qs);
    }
}
