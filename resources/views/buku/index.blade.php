@extends('layouts.main')

@section('title', 'Daftar Koleksi Buku')

@section('content')
<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h2 class="fw-bold mb-1 text-primary">
            <i class="bi bi-collection-fill me-2"></i>Daftar Koleksi Buku
        </h2>
        <p class="text-muted mb-0">Kelola dan lihat koleksi buku secara langsung</p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        @can('kelola-buku')
            <a href="{{ route('buku.create') }}" class="btn btn-primary btn-sm rounded-pill shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Tambah Buku Baru
            </a>
        @endcan
    </div>
</div>

<!-- Container Notifikasi Error & Berhasil -->
<div id="alertContainer"></div>

<!-- Tabel Data Buku (Dirender Langsung Menggunakan Blade Sesuai Spesifikasi Soal Modul 6) -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-semibold text-secondary">
            <i class="bi bi-table me-2"></i>Koleksi Terdaftar
        </h5>
        <span class="badge bg-secondary rounded-pill">{{ count($buku) }} Buku</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary text-uppercase fs-7">
                    <tr>
                        <th class="ps-4" style="width: 20%;">Kategori</th>
                        <th style="width: 30%;">Judul</th>
                        <th style="width: 20%;">Penulis</th>
                        <th class="text-center" style="width: 10%;">Tahun</th>
                        <th class="text-center pe-4" style="width: 20%;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tabelBukuBody">
                    @forelse($buku as $item)
                        <tr>
                            <td class="ps-4">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 fw-medium">
                                    <i class="bi bi-bookmark-fill me-1"></i>{{ $item->kategori->nama ?? 'Umum' }}
                                </span>
                            </td>
                            <td>
                                <!-- {{-- SOAL 1 (A1 - Celah XSS tanpa escaping): --}}
                                {{-- <strong class="text-dark">{!! $item->judul !!}</strong> --}} -->

                                {{-- SOAL 2 (A1 - Perbaikan XSS dengan Blade escaping bawaan): --}}
                                <strong class="text-dark">{{ $item->judul }}</strong>
                            </td>
                            <td class="text-secondary">{{ $item->penulis ?? '-' }}</td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $item->tahun_terbit ?? '-' }}</span>
                            </td>
                            <td class="text-center pe-4">
                                <div class="d-flex justify-content-center align-items-center gap-1">
                                    {{-- Hak Siswa: Tombol Pinjam --}}
                                    @can('mengajukan peminjaman buku')
                                        <form method="POST" action="{{ route('peminjaman.ajukan') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="buku_id" value="{{ $item->id }}">
                                            <button type="submit" class="btn btn-outline-success btn-sm rounded-pill px-3" title="Pinjam Buku Ini">
                                                <i class="bi bi-journal-plus me-1"></i> Pinjam
                                            </button>
                                        </form>
                                    @endcan

                                    {{-- Soal 3: Tombol Ubah dan Hapus hanya tampil jika lolos Gate kelola-buku --}}
                                    @can('kelola-buku')
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('buku.edit', $item->id) }}" class="btn btn-outline-warning" title="Ubah Data">
                                                <i class="bi bi-pencil-square"></i> Ubah
                                            </a>

                                            {{-- SOAL 1 (A3 - Celah CSRF hapus via link GET): --}}
                                            {{-- <a href="{{ url('/buku/' . $item->id . '/hapus') }}" class="btn btn-outline-danger" title="Hapus Data (Celah A3)">
                                                <i class="bi bi-trash"></i> Hapus
                                            </a> --}}

                                            {{-- SOAL 2 (A3 - Perbaikan CSRF via form DELETE berproteksi @csrf): --}}
                                            <form action="{{ route('buku.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus buku ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="Hapus Data">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
                                            </form>
                                        </div>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                Belum ada koleksi data buku.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
