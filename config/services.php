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

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'api_key' => env('GOOGLE_CSE_API_KEY'),
        'search_engine_id' => env('GOOGLE_CSE_CX'),
    ],

    'tavily' => [
        'api_key' => env('TAVILY_API_KEY'),
    ],

    'custom_openai' => [
        'enabled' => env('CUSTOM_OPENAI_ENABLED', false),
        'base_uri' => env('CUSTOM_OPENAI_BASE_URI', 'https://api.openai.com/v1'),
        'api_key' => env('CUSTOM_OPENAI_API_KEY'),
        'model' => env('CUSTOM_OPENAI_MODEL', 'gpt-4'),
        'strict_response' => env('CUSTOM_OPENAI_STRICT_RESPONSE', false),
        'timeout' => env('CUSTOM_OPENAI_TIMEOUT', 30),
    ],

    // Instagram araştırmasına özel operatif ayarlar (TR odaklı)
    'instagram_research' => [
        // TR öncelikli güvenilir kaynak alan adları
        'preferred_domains_tr' => [
            'sproutsocial.com',
            'blog.hootsuite.com',
            'buffer.com',
            'socialpilot.co',
            'planable.io',
            'later.com',
            'podcastle.ai',
            'insights.vaizle.com',
            'smartinsights.com',
        ],
        // Sorgu ve ziyaret limitleri
        'max_queries' => env('IG_RESEARCH_MAX_QUERIES', 3),
        'max_visits' => env('IG_RESEARCH_MAX_VISITS', 2),
        // Gecikmeler (ms)
        'inter_search_delay_ms' => env('IG_RESEARCH_INTER_SEARCH_DELAY_MS', 500),
        'inter_visit_delay_ms' => env('IG_RESEARCH_INTER_VISIT_DELAY_MS', 300),
        // Bölgesel/locale parametreleri
        'lang' => env('IG_RESEARCH_LANG', 'tr'),
        'gl' => env('IG_RESEARCH_GL', 'TR'),
        'lr' => env('IG_RESEARCH_LR', 'lang_tr'),
    ],

];
