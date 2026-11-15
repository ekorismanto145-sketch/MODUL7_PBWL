<?php

namespace App\Http\Controllers;

use App\Models\Buku;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BukuController extends Controller
{
    /**
     * Menampilkan daftar seluruh buku.
     */
    public function index(): View
    {
        $buku = Buku::with('kategori')->get();

        return view('buku.index', compact('buku'));
    }

    /**
     * Pencarian buku aman menggunakan binding parameter PDO (Mencegah SQL Injection).
     */
    public function cari(Request $request): JsonResponse
    {
        $judul = $request->query('judul');

        // SOAL 1 (A2 - Rentan SQL Injection):
        // $buku = DB::select("SELECT * FROM bukus WHERE judul = '$judul'");

        // SOAL 2 (A2 - Perbaikan dengan Parameter Binding):
        $buku = DB::select('SELECT * FROM bukus WHERE judul = ?', [$judul]);

        return response()->json($buku);
    }

    /**
     * Menyimpan buku baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kategori_id' => 'required|exists:kategoris,id',
            'judul' => 'required|string|max:255',
            'penulis' => 'required|string|max:255',
            'tahun_terbit' => 'required|integer',
        ]);

        Buku::create($validated);

        return redirect()->route('buku.index')->with('success', 'Buku berhasil ditambahkan.');
    }

    /**
     * Menampilkan formulir tambah buku.
     */
    public function create(): View
    {
        return view('buku.create');
    }

    /**
     * Menampilkan formulir ubah buku.
     */
    public function edit(Buku $buku): View
    {
        return view('buku.edit', compact('buku'));
    }

    /**
     * Memperbarui data buku.
     */
    public function update(Request $request, Buku $buku): RedirectResponse
    {
        $validated = $request->validate([
            'kategori_id' => 'required|exists:kategoris,id',
            'judul' => 'required|string|max:255',
            'penulis' => 'required|string|max:255',
            'tahun_terbit' => 'required|integer',
        ]);

        $buku->update($validated);

        return redirect()->route('buku.index')->with('success', 'Buku berhasil diperbarui.');
    }

    /**
     * Menghapus buku.
     * Celah A3 dipanggil via GET /buku/{buku}/hapus
     */
    public function destroy($buku): RedirectResponse
    {
        $item = Buku::findOrFail($buku);
        $item->delete();

        return redirect()->route('buku.index')->with('success', 'Buku berhasil dihapus dari sistem.');
    }
}
