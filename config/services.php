<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Layanan AI LLM (Penjelasan Faktor & Saran Tindak Lanjut - NFR-05)
    |--------------------------------------------------------------------------
    |
    | Penyedia yang didukung: "gemini" atau "deepseek" (atau kosongkan untuk
    | memakai saran statis bawaan RiskModel::suggestions()).
    | Data yang dikirim hanya skor, level, dan faktor numerik tanpa NIM/nama.
    |
    */

    'llm' => [
        'provider' => env('LLM_PROVIDER', ''),
        'api_key' => env('LLM_API_KEY', ''),
        'model' => env('LLM_MODEL', 'gemini-2.5-flash'),
        'base_url' => env('LLM_BASE_URL', 'https://api.deepseek.com'),
        'timeout' => (int) env('LLM_TIMEOUT', 10),
    ],

];
