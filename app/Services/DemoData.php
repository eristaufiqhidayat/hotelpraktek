<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\FolioLine;
use App\Models\MenuItem;
use App\Models\NightAudit;
use App\Models\PosOrder;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Setting;
use App\Models\Student;
use App\Models\WorkOrder;
use App\Support\Fmt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sample data for the training hotel (same scenario as the approved mockup):
 * 30 rooms on 3 floors, 13 in-house guests, today's arrivals, future bookings,
 * two weeks of history for reports and yesterday's class activity log.
 */
class DemoData
{
    private string $d0;
    private int $tax = 21;
    private array $types = [];
    private int $phones = 0;

    public function run(?string $today = null, array $keepSettings = []): void
    {
        $this->d0 = $today ?? now()->toDateString();

        DB::transaction(function () use ($keepSettings) {
            $this->wipe();
            $this->base($keepSettings);
            $this->stays();
            $this->pos();
            $this->history();
            $this->activity();
        });
    }

    private function wipe(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ([FolioLine::class, Reservation::class, PosOrder::class, ActivityLog::class, NightAudit::class, WorkOrder::class, Room::class, RoomType::class, Student::class, MenuItem::class, Setting::class] as $m) {
            $m::query()->delete();
        }
        Schema::enableForeignKeyConstraints();
    }

    private function base(array $keep): void
    {
        $settings = array_merge([
            'hotel' => 'Hotel Praktik Widuri', 'school' => 'SMK Keluarga Widuri', 'tax' => 21,
            'cls' => 'XI Perhotelan 1', 'guru_pin' => config('hotel.guru_pin'),
        ], $keep, ['biz_date' => $this->d0]);
        foreach ($settings as $k => $v) {
            Setting::create(['key' => $k, 'value' => $v]);
        }
        $this->tax = (int) $settings['tax'];

        $types = [
            ['STD', 'Standard Double', 450000], ['SUP', 'Superior Twin', 600000],
            ['DLX', 'Deluxe King', 850000], ['STE', 'Junior Suite', 1250000],
        ];
        foreach ($types as $i => [$code, $name, $rate]) {
            RoomType::create(['code' => $code, 'name' => $name, 'rate' => $rate, 'sort' => $i]);
            $this->types[$code] = ['name' => $name, 'rate' => $rate];
        }

        $rooms = [];
        for ($f = 1; $f <= 3; $f++) {
            for ($i = 1; $i <= 10; $i++) {
                $type = $f === 1 ? 'STD' : ($f === 2 ? 'SUP' : ($i >= 9 ? 'STE' : 'DLX'));
                $rooms[] = ['no' => $f.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'room_type_code' => $type, 'floor' => $f, 'status' => 'VC', 'attendant' => null, 'ooo_note' => null];
            }
        }
        Room::insert($rooms);

        foreach (['Dimas Pratama', 'Nadia Aulia', 'Sari Maharani', 'Fajar Setiawan', 'Putri Lestari', 'Rizky Hidayat', 'Ayu Kartika', 'Bayu Ramadhan'] as $i => $s) {
            Student::create(['name' => $s, 'sort' => $i]);
        }

        foreach (self::menu() as [$cat, $name, $price, $note]) {
            MenuItem::create(['category' => $cat, 'name' => $name, 'price' => $price, 'note' => $note ?: null]);
        }
    }

    public static function menu(): array
    {
        return [
            ['Makanan', 'Nasi Goreng Widuri', 45000, 'Telur, ayam, kerupuk'], ['Makanan', 'Mie Goreng Jawa', 42000, 'Mie kuning, sayur'],
            ['Makanan', 'Soto Ayam Lamongan', 38000, 'Koya, nasi'], ['Makanan', 'Gado-gado', 35000, 'Bumbu kacang'],
            ['Makanan', 'Sop Buntut', 85000, 'Nasi, emping'], ['Makanan', 'Ayam Bakar Taliwang', 65000, 'Plecing kangkung'],
            ['Makanan', 'Club Sandwich', 55000, 'Kentang goreng'], ['Makanan', 'Spaghetti Bolognese', 60000, 'Keju parmesan'],
            ['Minuman', 'Es Teh Manis', 15000, ''], ['Minuman', 'Kopi Tubruk', 18000, ''], ['Minuman', 'Cappuccino', 32000, ''],
            ['Minuman', 'Jus Alpukat', 28000, ''], ['Minuman', 'Wedang Jahe', 20000, ''], ['Minuman', 'Air Mineral', 12000, ''],
            ['Dessert', 'Pisang Goreng Keju', 25000, ''], ['Dessert', 'Klepon', 22000, ''], ['Dessert', 'Es Campur', 30000, ''], ['Dessert', 'Puding Cokelat', 25000, ''],
        ];
    }

    private function d(int $n): string
    {
        return Fmt::addDays($this->d0, $n);
    }

    private function taxOf(int $n): int
    {
        return (int) round($n * $this->tax / 100);
    }

    /** Create a confirmed reservation; returns [model, folio-lines accumulator]. */
    private function mk(string $guest, string $type, int $a, int $d, array $o = []): Reservation
    {
        $this->phones++;

        return Reservation::create([
            'guest' => $guest, 'phone' => '0812'.(3400000 + $this->phones * 7331), 'idno' => $o['idno'] ?? null,
            'nationality' => $o['nat'] ?? 'Indonesia', 'room_type_code' => $type, 'arrival' => $this->d($a), 'departure' => $this->d($d),
            'adults' => $o['adults'] ?? 2, 'rate' => $this->types[$type]['rate'], 'source' => $o['source'] ?? 'Telepon',
            'status' => $o['status'] ?? Reservation::CONFIRMED, 'room_no' => $o['room'] ?? null, 'note' => $o['note'] ?? null,
            'created_by' => $o['by'] ?? 'Data contoh',
        ]);
    }

    private array $lines = [];

    private function line(Reservation $r, string $date, string $dept, string $desc, int $amount, string $by = 'Data contoh'): void
    {
        $this->lines[] = ['reservation_id' => $r->id, 'date' => $date, 'dept' => $dept, 'description' => $desc, 'amount' => $amount, 'created_by' => $by, 'created_at' => now(), 'updated_at' => now()];
    }

    private function bal(Reservation $r): int
    {
        return array_sum(array_map(fn ($l) => $l['amount'], array_filter($this->lines, fn ($l) => $l['reservation_id'] === $r->id)));
    }

    private function nightly(Reservation $r, string $date): void
    {
        $this->line($r, $date, 'Kamar', 'Kamar '.$r->room_no.' · '.$this->types[$r->room_type_code]['name'], $r->rate, 'Night audit');
        $this->line($r, $date, 'Pajak & layanan', 'Pajak & layanan '.$this->tax.'%', $this->taxOf($r->rate), 'Night audit');
    }

    private function flush(): void
    {
        foreach (array_chunk($this->lines, 200) as $chunk) {
            FolioLine::insert($chunk);
        }
        $this->lines = [];
    }

    private function stays(): void
    {
        $inh = [
            ['Budi Santoso', '101', 'STD', -2, 0, []], ['Siti Rahmawati', '103', 'STD', -1, 1, []], ['Andreas Wijaya', '105', 'STD', -1, 2, ['source' => 'Perusahaan']],
            ['Maria Gonzalez', '106', 'STD', -3, 0, ['nat' => 'Spanyol', 'source' => 'Online travel agent', 'adults' => 1]], ['Hendra Gunawan', '201', 'SUP', -1, 1, ['source' => 'Perusahaan']],
            ['Dewi Kusuma', '202', 'SUP', -2, 0, []], ['Kenji Tanaka', '205', 'SUP', -1, 3, ['nat' => 'Jepang', 'source' => 'Online travel agent']],
            ['Rina Marlina', '208', 'SUP', -2, 1, []], ['Michael Tan', '210', 'SUP', -1, 0, ['nat' => 'Singapura', 'adults' => 1]],
            ['Lestari Wulandari', '302', 'DLX', -1, 2, ['source' => 'Website']], ['Robert Smith', '303', 'DLX', -2, 1, ['nat' => 'Australia', 'source' => 'Online travel agent']],
            ['Indah Permata', '305', 'DLX', -1, 1, []], ['Yusuf Maulana', '309', 'STE', -2, 2, ['source' => 'Perusahaan']],
        ];
        $odRooms = ['103', '201', '205', '303', '309', '101'];
        foreach ($inh as $k => [$g, $no, $type, $a, $d, $o]) {
            $foreign = isset($o['nat']) && $o['nat'] !== 'Indonesia';
            $o['idno'] = $foreign ? 'P'.(5100230 + $k * 911) : '3174'.(120000000000 + $k * 3571913);
            $r = $this->mk($g, $type, $a, $d, $o + ['status' => Reservation::IN_HOUSE, 'room' => $no]);
            $this->line($r, $r->arrival, 'Deposit', 'Deposit check-in (Tunai)', -500000);
            for ($n = 0; $n < -$a; $n++) {
                $this->nightly($r, Fmt::addDays($r->arrival, $n));
            }
            if ($k % 3 === 0) {
                $this->line($r, $this->d(-1), 'F&B', 'Widuri Resto · makan malam', 110000);
                $this->line($r, $this->d(-1), 'Pajak & layanan', 'Pajak & layanan F&B', $this->taxOf(110000));
            }
            if ($k % 4 === 1) {
                $this->line($r, $this->d(-1), 'Laundry', 'Laundry 5 potong', 75000);
            }
            Room::where('no', $no)->update(['status' => in_array($no, $odRooms, true) ? 'OD' : 'OC']);
        }

        $this->mk('Agus Prasetyo', 'DLX', 0, 2, ['source' => 'Online travel agent']);
        $this->mk('Fitri Handayani', 'STD', 0, 1, ['source' => 'Telepon', 'adults' => 1]);
        $this->mk('Sarah Johnson', 'SUP', 0, 3, ['nat' => 'Amerika Serikat', 'source' => 'Online travel agent']);
        $this->mk('Eko Nugroho', 'STD', 0, 2, ['source' => 'Perusahaan', 'note' => 'Tagihan kamar ke perusahaan']);
        $this->mk('Nur Aini', 'STE', 0, 1, ['source' => 'Website', 'note' => 'Bulan madu, minta dekorasi kamar']);
        $this->mk('Joko Susilo', 'DLX', 1, 3);
        $this->mk('Daniel Lee', 'SUP', 2, 4, ['nat' => 'Korea Selatan', 'source' => 'Online travel agent']);
        $this->mk('Ratna Sari', 'STD', 1, 2);
        $this->mk('Wahyu Hidayat', 'DLX', 3, 5, ['source' => 'Perusahaan']);

        // past stays (already checked out)
        foreach ([['Tono Wibowo', '104', 'STD', -3, -1], ['Lina Marpaung', '204', 'SUP', -2, -1], ['Gilang Ramadhan', '304', 'DLX', -3, -1], ['Anisa Rahma', '108', 'STD', -1, 0]] as [$g, $no, $type, $a, $d]) {
            $r = $this->mk($g, $type, $a, $d, ['idno' => '3171'.(220000000000 + (int) $no * 7919), 'status' => Reservation::CHECKED_OUT, 'room' => $no]);
            $this->line($r, $r->arrival, 'Deposit', 'Deposit check-in (Tunai)', -300000);
            for ($n = 0; $n < Fmt::nights($r->arrival, $r->departure); $n++) {
                $this->nightly($r, Fmt::addDays($r->arrival, $n));
            }
            $this->line($r, $r->departure, 'Pembayaran', 'Pelunasan (Kartu debit)', -$this->bal($r));
        }
        $this->flush();

        Room::whereIn('no', ['104', '108', '204', '304'])->update(['status' => 'VD']);
        Room::where('no', '207')->update(['status' => 'OOO', 'ooo_note' => 'AC tidak berfungsi, menunggu teknisi']);
        WorkOrder::create(['room_no' => '207', 'text' => 'AC tidak berfungsi, menunggu teknisi', 'date' => $this->d(-1), 'reported_by' => 'Data contoh', 'open' => true]);
        foreach (['101' => 'Sari', '103' => 'Bayu', '104' => 'Sari', '201' => 'Bayu'] as $no => $att) {
            Room::where('no', $no)->update(['attendant' => $att]);
        }
    }

    private function order(string $date, array $items, string $pay, string $room = '', string $by = 'Data contoh'): void
    {
        $menu = self::menu();
        $lines = array_map(fn ($it) => ['name' => $menu[$it[0]][1], 'price' => $menu[$it[0]][2], 'qty' => $it[1]], $items);
        $sub = array_sum(array_map(fn ($l) => $l['price'] * $l['qty'], $lines));
        PosOrder::create(['date' => $date, 'items' => $lines, 'subtotal' => $sub, 'tax' => $this->taxOf($sub), 'payment' => $pay, 'room_no' => $room ?: null, 'table_no' => null, 'created_by' => $by]);
    }

    private function pos(): void
    {
        $this->order($this->d(-3), [[0, 2], [8, 2]], 'Tunai');
        $this->order($this->d(-3), [[4, 1], [11, 1]], 'QRIS');
        $this->order($this->d(-2), [[5, 2], [10, 2], [14, 1]], 'Tunai');
        $this->order($this->d(-2), [[1, 1], [9, 1]], 'QRIS');
        $this->order($this->d(-1), [[0, 1], [3, 1], [8, 2]], 'Tunai');
        $this->order($this->d(-1), [[6, 2], [12, 2]], 'QRIS');
        $this->order($this->d(-1), [[7, 1], [10, 1]], 'Kartu');
        $this->order($this->d0, [[2, 2], [9, 2]], 'Tunai');
    }

    /** Two weeks of one-night stays so the reports have something to show. */
    private function history(): void
    {
        $first = ['Ahmad', 'Bambang', 'Citra', 'Dian', 'Endang', 'Farhan', 'Galih', 'Hana', 'Irfan', 'Jihan', 'Kevin', 'Laras', 'Mega', 'Niko', 'Oki', 'Prita', 'Reza', 'Tiara', 'Umar', 'Vina'];
        $last = ['Saputra', 'Wijaya', 'Lubis', 'Siregar', 'Hakim', 'Pratiwi', 'Nasution', 'Utami', 'Firmansyah', 'Anggraini'];
        $pool = Room::orderBy('no')->get(['no', 'room_type_code'])->all();
        $sources = Reservation::SOURCES;
        for ($off = -14; $off <= -3; $off++) {
            $n = 9 + (($off * 7 + 40) % 11);
            $day = $this->d($off);
            for ($k = 0; $k < $n; $k++) {
                $rm = $pool[($k * 3 + $off + 30) % count($pool)];
                $h = $this->mk($first[($k + $off + 20) % 20].' '.$last[($k * 2 + $off + 30) % 10], $rm->room_type_code, $off, $off + 1, [
                    'idno' => '3175'.(300000000000 + $k * 1777 + ($off + 20) * 99991), 'source' => $sources[$k % 5],
                    'status' => Reservation::CHECKED_OUT, 'room' => $rm->no, 'by' => 'Riwayat',
                ]);
                $this->nightly($h, $day);
                if ($k % 4 === 0) {
                    $this->line($h, $day, 'Laundry', 'Laundry', 60000);
                }
                $this->line($h, $h->departure, 'Pembayaran', 'Pelunasan', -$this->bal($h));
            }
            $this->order($day, [[$k % 8, 2], [8 + ($off + 20) % 6, 3]], 'Tunai');
            $this->order($day, [[($off + 20) % 8, 3], [10, 2], [14 + ($off + 20) % 4, 1]], 'QRIS');
            if ($off % 2 === 0) {
                $this->order($day, [[4, 2], [11, 2]], 'Kartu');
            }
        }
        $this->flush();
    }

    private function activity(): void
    {
        $y = $this->d(-1);
        $acts = [
            ['Dimas Pratama', 'Front Office', 'Check-in', 'RSV Kenji Tanaka ke kamar 205', true], ['Nadia Aulia', 'Front Office', 'Reservasi baru', 'Lestari Wulandari · Deluxe King', true],
            ['Fajar Setiawan', 'Front Office', 'Check-in', 'Mencoba memakai kamar 204 yang masih kotor', false], ['Fajar Setiawan', 'Front Office', 'Check-in', 'RSV Hendra Gunawan ke kamar 201', true],
            ['Sari Maharani', 'Housekeeping', 'Status kamar', 'Kamar 302 VD → VC', true], ['Bayu Ramadhan', 'Housekeeping', 'Laporan kerusakan', 'Kamar 207: AC tidak berfungsi', true],
            ['Putri Lestari', 'F&B / Kasir', 'POS', 'Pesanan meja 3 dibebankan ke kamar 101', true], ['Rizky Hidayat', 'F&B / Kasir', 'POS', 'Membebankan ke kamar kosong 204', false],
            ['Rizky Hidayat', 'F&B / Kasir', 'POS', 'Pesanan meja 5 dibayar QRIS', true], ['Ayu Kartika', 'Night Auditor', 'Night audit', 'Tutup hari '.Fmt::date($y), true],
            ['Nadia Aulia', 'Front Office', 'Check-out', 'Mencoba check-out saat saldo belum lunas', false], ['Nadia Aulia', 'Front Office', 'Check-out', 'Tono Wibowo kamar 104', true],
            ['Dimas Pratama', 'Front Office', 'Posting tagihan', 'Laundry kamar 202', true], ['Sari Maharani', 'Housekeeping', 'Status kamar', 'Kamar 310 VD → VC', true],
        ];
        $base = now()->subDay();
        $c = count($acts);
        foreach ($acts as $k => [$u, $role, $action, $detail, $ok]) {
            ActivityLog::create(['biz_date' => $y, 'user_name' => $u, 'role' => $role, 'action' => $action, 'detail' => $detail, 'ok' => $ok, 'created_at' => $base->copy()->subSeconds(($c - $k) * 420)]);
        }
        NightAudit::create(['biz_date' => $y, 'performed_by' => 'Ayu Kartika']);
    }
}
