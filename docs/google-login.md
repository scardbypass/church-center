# Login Google dan Dashboard Fulltime

**Status:** kode awal Google OAuth, dashboard login-terproteksi, pembuatan gereja, dan penghitung kontak telah ditambahkan. Belum diuji di VPS dan belum mencakup panel Admin Pusat, manajemen kontak, undangan, ataupun WhatsApp sungguhan.

## Setup Google Cloud

1. Buka Google Cloud Console dan buat/ pilih project.
2. Atur OAuth consent screen (nama aplikasi, email kontak, domain resmi, dan test users jika status masih Testing).
3. Buat OAuth Client ID tipe **Web application**.
4. Isi **Authorized redirect URI** dengan `https://YOUR_DOMAIN/auth/google/callback` persis.
5. Di `apps/web/.env`, set:

```dotenv
APP_URL=https://YOUR_DOMAIN
APP_ENV=production
APP_DEBUG=false
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=https://YOUR_DOMAIN/auth/google/callback
```

Jangan masukkan kredensial asli ke GitHub. Google OAuth memerlukan HTTPS di domain produksi.

## Deploy perubahan login

```bash
cd /var/www/church-center
git pull --ff-only
cp -R overlay/web/. apps/web/
cd apps/web
composer require laravel/socialite:^5.0 --no-interaction
php artisan migrate --force
php artisan optimize:clear
```

**Jangan jalankan ulang `php artisan key:generate`** pada server yang sudah memiliki pengguna/sesi.

Kunjungi `https://YOUR_DOMAIN/login`, pilih Google, lalu periksa `/dashboard`. Akun baru memiliki peran `fulltime`; akun Admin Pusat tidak dibuat otomatis. Dari dashboard, buat gereja, kemudian verifikasi data gereja tampil setelah refresh.

## Uji dan keamanan

- Coba OAuth dengan akun Google yang termasuk test users ketika aplikasi masih Testing.
- Periksa bahwa user tidak bisa mengakses `/dashboard` sebelum login.
- Coba buat gereja dengan nama kurang dari 3 karakter; seharusnya ditolak.
- Logout harus mengakhiri sesi.
- Verifikasi gereja lain tidak muncul di dashboard akun yang tidak tergabung.
- Akun dengan email yang sudah ada tetapi belum tertaut Google akan ditolak agar tidak terjadi pengambilalihan akun lewat pencocokan email otomatis.
- Implementasi ini masih perlu tes otomatis/integrasi, validasi token Google sesuai konfigurasi Socialite, dan review keamanan sebelum produksi.

## Pembatasan

Google Contacts import memerlukan OAuth scope/izin tambahan dan belum dibuat. Login Google **tidak** otomatis memberi akses Admin Pusat. Admin Pusat, pengelolaan staf, undangan, lisensi, pembayaran, dan bot akan dibangun sebagai modul berikutnya.
