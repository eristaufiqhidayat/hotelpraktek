<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public const DEPTS = ['Front Office', 'Housekeeping', 'F&B / Kasir', 'Night Auditor'];
    private const HOME = ['Front Office' => 'fo.index', 'Housekeeping' => 'hk.index', 'F&B / Kasir' => 'pos.index', 'Night Auditor' => 'audit.index'];

    public function show()
    {
        if (session('pms_user')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login', ['students' => Student::orderBy('sort')->get(), 'depts' => self::DEPTS]);
    }

    public function login(Request $request)
    {
        $role = $request->input('role') === 'guru' ? 'guru' : 'siswa';

        if ($role === 'guru') {
            $name = trim((string) $request->input('guru')) ?: 'Guru';
            if (! hash_equals((string) $this->hotel->setting('guru_pin', config('hotel.guru_pin')), (string) $request->input('pin'))) {
                return back()->withInput()->withErrors(['msg' => 'PIN guru salah.']);
            }
            $user = ['role' => 'guru', 'name' => $name];
        } else {
            $student = Student::find($request->input('student'));
            $dept = $request->input('dept');
            if (! $student || ! in_array($dept, self::DEPTS, true)) {
                return back()->withInput()->withErrors(['msg' => 'Pilih nama siswa dan bagian tugas.']);
            }
            $user = ['role' => 'siswa', 'name' => $student->name, 'dept' => $dept];
        }

        $request->session()->regenerate();
        session(['pms_user' => $user]);
        $this->hotel->log('Masuk', $role === 'guru' ? 'Guru masuk' : 'Bertugas sebagai '.$user['dept']);

        return redirect()->route($role === 'guru' ? 'guru' : self::HOME[$user['dept']]);
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['pms_user', 'pos_cart']);
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
