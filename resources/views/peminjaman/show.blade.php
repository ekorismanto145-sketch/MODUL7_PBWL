@extends('layouts.main')

@section('title', 'Detail Peminjaman')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-primary text-white py-3 rounded-top-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-info-circle me-2"></i>Detail Transaksi Peminjaman #{{ $peminjaman->id }}
                </h5>
                <span class="badge bg-light text-primary">IDOR Test Target</span>
            </div>
            <div class="card-body p-4">
                <table class="table table-bordered align-middle">
                    <tr>
                        <th class="bg-light w-25">ID Peminjaman</th>
                        <td>#{{ $peminjaman->id }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Nama Peminjam</th>
                        <td><strong>{{ $peminjaman->anggota->nama ?? '-' }}</strong></td>
                    </tr>
                    <tr>
                        <th class="bg-light">Email Peminjam</th>
                        <td>{{ $peminjaman->anggota->email ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Judul Buku</th>
                        <td>{{ $peminjaman->buku->judul ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Tanggal Pinjam</th>
                        <td>{{ $peminjaman->tanggal_pinjam }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Tanggal Kembali</th>
                        <td>
                            @if($peminjaman->tanggal_kembali)
                                <span class="badge bg-success">Dikembalikan ({{ $peminjaman->tanggal_kembali }})</span>
                            @else
                                <span class="badge bg-warning text-dark">Sedang Dipinjam</span>
                            @endif
                        </td>
                    </tr>
                </table>

                <div class="mt-4">
                    <a href="{{ route('peminjaman.riwayat') }}" class="btn btn-secondary rounded-pill px-4">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Riwayat
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
