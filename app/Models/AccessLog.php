<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** Relasi ke akun mahasiswa lewat student_ref agar NIM (users.username) tampil di log akses admin. */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_ref', 'student_ref');
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'lihat_hasil' => 'Membuka hasil risiko',
            'daftar_bimbingan' => 'Membuka daftar bimbingan',
            'detail_faktor' => 'Membuka faktor utama',
            'catat_bimbingan' => 'Mencatat hasil bimbingan & tindak lanjut',
            'input_dpa' => 'Memperbarui penilaian keaktifan/tugas akhir',
            'input_cepat' => 'Memperbarui nilai UTS/Tugas/UAS semester berjalan',
            default => $this->action,
        };
    }

    /** Tampilkan NIM mahasiswa jika relasi tersedia, atau fallback ke kode samar bila kosong/umum. */
    public function displayNim(): string
    {
        if ($this->student?->username) {
            return $this->student->username;
        }
        if (! $this->student_ref) {
            return 'Semua bimbingan';
        }

        return self::shortRef($this->student_ref);
    }

    /** Kode samar cadangan untuk kunci internal atau bila akun mahasiswa sudah dihapus. */
    public static function shortRef(?string $ref): string
    {
        if (! $ref) {
            return '-';
        }

        return 'MHS-'.strtoupper(substr($ref, 0, 8));
    }
}
