<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\DemoData;
use App\Services\Hotel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(DemoData::class)->run('2026-09-30');
    }

    private function asStudent(string $dept = 'Front Office'): static
    {
        return $this->withSession(['pms_user' => ['role' => 'siswa', 'name' => 'Dimas Pratama', 'dept' => $dept]]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/beranda')->assertRedirect('/masuk');
        $this->get('/masuk')->assertOk()->assertSee('Masuk ke hotel praktik');
    }

    public function test_all_pages_render_for_teacher(): void
    {
        $this->withSession(['pms_user' => ['role' => 'guru', 'name' => 'Guru']]);
        foreach (['/beranda', '/reservasi', '/front-office', '/housekeeping', '/restoran', '/night-audit', '/laporan', '/guru', '/pengaturan'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_student_cannot_open_teacher_panel(): void
    {
        $this->asStudent()->get('/guru')->assertRedirect('/beranda');
    }

    public function test_guru_login_requires_pin(): void
    {
        $this->post('/masuk', ['role' => 'guru', 'guru' => 'Bu Rina', 'pin' => 'salah'])->assertSessionHasErrors('msg');
        $this->post('/masuk', ['role' => 'guru', 'guru' => 'Bu Rina', 'pin' => 'widuri123'])->assertRedirect('/guru');
    }

    public function test_checkin_into_dirty_room_is_rejected_and_logged(): void
    {
        $r = Reservation::where('guest', 'Agus Prasetyo')->first();
        $this->asStudent()->post("/reservasi/{$r->id}/check-in", ['idno' => '3174000011112222', 'room' => '304'])
            ->assertSessionHasErrors('msg');
        $this->assertSame(Reservation::CONFIRMED, $r->fresh()->status);
        $this->assertTrue(ActivityLog::where('action', 'Check-in')->where('ok', false)->where('user_name', 'Dimas Pratama')->exists());
    }

    public function test_checkin_and_checkout_cycle(): void
    {
        $r = Reservation::where('guest', 'Agus Prasetyo')->first();
        $this->asStudent()->post("/reservasi/{$r->id}/check-in", ['idno' => '3174000011112222', 'room' => '301', 'deposit' => '500000', 'method' => 'Tunai']);
        $r->refresh();
        $this->assertSame(Reservation::IN_HOUSE, $r->status);
        $this->assertSame('OC', Room::find('301')->status);
        $this->assertSame(-500000, $r->balance());

        // saldo negatif: harus dikembalikan tepat
        $this->post("/reservasi/{$r->id}/check-out", ['pay' => '100', 'key' => 1])->assertSessionHasErrors('msg');
        $this->post("/reservasi/{$r->id}/check-out", ['pay' => '500000', 'method' => 'Tunai', 'key' => 1]);
        $r->refresh();
        $this->assertSame(Reservation::CHECKED_OUT, $r->status);
        $this->assertSame(0, $r->balance());
        $this->assertSame('VD', Room::find('301')->status);
        $this->assertSame('2026-09-30', $r->departure); // early departure
    }

    public function test_pos_charge_to_room_posts_to_folio(): void
    {
        $guest = Reservation::where('room_no', '103')->where('status', Reservation::IN_HOUSE)->first();
        $before = $guest->balance();
        $this->asStudent('F&B / Kasir')->post('/restoran/keranjang', ['id' => 1]);
        $this->post('/restoran/bayar', ['pay' => 'Charge to room', 'room' => '103']);
        $this->assertSame($before + 45000 + 9450, $guest->fresh()->balance());
    }

    public function test_pos_charge_to_empty_room_is_rejected(): void
    {
        $this->asStudent('F&B / Kasir')->post('/restoran/keranjang', ['id' => 1]);
        $this->post('/restoran/bayar', ['pay' => 'Charge to room', 'room' => '204'])->assertSessionHasErrors('msg');
    }

    public function test_night_audit_blocked_until_departures_resolved_then_advances_date(): void
    {
        $this->asStudent('Night Auditor')->post('/night-audit')->assertSessionHasErrors('msg');
        foreach (app(Hotel::class)->departures() as $r) {
            $this->post("/reservasi/{$r->id}/perpanjang");
        }
        $this->post('/night-audit')->assertRedirect('/night-audit');
        $this->assertSame('2026-10-01', app(Hotel::class)->setting('biz_date'));
        $this->assertSame(0, Reservation::where('status', Reservation::CONFIRMED)->where('arrival', '2026-09-30')->count());
        $this->assertSame(5, Reservation::where('status', Reservation::NO_SHOW)->count());
    }

    public function test_availability_blocks_overbooking(): void
    {
        $hotel = app(Hotel::class);
        $left = $hotel->available('STE', '2026-09-30', '2026-10-01');
        for ($i = 0; $i < $left; $i++) {
            $this->asStudent()->post('/reservasi', ['guest' => "Tamu Suite $i", 'arr' => '2026-09-30', 'dep' => '2026-10-01', 'type' => 'STE']);
        }
        $this->asStudent()->post('/reservasi', ['guest' => 'Tamu Kelebihan', 'arr' => '2026-09-30', 'dep' => '2026-10-01', 'type' => 'STE'])
            ->assertSessionHasErrors('msg');
    }
}
