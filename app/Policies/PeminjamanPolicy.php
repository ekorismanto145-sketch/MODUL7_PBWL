<?php

namespace App\Policies;

use App\Models\Peminjaman;
use App\Models\User;

class PeminjamanPolicy
{
    /**
     * Soal 4: Admin dan petugas boleh melihat semua peminjaman,
     * sedangkan siswa hanya boleh melihat peminjaman miliknya sendiri.
     */
    public function view(User $user, Peminjaman $peminjaman): bool
    {
        // 1. Role admin dan petugas dapat melihat seluruh peminjaman
        if ($user->hasRole(['admin', 'petugas'])) {
            return true;
        }

        // 2. Siswa hanya boleh melihat peminjaman miliknya sendiri
        // Cocokkan data anggota pada transaksi dengan email user yang sedang login
        return $peminjaman->anggota && $peminjaman->anggota->email === $user->email;
    }
}
