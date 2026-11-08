# PANDUAN LENGKAP PENGERJAAN TUGAS MODUL 6: AUDIT & PENGUATAN KEAMANAN WEB LARAVEL

Dokumen ini memuat panduan lengkap bertahap (*step-by-step*), penjelasan alur kerja setiap soal, potongan source code implementasi, panduan pengujian serangan dan verifikasi, format commit git, hingga checklist tangkapan layar (*screenshot*) untuk laporan akhir.

---

## DAFTAR ISI
1. [Ringkasan Alur & Target Praktikum](#ringkasan-alur--target-praktikum)
2. [Persiapan Awal & Aturan Git Commit](#persiapan-awal--aturan-git-commit)
3. [Langkah Pengerjaan Soal 1: Menghadirkan 4 Celah Keamanan (Vulnerability Injection)](#soal-1-menghadirkan-4-celah-keamanan)
4. [Langkah Pengerjaan Soal 2: Menambal Celah A1, A2, dan A3](#soal-2-menambal-celah-a1-a2-dan-a3)
5. [Langkah Pengerjaan Soal 3: Sentralisasi Otorisasi Buku Menggunakan Gate](#soal-3-sentralisasi-otorisasi-buku-menggunakan-gate)
6. [Langkah Pengerjaan Soal 4: Menutup Celah A4 (IDOR) Menggunakan Policy](#soal-4-menutup-celah-a4-idor-menggunakan-policy)
7. [Langkah Pengerjaan Soal 5: Penguatan Konfigurasi & Tabel Pengujian](#soal-5-penguatan-konfigurasi--tabel-pengujian)
8. [Panduan Pengambilan Screenshot & Format Laporan Resmi](#panduan-pengambilan-screenshot--format-laporan-resmi)

---

## RINGKASAN ALUR & TARGET PRAKTIKUM

Praktikum ini berfokus pada pemahaman kerentanan web standar OWASP Top 10 serta mekanisme pertahanan bawaan Laravel:
* **A1 - Cross-Site Scripting (XSS):** Memahami bahaya unescaped output `{!! $var !!}` vs Blade auto-escaping `{{ $var }}`.
* **A2 - SQL Injection (SQLi):** Memahami bahaya penggabungan langsung (*string concatenation*) pada raw SQL vs PDO Prepared Statements (*parameter binding*).
* **A3 - Cross-Site Request Forgery (CSRF):** Memahami bahaya aksi destruktif via method `GET` dan pentingnya verifikasi token CSRF pada method `DELETE`/`POST`.
* **A4 - Insecure Direct Object Reference (IDOR):** Memahami celah otorisasi saat mengambil objek berdasarkan parameter URL tanpa memeriksa hak kepemilikan data (*Horizontal Privilege Escalation*) dan solusinya menggunakan Laravel Policy.
* **Hardening & Verification:** Menutup mode debug produksi (`APP_DEBUG=false`), mencegah pembacaan file sensitif (`.env`), memperkuat kredensial seeder, dan uji matriks fungsional.

---

## PERSIAPAN AWAL & ATURAN GIT COMMIT

> [!IMPORTANT]
> Proyek dikumpulkan melalui GitHub dengan pesan commit yang diawali nama soal secara berurutan:
> 1. `git commit -m "Soal 1: Menghadirkan celah A1, A2, A3, dan A4 untuk audit keamanan"`
> 2. `git commit -m "Soal 2: Menambal celah XSS A1, SQL Injection A2, dan CSRF A3"`
> 3. `git commit -m "Soal 3: Menerapkan Gate kelola-buku pada AppServiceProvider dan Blade"`
> 4. `git commit -m "Soal 4: Menerapkan PeminjamanPolicy untuk mencegah IDOR pada peminjaman"`
> 5. `git commit -m "Soal 5: Penguatan konfigurasi keamanan dan update seeder"`

Sebelum memulai pengerjaan, pastikan file `.env` sudah masuk ke `.gitignore` agar kredensial lokal tidak terunggah ke repositori publik.

---

## SOAL 1: MENGHADIRKAN 4 CELAH KEAMANAN

### Alur Kerja & Penjelasan
Pada tahap ini, kita dengan sengaja memasukkan 4 jenis celah keamanan ke dalam proyek untuk membuktikan kerentanan sebelum dilakukan perbaikan. **Semua celah dibuat sekaligus dan di-commit dalam satu commit pertama.**

---

### Langkah 1.1: Menghadirkan Celah A1 (Cross-Site Scripting / XSS)
* **Penyebab Celah:** Penggunaan sintaks Blade unescaped `{!! $judul !!}` merender konten mentah HTML/JavaScript langsung ke browser tanpa filter `htmlspecialchars()`.
* **Implementasi Kode:**
  Pada tampilan daftar buku (`resources/views/buku/index.blade.php`), pastikan judul buku dicetak dengan sintaks unescaped:
  ```blade
  {!! $buku->judul !!}
  ```
  *(Jika tabel menggunakan fetch/JS, suntikkan judul langsung menggunakan `innerHTML` tanpa proses escape)*.
* **Pengujian Serangan A1:**
  1. Masukkan atau ubah judul buku dengan payload XSS berisi NIM:  
     `<script>alert('250441100136')</script>`
  2. Buka halaman daftar buku (`/buku`).
  3. **Hasil:** Browser mengeksekusi script dan memunculkan jendela dialog popup bertuliskan `250441100136`. Ambil tangkapan layar URL dan popup tersebut.

---

### Langkah 1.2: Menghadirkan Celah A2 (SQL Injection)
* **Penyebab Celah:** Penggabungan string nilai input pengguna langsung ke dalam string query SQL (`WHERE judul = '$judul'`) tanpa sanitasi atau parameter binding.
* **Implementasi Kode:**
  1. Di `routes/web.php` (di dalam grup `middleware('auth')`):
     ```php
     Route::get('/buku-cari', [BukuController::class, 'cari'])->name('buku.cari');
     ```
  2. Di `app/Http/Controllers/BukuController.php`:
     ```php
     public function cari(\Illuminate\Http\Request $request)
     {
         $judul = $request->query('judul');
         // CELAH SQL INJECTION: String concatenation langsung
         $buku = \Illuminate\Support\Facades\DB::select("SELECT * FROM bukus WHERE judul = '$judul'");
         return response()->json($buku);
     }
     ```
* **Pengujian Serangan A2:**
  1. Login ke aplikasi.
  2. Buka URL dengan payload SQLi:  
     `http://perpustakaan.test/buku-cari?judul=' OR '1'='1`
  3. **Hasil:** Query SQL menjadi bernilai selalu benar (*always true*), sehingga endpoint merespons dengan menampilkan seluruh koleksi buku dalam bentuk JSON. Ambil tangkapan layar URL dan JSON output.

---

### Langkah 1.3: Menghadirkan Celah A3 (CSRF pada Penghapusan Data)
* **Penyebab Celah:** Aksi penghapusan data buku (operasi berstatus destruktif / *state-changing*) ditempatkan pada HTTP method `GET` tanpa proteksi token CSRF.
* **Implementasi Kode:**
  1. Di `routes/web.php`:
     ```php
     Route::get('/buku/{buku}/hapus', [BukuController::class, 'destroy'])->name('buku.hapus.get');
     ```
  2. Pastikan method `destroy` di `BukuController.php` menghapus buku:
     ```php
     public function destroy($buku)
     {
         $item = \App\Models\Buku::findOrFail($buku);
         $item->delete();
         return redirect()->route('buku.index')->with('success', 'Buku berhasil dihapus.');
     }
     ```
  3. Buat berkas `jebakan.html` di luar direktori proyek web (misal di Desktop):
     ```html
     <!DOCTYPE html>
     <html lang="id">
     <head>
         <meta charset="UTF-8">
         <title>Klaim Saldo Gratis!</title>
     </head>
     <body>
         <h1>Selamat! Anda Memperoleh Reward Spesial</h1>
         <p>Silakan klik tombol klaim di bawah ini:</p>
         <!-- Tautan jebakan CSRF menghapus buku ID 1 secara diam-diam -->
         <a href="http://perpustakaan.test/buku/1/hapus">KLIK DI SINI UNTUK KLAIM HADIAH</a>
     </body>
     </html>
     ```
* **Pengujian Serangan A3:**
  1. Login ke aplikasi sebagai `admin` atau `petugas`.
  2. Buka berkas `jebakan.html` melalui browser di tab lain.
  3. Klik link klaim hadiah tersebut.
  4. **Hasil:** Browser mengarahkan ke `/buku/1/hapus` dengan cookie autentikasi yang sah milik admin/petugas, sehingga buku dengan ID 1 terhapus dari daftar. Ambil tangkapan layar.

---

### Langkah 1.4: Menghadirkan Celah A4 (Insecure Direct Object Reference / IDOR)
* **Penyebab Celah:** Mengambil dan menampilkan data sensitif transaksi langsung berdasarkan ID dari URL tanpa memeriksa relasi kepemilikan user yang sedang login (*Horizontal Privilege Escalation*).
* **Implementasi Kode:**
  1. Di `routes/web.php`:
     ```php
     Route::get('/peminjaman/{id}', [PeminjamanController::class, 'show'])->name('peminjaman.show');
     ```
  2. Di `app/Http/Controllers/PeminjamanController.php`:
     ```php
     public function show($id)
     {
         // CELAH IDOR: Mengambil data tanpa memeriksa apakah peminjaman ini milik user yang sedang login
         $peminjaman = \App\Models\Peminjaman::with(['anggota', 'buku'])->findOrFail($id);
         return view('peminjaman.show', compact('peminjaman'));
     }
     ```
  3. Di `resources/views/peminjaman/show.blade.php`:
     Tampilkan informasi transaksi seperti Nama Peminjam, Judul Buku, Tanggal Pinjam, dan Status.
* **Pengujian Serangan A4:**
  1. Login sebagai akun **Siswa A**.
  2. Buka data transaksi peminjaman milik **Siswa B** via URL:  
     `http://perpustakaan.test/peminjaman/2`
  3. **Hasil:** Siswa A dapat melihat data pribadi dan histori peminjaman milik Siswa B dengan status HTTP 200 OK. Ambil tangkapan layar URL dan datanya.

---

### Perintah Git Commit Soal 1
```bash
git add .
git commit -m "Soal 1: Menghadirkan celah A1, A2, A3, dan A4 untuk audit keamanan"
```

---

## SOAL 2: MENAMBAL CELAH A1, A2, DAN A3

### Alur Kerja & Penjelasan
Melakukan remediasi terhadap 3 kerentanan pertama menggunakan mekanisme keamanan standar framework Laravel.

---

### Langkah 2.1: Menambal A1 (XSS Remediation)
* **Penjelasan:** Menggunakan sintaks Blade escaping `{{ $buku->judul }}` yang secara otomatis menjalankan fungsi `htmlspecialchars()` bawaan PHP dengan flag `ENT_QUOTES` untuk mengonversi karakter HTML berbahaya seperti `<`, `>`, `"`, `'` menjadi entitas HTML yang aman.
* **Perbaikan Kode:**
  Ubah sintaks pemanggilan judul di view menjadi:
  ```blade
  {{ $buku->judul }}
  ```
* **Hasil Pengujian:** Judul dengan payload `<script>alert('250441100136')</script>` tampil utuh sebagai teks string biasa pada tabel tanpa memicu eksekusi JavaScript dialog alert.

---

### Langkah 2.2: Menambal A2 (Prepared Statement / Parameter Binding)
* **Penjelasan:** Mengganti penggabungan string dengan PDO Parameter Binding (`?`). Nilai input diperlakukan sebagai literal data oleh database engine, bukan bagian dari struktur sintaks perintah SQL.
* **Perbaikan Kode:**
  Di `BukuController.php`:
  ```php
  public function cari(\Illuminate\Http\Request $request)
  {
      $judul = $request->query('judul');
      
      // AMAN: Menggunakan binding parameter PDO
      $buku = \Illuminate\Support\Facades\DB::select(
          "SELECT * FROM bukus WHERE judul = ?",
          [$judul]
      );
      
      return response()->json($buku);
  }
  ```
* **Hasil Pengujian:** URL `http://perpustakaan.test/buku-cari?judul=' OR '1'='1` menghasilkan array kosong `[]` karena tidak ada buku yang memiliki judul sama persis dengan string payload tersebut.

---

### Langkah 2.3: Menambal A3 (CSRF Protection & HTTP Method DELETE)
* **Penjelasan:** Menghapus route GET penghapusan buku dan menggantinya dengan route DELETE yang dilindungi token `@csrf`. Token CSRF hanya diketahui oleh aplikasi di domain resmi dan diverifikasi oleh middleware `VerifyCsrfToken` Laravel.
* **Perbaikan Kode:**
  1. Hapus route `Route::get('/buku/{buku}/hapus', ...)` dari `routes/web.php`.
  2. Gunakan route HTTP method DELETE:
     ```php
     Route::delete('/buku/{buku}', [BukuController::class, 'destroy'])->name('buku.destroy');
     ```
  3. Pada form tombol hapus di view buku:
     ```blade
     <form action="{{ route('buku.destroy', $buku->id) }}" method="POST" class="d-inline">
         @csrf
         @method('DELETE')
         <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin?')">
             Hapus
         </button>
     </form>
     ```
* **Hasil Pengujian:**
  1. Mengklik link pada `jebakan.html` menghasilkan error **404 Not Found** atau **405 Method Not Allowed**, dan data buku tidak terhapus.
  2. Tombol Hapus pada antarmuka web aplikasi tetap berjalan sukses.
  3. Mengirimkan request HTTP `DELETE` ke `/buku/{id}` melalui Postman tanpa token CSRF menghasilkan error **419 Page Expired**.

---

### Perintah Git Commit Soal 2
```bash
git add .
git commit -m "Soal 2: Menambal celah XSS A1, SQL Injection A2, dan CSRF A3"
```

---

## SOAL 3: SENTRALISASI OTORISASI BUKU MENGGUNAKAN GATE

### Alur Kerja & Penjelasan
Otorisasi terpusat menggunakan Laravel Gate memberikan abstraksi logika hak akses yang terpisah dari kontroler dan route. Jika sewaktu-waktu aturan bisnis berubah (misalnya menambahkan role supervisor), perubahan cukup dilakukan di satu tempat (Gate definition).

---

### Langkah 3.1: Mendefinisikan Gate pada `AppServiceProvider`
* **File:** `app/Providers/AppServiceProvider.php`
* **Implementasi Kode:**
  Tambahkan definisi Gate `kelola-buku` di method `boot()`:
  ```php
  namespace App\Providers;

  use App\Models\User;
  use Illuminate\Support\Facades\Gate;
  use Illuminate\Support\ServiceProvider;

  class AppServiceProvider extends ServiceProvider
  {
      public function boot(): void
      {
          Gate::define('kelola-buku', function (User $user): bool {
              return $user->hasRole(['admin', 'petugas']);
          });
      }
  }
  ```

---

### Langkah 3.2: Menerapkan Middleware `can:kelola-buku` pada Route
* **File:** `routes/web.php`
* **Implementasi Kode:**
  Gantikan middleware otorisasi sebelumnya dengan middleware bawaan Laravel `can:kelola-buku`:
  ```php
  Route::middleware(['auth', 'can:kelola-buku'])->group(function () {
      Route::get('/buku/create', [BukuController::class, 'create'])->name('buku.create');
      Route::post('/buku', [BukuController::class, 'store'])->name('buku.store');
      Route::get('/buku/{buku}/edit', [BukuController::class, 'edit'])->name('buku.edit');
      Route::put('/buku/{buku}', [BukuController::class, 'update'])->name('buku.update');
      Route::delete('/buku/{buku}', [BukuController::class, 'destroy'])->name('buku.destroy');
  });
  ```

---

### Langkah 3.3: Memasang Direktif `@can` pada Tampilan Blade
* **File:** `resources/views/buku/index.blade.php`
* **Implementasi Kode:**
  Bungkus elemen aksi manajemen buku menggunakan `@can`:
  ```blade
  @can('kelola-buku')
      <a href="{{ route('buku.create') }}" class="btn btn-primary mb-3">
          <i class="bi bi-plus-circle"></i> Tambah Buku Baru
      </a>
  @endcan

  ...
  @can('kelola-buku')
      <a href="{{ route('buku.edit', $buku->id) }}" class="btn btn-warning btn-sm">Ubah</a>
      <form action="{{ route('buku.destroy', $buku->id) }}" method="POST" class="d-inline">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
      </form>
  @endcan
  ```

---

### Hasil Pengujian Soal 3
1. **Admin / Petugas:** Tampil form/tombol Tambah, Ubah, Hapus dan dapat menjalankan seluruh operasi.
2. **Siswa:**
   * Tombol Tambah, Ubah, dan Hapus tidak dirender pada tampilan daftar buku.
   * Saat siswa mengetikkan URL langsung `http://perpustakaan.test/buku/create`, sistem menolak dengan layar **403 | Forbidden**.

---

### Perintah Git Commit Soal 3
```bash
git add .
git commit -m "Soal 3: Menerapkan Gate kelola-buku pada AppServiceProvider dan Blade"
```

---

## SOAL 4: MENUTUP CELAH A4 (IDOR) MENGGUNAKAN POLICY

### Alur Kerja & Penjelasan
Pemeriksaan role sederhana (`hasRole('siswa')`) tidak dapat membedakan data milik siswa mana yang sedang diakses. Dibutuhkan *Object-level authorization* (Policy) untuk memastikan bahwa seorang siswa hanya memiliki hak akses (*view*) terhadap data peminjaman miliknya sendiri.

---

### Langkah 4.1: Membuat File `PeminjamanPolicy`
* Jalankan perintah:
  ```bash
  php artisan make:policy PeminjamanPolicy --model=Peminjaman
  ```
* Buka `app/Policies/PeminjamanPolicy.php` dan buat aturan pada method `view`:
  ```php
  namespace App\Policies;

  use App\Models\Peminjaman;
  use App\Models\User;

  class PeminjamanPolicy
  {
      /**
       * Menentukan apakah user boleh melihat detail peminjaman tertentu.
       */
      public function view(User $user, Peminjaman $peminjaman): bool
      {
          // Admin dan Petugas berhak melihat transaksi milik siapapun
          if ($user->hasRole(['admin', 'petugas'])) {
              return true;
          }

          // Siswa hanya boleh melihat transaksi miliknya sendiri
          return $peminjaman->anggota && $peminjaman->anggota->email === $user->email;
      }
  }
  ```

---

### Langkah 4.2: Memanggil Otorisasi Policy pada Controller
* **File:** `app/Http/Controllers/PeminjamanController.php`
* **Implementasi Kode:**
  Ganti pemeriksaan manual Modul 5 pada method `show` dengan `Gate::authorize()`:
  ```php
  use Illuminate\Support\Facades\Gate;

  public function show($id)
  {
      $peminjaman = Peminjaman::with(['anggota', 'buku'])->findOrFail($id);

      // Memeriksa hak akses menggunakan PeminjamanPolicy@view
      Gate::authorize('view', $peminjaman);

      return view('peminjaman.show', compact('peminjaman'));
  }
  ```

---

### Hasil Pengujian Soal 4
1. **Siswa Pemilik Data:** Membuka URL peminjaman miliknya berhasil dengan status **200 OK** dan data tampil lengkap.
2. **Siswa Lain:** Membuka URL peminjaman milik siswa lain mendapatkan penolakan **403 | Forbidden**.
3. **Petugas / Admin:** Membuka URL peminjaman milik siswa manapun tetap mendapatkan status **200 OK**.

---

### Perintah Git Commit Soal 4
```bash
git add .
git commit -m "Soal 4: Menerapkan PeminjamanPolicy untuk mencegah IDOR pada peminjaman"
```

---

## SOAL 5: PENGUATAN KONFIGURASI & TABEL PENGUJIAN

### Alur Kerja & Penjelasan
Langkah terakhir adalah menutup celah kebocoran informasi (*information disclosure*) pada konfigurasi lingkungan produksi (*hardening*).

---

### Langkah 5.1: Prosedur Penguatan Konfigurasi
1. **(a) Mode Debug Produksi:**
   * Di file `.env`, atur:
     ```env
     APP_ENV=production
     APP_DEBUG=false
     ```
   * Buat route sementara di `routes/web.php`:
     ```php
     Route::get('/uji-error', function () {
         throw new \RuntimeException('Uji Coba Debug Dinonaktifkan');
     });
     ```
   * Buka URL `http://perpustakaan.test/uji-error` di browser. Pastikan halaman menampilkan layar error generik 500 **tanpa rincian kode / file path / query SQL**.
   * Ambil screenshot, lalu hapus kembali route sementara tersebut.

2. **(b) Pemeriksaan Akses File `.env`:**
   * Akses URL: `http://perpustakaan.test/.env` melalui browser.
   * Pastikan web server merespons dengan **404 Not Found** atau **403 Forbidden**, memastikan file environment tidak dapat diunduh publik.

3. **(c) Pembaruan Password Seeder Admin:**
   * Buka `database/seeders/RoleAndPermissionSeeder.php`.
   * Ganti password default `admin123` menjadi password yang kuat, misalnya: `PerpusAdmin2026!#Secure`.
   * Jalankan ulang seeder:
     ```bash
     php artisan db:seed --class=RoleAndPermissionSeeder
     ```
   * Coba login menggunakan password lama `admin123`. Pastikan sistem menolak login tersebut.

---

### Langkah 5.2: Tabel Matriks Pengujian Lengkap

Berikut adalah tabel matriks pengujian seluruh skenario sesuai spesifikasi soal:

| No | Role | Skenario | Hasil yang Diharapkan | Hasil Aktual |
|:---:|:---:|:---|:---|:---|
| 1 | Belum login | Membuka `/peminjaman/1` | Dialihkan ke halaman login | Dialihkan ke `/login` (302 Redirect) |
| 2 | Admin | Menambah buku berjudul `<script>alert('250441100136')</script>` | Judul tampil sebagai teks, tanpa popup | Tampil sebagai teks biasa, tanpa popup dialog alert |
| 3 | Admin | Membuka `/buku-cari?judul=' OR '1'='1` | Hasil kosong `[]` | Mengembalikan respons JSON `[]` |
| 4 | Admin | Mengirim DELETE `/buku/{id}` melalui Postman tanpa token | 419 Page Expired | Mendapatkan status HTTP 419 Page Expired |
| 5 | Admin | Menghapus buku lewat tombol Hapus | Buku terhapus | Buku terhapus dari basis data dan daftar tabel |
| 6 | Admin | Membuka tautan `jebakan.html` saat login | Buku tidak terhapus (404 atau 405) | Respons 404 / 405, data buku tidak terhapus |
| 7 | Siswa | Membuka `/buku/create` langsung pada URL | 403 Forbidden, tombol kelola tidak tampil | Status 403 Forbidden, tombol aksi kelola disembunyikan |
| 8 | Siswa | Membuka `/peminjaman/{id}` miliknya sendiri | 200, data tampil | Status 200 OK, detail data peminjaman tampil |
| 9 | Siswa | Membuka peminjaman milik siswa lain | 403 Forbidden | Status 403 Forbidden (terblokir oleh Policy) |
| 10 | Petugas | Membuka peminjaman milik siswa mana pun | 200, data tampil | Status 200 OK, detail data peminjaman tampil |
| 11 | Semua | Membuka halaman error dengan `APP_DEBUG=false` | Pesan error umum tanpa jejak kode | Menampilkan halaman error 500 generik tanpa jejak kode |
| 12 | Semua | Membuka `/.env` dan login admin dengan password lama | 404 atau 403; login ditolak | URL `.env` menghasilkan 404/403; login `admin123` ditolak |

---

### Perintah Git Commit Soal 5
```bash
git add .
git commit -m "Soal 5: Penguatan konfigurasi keamanan dan update seeder"
```

---

## PANDUAN PENGAMBILAN SCREENSHOT & FORMAT LAPORAN RESMI

Dalam menyusun laporan resmi, cantumkan format 3 komponen untuk setiap nomor:
1. **Source Code:** Potongan kode yang Anda tulis sendiri sesuai spesifikasi soal.
2. **Hasil:** Tangkapan layar yang memperlihatkan **URL**, **status respons**, dan **hasil serangan / perbaikan**.
3. **Penjelasan:** Uraian teknis mengenai penyebab terjadinya celah dan mengapa perbaikan tersebut berhasil menutup kerentanan.

### Checklist Tangkapan Layar:
- [ ] **SS 1 (Soal 1 - A1):** Halaman `/buku` dengan popup dialog alert berisi NIM `250441100136`.
- [ ] **SS 2 (Soal 1 - A2):** Browser atau Postman membuka `/buku-cari?judul=' OR '1'='1` menampilkan seluruh data buku dalam JSON.
- [ ] **SS 3 (Soal 1 - A3):** File `jebakan.html` diklik dan buku terhapus melalui route GET.
- [ ] **SS 4 (Soal 1 - A4):** Siswa A membuka `/peminjaman/2` milik Siswa B dan data tampil (status 200).
- [ ] **SS 5 (Soal 2 - a):** Judul payload script tampil aman sebagai teks tanpa popup.
- [ ] **SS 6 (Soal 2 - b):** URL `/buku-cari?judul=' OR '1'='1` menghasilkan `[]`.
- [ ] **SS 7 (Soal 2 - c):** Tautan `jebakan.html` menghasilkan 404/405 dan Postman DELETE tanpa token menghasilkan 419.
- [ ] **SS 8 (Soal 3 - Gate):** Halaman 403 Forbidden pada siswa dan tampilan daftar buku siswa (tanpa tombol aksi) vs petugas (ada tombol aksi).
- [ ] **SS 9 (Soal 4 - Policy):** 3 screenshot akun (Siswa pemilik -> 200, Siswa lain -> 403, Petugas -> 200).
- [ ] **SS 10 (Soal 5 - Hardening):** Halaman error tanpa jejak kode, akses `/.env` terblokir (404/403), dan login admin password lama ditolak.
