# Timezone Configuration Fix

## Problem
- Aplikasi menunjukkan waktu UTC bukan waktu lokal Indonesia (WIB)
- Check-in jam 13:05 tercatat sebagai jam 06:04

## Solution
1. **Update timezone di config/app.php**
   ```php
   'timezone' => 'Asia/Jakarta',  // Sebelumnya: 'UTC'
   ```

2. **Clear cache**
   ```bash
   php artisan config:cache
   ```

## Notes
- Database menyimpan timestamp dalam UTC (standard practice)
- PHP/Laravel akan otomatis konversi ke timezone yang dikonfigurasi
- Frontend perlu memformat tanggal dengan memperhatikan timezone

## Timezone Indonesia
- WIB (Western Indonesia Time): Asia/Jakarta (UTC+7)
- WITA (Central Indonesia Time): Asia/Makassar (UTC+8) 
- WIT (Eastern Indonesia Time): Asia/Jayapura (UTC+9)

## Testing
```bash
# Check current server time
php artisan tinker --execute="echo Carbon\Carbon::now()->format('Y-m-d H:i:s')"
```

## Result
✅ Check-in/out sekarang tercatat dengan waktu lokal yang benar
✅ Display di frontend menunjukkan jam yang sesuai (13:11 bukan 06:11)
