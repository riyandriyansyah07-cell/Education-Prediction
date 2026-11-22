<?php

return [
    // Garam HMAC untuk menyamarkan NIM. Wajib diisi di .env dan tidak memakai APP_KEY.
    'pseudonym_salt' => env('PSEUDONYM_SALT', ''),

    // Pilihan mesin risiko:
    // - "gemini": LLM utama (Gemini/DeepSeek) dengan Structured JSON Output + guardrail UHAMKA;
    //             otomatis jatuh ke model aturan PHP bila API key kosong atau gagal.
    // - "http":   Layanan model Python (ml-service: Random Forest/XGBoost + SHAP), jatuh ke
    //             model aturan bila layanan tidak menjawab.
    // - "rule":   Model aturan berbobot di PHP (App\Support\RiskModel).
    'engine' => env('RISK_ENGINE', 'gemini'),
    'service_url' => rtrim((string) env('RISK_SERVICE_URL', ''), '/'),
    'service_token' => env('RISK_SERVICE_TOKEN'),
    'service_timeout' => 3,   // detik, prediksi (NFR-04: halaman hasil maksimal 3 detik)
    'train_timeout' => 120,   // detik, pelatihan

    // Target awal NFR-02. Versi model hanya bisa diaktifkan bila keduanya terpenuhi.
    'target_macro_f1' => 0.70,
    'target_recall_high' => 0.80,

    'import_max_kb' => 2048,
];