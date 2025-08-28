# 🚀 Quick Reference - Coffee Shop Attendance System

## 📌 Ringkasan Sistem

**Nama Aplikasi**: Coffee Shop Attendance Management System  
**Framework**: Laravel 9 + Bootstrap 5  
**Database**: MySQL  
**Auth**: Laravel Sanctum (API Token)  
**Lokasi**: `/Applications/XAMPP/htdocs/absence-app`

## 🔑 Demo Login Credentials

| Role | Email | Password | Akses |
|------|-------|----------|--------|
| HR Central | hr@coffee.com | password | Semua cabang |
| Branch Manager | manager@coffee.com | password | Cabang tertentu |
| Pengelola | pengelola@coffee.com | password | Max 3 cabang |
| Employee | employee@coffee.com | password | Self-service only |

## 📁 Struktur Folder Penting

```
absence-app/
├── app/Http/Controllers/Api/  → API Controllers
├── app/Models/               → Eloquent Models  
├── database/migrations/      → Database struktur
├── routes/api.php           → API endpoints
├── resources/views/         → Blade templates
└── public/                  → Web root
```

## 🌐 URL Penting

- **Web Login**: `http://localhost/absence-app/public/login`
- **API Base**: `http://localhost/absence-app/public/api`
- **Health Check**: `/api/health`

## 🔧 Perintah Artisan Penting

```bash
# Migration & Seeding
php artisan migrate:fresh --seed

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Generate key
php artisan key:generate

# List routes
php artisan route:list
```

## 📊 Database Tables Utama

1. **users** - Authentication & roles
2. **employees** - Profile karyawan
3. **attendances** - Record absensi
4. **branches** - Data cabang
5. **work_shifts** - Template shift
6. **leave_requests** - Pengajuan cuti
7. **roles** & **permissions** - RBAC

## 🔌 API Endpoints Utama

### Authentication
```
POST   /api/auth/login      → Login
POST   /api/auth/logout     → Logout  
GET    /api/auth/me         → Profile
```

### Attendance
```
GET    /api/employee/attendance/status     → Status hari ini
POST   /api/employee/attendance/checkin    → Check in/out
GET    /api/employee/attendance/history    → History
```

### Management
```
GET    /api/management/dashboard          → Dashboard data
GET    /api/management/employees          → List karyawan
GET    /api/management/attendance/daily   → Attendance harian
```

## 💡 Fitur Utama

### Untuk Karyawan:
- ✅ Check-in/out dengan GPS & foto
- 📊 Lihat history attendance
- 📅 Request cuti
- 📱 Mobile responsive

### Untuk Manager:
- 👥 Monitor real-time attendance
- 📈 Generate reports
- ✏️ Approve corrections
- 🏢 Manage multiple branches

### Untuk HR:
- 🔧 System configuration
- 👤 Employee management  
- 📊 Company-wide reports
- 🔒 Access control

## 🛡️ Security Features

1. **Token Authentication** - Bearer token di header
2. **GPS Geofencing** - Validasi lokasi
3. **Photo Verification** - Selfie wajib
4. **RBAC** - Role-based permissions
5. **Audit Trail** - Semua aksi dicatat

## ⚡ Performance Tips

1. Enable Laravel caching:
   ```bash
   php artisan config:cache
   php artisan route:cache
   ```

2. Optimize autoloader:
   ```bash
   composer install --optimize-autoloader --no-dev
   ```

3. Use pagination untuk large datasets

## 🐛 Troubleshooting

### GPS tidak berfungsi
- Cek browser permissions
- Pastikan HTTPS (atau localhost)
- Test di mobile browser

### Camera error
- Allow camera permissions
- Gunakan HTTPS
- Cek browser compatibility

### Token expired
- Re-login required
- Token tidak ada expiry by default

### Database connection error
- Cek XAMPP MySQL service
- Verify `.env` settings
- Check database exists

## 📱 Mobile App Checklist (Future)

- [ ] React Native / Flutter
- [ ] Offline mode support  
- [ ] Push notifications
- [ ] Biometric auth
- [ ] Background GPS
- [ ] Photo compression

## 🚦 Status Codes API

- `200` - Success
- `201` - Created
- `401` - Unauthorized (token invalid)
- `403` - Forbidden (no permission)
- `404` - Not found
- `422` - Validation error
- `500` - Server error

## 📝 Development Notes

1. **Timezone**: Set to `Asia/Jakarta` in config
2. **File Upload**: Photos stored in `storage/app/public/photos`
3. **Logs**: Check `storage/logs/laravel.log`
4. **Sessions**: Using file driver
5. **Queue**: Sync driver (no queue worker needed)

## 🔗 Useful Links

- Laravel Docs: https://laravel.com/docs/9.x
- Sanctum Docs: https://laravel.com/docs/9.x/sanctum
- Bootstrap 5: https://getbootstrap.com/docs/5.3
- FontAwesome: https://fontawesome.com/icons

## 📞 Support Contacts

- Technical Issues: IT Team
- Feature Requests: Product Manager
- Bug Reports: Create GitHub issue
- Documentation: Update this file

---
Last Updated: {{ date('Y-m-d') }}  
Version: 1.0.0
