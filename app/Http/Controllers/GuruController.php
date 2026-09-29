<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\PosOrder;
use App\Models\Reservation;
use App\Models\Student;
use App\Models\WorkOrder;
use App\Services\DemoData;
use App\Support\Fmt;
use Illuminate\Http\Request;

class GuruController extends Controller
{
    public const SCENARIOS = [
        'walkin' => ['Rombongan walk-in', '4 tamu datang bersamaan tanpa reservasi'],
        'vip' => ['Tamu VIP datang', 'Reservasi Junior Suite hari ini, perlu sambutan khusus'],
        'complain' => ['Komplain tamu', 'AC kamar tamu rusak, harus dipindah kamar'],
        'overbook' => ['Overbooking', 'Reservasi tambahan melebihi kamar Deluxe'],
        'breakfast' => ['Sarapan rombongan', '6 pesanan resto dibebankan ke kamar'],
    ];

    public function index(Request $request)
    {
        $students = Student::orderBy('sort')->get();
        $names = $students->pluck('name')->all();
        $logs = ActivityLog::whereIn('user_name', $names)->orderBy('created_at')->get();

        $stats = $students->map(function ($s) use ($logs) {
            $mine = $logs->where('user_name', $s->name);
            $ok = $mine->where('ok', true)->count();
            $err = $mine->where('ok', false)->count();
            $last = $mine->last();
            $acc = $ok + $err ? (int) round($ok / ($ok + $err) * 100) : null;
            $nilai = $acc === null ? null : max(50, min(100, (int) round(60 + $acc * 0.4 - ($ok < 3 ? 5 : 0))));

            return (object) ['name' => $s->name, 'ok' => $ok, 'err' => $err, 'acc' => $acc, 'nilai' => $nilai, 'last' => $last, 'role' => $last->role ?? null];
        });

        $total = $logs->count();
        $errs = $logs->where('ok', false)->count();
        $u = (string) $request->query('u');

        return view('guru.index', [
            'stats' => $stats,
            'students' => $students,
            'active' => $stats->filter(fn ($s) => $s->ok + $s->err > 0)->count(),
            'total' => $total,
            'errs' => $errs,
            'u' => $u,
            'feed' => ActivityLog::when($u !== '', fn ($q) => $q->where('user_name', $u))->orderByDesc('created_at')->orderByDesc('id')->limit(60)->get(),
            'scenarios' => self::SCENARIOS,
        ]);
    }

    public function scenario(Request $request)
    {
        $h = $this->hotel;
        $s = (string) $request->input('s');
        if (! isset(self::SCENARIOS[$s])) {
            return back();
        }
        $d = $h->bizDate();
        $types = $h->types();
        $add = function (string $g, string $type, int $n, array $o = []) use ($d, $types) {
            return Reservation::create([
                'guest' => $g, 'phone' => '0813'.random_int(10000000, 99999999), 'nationality' => $o['nat'] ?? 'Indonesia',
                'room_type_code' => $type, 'arrival' => $d, 'departure' => Fmt::addDays($d, $n), 'adults' => 2, 'rate' => $types[$type]->rate,
                'source' => $o['source'] ?? 'Walk-in', 'status' => Reservation::CONFIRMED, 'note' => $o['note'] ?? null, 'created_by' => 'Skenario guru',
            ]);
        };

        if ($s === 'walkin') {
            foreach (['Rombongan · Bpk. Heru', 'Rombongan · Ibu Sinta', 'Rombongan · Bpk. Rudi', 'Rombongan · Ibu Wati'] as $i => $n) {
                $add($n, $i < 2 ? 'STD' : 'SUP', 1, ['note' => 'Rombongan studi banding, satu tagihan']);
            }
        }
        if ($s === 'vip') {
            $add('Bpk. Dr. Surya (VIP)', 'STE', 2, ['source' => 'Perusahaan', 'note' => 'VIP: welcome drink, buah, sambut di lobi']);
        }
        if ($s === 'overbook') {
            for ($i = 1; $i <= 3; $i++) {
                $add('Tamu overbooking '.$i, 'DLX', 1, ['source' => 'Online travel agent', 'note' => 'Periksa ketersediaan Deluxe']);
            }
        }
        if ($s === 'complain') {
            $g = $h->inhouse()->first(fn ($r) => in_array($r->room_type_code, ['DLX', 'SUP'], true));
            if (! $g) {
                return back()->with('toast', ['Tidak ada tamu yang bisa dipakai untuk skenario ini.', 'err']);
            }
            WorkOrder::create(['room_no' => $g->room_no, 'text' => 'Komplain tamu '.$g->guest.': AC tidak dingin, minta pindah kamar', 'date' => $d, 'reported_by' => 'Skenario guru', 'open' => true]);
            $g->update(['note' => 'KOMPLAIN: AC tidak dingin, minta pindah kamar']);
        }
        if ($s === 'breakfast') {
            foreach ($h->inhouse()->take(6) as $r) {
                $sub = 45000 + 18000;
                $r->folio()->create(['date' => $d, 'dept' => 'F&B', 'description' => 'Sarapan rombongan (skenario)', 'amount' => $sub, 'created_by' => 'Skenario guru']);
                $r->folio()->create(['date' => $d, 'dept' => 'Pajak & layanan', 'description' => 'Pajak & layanan F&B '.$h->tax().'%', 'amount' => $h->taxOf($sub), 'created_by' => 'Skenario guru']);
                PosOrder::create(['date' => $d, 'items' => [['name' => 'Nasi Goreng Widuri', 'price' => 45000, 'qty' => 1], ['name' => 'Kopi Tubruk', 'price' => 18000, 'qty' => 1]], 'subtotal' => $sub, 'tax' => $h->taxOf($sub), 'payment' => 'Dibebankan', 'room_no' => $r->room_no, 'created_by' => 'Skenario guru']);
            }
        }
        $label = self::SCENARIOS[$s][0];
        $this->done('Skenario', $label.' dikirim ke kelas', 'Skenario "'.$label.'" dikirim. Siswa akan melihatnya di Front Office.');

        return back();
    }

    public function export()
    {
        $name = 'log-aktivitas-'.preg_replace('/\s+/', '-', $this->hotel->cls()).'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Tanggal hotel', 'Waktu', 'Pengguna', 'Peran', 'Aksi', 'Detail', 'Hasil']);
            ActivityLog::orderByDesc('created_at')->orderByDesc('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $l) {
                    fputcsv($out, [$l->biz_date, $l->created_at?->format('d/m/Y H.i.s'), $l->user_name, $l->role, $l->action, $l->detail, $l->ok ? 'Benar' : 'Salah']);
                }
            });
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function reset(DemoData $demo)
    {
        $h = $this->hotel;
        $keep = ['hotel' => $h->name(), 'cls' => $h->cls(), 'guru_pin' => $h->setting('guru_pin', config('hotel.guru_pin'))];
        $demo->run(null, $keep);
        session()->forget('pos_cart');

        return redirect()->route('guru')->with('toast', ['Hotel dikembalikan ke data awal.', 'ok']);
    }
}
