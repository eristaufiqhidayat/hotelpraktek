<?php

namespace Database\Seeders;

use App\Services\DemoData;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Isi hotel praktik dengan data contoh (kamar, tamu, riwayat 2 minggu, log kelas). */
    public function run(): void
    {
        app(DemoData::class)->run();
    }
}
