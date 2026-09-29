<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use App\Models\Student;
use App\Support\Fmt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'types' => $this->hotel->types(),
            'students' => Student::orderBy('sort')->pluck('name')->implode("\n"),
        ]);
    }

    public function save(Request $request)
    {
        $h = $this->hotel;
        $h->put('hotel', trim((string) $request->input('hotel')) ?: $h->name());
        $h->put('cls', trim((string) $request->input('cls')) ?: $h->cls());
        $h->put('tax', max(0, min(50, (int) $request->input('tax'))));
        foreach (RoomType::all() as $t) {
            $v = Fmt::num($request->input('rate_'.$t->code));
            if ($v) {
                $t->update(['rate' => $v]);
            }
        }
        $pin = trim((string) $request->input('pin'));
        if ($pin !== '') {
            if (mb_strlen($pin) < 4) {
                return back()->withInput()->withErrors(['msg' => 'PIN guru minimal 4 karakter.'])->with('toast', ['PIN guru minimal 4 karakter.', 'err']);
            }
            $h->put('guru_pin', $pin);
        }
        $this->done('Pengaturan', 'Pengaturan hotel diperbarui', 'Pengaturan tersimpan.');

        return back();
    }

    public function students(Request $request)
    {
        $list = array_values(array_unique(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $request->input('list'))))));
        if (! $list) {
            return back()->with('toast', ['Daftar siswa tidak boleh kosong.', 'err']);
        }
        DB::transaction(function () use ($list) {
            Student::query()->delete();
            foreach ($list as $i => $name) {
                Student::create(['name' => $name, 'sort' => $i]);
            }
        });
        $this->done('Pengaturan', 'Daftar siswa diperbarui ('.count($list).' siswa)', 'Daftar siswa tersimpan.');

        return back();
    }
}
