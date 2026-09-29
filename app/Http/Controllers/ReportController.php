<?php

namespace App\Http\Controllers;

use App\Support\Fmt;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $days = in_array((int) $request->query('days'), [7, 14, 30], true) ? (int) $request->query('days') : 7;
        $b = $this->hotel->bizDate();
        $dates = array_map(fn ($i) => Fmt::addDays($b, -$i), range($days - 1, 0));
        $rows = $this->hotel->statsRange($dates);
        $sum = ['room' => 0, 'fnb' => 0, 'other' => 0, 'occ' => 0, 'avail' => 0];
        foreach ($rows as $r) {
            $sum['room'] += $r['roomRev'];
            $sum['fnb'] += $r['fnb'];
            $sum['other'] += $r['other'];
            $sum['occ'] += $r['occ'];
            $sum['avail'] += $r['avail'];
        }
        $max = max(array_merge(array_column($rows, 'total'), [1]));

        return view('reports.index', compact('days', 'rows', 'sum', 'max'));
    }
}
