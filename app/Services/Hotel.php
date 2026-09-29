<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\FolioLine;
use App\Models\PosOrder;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Setting;
use App\Support\Fmt;
use Illuminate\Support\Collection;

/**
 * Core hotel rules shared across modules: settings, business date, availability,
 * statistics (occupancy, ADR, RevPAR) and the activity log used for grading.
 */
class Hotel
{
    private ?array $settings = null;

    public function setting(string $key, $default = null)
    {
        $this->settings ??= Setting::pluck('value', 'key')->all();

        return $this->settings[$key] ?? $default;
    }

    public function put(string $key, $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        $this->settings = null;
    }

    public function bizDate(): string
    {
        return $this->setting('biz_date', now()->toDateString());
    }

    public function tax(): int
    {
        return (int) $this->setting('tax', 21);
    }

    public function taxOf(int $n): int
    {
        return (int) round($n * $this->tax() / 100);
    }

    public function name(): string
    {
        return $this->setting('hotel', 'Hotel Praktik Widuri');
    }

    public function school(): string
    {
        return $this->setting('school', 'SMK Keluarga Widuri');
    }

    public function cls(): string
    {
        return $this->setting('cls', 'XI Perhotelan 1');
    }

    /* ---------- current user (session based) ---------- */

    public function user(): ?array
    {
        return session('pms_user');
    }

    public function who(): string
    {
        return $this->user()['name'] ?? 'Sistem';
    }

    public function isGuru(): bool
    {
        return ($this->user()['role'] ?? null) === 'guru';
    }

    public function roleLabel(): string
    {
        $u = $this->user();
        if (! $u) {
            return 'Sistem';
        }

        return $u['role'] === 'guru' ? 'Guru' : $u['dept'];
    }

    /* ---------- activity log ---------- */

    public function log(string $action, string $detail, bool $ok = true): void
    {
        ActivityLog::create([
            'biz_date' => $this->bizDate(), 'user_name' => $this->who(), 'role' => $this->roleLabel(),
            'action' => $action, 'detail' => $detail, 'ok' => $ok, 'created_at' => now(),
        ]);
    }

    /* ---------- room lists ---------- */

    public function sellable(): int
    {
        return Room::where('status', '!=', 'OOO')->count();
    }

    public function arrivals(): Collection
    {
        return Reservation::with('type')->where('status', Reservation::CONFIRMED)->where('arrival', $this->bizDate())->orderBy('id')->get();
    }

    public function departures(): Collection
    {
        return Reservation::with(['type', 'folio'])->where('status', Reservation::IN_HOUSE)->where('departure', '<=', $this->bizDate())->orderBy('room_no')->get();
    }

    public function inhouse(): Collection
    {
        return Reservation::with(['type', 'folio'])->where('status', Reservation::IN_HOUSE)->orderBy('room_no')->get();
    }

    public function guestIn(string $roomNo): ?Reservation
    {
        return Reservation::where('status', Reservation::IN_HOUSE)->where('room_no', $roomNo)->first();
    }

    /** Minimum number of rooms of a type still sellable on every night in [a, d). */
    public function available(string $type, string $a, string $d, ?int $ignoreId = null): int
    {
        $total = Room::where('room_type_code', $type)->where('status', '!=', 'OOO')->count();
        $res = Reservation::where('room_type_code', $type)
            ->whereIn('status', [Reservation::CONFIRMED, Reservation::IN_HOUSE])
            ->where('arrival', '<', $d)->where('departure', '>', $a)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->get(['arrival', 'departure']);
        $min = $total;
        for ($x = $a; $x < $d; $x = Fmt::addDays($x, 1)) {
            $used = $res->filter(fn ($r) => $r->arrival <= $x && $x < $r->departure)->count();
            $min = min($min, $total - $used);
        }

        return $min;
    }

    /* ---------- statistics ---------- */

    public function statsFor(string $date): array
    {
        return $this->statsRange([$date])[0];
    }

    /** @param string[] $dates */
    public function statsRange(array $dates): array
    {
        if (! $dates) {
            return [];
        }
        $from = min($dates);
        $to = max($dates);
        $avail = Room::count() - Room::where('status', 'OOO')->count();
        $stays = Reservation::whereIn('status', [Reservation::IN_HOUSE, Reservation::CHECKED_OUT])
            ->whereNotNull('room_no')->where('arrival', '<=', $to)->where('departure', '>', $from)
            ->get(['arrival', 'departure']);
        $folio = FolioLine::whereBetween('date', [$from, $to])
            ->whereIn('dept', array_merge(['Kamar'], FolioLine::OTHER_REVENUE))
            ->selectRaw('date, dept, SUM(amount) as total')->groupBy('date', 'dept')->get();
        $fnb = PosOrder::whereBetween('date', [$from, $to])->selectRaw('date, SUM(subtotal) as total')->groupBy('date')->pluck('total', 'date');

        return array_map(function ($date) use ($avail, $stays, $folio, $fnb) {
            $occ = $stays->filter(fn ($r) => $r->arrival <= $date && $date < $r->departure)->count();
            $roomRev = (int) $folio->where('date', $date)->where('dept', 'Kamar')->sum('total');
            $other = (int) $folio->where('date', $date)->whereIn('dept', FolioLine::OTHER_REVENUE)->sum('total');
            $f = (int) ($fnb[$date] ?? 0);

            return [
                'date' => $date, 'occ' => $occ, 'avail' => $avail, 'pct' => $avail ? $occ / $avail * 100 : 0,
                'roomRev' => $roomRev, 'adr' => $occ ? $roomRev / $occ : 0, 'revpar' => $avail ? $roomRev / $avail : 0,
                'fnb' => $f, 'other' => $other, 'total' => $roomRev + $f + $other,
            ];
        }, $dates);
    }

    public function types(): Collection
    {
        return RoomType::orderBy('sort')->get()->keyBy('code');
    }
}
