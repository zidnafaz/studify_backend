# Fitur 3 - Manajemen Jadwal Kelas (Class Schedule CRUD)

## 📋 Overview
Feature untuk PJ (Penanggung Jawab/Owner) kelas melakukan CRUD (Create, Read, Update, Delete) jadwal kuliah kelas.

---

## 🎯 Requirements

### Functional Requirements:
- ✅ **Create** - Owner dapat menambah jadwal kelas baru
- ✅ **Read** - Member dapat melihat semua jadwal kelas
- ✅ **Update** - Owner/Coordinator dapat mengubah jadwal
- ✅ **Delete** - Owner/Coordinator dapat menghapus jadwal

### Authorization Rules:
1. **View (List/Detail)** - User harus member kelas ATAU owner
2. **Create** - Hanya Owner yang bisa create jadwal
3. **Update** - Owner ATAU Coordinator 1/2 yang bisa update
4. **Delete** - Owner ATAU Coordinator 1/2 yang bisa delete

---

## 📁 File Structure

```
studify_backend/
├── app/
│   ├── Http/Controllers/
│   │   └── ClassScheduleController.php        # CRUD controller
│   ├── Models/
│   │   ├── ClassSchedule.php                  # Model (sudah ada)
│   │   ├── Classroom.php                      # Model (sudah ada)
│   │   └── User.php                           # Model (sudah ada)
│   ├── Policies/
│   │   └── ClassSchedulePolicy.php            # Authorization policy
│   └── Providers/
│       └── AppServiceProvider.php             # Policy registration
├── database/
│   ├── factories/
│   │   ├── ClassScheduleFactory.php           # Test factory
│   │   └── ClassroomFactory.php               # Test factory
│   └── migrations/
│       ├── 2024_11_14_000001_create_classrooms_table.php
│       ├── 2024_11_14_000002_create_classroom_user_table.php
│       └── 2024_11_14_000003_create_class_schedules_table.php
├── routes/
│   └── api.php                                # API routes
└── tests/
    └── Feature/
        └── ClassScheduleTest.php              # PHPUnit tests (22 test cases)
```

---

## 🗄️ Database Schema

### Table: `classrooms`
```sql
- id (bigint, PK)
- owner_id (bigint, FK -> users.id) [onDelete: restrict]
- name (varchar)
- unique_code (varchar, unique)
- description (text, nullable)
- timestamps
- deleted_at (soft delete)
```

### Table: `classroom_user` (Pivot)
```sql
- id (bigint, PK)
- classroom_id (bigint, FK -> classrooms.id) [onDelete: cascade]
- user_id (bigint, FK -> users.id) [onDelete: cascade]
- timestamps
- deleted_at (soft delete)
```

### Table: `class_schedules`
```sql
- id (bigint, PK)
- classroom_id (bigint, FK -> classrooms.id)
- coordinator_1 (bigint, FK -> users.id, nullable)
- coordinator_2 (bigint, FK -> users.id, nullable)
- title (varchar)
- start_time (datetime)
- end_time (datetime)
- location (varchar, nullable)
- lecturer (varchar, nullable)
- description (text, nullable)
- color (varchar, default: '#5CD9C1')
- timestamps
- deleted_at (soft delete)
```

---

## 🚀 API Endpoints

**Base URL:** `/api`

### Authentication
Semua endpoint memerlukan JWT token di header:
```
Authorization: Bearer {jwt_token}
```

### Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `GET` | `/classrooms/{classroom}/schedules` | List semua jadwal | Member/Owner |
| `POST` | `/classrooms/{classroom}/schedules` | Buat jadwal baru | Owner only |
| `GET` | `/classrooms/{classroom}/schedules/{schedule}` | Detail jadwal | Member/Owner |
| `PUT` | `/classrooms/{classroom}/schedules/{schedule}` | Update jadwal | Owner/Coordinator |
| `DELETE` | `/classrooms/{classroom}/schedules/{schedule}` | Hapus jadwal | Owner/Coordinator |

---

## 📝 Request/Response Examples

### 1. List Schedules
**Request:**
```http
GET /api/classrooms/1/schedules
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "classroom_id": 1,
      "coordinator_1": 2,
      "coordinator_2": null,
      "title": "Pemrograman Web",
      "start_time": "2025-11-20 08:00:00",
      "end_time": "2025-11-20 10:00:00",
      "location": "Ruang 301",
      "lecturer": "Dr. John Doe",
      "description": "Pertemuan ke-5: RESTful API",
      "color": "#5CD9C1",
      "created_at": "2025-11-16T10:00:00.000000Z",
      "updated_at": "2025-11-16T10:00:00.000000Z",
      "coordinator1": {
        "id": 2,
        "name": "Jane Doe",
        "email": "jane@example.com"
      },
      "coordinator2": null
    }
  ]
}
```

### 2. Create Schedule
**Request:**
```http
POST /api/classrooms/1/schedules
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Pemrograman Web",
  "start_time": "2025-11-20 08:00:00",
  "end_time": "2025-11-20 10:00:00",
  "location": "Ruang 301",
  "lecturer": "Dr. John Doe",
  "description": "Pertemuan ke-5: RESTful API",
  "color": "#5CD9C1",
  "coordinator_1": 2,
  "coordinator_2": 3
}
```

**Response:**
```json
{
  "message": "Jadwal kelas berhasil dibuat",
  "data": {
    "id": 1,
    "classroom_id": 1,
    "title": "Pemrograman Web",
    "start_time": "2025-11-20 08:00:00",
    "end_time": "2025-11-20 10:00:00",
    "location": "Ruang 301",
    "lecturer": "Dr. John Doe",
    "description": "Pertemuan ke-5: RESTful API",
    "color": "#5CD9C1",
    "coordinator_1": 2,
    "coordinator_2": 3,
    "created_at": "2025-11-16T10:00:00.000000Z",
    "updated_at": "2025-11-16T10:00:00.000000Z"
  }
}
```

### 3. Update Schedule
**Request:**
```http
PUT /api/classrooms/1/schedules/1
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Pemrograman Web - Updated",
  "location": "Ruang 302"
}
```

**Response:**
```json
{
  "message": "Jadwal kelas berhasil diperbarui",
  "data": {
    "id": 1,
    "title": "Pemrograman Web - Updated",
    "location": "Ruang 302",
    ...
  }
}
```

### 4. Delete Schedule
**Request:**
```http
DELETE /api/classrooms/1/schedules/1
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Jadwal kelas berhasil dihapus"
}
```

---

## 🔐 Authorization Logic

### Policy: `ClassSchedulePolicy.php`

```php
// View - Member atau Owner
public function view(User $user, Classroom $classroom)
{
    return $user->id === $classroom->owner_id 
        || $classroom->users()->where('user_id', $user->id)->exists();
}

// Create - Owner only
public function create(User $user, Classroom $classroom)
{
    return $user->id === $classroom->owner_id;
}

// Update - Owner atau Coordinator 1/2
public function update(User $user, ClassSchedule $schedule, Classroom $classroom)
{
    return $user->id === $classroom->owner_id
        || $user->id === $schedule->coordinator_1
        || $user->id === $schedule->coordinator_2;
}

// Delete - Owner atau Coordinator 1/2
public function delete(User $user, ClassSchedule $schedule, Classroom $classroom)
{
    return $user->id === $classroom->owner_id
        || $user->id === $schedule->coordinator_1
        || $user->id === $schedule->coordinator_2;
}
```

---

## ✅ Validation Rules

### Create/Update Schedule:
```php
'title' => 'required|string|max:255',
'start_time' => 'required|date',
'end_time' => 'required|date|after:start_time',
'location' => 'nullable|string|max:255',
'lecturer' => 'nullable|string|max:255',
'description' => 'nullable|string',
'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
'coordinator_1' => 'nullable|exists:users,id',
'coordinator_2' => 'nullable|exists:users,id'
```

**Validation Errors (422):**
```json
{
  "message": "Validation errors",
  "errors": {
    "title": ["The title field is required."],
    "end_time": ["The end time must be a date after start time."]
  }
}
```

---

## 🧪 Testing

### Run Tests:
```bash
# Run all tests
php artisan test --filter=ClassScheduleTest

# Run specific test
php artisan test --filter=test_owner_can_create_class_schedule

# With coverage
php artisan test --filter=ClassScheduleTest --coverage
```

### Test Coverage (22 test cases):
- ✅ Authorization (Owner, Coordinator, Member, Non-member)
- ✅ CRUD Operations
- ✅ Validation Rules
- ✅ Business Logic (ordering, default values)
- ✅ JWT Authentication
- ✅ Soft Deletes

---

## ⚠️ Important Notes untuk Sync

### 1. **Owner Membership Logic**
- Owner HARUS juga menjadi member di `classroom_user` table
- Saat create classroom, auto-attach owner sebagai member
- Policy `view()` tetap cek owner_id untuk backward compatibility

### 2. **Cascade Behavior**
```php
// classrooms table
owner_id -> onDelete('restrict')  // Owner harus transfer dulu sebelum delete account

// classroom_user table
user_id -> onDelete('cascade')    // OK - hanya hapus relasi
classroom_id -> onDelete('cascade') // OK - kalau classroom dihapus, relasi ikut
```

### 3. **Transfer Ownership (Future Feature)**
Fitur ini belum diimplementasi, tapi sudah disiapkan:
- Owner harus transfer ownership sebelum leave classroom
- Owner harus transfer semua classroom sebelum delete account
- New owner harus sudah jadi member

### 4. **Soft Deletes**
Semua table menggunakan soft deletes:
- `deleted_at IS NULL` untuk data aktif
- Policy sudah handle `whereNull('deleted_at')`

### 5. **RESTful API Pattern**
```
POST   /classrooms/{id}/schedules       -> store()
GET    /classrooms/{id}/schedules       -> index()
GET    /classrooms/{id}/schedules/{id}  -> show()
PUT    /classrooms/{id}/schedules/{id}  -> update()
DELETE /classrooms/{id}/schedules/{id}  -> destroy()
```

---

## 🚧 TODO (Belum Diimplementasi)

1. **ClassroomController** - Untuk create/manage classroom
2. **Transfer Ownership Endpoint** - `/classrooms/{id}/transfer`
3. **Leave Classroom Endpoint** - `/classrooms/{id}/leave`
4. **Validation untuk Delete User** - Cek owned classrooms

---

## 📦 Dependencies

```json
{
  "require": {
    "tymon/jwt-auth": "^2.0",
    "laravel/framework": "^11.0"
  }
}
```

---

## 🔗 Related Models

### ClassSchedule Model
```php
// Relationships
classroom()     -> belongsTo(Classroom)
coordinator1()  -> belongsTo(User, 'coordinator_1')
coordinator2()  -> belongsTo(User, 'coordinator_2')

// Fillable
['classroom_id', 'coordinator_1', 'coordinator_2', 'title', 
 'start_time', 'end_time', 'location', 'lecturer', 'description', 'color']

// Casts
'start_time' => 'datetime'
'end_time' => 'datetime'
```

### Classroom Model
```php
// Relationships
owner()          -> belongsTo(User, 'owner_id')
users()          -> belongsToMany(User, 'classroom_user')
classSchedules() -> hasMany(ClassSchedule)
```

---

## 📞 Contact

Jika ada pertanyaan tentang implementasi fitur ini, hubungi team lead atau lihat dokumentasi di repo.

**Last Updated:** November 16, 2025
**Feature Status:** ✅ Completed & Tested
