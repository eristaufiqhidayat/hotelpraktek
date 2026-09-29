<?php

namespace App\Http\Controllers;

use App\Models\PosOrder;
use App\Models\Room;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $h = $this->hotel;
        $counts = Room::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');

        return view('dashboard', [
            'arrivals' => $h->arrivals(),
            'departures' => $h->departures(),
            'inhouse' => $h->inhouse()->count(),
            'sellable' => $h->sellable(),
            'vc' => (int) ($counts['VC'] ?? 0),
            'dirty' => (int) ($counts['VD'] ?? 0) + (int) ($counts['OD'] ?? 0),
            'counts' => $counts,
            'fnbToday' => (int) PosOrder::where('date', $h->bizDate())->sum('subtotal'),
        ]);
    }
}
