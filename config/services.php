<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'supabase' => [
        'url' => env('SUPABASE_URL'),
        'service_role' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'bucket' => env('SUPABASE_BUCKET'),
        'bucket_events' => env('SUPABASE_BUCKET_EVENTS', env('SUPABASE_BUCKET')),
        'bucket_works' => env('SUPABASE_BUCKET_WORKS', env('SUPABASE_BUCKET')),
        'bucket_corrected_works' => env('SUPABASE_BUCKET_CORRECTED_WORKS', 'corrected-works-dev'),
        'bucket_official_works' => env('SUPABASE_BUCKET_OFFICIAL_WORKS', 'official-works-dev'),
        'bucket_evaluator_to_coordinator' => env('SUPABASE_BUCKET_EVALUATOR_TO_COORDINATOR', 'evaluator-to-coordinator-dev'),
        'bucket_coordinator_to_participant' => env('SUPABASE_BUCKET_COORDINATOR_TO_PARTICIPANT', 'coordinator-to-participant-dev'),
        'bucket_attachments' => env('SUPABASE_BUCKET_ATTACHMENTS', env('SUPABASE_BUCKET_EVENTS', env('SUPABASE_BUCKET'))),
        'bucket_signatures' => env('SUPABASE_BUCKET_SIGNATURES', 'signatures-dev'),
        'bucket_certificates' => env('SUPABASE_BUCKET_CERTIFICATES', 'certificates-dev'),
    ],

];
