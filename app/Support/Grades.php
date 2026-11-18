<?php

namespace App\Support;

/**
 * Konversi nilai huruf atau angka 0-100 (maupun bobot 0-4) mengikuti skala nilai UHAMKA:
 * A: 80 - 100 (4,00)
 * B: 68 - 79,99 (3,00)
 * C: 56 - 67,99 (2,00)
 * D: 45 - 55,99 (1,00)
 * E: 0  - 44,99 (0,00)
 */
final class Grades
{
    public const THRESHOLD_A = 80.0;

    public const THRESHOLD_B = 68.0;

    public const THRESHOLD_C = 56.0;

    public const THRESHOLD_D = 45.0;

    /** Bobot nilai huruf utama UHAMKA (beserta dukungan kompatibilitas berkas lama bila ada). */
    private const LETTERS = [
        'A' => 4.0,
        'B' => 3.0,
        'C' => 2.0,
        'D' => 1.0,
        'E' => 0.0,
        // Kompatibilitas transkrip lama bila masih memuat huruf pecahan
        'A-' => 3.7,
        'AB' => 3.5,
        'B+' => 3.3,
        'B-' => 2.7,
        'BC' => 2.5,
        'C+' => 2.3,
        'C-' => 1.7,
        'D+' => 1.3,
    ];

    /** Batas bawah nilai angka 0-100 untuk skala resmi UHAMKA (A, B, C, D, E). */
    private const BANDS = [
        [self::THRESHOLD_A, 4.0, 'A'],
        [self::THRESHOLD_B, 3.0, 'B'],
        [self::THRESHOLD_C, 2.0, 'C'],
        [self::THRESHOLD_D, 1.0, 'D'],
        [0.0, 0.0, 'E'],
    ];

    public static function parseNumber(?string $raw): ?float
    {
        if ($raw === null) {
            return null;
        }
        $v = trim(str_replace(['%', ' '], '', $raw));
        if (! preg_match('/^-?\d+([.,]\d+)?$/', $v)) {
            return null;
        }

        return (float) str_replace(',', '.', $v);
    }

    /**
     * Kembalikan bobot 0-4, atau null bila nilai tidak dikenal.
     *
     * @param  string  $scale  'score100' (huruf A-E atau angka 0-100), 'bobot' (huruf atau bobot 0-4), atau 'huruf' (hanya huruf)
     */
    public static function toPoint(string $raw, string $scale = 'score100'): ?float
    {
        $v = strtoupper(trim(str_replace("\u{2212}", '-', $raw)));
        if ($v === '') {
            return null;
        }
        if (isset(self::LETTERS[$v])) {
            return self::LETTERS[$v];
        }
        if ($scale === 'huruf') {
            return null;
        }

        $n = self::parseNumber($v);
        if ($n === null || $n < 0.0) {
            return null;
        }

        if ($scale === 'bobot') {
            return $n <= 4.0 ? round($n, 2) : null;
        }

        if ($n > 100.0) {
            return null;
        }
        foreach (self::BANDS as [$min, $point]) {
            if ($n >= $min) {
                return $point;
            }
        }

        return null;
    }

    /** Konversi skor 0-100 menjadi nilai huruf UHAMKA (A, B, C, D, E). */
    public static function toLetter(float $score100): string
    {
        foreach (self::BANDS as [$min, , $letter]) {
            if ($score100 >= $min) {
                return $letter;
            }
        }

        return 'E';
    }

    /** Konversi bobot 0-4 menjadi nilai huruf UHAMKA (A, B, C, D, E). */
    public static function pointToLetter(float $point): string
    {
        if ($point >= 3.5) {
            return 'A';
        }
        if ($point >= 2.5) {
            return 'B';
        }
        if ($point >= 1.5) {
            return 'C';
        }
        if ($point >= 0.5) {
            return 'D';
        }

        return 'E';
    }

    /**
     * Konversi nilai huruf atau bobot KHS (0-4) ke ekuivalen nilai tengah skala 0-100
     * ketika nilai angka mentah tidak disimpan pada KHS resmi.
     */
    public static function pointToScore100(float $point, ?string $rawInput = null): float
    {
        if ($rawInput !== null) {
            $parsed = self::parseNumber($rawInput);
            if ($parsed !== null && $parsed > 4.0 && $parsed <= 100.0) {
                return round($parsed, 2);
            }
        }

        return match (self::pointToLetter($point)) {
            'A' => 85.0,
            'B' => 74.0,
            'C' => 62.0,
            'D' => 50.0,
            default => 30.0,
        };
    }
}
