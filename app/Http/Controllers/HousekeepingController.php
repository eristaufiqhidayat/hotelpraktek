<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Student;
use App\Models\WorkOrder;
use Illuminate\Http\Request;

class HousekeepingController extends Controller
{
    /** Perubahan status yang diizinkan dari panel housekeeping. */
    public const FLOW = [
        'VD' => [['VC', 'Selesai dibersihkan → VC', 'grn']],
        'OD' => [['OC', 'Selesai dibersihkan → OC', 'grn']],
        'VC' => [['VD', 'Tandai kotor → VD', '']],
        'OC' => [['OD', 'Tandai perlu dibersihkan → OD', '']],
        'OOO' => [['VD', 'Perbaikan selesai → VD', 'grn']],
    ];

    public function index(Request $request)
    {
        $filter = array_key_exists($request->query('st'), Room::STATUS) ? $request->query('st') : 'all';
        $rooms = Room::with(['type', 'currentStay'])->orderBy('no')->get();
        $sel = $rooms->firstWhere('no', $request->query('room'));

        return view('housekeeping.index', [
            'filter' => $filter,
            'rooms' => $rooms,
            'floors' => $rooms->groupBy('floor'),
            'counts' => $rooms->countBy('status'),
            'sel' => $sel,
            'students' => Student::orderBy('sort')->get(),
            'issues' => WorkOrder::where('open', true)->orderByDesc('id')->get(),
        ]);
    }

    public function status(Request $request, Room $room)
    {
        $to = (string) $request->input('status');
        $from = $room->status;
        if (! in_array($to, array_column(self::FLOW[$from] ?? [], 0), true)) {
            return $this->fail('Status kamar', 'Perubahan status kamar '.$room->no.' dari '.$from.' ke '.$to.' tidak diizinkan.');
        }
        if ($from === 'OOO') {
            WorkOrder::where('room_no', $room->no)->where('open', true)->update(['open' => false]);
            $room->ooo_note = null;
        }
        $room->status = $to;
        $room->save();
        $this->done('Status kamar', 'Kamar '.$room->no.' '.$from.' → '.$to, 'Kamar '.$room->no.' kini '.$to.'.');

        return back();
    }

    public function attendant(Request $request, Room $room)
    {
        $att = trim((string) $request->input('attendant'));
        $room->update(['attendant' => $att ?: null]);
        $this->done('Tugas housekeeping', 'Kamar '.$room->no.' → '.($att ?: 'belum ditugaskan'), 'Room attendant kamar '.$room->no.' diperbarui.');

        return back();
    }

    public function issue(Request $request, Room $room)
    {
        $t = trim((string) $request->input('text'));
        if (mb_strlen($t) < 4) {
            return $this->fail('Laporan kerusakan', 'Jelaskan kerusakan yang ditemukan.');
        }
        WorkOrder::create(['room_no' => $room->no, 'text' => $t, 'date' => $this->hotel->bizDate(), 'reported_by' => $this->hotel->who(), 'open' => true]);
        if ($request->boolean('block') && ! $this->hotel->guestIn($room->no)) {
            $room->update(['status' => 'OOO', 'ooo_note' => $t]);
        }
        $this->done('Laporan kerusakan', 'Kamar '.$room->no.': '.$t, 'Laporan kerusakan kamar '.$room->no.' terkirim ke Engineering.');

        return redirect()->route('hk.index', ['room' => $room->no]);
    }
}
