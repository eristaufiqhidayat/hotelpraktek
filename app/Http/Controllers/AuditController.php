<?php

namespace App\Http\Controllers;

use App\Models\NightAudit;
use App\Models\Room;
use App\Support\Fmt;
use Illuminate\Support\Facades\DB;

class AuditController extends Controller
{
    public function index()
    {
        $h = $this->hotel;
        $inhouse = $h->inhouse();

        return view('audit.index', [
            'arrivals' => $h->arrivals(),
            'departures' => $h->departures(),
            'inhouse' => $inhouse,
            'roomTotal' => (int) $inhouse->sum('rate'),
            'prev' => $h->statsFor(Fmt::addDays($h->bizDate(), -1)),
        ]);
    }

    public function run()
    {
        $h = $this->hotel;
        if ($h->departures()->count()) {
            return $this->fail('Night audit', 'Masih ada tamu yang harus check-out atau diperpanjang.');
        }
        $d = $h->bizDate();
        $ns = $h->arrivals();

        $st = DB::transaction(function () use ($h, $d, $ns) {
            foreach ($ns as $r) {
                $r->update(['status' => 'No-show']);
            }
            foreach ($h->inhouse() as $r) {
                $r->folio()->create(['date' => $d, 'dept' => 'Kamar', 'description' => 'Kamar '.$r->room_no.' · '.$r->type->name, 'amount' => $r->rate, 'created_by' => 'Night audit']);
                $r->folio()->create(['date' => $d, 'dept' => 'Pajak & layanan', 'description' => 'Pajak & layanan '.$h->tax().'%', 'amount' => $h->taxOf($r->rate), 'created_by' => 'Night audit']);
                Room::where('no', $r->room_no)->where('status', 'OC')->update(['status' => 'OD']);
            }
            $st = $h->statsFor($d);
            NightAudit::create(['biz_date' => $d, 'performed_by' => $h->who(), 'stats' => $st + ['noshow' => $ns->count()]]);

            return $st;
        });

        // log dicatat pada tanggal hari yang ditutup, lalu tanggal hotel maju
        $this->done('Night audit', 'Tutup hari '.Fmt::date($d).' · okupansi '.number_format($st['pct'], 1, ',', '.').'%'.($ns->count() ? ' · '.$ns->count().' no-show' : ''), 'Night audit selesai. Tanggal hotel kini '.Fmt::date(Fmt::addDays($d, 1)).'.');
        $h->put('biz_date', Fmt::addDays($d, 1));

        $this->receipt('Laporan night audit', Fmt::date($d, true), view('receipts.audit', ['st' => $st, 'noshow' => $ns->count()])->render());

        return redirect()->route('audit.index');
    }
}
