<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Private Storage
    |--------------------------------------------------------------------------
    |
    | Disk and base folder used for every applicant-uploaded file and every
    | generated document. This location must never be web accessible.
    |
    */

    'storage' => [
        'disk' => env('PPDB_STORAGE_DISK', 'private'),
        'root_folder' => 'ppdb',
    ],

    /*
    |--------------------------------------------------------------------------
    | Access Code
    |--------------------------------------------------------------------------
    |
    | Alphabet deliberately excludes 0, O, 1 and I so codes stay readable when
    | printed on the registration receipt.
    |
    */

    'access_code' => [
        'length' => 8,
        'alphabet' => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',
    ],

    /*
    |--------------------------------------------------------------------------
    | Registration Number
    |--------------------------------------------------------------------------
    |
    | Format: YYGGNNNNNN (2 digit year + 2 digit wave code + 6 digit sequence).
    |
    */

    'registration_number' => [
        'length' => 10,
        'sequence_digits' => 6,
    ],

    /*
    |--------------------------------------------------------------------------
    | Applicant Session
    |--------------------------------------------------------------------------
    |
    | Session keys used by the applicant guard. Applicants never get a User
    | record; they authenticate with registration number + access code.
    |
    */

    'applicant_session' => [
        'key' => 'ppdb_applicant_registration_id',
        'token_key' => 'ppdb_applicant_token',
        'plain_code_key' => 'ppdb_applicant_plain_code',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limit' => [
        'status_check' => env('PPDB_RATE_LIMIT_STATUS', '5,1'),
        'admin_login' => env('PPDB_RATE_LIMIT_ADMIN_LOGIN', '5,1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    |
    | Hard ceiling applied on top of each document type's own max_size_kb, plus
    | the extension/mime allow-list the server enforces.
    |
    */

    'uploads' => [
        // Serverless hosts cap the request body well below this: a Vercel
        // function rejects anything over 4.5 MB before PHP sees it, and the
        // applicant would get an opaque 413 instead of a validation message.
        // Lower the ceiling there through the environment rather than letting
        // an upload the form advertised as valid fail at the edge.
        'absolute_max_size_kb' => (int) env('PPDB_UPLOAD_MAX_KB', 5120),
        'default_max_size_kb' => 2048,
        'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
        'mime_map' => [
            'pdf' => ['application/pdf'],
            'jpg' => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png' => ['image/png'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Registration Wizard
    |--------------------------------------------------------------------------
    */

    'wizard' => [
        'steps' => [
            'pendaftaran' => 'Data Pendaftaran',
            'biodata' => 'Biodata',
            'alamat' => 'Alamat',
            'orang-tua' => 'Orang Tua/Wali',
            'asal-sekolah' => 'Asal Sekolah',
            'program' => 'Program',
            'berkas' => 'Berkas',
            'review' => 'Review',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reference Data
    |--------------------------------------------------------------------------
    |
    | Small closed vocabularies that are not worth a database table.
    |
    */

    'reference' => [
        'genders' => [
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
        ],
        'religions' => [
            'Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya',
        ],
        'relationships' => [
            'father' => 'Ayah',
            'mother' => 'Ibu',
            'guardian' => 'Wali',
        ],
        'school_types' => [
            'SMP' => 'SMP',
            'MTs' => 'MTs',
            'Paket B' => 'Paket B',
            'Lainnya' => 'Lainnya',
        ],
        'school_statuses' => [
            'Negeri' => 'Negeri',
            'Swasta' => 'Swasta',
        ],
        'educations' => [
            'Tidak Sekolah', 'SD/Sederajat', 'SMP/Sederajat', 'SMA/Sederajat',
            'D1', 'D2', 'D3', 'D4/S1', 'S2', 'S3',
        ],
        'income_ranges' => [
            'Kurang dari Rp1.000.000',
            'Rp1.000.000 - Rp2.000.000',
            'Rp2.000.000 - Rp3.000.000',
            'Rp3.000.000 - Rp5.000.000',
            'Lebih dari Rp5.000.000',
            'Tidak Berpenghasilan',
        ],
    ],

];
