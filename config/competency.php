<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Departments
    |--------------------------------------------------------------------------
    |
    | Setiap key harus sama persis dengan kolom `department` yang tersimpan
    | di tabel competency_categories/competency_tests, dan dipakai sebagai
    | mapping nama sheet pada file soal-competency.xlsx saat proses import.
    |
    */
    'departments' => [
        'sales' => [
            'label' => 'Sales',
            'sheet' => 'SALES',
        ],
        'marketing' => [
            'label' => 'Marketing',
            'sheet' => 'MARKETING',
        ],
        'procurement' => [
            'label' => 'Procurement',
            'sheet' => 'PROCUREMENT',
        ],
        'content_creator' => [
            'label' => 'Content Creator',
            'sheet' => 'C Creator',
        ],
        'finance' => [
            'label' => 'Finance',
            'sheet' => 'FINANCE',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Durasi & Jumlah Soal
    |--------------------------------------------------------------------------
    |
    | Semua divisi diberi waktu pengerjaan yang sama. Setiap soal dihitung
    | setara 1 menit, sehingga jumlah soal per subtes (kategori) sebuah
    | divisi menyesuaikan agar totalnya tetap `total_questions`, meskipun
    | jumlah subtes tiap divisi berbeda (bisa 3, 4, atau 5 subtes).
    |
    */
    'duration_minutes' => 30,

    'total_questions' => 30,

];
