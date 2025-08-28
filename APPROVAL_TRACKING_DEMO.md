# 📊 Enhanced Approval Tracking System

## 🔍 **Cara Tracking Approval Leave Request**

Dengan implementasi baru ini, sekarang Anda bisa tahu:

### 1. **Sudah Diapprove Siapa**
```javascript
// Dari API response untuk setiap leave request:
{
  "approval_timeline": [
    {
      "level": 1,
      "title": "Store Supervisor",
      "status": "approved", // approved, pending, not_reached
      "approved_by": "Budi Santoso", // nama approver
      "approved_at": "2025-08-28T10:30:00Z", // waktu approval
      "notes": "Approved with condition", // catatan approver
      "is_current": false, // apakah step ini sedang aktif
      "color": "success" // success, warning, muted
    },
    {
      "level": 2,
      "title": "Branch Manager",
      "status": "pending", // sedang menunggu
      "approved_by": null,
      "approved_at": null,
      "is_current": true, // 👈 STEP AKTIF SEKARANG
      "color": "warning"
    }
    // ... dst
  ]
}
```

### 2. **Perlu Diapprove Siapa**
```javascript
// Next approver info:
{
  "next_approver": {
    "id": 123,
    "name": "Siti Manager", // nama yang harus approve selanjutnya
    "role": "branch_manager", // role approver
    "action_needed": true // apakah butuh action dari orang ini
  }
}
```

### 3. **Progress Approval**
```javascript
{
  "approval_progress": 50, // 0-100% progress
  "current_step": "Waiting for Branch Manager approval" // deskripsi step saat ini
}
```

## 🎨 **Visual Timeline di UI**

Ketika klik "View Details" pada leave request, akan tampil:

```
📋 Approval Timeline

✅ Store Supervisor          ✅ APPROVED
   └─ Approved by: Budi Santoso
   └─ Date: 28 Aug 2025, 10:30 AM
   └─ Notes: Looks good

⏳ Branch Manager            🟡 PENDING (Current Step)
   └─ Waiting for: Siti Manager
   └─ Expected action: Manager approval required

⏸️ HR Central               ⏸️ NOT REACHED
   └─ Will be assigned after manager approval

⏸️ Final Approval           ⏸️ NOT REACHED
   └─ Automatic after HR approval
```

## 📊 **Status Tracking Table**

Di tabel utama leave requests, tambahan kolom:

| Request | Type | Status | **Next Action** | **Progress** |
|---------|------|--------|----------------|--------------|
| #001 | Annual | Pending | ⏳ Waiting for Budi (Pengelola) | ████░░░░ 25% |
| #002 | Sick | Pending | ⏳ Waiting for Siti (Manager) | ████████░░ 50% |
| #003 | Personal | Approved | ✅ Fully Approved | ████████████ 100% |

## 🔔 **Notification System**

Untuk approver yang sedang bertugas:

```
🔔 You have 3 pending leave requests to review:
   • Annual Leave - John Doe (2 days ago)
   • Sick Leave - Jane Smith (1 day ago)  
   • Personal Leave - Bob Wilson (3 hours ago)
   
   [Review Now] [View All]
```

## 🛠️ **Cara Test Tracking System**

### Test dengan Data Dummy

1. **Submit Leave Request**
   ```bash
   # Status akan: pending
   # Next approver: Pengelola for your branch
   ```

2. **Pengelola Approve** (Manual via database)
   ```sql
   UPDATE leave_requests SET 
     status = 'approved_by_pengelola',
     pengelola_approved_by = 1, 
     pengelola_approved_at = NOW(),
     pengelola_notes = 'Approved by supervisor'
   WHERE id = 1;
   ```

3. **Check Timeline**
   ```javascript
   // API call ke /api/employee/leave
   // Akan show: Level 1 approved, Level 2 pending
   ```

### Test Functions

```javascript
// Test di browser console saat di halaman leaves:
const leave = leavePage.state.leaves[0]; // ambil leave pertama

// Check timeline
console.log('Timeline:', leave.approval_timeline);

// Check next approver  
console.log('Next approver:', leave.next_approver);

// Check progress
console.log('Progress:', leave.approval_progress + '%');
```

## 💡 **Benefits of This System**

1. **Transparency** - Employee tau persis dimana requestnya stuck
2. **Accountability** - Jelas siapa yang bertanggung jawab approve
3. **Efficiency** - Approver tau urgent requests yang perlu action
4. **Audit Trail** - Complete history siapa approve kapan dengan notes
5. **User Experience** - Visual progress bar dan clear status

## 🎯 **Real World Usage**

### Untuk Employee:
- "Leave request saya sudah sampai mana ya?"
- "Siapa yang belum approve?"
- "Kenapa lama banget prosesnya?"

### Untuk Approver:
- "Ada berapa request yang pending ke saya?"
- "Request mana yang paling urgent?"
- "History approval apa yang sudah saya lakukan?"

### Untuk HR/Management:
- "Bottleneck approval ada dimana?"
- "Approver mana yang paling lambat?"
- "Berapa average approval time per level?"

---

*Dengan system ini, approval leave request menjadi transparan dan efficient!* 🚀
