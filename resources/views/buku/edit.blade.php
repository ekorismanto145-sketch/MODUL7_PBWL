@extends('layouts.main')

@section('title', 'Ubah Data Buku')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-warning text-dark py-3 rounded-top-3">
                <h5 class="card-title mb-0 fw-semibold">
                    <i class="bi bi-pencil-square me-2"></i>Ubah Data Koleksi Buku
                </h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('buku.update', $buku->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="kategori_id" class="form-label fw-semibold">Kategori</label>
                        <select name="kategori_id" id="kategori_id" class="form-select @error('kategori_id') is-invalid @enderror" required>
                            @foreach(\App\Models\Kategori::all() as $kat)
                                <option value="{{ $kat->id }}" {{ old('kategori_id', $buku->kategori_id) == $kat->id ? 'selected' : '' }}>
                                    {{ $kat->nama }}
                                </option>
                            @endforeach
                        </select>
                        @error('kategori_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="judul" class="form-label fw-semibold">Judul Buku</label>
                        <input type="text" name="judul" id="judul" class="form-control @error('judul') is-invalid @enderror" value="{{ old('judul', $buku->judul) }}" required>
                        @error('judul')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="penulis" class="form-label fw-semibold">Penulis</label>
                        <input type="text" name="penulis" id="penulis" class="form-control @error('penulis') is-invalid @enderror" value="{{ old('penulis', $buku->penulis) }}" required>
                        @error('penulis')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="tahun_terbit" class="form-label fw-semibold">Tahun Terbit</label>
                        <input type="number" name="tahun_terbit" id="tahun_terbit" class="form-control @error('tahun_terbit') is-invalid @enderror" value="{{ old('tahun_terbit', $buku->tahun_terbit) }}" required>
                        @error('tahun_terbit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-warning rounded-pill px-4">
                            <i class="bi bi-save me-1"></i> Perbarui Buku
                        </button>
                        <a href="{{ route('buku.index') }}" class="btn btn-secondary rounded-pill px-3">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
