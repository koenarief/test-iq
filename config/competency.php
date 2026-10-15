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
    | `disc_weights` = bobot DISC Fit Score per posisi (bobot-disc.pdf), dalam
    | persen dan wajib berjumlah 100. Skor DISC kandidat di Assessment =
    | Σ(graph_X × bobot_X) sesuai departemen posisinya.
    |
    */
    'departments' => [
        'sales' => [
            'label' => 'Sales',
            'sheet' => 'SALES',
            'disc_weights' => ['D' => 30, 'I' => 35, 'S' => 15, 'C' => 20],
        ],
        'marketing' => [
            'label' => 'Marketing',
            'sheet' => 'MARKETING',
            'disc_weights' => ['D' => 25, 'I' => 30, 'S' => 20, 'C' => 25],
        ],
        'procurement' => [
            'label' => 'Procurement',
            'sheet' => 'PROCUREMENT',
            'disc_weights' => ['D' => 25, 'I' => 15, 'S' => 25, 'C' => 35],
        ],
        'content_creator' => [
            'label' => 'Content Creator',
            'sheet' => 'C Creator',
            'disc_weights' => ['D' => 20, 'I' => 35, 'S' => 20, 'C' => 25],
        ],
        'finance' => [
            'label' => 'Finance',
            'sheet' => 'FINANCE',
            'disc_weights' => ['D' => 20, 'I' => 15, 'S' => 25, 'C' => 40],
        ],
        'teknisi_jaringan' => [
            'label' => 'Teknisi Jaringan',
            'sheet' => 'Teknisi Jaringan',
            'disc_weights' => ['D' => 25, 'I' => 15, 'S' => 25, 'C' => 35],
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
