<?php

namespace App\View;

use App\Models\Reservation;
use App\Models\Room;
use App\Services\Hotel;
use Illuminate\View\View;

/** Menyiapkan data modal yang dibuka lewat query string (?modal=checkin&id=12). */
class ModalComposer
{
    public function __construct(private Hotel $hotel) {}

    public function compose(View $view): void
    {
        $modal = request('modal');
        $id = request('id');
        $data = ['modal' => null];

        if (in_array($modal, ['newres', 'walkin'], true)) {
            $data = ['modal' => $modal, 'types' => $this->hotel->types()];
        } elseif (in_array($modal, ['checkin', 'folio', 'checkout'], true) && ($r = Reservation::with(['type', 'folio'])->find($id))) {
            $okStatus = $modal === 'checkin' ? Reservation::CONFIRMED : Reservation::IN_HOUSE;
            if ($r->status === $okStatus) {
                $data = ['modal' => $modal, 'r' => $r];
                if ($modal === 'checkin') {
                    $data['rooms'] = Room::with('type')->get()
                        ->sortBy(fn ($x) => ($x->room_type_code === $r->room_type_code ? '0' : '1').$x->no)->values();
                }
            }
        } elseif ($modal === 'ooo' && ($room = Room::with('type')->find($id))) {
            $data = ['modal' => 'ooo', 'room' => $room, 'occupied' => (bool) $this->hotel->guestIn($room->no)];
        }

        $view->with($data + ['receipt' => session('receipt')]);
    }
}
