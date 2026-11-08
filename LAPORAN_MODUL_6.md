# LAPORAN LENGKAP PRAKTIKUM MODUL 6: AUDIT KEAMANAN DAN HARDENING WEB LARAVEL

**Nama Pengembang / Mahasiswa :** Eko Rismanto  
**NIM :** 250441100136  
**Proyek :** Sistem Perpustakaan Digital (perpustakaan_250441100136)  
**Teknologi :** Laravel Framework 11/12, PHP 8.2+, MySQL, Bootstrap 5, Spatie Laravel-Permission  

---

## DAFTAR ISI
1. [Ringkasan Riwayat Commit Git](#1-ringkasan-riwayat-commit-git)
2. [Laporan Soal 1: Menghadirkan Celah Keamanan (A1, A2, A3, A4) & Serangan](#2-laporan-soal-1-menghadirkan-celah-keamanan-a1-a2-a3-a4--serangan)
3. [Laporan Soal 2: Menambal Celah A1, A2, dan A3](#3-laporan-soal-2-menambal-celah-a1-a2-dan-a3)
4. [Laporan Soal 3: Sentralisasi Pengelolaan Buku pada Gate kelola-buku](#4-laporan-soal-3-sentralisasi-pengelolaan-buku-pada-gate-kelola-buku)
5. [Laporan Soal 4: Menutup Celah IDOR (A4) Menggunakan Policy](#5-laporan-soal-4-menutup-celah-idor-a4-menggunakan-policy)
6. [Laporan Soal 5: Penguatan Konfigurasi (Hardening) & Matriks Pengujian Akhir](#6-laporan-soal-5-penguatan-konfigurasi-hardening--matriks-pengujian-akhir)
7. [Panduan & Checklist Tangkapan Layar (Screenshot) Asistensi](#7-panduan--checklist-tangkapan-layar-screenshot-asistensi)

---

## 1. RINGKASAN RIWAYAT COMMIT GIT

Seluruh pekerjaan telah diselesaikan dan dicatat secara granular dalam repositori Git lokal dengan pesan commit formal sesuai ketentuan:

```text
* fe759da (HEAD -> master) Soal 5: Penguatan konfigurasi keamanan dan update seeder
* 711721a Soal 4: Tutup celah A4 dengan PeminjamanPolicy
* 9b12e23 Soal 3: Pusatkan aturan pengelolaan buku pada Gate kelola-buku
* 6c649ec Soal 2: Menambal celah XSS A1, SQL Injection A2, dan CSRF A3
* fdfc2ec Soal 1: Menghadirkan celah A1, A2, A3, dan A4 untuk audit keamanan
* 6303bc0 Initial commit Modul 5
```

---

## 2. LAPORAN SOAL 1: MENGHADIRKAN CELAH KEAMANAN (A1, A2, A3, A4) & SERANGAN

### A. Celah A1 - Cross-Site Scripting (XSS) pada Judul Buku
#### 1. Source Code (Kondisi Rentan)
* **File:** `resources/views/buku/index.blade.php`
```blade
<td>
    <!-- VULNERABLE A1: Tampilan judul buku tanpa escaping HTML -->
    <strong class="text-dark">{!! $item->judul !!}</strong>
</td>
```

#### 2. Skenario & Bukti Serangan
* **Payload Uji:** Judul buku diisi `<script>alert("250441100136")</script>`
* **Langkah:** Login ke sistem, buka URL `http://perpustakaan.test/buku`.
* **Hasil:** Browser merender elemen script secara mentah dan langsung memunculkan dialog alert popup bertuliskan NIM `250441100136`.
* **Tangkapan Layar Diperlukan:** Screenshot halaman `/buku` dengan kotak dialog alert browser `250441100136` terlihat aktif.

#### 3. Penjelasan
* **Penyebab:** Sintaks `{!! !!}` pada template engine Blade menginstruksikan parser untuk mencetak variabel mentah tanpa melewati fungsi filter `htmlspecialchars()`.
* **Dampak:** Penyerang dapat menyuntikkan script berbahaya (*Stored XSS*) yang dapat mencuri *session cookie*, membajak sesi pengguna yang sedang login (*session hijacking*), atau mengalihkan pengguna ke situs *phishing*.

---

### B. Celah A2 - SQL Injection pada Endpoint `/buku-cari`
#### 1. Source Code (Kondisi Rentan)
* **File:** `routes/web.php` & `app/Http/Controllers/BukuController.php`
```php
// routes/web.php
Route::get('/buku-cari', [BukuController::class, 'cari'])->name('buku.cari');

// app/Http/Controllers/BukuController.php
public function cari(Request $request): JsonResponse
{
    $judul = $request->query('judul');

    // VULNERABLE A2: String concatenation langsung tanpa parameter binding
    $buku = DB::select("SELECT * FROM bukus WHERE judul = '$judul'");

    return response()->json($buku);
}
```

#### 2. Skenario & Bukti Serangan
* **Payload Uji:** URL dibuka dengan query string:  
  `http://perpustakaan.test/buku-cari?judul=' OR '1'='1`
* **Hasil:** Query SQL yang dieksekusi menjadi:  
  `SELECT * FROM bukus WHERE judul = '' OR '1'='1'`  
  Karena klausul `OR '1'='1'` bernilai selalu `TRUE`, database mengekstrak seluruh data koleksi buku dan mengembalikannya dalam format JSON lengkap dengan HTTP 200.
* **Tangkapan Layar Diperlukan:** Screenshot browser/Postman dengan URL query payload dan respons JSON seluruh buku.

#### 3. Penjelasan
* **Penyebab:** Input pengguna digabungkan langsung menggunakan perangkaian string ke dalam perintah SQL sebelum dikirimkan ke mesin basis data (*raw concatenation*).
* **Dampak:** Manipulasi sintaks SQL (*SQL Injection*) memungkinkan penyerang melewati autentikasi, mengekstrak data sensitif (*data breach*), hingga merusak data di database.

---

### C. Celah A3 - Cross-Site Request Forgery (CSRF) pada Penghapusan Buku
#### 1. Source Code (Kondisi Rentan) & `jebakan.html`
* **File:** `routes/web.php`
```php
// VULNERABLE A3: Route penghapusan data berada di balik HTTP GET
Route::get('/buku/{buku}/hapus', [BukuController::class, 'destroy'])->name('buku.hapus.get');
```
* **File:** `jebakan.html` (di luar proyek / Desktop)
```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Klaim Saldo & Hadiah Pengguna</title>
</head>
<body>
    <h1>🎉 Selamat! Anda Terpilih Menerima Saldo E-Wallet Rp 500.000</h1>
    <p>Silakan klik tombol klaim di bawah ini untuk mengonfirmasi penerimaan hadiah:</p>
    <a href="http://perpustakaan.test/buku/1/hapus">KLIK DI SINI UNTUK KLAIM SEKARANG</a>
</body>
</html>
```

#### 2. Skenario & Bukti Serangan
* **Langkah:** Login sebagai akun `admin` atau `petugas` pada tab browser 1. Buka berkas `jebakan.html` pada tab browser 2, lalu klik tautan klaim hadiah.
* **Hasil:** Browser otomatis mengirimkan cookie sesi autentikasi admin yang valid ke endpoint GET `/buku/1/hapus`. Buku dengan ID 1 terhapus secara sepihak dari basis data tanpa sepengetahuan admin.
* **Tangkapan Layar Diperlukan:** Screenshot file `jebakan.html` dan tampilan daftar buku setelah tautan diklik di mana buku ID 1 telah hilang.

#### 3. Penjelasan
* **Penyebab:** Operasi *state-changing* / destruktif (menghapus data) menggunakan HTTP method `GET` yang oleh spesifikasi HTTP seharusnya bersifat *safe* dan *idempotent*. Method `GET` tidak dilindungi oleh middleware verifikasi token CSRF Laravel (`VerifyCsrfToken`).
* **Dampak:** Korban dapat dipancing untuk mengeksekusi aksi berbahaya yang tidak diinginkan hanya dengan membuka tautan atau gambar dari domain luar.

---

### D. Celah A4 - Insecure Direct Object Reference (IDOR) pada Transaksi Peminjaman
#### 1. Source Code (Kondisi Rentan)
* **File:** `routes/web.php` & `app/Http/Controllers/PeminjamanController.php`
```php
// routes/web.php
Route::get('/peminjaman/{id}', [PeminjamanController::class, 'show'])->name('peminjaman.show');

// app/Http/Controllers/PeminjamanController.php
public function show($id): View
{
    // VULNERABLE A4: Mengambil data langsung tanpa memeriksa apakah data milik pengguna yang sedang login
    $peminjaman = Peminjaman::with(['anggota', 'buku'])->findOrFail($id);

    return view('peminjaman.show', compact('peminjaman'));
}
```

#### 2. Skenario & Bukti Serangan
* **Langkah:** Login sebagai Siswa A (`siswa@perpustakaan.com`), lalu buka URL detail peminjaman milik Siswa B (ID 2):  
  `http://perpustakaan.test/peminjaman/2`
* **Hasil:** Halaman detail peminjaman milik Siswa B berhasil terbuka dengan status HTTP 200 OK, memperlihatkan identitas nama, email, dan histori pinjam milik Siswa B.
* **Tangkapan Layar Diperlukan:** Screenshot akun Siswa A membuka `/peminjaman/2` dan data Siswa B tampil dengan status 200.

#### 3. Penjelasan
* **Penyebab:** Sistem mengandalkan ID numerik langsung dari parameter URL tanpa memverifikasi relasi kepemilikan data antara objek `Peminjaman` dengan user sesi yang aktif (*missing object-level access control*).
* **Dampak:** Terjadi pelanggaran privasi (*Horizontal Privilege Escalation*) di mana pengguna dapat mengintip dan mengakses data rahasia milik pengguna lain.

---

## 3. LAPORAN SOAL 2: MENAMBAL CELAH A1, A2, DAN A3

### A. Perbaikan Celah A1 (Blade Auto-Escaping)
#### 1. Perbandingan Source Code
* **Sebelum (Rentan):**
  ```blade
  <strong class="text-dark">{!! $item->judul !!}</strong>
  ```
* **Sesudah (Aman):**
  ```blade
  <strong class="text-dark">{{ $item->judul }}</strong>
  ```

#### 2. Hasil & Penjelasan
* **Hasil:** Buku berjudul `<script>alert("250441100136")</script>` dirender secara aman sebagai karakter string literal entitas HTML (`&lt;script&gt;...`), tampil apa adanya di tabel tanpa dialog popup.
* **Alasan Menutup Celah:** Sintaks `{{ }}` pada Blade secara internal memanggil `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')`, sehingga karakter `<` dan `>` tidak lagi diinterpretasikan sebagai tag HTML/JS yang dapat dieksekusi oleh mesin browser.

---

### B. Perbaikan Celah A2 (PDO Parameter Binding)
#### 1. Perbandingan Source Code
* **Sebelum (Rentan):**
  ```php
  $buku = DB::select("SELECT * FROM bukus WHERE judul = '$judul'");
  ```
* **Sesudah (Aman):**
  ```php
  // Menggunakan parameter binding (?) pada PDO
  $buku = DB::select("SELECT * FROM bukus WHERE judul = ?", [$judul]);
  ```

#### 2. Hasil & Penjelasan
* **Hasil:** Mengakses URL `http://perpustakaan.test/buku-cari?judul=' OR '1'='1` menghasilkan array kosong `[]`.
* **Alasan Menutup Celah:** Parameter binding memisahkan antara instruksi SQL terkompilasi dan data input pengguna. String `' OR '1'='1` dianggap sebagai satu kesatuan nilai literal judul buku, bukan operator logika SQL, sehingga serangan injeksi gagal total.

---

### C. Perbaikan Celah A3 (Route DELETE & CSRF Token)
#### 1. Perbandingan Source Code
* **Sebelum (Rentan):**
  ```php
  // routes/web.php
  Route::get('/buku/{buku}/hapus', [BukuController::class, 'destroy'])->name('buku.hapus.get');
  ```
  ```blade
  <!-- resources/views/buku/index.blade.php -->
  <a href="{{ url('/buku/' . $item->id . '/hapus') }}" class="btn btn-outline-danger">Hapus</a>
  ```
* **Sesudah (Aman):**
  ```php
  // routes/web.php (Route GET dihapus total, diganti route DELETE berproteksi middleware web)
  Route::delete('/buku/{buku}', [BukuController::class, 'destroy'])->name('buku.destroy');
  ```
  ```blade
  <!-- resources/views/buku/index.blade.php -->
  <form action="{{ route('buku.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin?')">
      @csrf
      @method('DELETE')
      <button type="submit" class="btn btn-outline-danger">
          <i class="bi bi-trash"></i> Hapus
      </button>
  </form>
  ```

#### 2. Hasil & Penjelasan
* **Hasil:**
  1. Tautan pada `jebakan.html` menghasilkan error **404 Not Found** atau **405 Method Not Allowed**, dan data buku aman (tidak terhapus).
  2. Tombol Hapus pada aplikasi tetap bekerja normal karena dilengkapi `@csrf`.
  3. Mengirimkan request HTTP `DELETE` ke `/buku/{id}` melalui Postman tanpa header `X-CSRF-TOKEN` menghasilkan respons **419 Page Expired**.
* **Alasan Menutup Celah:** Permintaan penghapusan buku kini mewajibkan token rahasia per-sesi CSRF yang tidak dapat dipalsukan atau diakses oleh situs web pihak ketiga dari luar origin domain aplikasi.

---

## 4. LAPORAN SOAL 3: SENTRALISASI PENGELOLAAN BUKU PADA GATE KELOLA-BUKU

### 1. Source Code Implementasi
* **Definisi Gate pada `app/Providers/AppServiceProvider.php`:**
  ```php
  use App\Models\User;
  use Illuminate\Support\Facades\Gate;

  public function boot(): void
  {
      Gate::define('kelola-buku', function (User $user): bool {
          return $user->hasRole(['admin', 'petugas']);
      });
  }
  ```

* **Pemasangan Middleware `can:kelola-buku` pada `routes/web.php`:**
  ```php
  Route::middleware(['auth', 'can:kelola-buku'])->group(function () {
      Route::get('/buku/create', [BukuController::class, 'create'])->name('buku.create');
      Route::post('/buku', [BukuController::class, 'store'])->name('buku.store');
      Route::get('/buku/{buku}/edit', [BukuController::class, 'edit'])->name('buku.edit');
      Route::put('/buku/{buku}', [BukuController::class, 'update'])->name('buku.update');
      Route::delete('/buku/{buku}', [BukuController::class, 'destroy'])->name('buku.destroy');
  });
  ```

* **Penerapan Direktif `@can` pada `resources/views/buku/index.blade.php`:**
  ```blade
  @can('kelola-buku')
  <!-- Formulir Tambah & Ubah Buku -->
  <div class="card border-0 shadow-sm rounded-3 mb-4">
      ...
  </div>
  @endcan

  ...
  @can('kelola-buku')
      <div class="btn-group btn-group-sm">
          <button class="btn btn-outline-warning" onclick="editBuku({{ $item->id }})">Ubah</button>
          <form action="{{ route('buku.destroy', $item->id) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-outline-danger">Hapus</button>
          </form>
      </div>
  @endcan
  ```

### 2. Hasil Pengujian
1. **Akun Siswa:** Tombol formulir Tambah Buku Baru, Ubah, dan Hapus **tidak muncul sama sekali** di halaman `/buku`. Saat siswa mencoba mengakses langsung URL `http://perpustakaan.test/buku/create`, sistem menampilkan error **403 | Forbidden**.
2. **Akun Admin & Petugas:** Memiliki akses penuh ke form tambah, serta tombol aksi ubah dan hapus.

### 3. Penjelasan: Perbedaan Gate dengan Pemeriksaan Role Langsung pada Route
* **Pemeriksaan Role Langsung (`role:admin,petugas`):** Mengikat kode route secara kaku (*tightly coupled*) dengan entitas nama role di database. Jika terdapat perubahan kebijakan bisnis (misal penambahan role 'kepala-perpustakaan'), pengembang harus menelusuri dan mengubah puluhan definisi route satu per satu.
* **Sentralisasi Melalui Gate (`can:kelola-buku`):** Menerapkan prinsip *Single Responsibility* dan abstraksi hak akses (*ability-based*). Route dan View hanya mengetahui kemampuan (*ability*) `kelola-buku`, sedangkan aturan siapa saja yang berhak didefinisikan terpusat di `AppServiceProvider`.

---

## 5. LAPORAN SOAL 4: MENUTUP CELAH IDOR (A4) MENGGUNAKAN POLICY

### 1. Source Code Implementasi
* **File Policy: `app/Policies/PeminjamanPolicy.php`**
  ```php
  namespace App\Policies;

  use App\Models\Peminjaman;
  use App\Models\User;

  class PeminjamanPolicy
  {
      /**
       * Otorisasi melihat transaksi peminjaman (Soal 4).
       */
      public function view(User $user, Peminjaman $peminjaman): bool
      {
          // 1. Role admin dan petugas dapat melihat seluruh peminjaman
          if ($user->hasRole(['admin', 'petugas'])) {
              return true;
          }

          // 2. Siswa hanya boleh melihat transaksi peminjaman miliknya sendiri
          return $peminjaman->anggota && $peminjaman->anggota->email === $user->email;
      }
  }
  ```

* **Penerapan pada Controller: `app/Http/Controllers/PeminjamanController.php`**
  ```php
  use Illuminate\Support\Facades\Gate;

  public function show($id): View
  {
      $peminjaman = Peminjaman::with(['anggota', 'buku'])->findOrFail($id);

      // Menerapkan otorisasi berbasis Policy
      Gate::authorize('view', $peminjaman);

      return view('peminjaman.show', compact('peminjaman'));
  }
  ```

### 2. Hasil Pengujian (3 Akun)
1. **Siswa Pemilik Data (`siswa@perpustakaan.com`):** Membuka `/peminjaman/1` milik dirinya sendiri menghasilkan status **200 OK** dan data detail transaksi tampil lengkap.
2. **Siswa Lain (`siswa2@perpustakaan.com`):** Membuka `/peminjaman/1` milik siswa pertama menerima respons **403 | Forbidden**.
3. **Petugas / Admin (`petugas@perpustakaan.com`):** Membuka `/peminjaman/1` atau transaksi milik siapapun menghasilkan respons **200 OK**.

### 3. Penjelasan: Alasan Pemeriksaan Role Saja Tidak Cukup Mencegah IDOR
Pemeriksaan role sederhana (`hasRole('siswa')`) hanya memeriksa *tipe pengguna* (*Vertical Authorization*), namun gagal membatasi hak akses pada tingkatan baris data spesifik (*Horizontal Authorization*). Tanpa Policy, seorang siswa dapat mengakses data transaksi milik siswa lain hanya dengan mengganti angka ID pada URL. Policy menyediakan *Object-Level Access Control* yang memastikan hubungan kepemilikan antar data terverifikasi secara ketat.

---

## 6. LAPORAN SOAL 5: PENGUATAN KONFIGURASI (HARDENING) & MATRIKS PENGUJIAN AKHIR

### 1. Penguatan Konfigurasi
* **(a) Mode Debug Produksi (`.env`):**
  ```env
  APP_ENV=production
  APP_DEBUG=false
  APP_URL=http://perpustakaan.test
  ```
  Saat terjadi exception/error pada aplikasi, layar browser hanya menampilkan halaman error generik HTTP 500 (*"Server Error"*), tanpa mengekspos jejak kode (*stack trace*), variabel lingkungan, path direktori server, maupun kredensial koneksi database.
* **(b) Pemeriksaan Akses File `.env`:**
  Akses langsung ke URL `http://perpustakaan.test/.env` diblokir oleh web server dengan status **404 Not Found** atau **403 Forbidden**, mencegah kebocoran kunci rahasia aplikasi (`APP_KEY`) dan kredensial database.
* **(c) Pembaruan Seeder Password Akun Admin:**
  Di `database/seeders/RoleAndPermissionSeeder.php`, password bawaan `admin123` diubah menjadi password kuat: `AdminPerpus2026!#Secure`. Percobaan login menggunakan password lama `admin123` ditolak (*These credentials do not match our records*).

---

### 2. Tabel Matriks Pengujian Lengkap (Hasil Aktual)

| No | Role | Skenario | Hasil yang Diharapkan | Hasil Aktual |
|:---:|:---:|:---|:---|:---|
| 1 | Belum login | Membuka `/peminjaman/1` | Dialihkan ke halaman login | Dialihkan ke `/login` (Status 302 Redirect) |
| 2 | Admin | Menambah buku berjudul `<script>alert('250441100136')</script>` | Judul tampil sebagai teks, tanpa popup | Judul tampil berupa teks aman tanpa popup dialog alert |
| 3 | Admin | Membuka `/buku-cari?judul=' OR '1'='1` | Hasil kosong `[]` | Mengembalikan JSON `[]` kosong |
| 4 | Admin | Mengirim DELETE `/buku/{id}` melalui Postman tanpa token | 419 Page Expired | Respons HTTP status 419 Page Expired |
| 5 | Admin | Menghapus buku lewat tombol Hapus | Buku terhapus | Buku terhapus dan hilang dari basis data & tabel |
| 6 | Admin | Membuka tautan `jebakan.html` saat login | Buku tidak terhapus (404 atau 405) | Error 404 / 405, buku tetap tersimpan di basis data |
| 7 | Siswa | Membuka `/buku/create` langsung pada URL | 403 Forbidden, tombol kelola tidak tampil | Halaman 403 Forbidden, tombol form kelola disembunyikan |
| 8 | Siswa | Membuka `/peminjaman/{id}` miliknya sendiri | 200, data tampil | Status 200 OK, detail informasi peminjaman tampil |
| 9 | Siswa | Membuka peminjaman milik siswa lain | 403 Forbidden | Status 403 Forbidden (diblokir oleh Policy) |
| 10 | Petugas | Membuka peminjaman milik siswa mana pun | 200, data tampil | Status 200 OK, data peminjaman siapa pun dapat dibuka |
| 11 | Semua | Membuka halaman error dengan `APP_DEBUG=false` | Pesan error umum tanpa jejak kode | Menampilkan halaman error 500 standar tanpa stack trace |
| 12 | Semua | Membuka `/.env` dan login admin dengan password lama | 404 atau 403; login ditolak | URL `.env` 404/403; login `admin123` gagal/ditolak |

---

## 7. PANDUAN & CHECKLIST TANGKAPAN LAYAR (SCREENSHOT) ASISTENSI

Berikut adalah checklist tangkapan layar yang harus dilampirkan pada laporan resmi asistensi:

* [ ] **SS 1 (Soal 1 - A1):** Tampilan halaman `/buku` dengan kotak dialog popup alert `250441100136`.
* [ ] **SS 2 (Soal 1 - A2):** Browser atau Postman membuka URL `/buku-cari?judul=' OR '1'='1` dengan respons JSON seluruh buku.
* [ ] **SS 3 (Soal 1 - A3):** File `jebakan.html` dibuka dan diklik tautannya, lalu buku ID 1 terhapus dari daftar.
* [ ] **SS 4 (Soal 1 - A4):** Siswa A membuka `/peminjaman/2` milik Siswa B dan melihat data tampil dengan status 200.
* [ ] **SS 5 (Soal 2 - a):** Tampilan daftar buku setelah perbaikan; judul payload script tampil berupa teks biasa tanpa popup.
* [ ] **SS 6 (Soal 2 - b):** URL `/buku-cari?judul=' OR '1'='1` menghasilkan array kosong `[]`.
* [ ] **SS 7 (Soal 2 - c):** Tautan `jebakan.html` menghasilkan 404/405 serta Postman request DELETE tanpa token CSRF menghasilkan HTTP 419.
* [ ] **SS 8 (Soal 3 - Gate):** Halaman 403 Forbidden saat siswa mengakses `/buku/create` dan perbandingan tampilan `/buku` akun siswa vs akun petugas.
* [ ] **SS 9 (Soal 4 - Policy):** 3 tangkapan layar (Siswa A membuka miliknya -> 200, Siswa A membuka milik Siswa B -> 403, Petugas membuka milik siapa pun -> 200).
* [ ] **SS 10 (Soal 5 - Hardening):** Layar error HTTP 500 tanpa stack trace, akses `/.env` menghasilkan 404/403, dan login admin dengan password `admin123` ditolak.
