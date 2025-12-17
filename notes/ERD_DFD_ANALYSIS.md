# STUDIFY - Entity Relationship Diagram (ERD) & Data Flow Diagram (DFD)

## 📋 RINGKASAN PROJECT

**Studify** adalah aplikasi manajemen jadwal pembelajaran berbasis Flutter (frontend) dan Laravel (backend) yang memungkinkan pengguna untuk:
- Membuat dan bergabung ke classroom
- Mengelola jadwal kelas dan jadwal pribadi
- Menerima notifikasi dan reminder
- Kolaborasi dengan koordinator kelas

---

## 🗄️ ENTITY RELATIONSHIP DIAGRAM (ERD)

### ERD Overview
```
┌─────────────────────────────────────────────────────────────────────────┐
│                         STUDIFY DATABASE SCHEMA                         │
└─────────────────────────────────────────────────────────────────────────┘

                    ┌──────────────────────┐
                    │       USERS          │
                    ├──────────────────────┤
                    │ PK: id               │
                    │     name             │
                    │     email (unique)   │
                    │     password         │
                    │     email_verified_at│
                    │     remember_token   │
                    │     created_at       │
                    │     updated_at       │
                    │     deleted_at       │
                    └──────────────────────┘
                            │ │ │
          ┌─────────────────┘ │ └─────────────────┐
          │                   │                   │
          │ (owner_id)        │ (user_id)         │ (user_id)
          ▼                   ▼                   ▼
┌────────────────────┐  ┌────────────────┐  ┌─────────────────────┐
│    CLASSROOMS      │  │ CLASSROOM_USER │  │ PERSONAL_SCHEDULES  │
├────────────────────┤  ├────────────────┤  ├─────────────────────┤
│ PK: id             │  │ PK: id         │  │ PK: id              │
│ FK: owner_id       │  │ FK: classroom_id│  │ FK: user_id         │
│     name           │  │ FK: user_id    │  │     title           │
│     unique_code    │  │     created_at │  │     start_time      │
│     description    │  │     updated_at │  │     end_time        │
│     created_at     │  │     deleted_at │  │     location        │
│     updated_at     │  └────────────────┘  │     description     │
│     deleted_at     │         │            │     color           │
└────────────────────┘         │            │     created_at      │
          │                    │            │     updated_at      │
          │                    ▼            │     deleted_at      │
          │           ┌────────────────┐   └─────────────────────┘
          │           │     USERS      │            │
          │           │ (many-to-many) │            │
          │           └────────────────┘            │
          │                                         │
          │ (classroom_id)                          │
          ▼                                         │
┌──────────────────────────┐                       │
│   CLASS_SCHEDULES        │                       │
├──────────────────────────┤                       │
│ PK: id                   │                       │
│ FK: classroom_id         │                       │
│ FK: coordinator_1        │───────────────────────┘
│ FK: coordinator_2        │   (polymorphic)
│     title                │        │
│     start_time           │        │
│     end_time             │        ▼
│     location             │   ┌────────────────────┐
│     lecturer             │   │    REMINDERS       │
│     description          │   ├────────────────────┤
│     color                │   │ PK: id             │
│     created_at           │   │     remindable_type│
│     updated_at           │   │     remindable_id  │
│     deleted_at           │   │     minutes_before │
└──────────────────────────┘   │     status         │
                               │     created_at     │
                               │     updated_at     │
┌──────────────────────┐       │     deleted_at     │
│   DEVICE_TOKENS      │       └────────────────────┘
├──────────────────────┤
│ PK: id               │       ┌────────────────────┐
│ FK: user_id          │       │   NOTIFICATIONS    │
│     token (unique)   │       ├────────────────────┤
│     platform         │       │ PK: id             │
│     created_at       │       │ FK: user_id        │
│     updated_at       │       │     title          │
│     deleted_at       │       │     body           │
└──────────────────────┘       │     data (json)    │
                               │     is_read        │
                               │     created_at     │
                               │     updated_at     │
                               └────────────────────┘
```

---

## 📊 DETAIL TABEL DAN RELASI

### 1. **USERS** (Tabel Utama Pengguna)
**Kolom:**
- `id` (PK): Identifier unik pengguna
- `name`: Nama lengkap pengguna
- `email`: Email (unique) untuk login
- `password`: Password terenkripsi
- `email_verified_at`: Timestamp verifikasi email
- `remember_token`: Token untuk "remember me"
- `created_at`, `updated_at`, `deleted_at`: Timestamp management

**Relasi:**
- **1:N** dengan `classrooms` (sebagai owner) → `owner_id`
- **N:M** dengan `classrooms` (sebagai member) melalui `classroom_user`
- **1:N** dengan `personal_schedules` → `user_id`
- **1:N** dengan `class_schedules` → `coordinator_1`, `coordinator_2`
- **1:N** dengan `device_tokens` → `user_id`
- **1:N** dengan `notifications` → `user_id`

---

### 2. **CLASSROOMS** (Ruang Kelas Virtual)
**Kolom:**
- `id` (PK): Identifier unik classroom
- `owner_id` (FK): Pemilik/pembuat classroom
- `name`: Nama classroom
- `unique_code`: Kode unik untuk join (UNIQUE)
- `description`: Deskripsi classroom
- `created_at`, `updated_at`, `deleted_at`: Timestamp management

**Relasi:**
- **N:1** dengan `users` (owner) → `owner_id`
- **N:M** dengan `users` (members) melalui `classroom_user`
- **1:N** dengan `class_schedules` → `classroom_id`

**Business Rules:**
- Satu user dapat memiliki banyak classrooms (sebagai owner)
- Satu classroom hanya memiliki satu owner
- Owner tidak bisa dihapus jika masih memiliki classroom (ON DELETE RESTRICT)

---

### 3. **CLASSROOM_USER** (Pivot Table untuk Membership)
**Kolom:**
- `id` (PK): Identifier unik
- `classroom_id` (FK): ID classroom
- `user_id` (FK): ID user yang bergabung
- `created_at`, `updated_at`, `deleted_at`: Timestamp management

**Relasi:**
- **N:1** dengan `classrooms` → `classroom_id` (ON DELETE CASCADE)
- **N:1** dengan `users` → `user_id` (ON DELETE CASCADE)

**Business Rules:**
- Soft delete untuk maintain history membership
- Ketika classroom dihapus, semua membership ikut terhapus
- Ketika user dihapus, semua membership ikut terhapus

---

### 4. **CLASS_SCHEDULES** (Jadwal Kelas)
**Kolom:**
- `id` (PK): Identifier unik
- `classroom_id` (FK): ID classroom
- `coordinator_1` (FK): ID user koordinator pertama (nullable)
- `coordinator_2` (FK): ID user koordinator kedua (nullable)
- `title`: Judul jadwal (misal: "Kuliah Matematika")
- `start_time`: Waktu mulai
- `end_time`: Waktu selesai
- `location`: Lokasi (misal: "Ruang A101")
- `lecturer`: Nama dosen/pengajar
- `description`: Deskripsi detail
- `color`: Kode warna untuk UI (default: #3B82F6)
- `created_at`, `updated_at`, `deleted_at`: Timestamp management

**Relasi:**
- **N:1** dengan `classrooms` → `classroom_id` (ON DELETE CASCADE)
- **N:1** dengan `users` (coordinator_1) → `coordinator_1` (ON DELETE SET NULL)
- **N:1** dengan `users` (coordinator_2) → `coordinator_2` (ON DELETE SET NULL)
- **1:N** dengan `reminders` (polymorphic) → `remindable_type`, `remindable_id`

**Business Rules:**
- Jadwal akan terhapus jika classroom dihapus
- Koordinator bersifat optional (nullable)
- Jika koordinator dihapus, field koordinator menjadi null

---

### 5. **PERSONAL_SCHEDULES** (Jadwal Pribadi)
**Kolom:**
- `id` (PK): Identifier unik
- `user_id` (FK): ID user pemilik jadwal
- `title`: Judul jadwal pribadi
- `start_time`: Waktu mulai
- `end_time`: Waktu selesai
- `location`: Lokasi (nullable)
- `description`: Deskripsi (nullable)
- `color`: Kode warna untuk UI (default: #10B981)
- `created_at`, `updated_at`, `deleted_at`: Timestamp management

**Relasi:**
- **N:1** dengan `users` → `user_id` (ON DELETE CASCADE)
- **1:N** dengan `reminders` (polymorphic) → `remindable_type`, `remindable_id`

**Business Rules:**
- Jadwal pribadi hanya dimiliki oleh satu user
- Jadwal akan terhapus jika user dihapus

---

### 6. **REMINDERS** (Pengingat untuk Jadwal)
**Kolom:**
- `id` (PK): Identifier unik
- `remindable_type`: Tipe model (ClassSchedule/PersonalSchedule)
- `remindable_id`: ID dari model yang diingatkan
- `minutes_before_start`: Berapa menit sebelum jadwal (default: 30)
- `status`: Status reminder ('pending', 'sent', 'failed')
- `created_at`, `updated_at`, `deleted_at`: Timestamp management

**Relasi:**
- **N:1** Polymorphic dengan:
  - `class_schedules` → `remindable_type` = 'App\Models\ClassSchedule'
  - `personal_schedules` → `remindable_type` = 'App\Models\PersonalSchedule'

**Business Rules:**
- Satu jadwal (kelas/pribadi) dapat memiliki banyak reminders
- Status default adalah 'pending'
- Polymorphic relationship untuk flexibility

---

### 7. **DEVICE_TOKENS** (Token Push Notification)
**Kolom:**
- `id` (PK): Identifier unik
- `user_id` (FK): ID user pemilik device
- `token`: FCM/APNS token (UNIQUE)
- `platform`: Platform device (iOS/Android)
- `created_at`, `updated_at`, `deleted_at`: Timestamp management

**Relasi:**
- **N:1** dengan `users` → `user_id` (ON DELETE CASCADE)

**Business Rules:**
- Satu user dapat memiliki banyak devices
- Token harus unik untuk setiap device
- Device token dihapus jika user dihapus

---

### 8. **NOTIFICATIONS** (Notifikasi untuk User)
**Kolom:**
- `id` (PK): Identifier unik
- `user_id` (FK): ID user penerima notifikasi
- `title`: Judul notifikasi
- `body`: Isi notifikasi
- `data`: Data tambahan dalam format JSON (nullable)
- `is_read`: Status sudah dibaca atau belum (default: false)
- `created_at`, `updated_at`: Timestamp management

**Relasi:**
- **N:1** dengan `users` → `user_id` (ON DELETE CASCADE)

**Business Rules:**
- Notifikasi dimiliki oleh satu user
- Notifikasi terhapus jika user dihapus
- Data JSON untuk menyimpan payload custom

---

## 🔄 DATA FLOW DIAGRAM (DFD)

### Level 0 - Context Diagram
```
┌────────────────────────────────────────────────────────────────┐
│                     STUDIFY SYSTEM                              │
│                                                                 │
│  ┌──────────┐                                  ┌──────────┐   │
│  │  MOBILE  │ ◄───── API Requests ────────────►│  LARAVEL │   │
│  │  FLUTTER │        (JSON/REST)                │  BACKEND │   │
│  │   APP    │ ◄───── Push Notifications ───────┤    API   │   │
│  └──────────┘                                  └──────────┘   │
│      ▲                                              │          │
│      │                                              ▼          │
│      │                                         ┌──────────┐   │
│      └──────── User Interactions ─────────────┤ DATABASE │   │
│                                                └──────────┘   │
└────────────────────────────────────────────────────────────────┘

External Entities:
┌────────┐           ┌────────┐           ┌────────────┐
│  USER  │           │ ADMIN  │           │  FIREBASE  │
└────────┘           └────────┘           │  (FCM/PN)  │
                                          └────────────┘
```

---

### Level 1 - DFD Utama
```
┌──────────────────────────────────────────────────────────────────────────┐
│                        STUDIFY - DFD LEVEL 1                              │
└──────────────────────────────────────────────────────────────────────────┘

┌─────────┐
│  USER   │
└────┬────┘
     │
     │ 1. Login/Register
     ▼
┌──────────────────────┐         ┌──────────────────┐
│  1.0 AUTHENTICATION  │────────►│  D1: users       │
│      MANAGEMENT      │◄────────│  D2: device_     │
└──────────────────────┘         │      tokens      │
     │                           └──────────────────┘
     │ JWT Token
     ▼
┌──────────────────────┐         ┌──────────────────┐
│  2.0 CLASSROOM       │────────►│  D3: classrooms  │
│      MANAGEMENT      │◄────────│  D4: classroom_  │
└──────────────────────┘         │      user        │
     │                           └──────────────────┘
     │ Classroom Data
     ▼
┌──────────────────────┐         ┌──────────────────┐
│  3.0 SCHEDULE        │────────►│  D5: class_      │
│      MANAGEMENT      │◄────────│      schedules   │
└──────────────────────┘         │  D6: personal_   │
     │                           │      schedules   │
     │ Schedule Data             └──────────────────┘
     ▼
┌──────────────────────┐         ┌──────────────────┐
│  4.0 REMINDER &      │────────►│  D7: reminders   │
│      NOTIFICATION    │◄────────│  D8: notifications│
└──────────────────────┘         └──────────────────┘
     │
     │ Push Notifications
     ▼
┌──────────────────────┐
│  5.0 FIREBASE        │
│      CLOUD           │
│      MESSAGING       │
└──────────────────────┘
```

---

### Level 2 - DFD Detail per Modul

#### 2.1 AUTHENTICATION MANAGEMENT
```
┌─────────┐
│  USER   │
└────┬────┘
     │
     │ Register Data
     ▼
┌──────────────────┐      Validate        ┌──────────────┐
│  1.1 REGISTER    │─────────────────────►│ D1: users    │
│      USER        │◄─────────────────────┤              │
└──────────────────┘      Store User       └──────────────┘
     │
     │ Credentials
     ▼
┌──────────────────┐      Verify          ┌──────────────┐
│  1.2 LOGIN       │─────────────────────►│ D1: users    │
│      USER        │◄─────────────────────┤              │
└──────────────────┘      User Data        └──────────────┘
     │
     │ JWT Token
     ▼
┌──────────────────┐
│  1.3 GENERATE    │
│      JWT TOKEN   │
└──────────────────┘
     │
     │ Device Token
     ▼
┌──────────────────┐      Store           ┌──────────────┐
│  1.4 STORE       │─────────────────────►│ D2: device_  │
│      DEVICE      │                      │     tokens   │
│      TOKEN       │                      └──────────────┘
└──────────────────┘
```

#### 2.2 CLASSROOM MANAGEMENT
```
┌─────────┐
│  USER   │
└────┬────┘
     │
     │ Create Classroom
     ▼
┌──────────────────┐                      ┌──────────────┐
│  2.1 CREATE      │─────────────────────►│ D3: classrooms│
│      CLASSROOM   │      Generate         │              │
└──────────────────┘      Unique Code     └──────────────┘
     │
     │ Unique Code
     ▼
┌──────────────────┐      Validate        ┌──────────────┐
│  2.2 JOIN        │─────────────────────►│ D3: classrooms│
│      CLASSROOM   │      Code             │              │
└──────────────────┘                      └──────────────┘
     │
     │ Membership Data
     ▼
┌──────────────────┐      Store           ┌──────────────┐
│  2.3 ADD MEMBER  │─────────────────────►│ D4: classroom│
│      TO CLASS    │                      │     _user    │
└──────────────────┘                      └──────────────┘
     │
     │ Update Request
     ▼
┌──────────────────┐      Validate        ┌──────────────┐
│  2.4 UPDATE/     │─────────────────────►│ D3: classrooms│
│      DELETE      │      Permission       │              │
│      CLASSROOM   │                      └──────────────┘
└──────────────────┘
     │
     │ Transfer Data
     ▼
┌──────────────────┐      Update          ┌──────────────┐
│  2.5 TRANSFER    │─────────────────────►│ D3: classrooms│
│      OWNERSHIP   │      owner_id         │              │
└──────────────────┘                      └──────────────┘
```

#### 2.3 SCHEDULE MANAGEMENT
```
┌─────────┐
│  USER   │
└────┬────┘
     │
     │ Class Schedule Data
     ▼
┌──────────────────┐      Validate        ┌──────────────┐
│  3.1 CREATE      │─────────────────────►│ D3: classrooms│
│      CLASS       │      Membership       │              │
│      SCHEDULE    │                      └──────────────┘
└──────────────────┘                            │
     │                                          │
     │ Store Schedule                           ▼
     ▼                                   ┌──────────────┐
┌──────────────────┐                    │ D5: class_   │
│  3.2 SAVE        │───────────────────►│     schedules│
│      SCHEDULE    │                    └──────────────┘
└──────────────────┘
     │
     │ Personal Schedule Data
     ▼
┌──────────────────┐                    ┌──────────────┐
│  3.3 CREATE      │───────────────────►│ D6: personal_│
│      PERSONAL    │      Store          │     schedules│
│      SCHEDULE    │                    └──────────────┘
└──────────────────┘
     │
     │ Query Parameters
     ▼
┌──────────────────┐      Fetch          ┌──────────────┐
│  3.4 GET         │◄────────────────────│ D5: class_   │
│      COMBINED    │      Class Schedules│     schedules│
│      SCHEDULES   │                     └──────────────┘
└──────────────────┘                            │
     │                                          ▼
     │ Merge                             ┌──────────────┐
     └──────────────────────────────────►│ D6: personal_│
           Fetch Personal Schedules      │     schedules│
                                         └──────────────┘
```

#### 2.4 REMINDER & NOTIFICATION MANAGEMENT
```
┌─────────┐
│  USER   │
└────┬────┘
     │
     │ Create Reminder
     ▼
┌──────────────────┐      Store           ┌──────────────┐
│  4.1 CREATE      │─────────────────────►│ D7: reminders│
│      REMINDER    │      Polymorphic      │              │
└──────────────────┘      Relation         └──────────────┘
     │
     │ Schedule Check (Cron/Queue)
     ▼
┌──────────────────┐      Query           ┌──────────────┐
│  4.2 CHECK       │◄────────────────────│ D7: reminders│
│      PENDING     │      Pending          │              │
│      REMINDERS   │      Reminders        └──────────────┘
└──────────────────┘
     │
     │ Send Notification
     ▼
┌──────────────────┐                      ┌──────────────┐
│  4.3 SEND PUSH   │─────────────────────►│ D2: device_  │
│      NOTIFICATION│      Get Token        │     tokens   │
└──────────────────┘                      └──────────────┘
     │
     │ FCM Request
     ▼
┌──────────────────┐
│  4.4 FIREBASE    │
│      CLOUD       │
│      MESSAGING   │
└──────────────────┘
     │
     │ Store Notification Log
     ▼
┌──────────────────┐      Store           ┌──────────────┐
│  4.5 LOG         │─────────────────────►│ D8: notifications│
│      NOTIFICATION│                      │              │
└──────────────────┘                      └──────────────┘
     │
     │ Update Status
     ▼
┌──────────────────┐      Update          ┌──────────────┐
│  4.6 UPDATE      │─────────────────────►│ D7: reminders│
│      REMINDER    │      Status to        │              │
│      STATUS      │      'sent'/'failed'  └──────────────┘
└──────────────────┘
```

---

## 📡 API ENDPOINTS & DATA FLOW

### Authentication Flow
```
POST /api/users (Register)
  Input: { name, email, password }
  ──► Validate Input
  ──► Hash Password
  ──► Store in D1: users
  ──► Return: { user, token }

POST /api/auth/login
  Input: { email, password }
  ──► Validate Credentials
  ──► Generate JWT Token
  ──► Return: { token, user }

POST /api/device-tokens
  Input: { token, platform }
  ──► Store in D2: device_tokens
  ──► Return: { success }
```

### Classroom Flow
```
POST /api/classrooms
  Input: { name, description }
  ──► Generate unique_code
  ──► Store in D3: classrooms (owner_id = current user)
  ──► Add owner to D4: classroom_user
  ──► Return: { classroom }

POST /api/classrooms/join
  Input: { unique_code }
  ──► Validate unique_code in D3
  ──► Add user to D4: classroom_user
  ──► Return: { classroom }

GET /api/classrooms
  ──► Fetch from D3 & D4 (owned + member)
  ──► Return: { classrooms[] }
```

### Schedule Flow
```
POST /api/classrooms/{id}/schedules
  Input: { title, start_time, end_time, ... }
  ──► Validate user is member (D4)
  ──► Store in D5: class_schedules
  ──► Return: { schedule }

POST /api/personal-schedules
  Input: { title, start_time, end_time, ... }
  ──► Store in D6: personal_schedules
  ──► Return: { schedule }

GET /api/schedules (Combined)
  ──► Fetch class schedules from D5 (via D4)
  ──► Fetch personal schedules from D6
  ──► Merge & Sort
  ──► Return: { schedules[] }
```

### Reminder & Notification Flow
```
POST /api/reminders
  Input: { remindable_type, remindable_id, minutes_before }
  ──► Store in D7: reminders
  ──► Return: { reminder }

[Background Job/Cron]
  ──► Query D7 for pending reminders
  ──► Check if time to send
  ──► Get user's device_tokens from D2
  ──► Send FCM notification
  ──► Store in D8: notifications
  ──► Update D7 status to 'sent'

GET /api/notifications
  ──► Fetch from D8 (user_id = current user)
  ──► Return: { notifications[] }
```

---

## 🔐 SECURITY & BUSINESS LOGIC

### Authentication & Authorization
- **JWT Token** untuk autentikasi API
- **Middleware auth:api** untuk protected routes
- **Soft Delete** untuk audit trail
- **Password Hashing** menggunakan bcrypt

### Classroom Authorization Rules
```
┌─────────────────────────────────────────────────────────┐
│ ACTION             │ OWNER │ MEMBER │ NON-MEMBER        │
├─────────────────────────────────────────────────────────┤
│ View Classroom     │  ✓    │   ✓    │   ✗               │
│ Update Classroom   │  ✓    │   ✗    │   ✗               │
│ Delete Classroom   │  ✓    │   ✗    │   ✗               │
│ Add Schedule       │  ✓    │   ✓    │   ✗               │
│ Edit Schedule      │  ✓    │   ✓*   │   ✗               │
│ Delete Schedule    │  ✓    │   ✓*   │   ✗               │
│ Remove Member      │  ✓    │   ✗    │   ✗               │
│ Transfer Ownership │  ✓    │   ✗    │   ✗               │
└─────────────────────────────────────────────────────────┘
* Member can edit/delete if they are coordinator
```

### Data Integrity Rules
- **ON DELETE CASCADE**: classroom_user, class_schedules, personal_schedules
- **ON DELETE SET NULL**: coordinator_1, coordinator_2
- **ON DELETE RESTRICT**: classroom owner_id
- **UNIQUE Constraints**: email, classroom.unique_code, device_tokens.token

---

## 📊 KEY METRICS & QUERIES

### Common Query Patterns

#### Get User's All Schedules (Optimized)
```sql
-- Class Schedules
SELECT cs.* 
FROM class_schedules cs
JOIN classrooms c ON cs.classroom_id = c.id
JOIN classroom_user cu ON c.id = cu.classroom_id
WHERE cu.user_id = ? AND cu.deleted_at IS NULL

UNION ALL

-- Personal Schedules
SELECT ps.* 
FROM personal_schedules ps
WHERE ps.user_id = ?

ORDER BY start_time ASC
```

#### Get Pending Reminders to Send
```sql
SELECT r.*, 
       s.start_time,
       s.user_id (for personal) OR cu.user_id (for class)
FROM reminders r
LEFT JOIN personal_schedules ps ON r.remindable_type = 'PersonalSchedule' 
                                AND r.remindable_id = ps.id
LEFT JOIN class_schedules cs ON r.remindable_type = 'ClassSchedule' 
                             AND r.remindable_id = cs.id
WHERE r.status = 'pending'
  AND (ps.start_time - INTERVAL r.minutes_before_start MINUTE) <= NOW()
   OR (cs.start_time - INTERVAL r.minutes_before_start MINUTE) <= NOW()
```

---

## 🚀 DEPLOYMENT & SCALING CONSIDERATIONS

### Database Indexing Strategy
```sql
-- Users
INDEX idx_users_email ON users(email)

-- Classrooms
INDEX idx_classrooms_unique_code ON classrooms(unique_code)
INDEX idx_classrooms_owner_id ON classrooms(owner_id)

-- Classroom User
INDEX idx_classroom_user_user ON classroom_user(user_id)
INDEX idx_classroom_user_classroom ON classroom_user(classroom_id)

-- Schedules
INDEX idx_class_schedules_classroom ON class_schedules(classroom_id)
INDEX idx_class_schedules_time ON class_schedules(start_time, end_time)
INDEX idx_personal_schedules_user ON personal_schedules(user_id)
INDEX idx_personal_schedules_time ON personal_schedules(start_time, end_time)

-- Reminders
INDEX idx_reminders_polymorphic ON reminders(remindable_type, remindable_id)
INDEX idx_reminders_status ON reminders(status)
```

### Performance Optimization
- **Eager Loading** untuk menghindari N+1 queries
- **Pagination** untuk list endpoints
- **Caching** untuk classroom members
- **Queue Jobs** untuk reminder processing & notifications

---

## 📝 KESIMPULAN

Project **Studify** memiliki arsitektur database yang well-structured dengan:

✅ **Relasi yang jelas** antar entity  
✅ **Soft delete** untuk audit trail  
✅ **Polymorphic relationship** untuk flexibility (Reminders)  
✅ **Proper foreign key constraints** untuk data integrity  
✅ **JWT authentication** untuk security  
✅ **Push notification** integration dengan FCM  

**Total Tables**: 11 tables (3 system tables + 8 application tables)

**Entity Types**:
- Core: Users, Classrooms, Schedules
- Supporting: Reminders, Notifications, Device Tokens
- Pivot: classroom_user

---

**Generated**: December 17, 2025  
**Project**: Studify - Schedule Management System  
**Tech Stack**: Laravel 11 + Flutter + PostgreSQL/MySQL + Firebase
