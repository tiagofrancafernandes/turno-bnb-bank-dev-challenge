<?php

return [
    'tests' => [
        'upload_real_file' => filter_var(env('UPLOAD_REAL_FILE_ON_TESTS', false), FILTER_VALIDATE_BOOL),
        'check' => [
            //
        ],
    ],
];
