<?php

return [
    'disk' => 'private',
    'max_size_kb' => env('UPLOAD_MAX_SIZE_KB', 10240),
    'allowed_mimes' => [
        'pdf',
        'docx',
        'doc',
        'xlsx',
        'xls',
        'png',
        'jpg',
        'jpeg',
        'csv',
    ],
];
