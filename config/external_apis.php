<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Text-to-Speech APIs Configuration
    |--------------------------------------------------------------------------
    */
    'tts' => [
        'orpheus' => [
            'streaming' => env('ORPHEUS_STREAMING_API', 'http://13.223.97.36:9090'),
            'male' => env('ORPHEUS_MALE_API', 'http://34.230.29.106:8001'),
            'female' => env('ORPHEUS_FEMALE_API', 'http://34.230.29.106:8000'),
        ],
        'spark' => [
            'base_url' => env('SPARK_TTS_API', 'http://13.223.97.36:9992'),
        ],
        'mira' => [
            'base_url' => env('MIRA_TTS_API', 'http://13.220.246.239:7000'),
        ],
        'yourtts' => [
            'male' => env('YOURTTS_MALE_API', 'http://54.144.193.94:7001'),
            'female' => env('YOURTTS_FEMALE_API', 'http://54.144.193.94:7002'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Speech-to-Text API Configuration
    |--------------------------------------------------------------------------
    */
    'stt' => [
        'base_url' => env('STT_API', 'http://54.144.193.94:7100'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Chat APIs Configuration
    |--------------------------------------------------------------------------
    */
    'chat' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'api_url' => env('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        ],
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'api_url' => env('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Biometria APIs Configuration
    |--------------------------------------------------------------------------
    */
    'biometria' => [
        'base_url' => env('BIOMETRIA_API', 'http://localhost:5000'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Deepfake Detection API Configuration
    |--------------------------------------------------------------------------
    */
    'deepfake' => [
        'base_url' => env('DEEPFAKE_API', 'http://localhost:5003'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Demo Texts for Voice Models
    |--------------------------------------------------------------------------
    */
    'demo_texts' => [
        'default' => 'Olá, eu sou uma voz sintética criada pela Ermis AI. Como posso ajudá-lo hoje?',
        'dub_fem11_feliz' => 'Que dia maravilhoso! Estou tão animada com as possibilidades que a tecnologia nos oferece!',
        'dub_fem12_tristeza' => 'Às vezes a vida nos traz momentos difíceis... mas sempre há esperança no amanhã.',
        'dub_fem17_surpresa' => 'Nossa! Não acredito no que estou vendo! Isso é absolutamente incrível!',
        'dub_fem6_raiva' => 'Isso é inaceitável! Não vou tolerar mais essa situação!',
        'dub_fem7_medo' => 'Tenho medo do que pode acontecer... essa situação me deixa muito nervosa.',
        'dub_masc5_raiva' => 'Estou furioso com essa injustiça! Isso precisa mudar agora mesmo!',
        'dub_masc8_medo' => 'Estou com muito medo... não sei se conseguirei enfrentar isso sozinho.',
        'f_happy' => 'Que alegria poder falar com você! Hoje é um dia perfeito para novas descobertas!',
        'f_angry' => 'Estou realmente irritada com essa situação! Isso não pode continuar assim!',
        'f_sad' => 'Estou me sentindo melancólica hoje... às vezes é assim mesmo.',
        'm_happy' => 'Estou muito feliz em poder ajudar! Vamos fazer coisas incríveis juntos!',
        'm_angry' => 'Isso me deixa muito irritado! Precisa ser resolvido imediatamente!',
        'm_sad' => 'Estou com o coração pesado... alguns dias são mais difíceis que outros.',
    ],
];
