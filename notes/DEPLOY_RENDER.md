# Studify Backend - Render Deployment

## Prerequisites
- Render.com account
- Database sudah setup di Aiven (MySQL)
- Repository Git dengan branch `deploy/render`

## Environment Variables yang Perlu di Set di Render

Buka Render Dashboard → Web Service → Environment:

### App Configuration
```
APP_NAME=Studify
APP_ENV=production
APP_KEY=base64:TlwykZgt8c5c8WIEiifNgZn0F1kW1ghq3m1MVlOkAtc=
APP_DEBUG=false
APP_URL=https://your-app-name.onrender.com
```

### Database Configuration (Aiven)
```
DB_CONNECTION=mysql
DB_HOST=your-mysql-host.aivencloud.com
DB_PORT=your-port
DB_DATABASE=your-database-name
DB_USERNAME=your-username
DB_PASSWORD=your-password-from-aiven
```

### Session & Cache
```
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database
```

### Logging
```
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### JWT
```
JWT_SECRET=your-jwt-secret-key
JWT_TTL=60
```

## Deployment Steps

### 1. Push ke GitHub
```bash
git add .
git commit -m "Add Docker configuration for Render deployment"
git push origin deploy/render
```

### 2. Create Web Service di Render
1. Login ke [Render Dashboard](https://dashboard.render.com/)
2. Click "New +" → "Web Service"
3. Connect repository GitHub Anda
4. Pilih branch: `deploy/render`

### 3. Configure Service
- **Name**: studify-backend
- **Environment**: Docker
- **Region**: Singapore (atau terdekat)
- **Branch**: deploy/render
- **Dockerfile Path**: ./Dockerfile

### 4. Set Environment Variables
Copy semua environment variables di atas ke Render Environment section.

### 5. Deploy
Click "Create Web Service" dan tunggu deployment selesai.

## Post-Deployment

### Test API
```bash
curl https://your-app-name.onrender.com/api/health
```

### Check Logs
Buka Render Dashboard → Logs untuk monitoring aplikasi.

### Run Artisan Commands (jika diperlukan)
Via Render Shell:
```bash
php artisan cache:clear
php artisan config:clear
php artisan migrate --force
```

## Troubleshooting

### Database Connection Issues
- Pastikan IP Render sudah di-whitelist di Aiven
- Verify credentials database di environment variables

### 502 Bad Gateway
- Check logs di Render dashboard
- Pastikan port 8080 sudah expose dengan benar
- Verify nginx dan php-fpm running

### Storage Permission Issues
```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

## Notes
- Render free tier akan sleep setelah 15 menit tidak ada aktivitas
- Database tetap menggunakan Aiven (tidak berubah)
- Logs dapat dilihat di Render Dashboard
- Auto-deploy enabled untuk setiap push ke branch `deploy/render`

## Health Check Endpoint
Tambahkan di `routes/api.php` jika belum ada:
```php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected'
    ]);
});
```
