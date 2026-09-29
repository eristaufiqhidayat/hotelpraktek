<?php

return [
    // PIN awal untuk login guru. Setelah seeding, PIN disimpan di tabel settings
    // dan bisa diganti guru lewat menu Pengaturan.
    'guru_pin' => env('GURU_PIN', 'widuri123'),
];
