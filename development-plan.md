# Rencana Pengembangan Aplikasi Absensi

## Overview
Aplikasi absensi berbasis web dengan fitur lengkap untuk manajemen kehadiran karyawan.

## Fase Pengembangan (8 Minggu)

### Fase 1: Setup Foundation (Minggu 1)
- ✅ Setup Laravel + database (sudah ada)
- Authentication & role management
- Database migrations untuk: users, employees, attendance, leaves, schedules
- Basic UI layout dengan Tailwind CSS

### Fase 2: Core Features (Minggu 2-4)
- Sistem absensi harian (check-in/out)
- Rekap kehadiran periode
- Perhitungan keterlambatan & pulang cepat
- Pengajuan izin/sakit/cuti dengan approval workflow

### Fase 3: Advanced Features (Minggu 5-6)
- Pengaturan jadwal kerja & shift
- Manajemen hari libur nasional
- Notifikasi otomatis via email
- Export laporan ke Excel/PDF

### Fase 4: Analytics & Integration (Minggu 7-8)
- Dashboard analytics interaktif
- Integrasi payroll (opsional)
- Multi-location support
- Advanced filtering & reporting

## Database Schema (Fase 1)
- **users**: login authentication
- **employees**: data karyawan
- **attendances**: absensi harian
- **leaves**: pengajuan cuti/izin
- **schedules**: jadwal kerja
- **holidays**: hari libur
- **positions**: jabatan karyawan

## Tech Stack
- **Backend**: Laravel 10 ✅ (sudah ada)
- **Database**: MySQL ✅ (sudah ada)
- **Frontend**: Blade + Livewire
- **CSS**: Tailwind CSS
- **Authentication**: Laravel Breeze

## Progress Tracking
- [x] Laravel project setup
- [ ] Database migrations
- [ ] Authentication system
- [ ] Role management (Admin, HR, Employee)
- [ ] Basic UI layout

---
*Estimasi total: 8 minggu*
