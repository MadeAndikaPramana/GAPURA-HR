<?php
// config/google.php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Drive Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Google Drive API integration
    | Used for storing certificate files and documents
    |
    */

    'drive' => [
        /*
        |--------------------------------------------------------------------------
        | Service Account Credentials
        |--------------------------------------------------------------------------
        |
        | Path to the Google Service Account JSON credentials file
        | Download this from Google Cloud Console > IAM & Admin > Service Accounts
        |
        */
        'credentials_path' => env('GOOGLE_DRIVE_CREDENTIALS_PATH', storage_path('app/google/service-account.json')),

        /*
        |--------------------------------------------------------------------------
        | Root Folder ID
        |--------------------------------------------------------------------------
        |
        | The Google Drive folder ID where all certificate files will be stored
        | Create a folder in Google Drive and share it with your service account
        | Then copy the folder ID from the URL
        |
        */
        'root_folder_id' => env('GOOGLE_DRIVE_ROOT_FOLDER_ID'),

        /*
        |--------------------------------------------------------------------------
        | File Upload Settings
        |--------------------------------------------------------------------------
        */
        'max_file_size' => env('GOOGLE_DRIVE_MAX_FILE_SIZE', 10485760), // 10MB in bytes
        'allowed_mime_types' => [
            'application/pdf',
            'image/jpeg',
            'image/jpg',
            'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ],
        'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],

        /*
        |--------------------------------------------------------------------------
        | Folder Structure Settings
        |--------------------------------------------------------------------------
        */
        'folder_structure' => [
            'certificates' => 'Certificates',
            'background_checks' => 'Background-Checks',
            'training_records' => 'Training-Records',
            'archived' => 'Archived'
        ],

        /*
        |--------------------------------------------------------------------------
        | File Naming Convention
        |--------------------------------------------------------------------------
        |
        | Template for naming files in Google Drive
        | Variables: {employee_id}, {cert_type}, {version}, {issue_date}, {expiry_date}
        |
        */
        'filename_template' => 'v{version}_{issue_date}_{expiry_date}',
        'date_format' => 'Y-m-d',

        /*
        |--------------------------------------------------------------------------
        | Sharing Settings
        |--------------------------------------------------------------------------
        */
        'default_sharing' => [
            'type' => 'anyone', // 'user', 'group', 'domain', 'anyone'
            'role' => 'reader'  // 'reader', 'writer', 'commenter'
        ],

        /*
        |--------------------------------------------------------------------------
        | Cache Settings
        |--------------------------------------------------------------------------
        */
        'cache' => [
            'folder_structure' => [
                'enabled' => true,
                'ttl' => 3600 // 1 hour
            ],
            'file_metadata' => [
                'enabled' => true,
                'ttl' => 1800 // 30 minutes
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Error Handling
        |--------------------------------------------------------------------------
        */
        'retry' => [
            'max_attempts' => 3,
            'delay' => 1000, // milliseconds
            'exponential_backoff' => true
        ],

        /*
        |--------------------------------------------------------------------------
        | Logging
        |--------------------------------------------------------------------------
        */
        'logging' => [
            'enabled' => env('GOOGLE_DRIVE_LOGGING', true),
            'level' => env('GOOGLE_DRIVE_LOG_LEVEL', 'info'), // debug, info, warning, error
            'log_uploads' => true,
            'log_downloads' => true,
            'log_deletions' => true
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Google API Common Settings
    |--------------------------------------------------------------------------
    */
    'api' => [
        'application_name' => env('GOOGLE_APPLICATION_NAME', config('app.name')),
        'timeout' => env('GOOGLE_API_TIMEOUT', 60), // seconds
        'user_agent' => env('GOOGLE_USER_AGENT', config('app.name') . '/1.0'),
    ]
];
