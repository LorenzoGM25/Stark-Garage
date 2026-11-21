<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'event',
        'subject_type',
        'subject_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public static function labelFor(string $event): string
    {
        $labels = [
            'auth.login' => 'Login',
            'auth.logout' => 'Logout',
            'auth.login_failed' => 'Login Gagal',
            'auth.lockout' => 'Login Diblokir Sementara',
            'auth.password_reset' => 'Reset Kata Sandi',
            'account.registered' => 'Akun Baru',
            'account.updated' => 'Akun Diubah',
            'account.locked' => 'Akun Dikunci',
            'account.unlocked' => 'Akun Dibuka',
            'account.password_reset' => 'Kata Sandi Direset Owner',
            'account.face_reset' => 'Data Wajah Dihapus',
            'account.force_logout' => 'Paksa Keluar',
            'employee.created' => 'Karyawan Ditambah',
            'employee.updated' => 'Karyawan Diubah',
            'employee.deleted' => 'Karyawan Dihapus',
            'employee.deactivated' => 'Karyawan Dinonaktifkan',
            'profile.updated' => 'Profil Diubah',
            'profile.deleted' => 'Akun Dihapus Sendiri',
            'password.updated' => 'Kata Sandi Diubah',
            'settings.updated' => 'Pengaturan Diubah',
            'face.registered' => 'Wajah Didaftarkan',
            'face.reset' => 'Wajah Direset',
            'product.created' => 'Produk Ditambah',
            'product.updated' => 'Produk Diubah',
            'product.deleted' => 'Produk Dihapus',
            'expense.created' => 'Pengeluaran Dicatat',
            'expense.updated' => 'Pengeluaran Diubah',
            'expense.deleted' => 'Pengeluaran Dihapus',
            'order.completed' => 'Transaksi Selesai',
            'order.voided' => 'Transaksi Dibatalkan',
            'payroll.generated' => 'Payroll Digenerate',
            'payroll.paid' => 'Payroll Dibayar',
            'attendance.clock_in' => 'Clock-in',
            'attendance.clock_out' => 'Clock-out',
            'attendance.manual' => 'Absensi Manual',
            'http.post' => 'Aksi Simpan',
            'http.put' => 'Aksi Ubah',
            'http.patch' => 'Aksi Ubah',
            'http.delete' => 'Aksi Hapus',
        ];

        return $labels[$event] ?? Str::headline(str_replace(['.', '_'], ' ', $event));
    }

    public function getEventLabelAttribute(): string
    {
        return self::labelFor($this->event);
    }

    public function getEventToneAttribute(): string
    {
        return match (true) {
            str_contains($this->event, 'unlocked') => 'success',
            str_contains($this->event, 'failed'), str_contains($this->event, 'lockout'),
            str_contains($this->event, 'locked'), str_contains($this->event, 'deleted'),
            str_contains($this->event, 'deactivated'), str_contains($this->event, 'voided') => 'danger',
            str_contains($this->event, 'login'), str_contains($this->event, 'logout'),
            str_contains($this->event, 'clock_in'), str_contains($this->event, 'clock_out') => 'info',
            str_contains($this->event, 'updated'), str_contains($this->event, 'reset') => 'warning',
            default => 'success',
        };
    }
}
