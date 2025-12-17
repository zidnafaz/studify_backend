# 🔐 STUDIFY - Login Flow Documentation

## 📋 Ringkasan

Dokumen ini menjelaskan **alur lengkap proses login** di aplikasi Studify, mulai dari user mengirim request dari mobile app hingga mendapatkan JWT token.

---

## 🎯 Overview - Login Process

```
┌─────────────┐       HTTP POST        ┌──────────────┐
│   Flutter   │  ───────────────────►  │   Laravel    │
│  Mobile App │      JSON Request      │   API Server │
└─────────────┘                        └──────────────┘
                                              │
                                              ▼
                    ┌─────────────────────────────────────┐
                    │  Login Flow - File Processing       │
                    ├─────────────────────────────────────┤
                    │  1. Route (api.php)                 │
                    │  2. Controller (AuthController.php) │
                    │  3. Validator (Request Validation)  │
                    │  4. Auth Facade (JWT Auth)          │
                    │  5. User Provider (config/auth.php) │
                    │  6. Model (User.php)                │
                    │  7. Database (users table)          │
                    │  8. JWT Token Generation            │
                    └─────────────────────────────────────┘
```

---

## 🔄 Step-by-Step Login Flow

### **Step 1: API Request dari Flutter App**

**Endpoint:**
```
POST /api/auth/login
```

**Request Body (JSON):**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Request Headers:**
```
Content-Type: application/json
Accept: application/json
```

---

### **Step 2: Route Handling**

**📁 File:** `routes/api.php` (Line 38-42)

```php
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::delete('login', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('user', [AuthController::class, 'me']);
    Route::post('profile', [AuthController::class, 'updateProfile']);
});
```

**Apa yang terjadi:**
- Laravel routing menangkap request `POST /api/auth/login`
- Routing meneruskan request ke `AuthController@login` method
- Route ini **TIDAK** memerlukan authentication (public route)

---

### **Step 3: Controller Processing**

**📁 File:** `app/Http/Controllers/AuthController.php`

#### 3.1 Constructor - Middleware Setup

```php
public function __construct()
{
    // Exclude refresh from auth:api middleware so it can accept expired tokens
    $this->middleware('auth:api', ['except' => ['login', 'register', 'refresh']]);
}
```

**Penjelasan:**
- Method `login`, `register`, dan `refresh` **dikecualikan** dari middleware `auth:api`
- Artinya, endpoint ini bisa diakses tanpa token (public endpoint)

#### 3.2 Login Method (Line 67-92)

```php
public function login(Request $request)
{
    // 1. VALIDATION
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required|string|min:6',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => __('messages.validation_errors'),
            'errors' => $validator->errors(),
        ], 422);
    }

    // 2. EXTRACT CREDENTIALS
    $credentials = $request->only('email', 'password');

    // 3. ATTEMPT LOGIN
    if (!$token = Auth::attempt($credentials)) {
        return response()->json([
            'message' => __('messages.invalid_credentials'),
        ], 401);
    }

    // 4. RETURN TOKEN
    return $this->respondWithToken($token);
}
```

**Proses Detail:**

**A. Request Validation**
- Validasi input email dan password
- Email harus valid format
- Password minimal 6 karakter
- Jika gagal, return error 422 (Unprocessable Entity)

**B. Extract Credentials**
```php
$credentials = $request->only('email', 'password');
// Result: ['email' => 'user@example.com', 'password' => 'password123']
```

**C. Authentication Attempt**
```php
if (!$token = Auth::attempt($credentials))
```

Ini adalah **inti dari proses login**. Method `Auth::attempt()` melakukan:
1. Mencari user berdasarkan email di database
2. Memverifikasi password dengan hash
3. Jika berhasil, generate JWT token
4. Jika gagal, return `false`

---

### **Step 4: Authentication Facade**

**📁 File:** `Illuminate\Support\Facades\Auth` (Laravel Core)

Ketika kita panggil `Auth::attempt($credentials)`, Laravel melakukan:

#### 4.1 Guard Resolution

**📁 File:** `config/auth.php` (Line 15-18)

```php
'defaults' => [
    'guard' => env('AUTH_GUARD', 'api'),
    'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
],
```

**📁 File:** `config/auth.php` (Line 40-50)

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],

    'api' => [
        'driver' => 'jwt',          // ← Menggunakan JWT driver
        'provider' => 'users',       // ← Provider untuk mencari user
    ],
],
```

**Penjelasan:**
- Default guard adalah `api`
- Driver `jwt` menggunakan package **tymon/jwt-auth**
- Provider `users` akan mencari di tabel `users`

#### 4.2 User Provider

**📁 File:** `config/auth.php` (Line 68-75)

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => env('AUTH_MODEL', App\Models\User::class),
    ],
],
```

**Penjelasan:**
- Provider `users` menggunakan Eloquent driver
- Model yang digunakan adalah `App\Models\User`
- Provider ini yang akan query database

---

### **Step 5: Model & Database Query**

**📁 File:** `app/Models/User.php`

#### 5.1 User Model Configuration

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
```

**Penjelasan:**
- Model extends `Authenticatable` → untuk authentication
- Implements `JWTSubject` → untuk JWT token generation
- `password` di-cast ke `hashed` → auto hashing saat save

#### 5.2 JWT Interface Methods

```php
/**
 * Get the identifier that will be stored in the subject claim of the JWT.
 */
public function getJWTIdentifier()
{
    return $this->getKey(); // Return user ID
}

/**
 * Return a key value array, containing any custom claims to be added to the JWT.
 */
public function getJWTCustomClaims()
{
    return []; // Bisa tambahkan custom claims seperti role, permissions
}
```

**Penjelasan:**
- `getJWTIdentifier()` mengembalikan ID user (primary key)
- `getJWTCustomClaims()` untuk custom data dalam token (saat ini kosong)

#### 5.3 Database Query

**SQL yang Dijalankan:**
```sql
SELECT * 
FROM users 
WHERE email = 'user@example.com' 
  AND deleted_at IS NULL 
LIMIT 1;
```

**Penjelasan:**
- Mencari user berdasarkan email
- Soft delete aware (hanya user yang belum dihapus)
- Jika ditemukan, lanjut verifikasi password

---

### **Step 6: Password Verification**

**Laravel Internal Process:**

```php
// Simplified internal process
$user = User::where('email', $credentials['email'])->first();

if (!$user) {
    return false; // User not found
}

if (!Hash::check($credentials['password'], $user->password)) {
    return false; // Password tidak cocok
}

// Password cocok, user authenticated!
```

**Hash Comparison:**
- Password dari request: `"password123"` (plain text)
- Password di database: `"$2y$12$abc...xyz"` (bcrypt hash)
- `Hash::check()` membandingkan keduanya
- Jika cocok, authentication berhasil

---

### **Step 7: JWT Token Generation**

**📁 File:** `config/jwt.php`

#### 7.1 JWT Configuration

```php
'secret' => env('JWT_SECRET'),  // Secret key untuk signing token

'ttl' => (int) env('JWT_TTL', 60),  // Token expire dalam 60 menit

'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 20160),  // Refresh dalam 14 hari

'algo' => env('JWT_ALGO', 'HS256'),  // Hashing algorithm
```

#### 7.2 Token Generation Process

Setelah user terverifikasi, JWT token dibuat dengan struktur:

**Token Structure:**
```
HEADER.PAYLOAD.SIGNATURE
```

**HEADER (Base64 encoded):**
```json
{
  "typ": "JWT",
  "alg": "HS256"
}
```

**PAYLOAD (Base64 encoded):**
```json
{
  "iss": "http://localhost:8000",     // Issuer (dari config/app.php)
  "iat": 1702800000,                  // Issued At (timestamp)
  "exp": 1702803600,                  // Expiration (iat + 60 menit)
  "nbf": 1702800000,                  // Not Before
  "jti": "abc123def456",              // JWT ID (unique identifier)
  "sub": 1,                           // Subject (user ID dari getJWTIdentifier)
  "prv": "hash_of_user_model"         // Provider hash
}
```

**SIGNATURE:**
```
HMACSHA256(
  base64UrlEncode(header) + "." + base64UrlEncode(payload),
  JWT_SECRET
)
```

**Hasil Token (contoh):**
```
eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vbG9jYWxob3N0OjgwMDAiLCJpYXQiOjE3MDI4MDAwMDAsImV4cCI6MTcwMjgwMzYwMCwibmJmIjoxNzAyODAwMDAwLCJqdGkiOiJhYmMxMjNkZWY0NTYiLCJzdWIiOjEsInBydiI6Imhhc2hfb2ZfdXNlcl9tb2RlbCJ9.xyz789signature
```

---

### **Step 8: Response Generation**

**📁 File:** `app/Http/Controllers/AuthController.php` (Line 146-157)

```php
protected function respondWithToken($token)
{
    return response()->json([
        'data' => [
            'user' => Auth::user(),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
        ]
    ]);
}
```

**Response JSON ke Flutter:**
```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "email_verified_at": null,
      "created_at": "2024-11-14T10:00:00.000000Z",
      "updated_at": "2024-11-14T10:00:00.000000Z"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

**Penjelasan Response:**
- `user`: Data user yang login (tanpa password)
- `access_token`: JWT token untuk request selanjutnya
- `token_type`: "bearer" (akan digunakan di header: `Authorization: Bearer <token>`)
- `expires_in`: Waktu expire dalam detik (3600 = 1 jam)

---

## 📊 Complete Flow Diagram

```
┌────────────────────────────────────────────────────────────────────────┐
│                         LOGIN FLOW SEQUENCE                             │
└────────────────────────────────────────────────────────────────────────┘

 Flutter App                                           Laravel Backend
     │                                                        │
     │  1. POST /api/auth/login                             │
     │     { email, password }                              │
     ├──────────────────────────────────────────────────────►│
     │                                                        │
     │                                    2. routes/api.php  │
     │                                       ↓ Route Match   │
     │                                                        │
     │                        3. AuthController@login        │
     │                           ↓ Validate Input            │
     │                                                        │
     │                        4. Validator::make()           │
     │                           ↓ Check email & password    │
     │                                                        │
     │                        5. Auth::attempt()             │
     │                           ↓ Resolve 'api' guard      │
     │                                                        │
     │                        6. config/auth.php             │
     │                           ↓ Get JWT driver            │
     │                           ↓ Get 'users' provider     │
     │                                                        │
     │                        7. Query Database              │
     │                           SELECT * FROM users         │
     │                           WHERE email = ?             │
     │                           ↓ User found                │
     │                                                        │
     │                        8. Hash::check()               │
     │                           ↓ Verify password           │
     │                           ✓ Password match            │
     │                                                        │
     │                        9. Generate JWT Token          │
     │                           ↓ Use JWT_SECRET            │
     │                           ↓ Set TTL (60 min)          │
     │                           ↓ Add user ID to payload   │
     │                                                        │
     │                       10. respondWithToken()          │
     │                           ↓ Format response           │
     │                                                        │
     │  11. Response: { user, access_token, ... }           │
     │◄──────────────────────────────────────────────────────┤
     │                                                        │
     │  12. Save token to local storage                      │
     │      Use for next requests                            │
     │                                                        │
```

---

## 📂 File Map - Login Process

Berikut daftar semua file yang terlibat dalam proses login:

### **1. Entry Point**
```
routes/api.php
├── Line 38-42: Route definition untuk auth
└── Mengarahkan ke AuthController@login
```

### **2. Controller Layer**
```
app/Http/Controllers/AuthController.php
├── Line 14-22: Constructor (middleware setup)
├── Line 67-92: login() method
│   ├── Validation
│   ├── Auth::attempt()
│   └── respondWithToken()
└── Line 146-157: respondWithToken() helper method
```

### **3. Model Layer**
```
app/Models/User.php
├── Line 12: Implements JWTSubject interface
├── Line 21-25: $fillable (mass assignment)
├── Line 31-34: $hidden (hide password dari response)
├── Line 41-46: casts() (password hashing)
├── Line 107-111: getJWTIdentifier() (return user ID)
└── Line 118-121: getJWTCustomClaims() (custom claims)
```

### **4. Configuration Files**
```
config/auth.php
├── Line 15-18: Default guard ('api')
├── Line 40-50: Guards definition
│   └── 'api' guard uses JWT driver
└── Line 68-75: User provider configuration

config/jwt.php
├── Line 28: JWT_SECRET
├── Line 105: TTL (60 minutes)
├── Line 124: Refresh TTL (20160 minutes = 14 days)
└── Line 136: Algorithm (HS256)
```

### **5. Database**
```
database/migrations/0001_01_01_000000_create_users_table.php
└── Line 14-22: users table schema
    ├── id (PK)
    ├── name
    ├── email (unique)
    ├── password (hashed)
    └── timestamps
```

### **6. Environment Variables**
```
.env
├── JWT_SECRET=your_secret_key_here
├── JWT_TTL=60
├── JWT_REFRESH_TTL=20160
├── JWT_ALGO=HS256
└── AUTH_GUARD=api
```

---

## 🔐 Security Features

### **1. Password Hashing**
- Password tidak pernah disimpan plain text
- Menggunakan bcrypt algorithm (cost factor 12)
- Auto hashing via model cast

### **2. JWT Token Security**
- Token signed dengan secret key
- Expire time (default 60 menit)
- Refresh token untuk perpanjangan session

### **3. Validation**
- Email format validation
- Password minimum length (6 karakter)
- Input sanitization

### **4. Soft Delete**
- User yang dihapus tidak bisa login
- Data tetap ada di database (audit trail)

---

## ❌ Error Handling

### **1. Validation Error (422)**
```json
{
  "message": "Validation errors",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

### **2. Invalid Credentials (401)**
```json
{
  "message": "Invalid credentials"
}
```

**Terjadi jika:**
- Email tidak ditemukan
- Password salah
- User sudah di-soft delete

---

## 🧪 Testing Login

### **Menggunakan cURL:**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

### **Menggunakan Postman:**
```
Method: POST
URL: http://localhost:8000/api/auth/login
Headers:
  Content-Type: application/json
  Accept: application/json
Body (raw JSON):
{
  "email": "user@example.com",
  "password": "password123"
}
```

### **Response Success (200):**
```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "created_at": "2024-11-14T10:00:00.000000Z",
      "updated_at": "2024-11-14T10:00:00.000000Z"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

---

## 🚀 Menggunakan Token di Request Berikutnya

Setelah login berhasil, Flutter app harus menyimpan `access_token` dan menggunakannya di setiap request:

### **Request Header:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### **Contoh Request Protected Endpoint:**
```bash
curl -X GET http://localhost:8000/api/classrooms \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
  -H "Accept: application/json"
```

---

## 🔄 Related Endpoints

### **1. Register**
```
POST /api/users
```
- Membuat user baru
- Auto login setelah register
- Return user + token

### **2. Refresh Token**
```
POST /api/auth/refresh
```
- Perpanjang token yang akan expire
- Tidak perlu login ulang
- Return token baru

### **3. Get Current User**
```
GET /api/auth/user
```
- Mendapatkan data user yang sedang login
- Memerlukan valid token
- Return user data

### **4. Logout**
```
DELETE /api/auth/login
```
- Invalidate current token
- User harus login ulang
- Return 204 No Content

### **5. Update Profile**
```
POST /api/auth/profile
```
- Update nama user
- Memerlukan valid token
- Return updated user data

---

## 📝 Summary

**File yang Terlibat dalam Login:**

| No | File | Role |
|----|------|------|
| 1 | `routes/api.php` | Route definition |
| 2 | `app/Http/Controllers/AuthController.php` | Business logic |
| 3 | `app/Models/User.php` | Data model & JWT interface |
| 4 | `config/auth.php` | Authentication configuration |
| 5 | `config/jwt.php` | JWT token configuration |
| 6 | `database/migrations/*_create_users_table.php` | Database schema |
| 7 | `.env` | Environment variables |

**Flow Singkat:**
```
Request → Route → Controller → Validator → Auth Facade → 
Config → User Provider → Model → Database → Password Check → 
JWT Generate → Response
```

**Key Points:**
✅ Login tidak memerlukan token (public endpoint)  
✅ Password di-hash dengan bcrypt  
✅ JWT token expire dalam 60 menit (configurable)  
✅ Token bisa di-refresh tanpa login ulang  
✅ Soft delete aware  
✅ Return user data + token dalam 1 response  

---

**Generated**: December 17, 2025  
**Project**: Studify Backend - Login Flow Documentation  
**Tech Stack**: Laravel 11 + JWT Auth (tymon/jwt-auth)
