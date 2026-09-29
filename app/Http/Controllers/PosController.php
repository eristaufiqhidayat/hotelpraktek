<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\PosOrder;
use App\Support\Fmt;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public const PAYMENTS = ['Tunai' => 'Tunai', 'QRIS' => 'QRIS', 'Kartu' => 'Kartu', 'Charge to room' => 'Bebankan ke kamar'];

    private function cart(): array
    {
        return session('pos_cart', []);
    }

    public function index(Request $request)
    {
        $cat = in_array($request->query('cat'), MenuItem::CATEGORIES, true) ? $request->query('cat') : 'Semua';
        $cart = $this->cart();
        $sub = array_sum(array_map(fn ($c) => $c['price'] * $c['qty'], $cart));

        return view('pos.index', [
            'cat' => $cat,
            'items' => MenuItem::where('active', true)->when($cat !== 'Semua', fn ($q) => $q->where('category', $cat))->orderBy('id')->get(),
            'cart' => $cart,
            'sub' => $sub,
            'tax' => $this->hotel->taxOf($sub),
            'rooms' => $this->hotel->inhouse(),
            'today' => PosOrder::where('date', $this->hotel->bizDate())->orderByDesc('id')->get(),
        ]);
    }

    public function add(Request $request)
    {
        $m = MenuItem::findOrFail($request->input('id'));
        $cart = $this->cart();
        $i = array_search($m->id, array_column($cart, 'id'), true);
        if ($i === false) {
            $cart[] = ['id' => $m->id, 'name' => $m->name, 'price' => $m->price, 'qty' => 1];
        } else {
            $cart[$i]['qty']++;
        }
        session(['pos_cart' => $cart]);

        return back();
    }

    public function qty(Request $request)
    {
        $cart = $this->cart();
        $i = (int) $request->input('i');
        if (isset($cart[$i])) {
            $cart[$i]['qty'] += $request->input('d') == -1 ? -1 : 1;
            if ($cart[$i]['qty'] <= 0) {
                array_splice($cart, $i, 1);
            }
        }
        session(['pos_cart' => array_values($cart)]);

        return back();
    }

    public function clear()
    {
        session()->forget('pos_cart');

        return back();
    }

    public function pay(Request $request)
    {
        $h = $this->hotel;
        $cart = $this->cart();
        if (! $cart) {
            return $this->fail('POS', 'Pesanan masih kosong.');
        }
        $pay = array_key_exists($request->input('pay'), self::PAYMENTS) ? $request->input('pay') : 'Tunai';
        $table = trim((string) $request->input('table'));
        $sub = array_sum(array_map(fn ($c) => $c['price'] * $c['qty'], $cart));
        $tax = $h->taxOf($sub);
        $rno = '';

        if ($pay === 'Charge to room') {
            $rno = (string) $request->input('room');
            if ($rno === '') {
                return $this->fail('POS', 'Pilih kamar tamu untuk membebankan tagihan.');
            }
            $g = $h->guestIn($rno);
            if (! $g) {
                return $this->fail('POS', 'Kamar '.$rno.' tidak sedang ditempati tamu. Tagihan tidak bisa dibebankan.');
            }
            $g->folio()->create(['date' => $h->bizDate(), 'dept' => 'F&B', 'description' => 'Widuri Resto'.($table ? ' · meja '.$table : ''), 'amount' => $sub, 'created_by' => $h->who()]);
            $g->folio()->create(['date' => $h->bizDate(), 'dept' => 'Pajak & layanan', 'description' => 'Pajak & layanan F&B '.$h->tax().'%', 'amount' => $tax, 'created_by' => $h->who()]);
        }

        $o = PosOrder::create([
            'date' => $h->bizDate(), 'items' => array_map(fn ($c) => ['name' => $c['name'], 'price' => $c['price'], 'qty' => $c['qty']], $cart),
            'subtotal' => $sub, 'tax' => $tax, 'payment' => $pay === 'Charge to room' ? 'Dibebankan' : $pay,
            'room_no' => $rno ?: null, 'table_no' => $table ?: null, 'created_by' => $h->who(),
        ]);
        $this->done('POS', 'Pesanan '.$o->code.($table ? ' meja '.$table : '').' · '.Fmt::rp($sub + $tax).' · '.($rno ? 'ke kamar '.$rno : $o->payment), 'Pesanan '.$o->code.' tersimpan.');
        session()->forget('pos_cart');
        $this->receipt('Struk '.$o->code, Fmt::date($o->date), view('receipts.pos', ['o' => $o, 'taxPct' => $h->tax()])->render());

        return redirect()->route('pos.index');
    }
}
