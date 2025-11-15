# Quick Start - Deploy Studify Backend to Render

## 🚀 Deployment Steps

### 1. Persiapan Repository
```bash
# Pastikan berada di branch deploy/render
git checkout deploy/render

# Push semua perubahan
git add .
git commit -m "Add Docker configuration for Render deployment"
git push origin deploy/render
```

### 2. Setup di Render.com

#### A. Create New Web Service
1. Login ke [Render Dashboard](https://dashboard.render.com/)
2. Click **"New +"** → **"Web Service"**
3. Connect your GitHub repository
4. Select branch: `deploy/render`

#### B. Service Configuration
```
Name: studify-backend
Environment: Docker
Region: Singapore
Branch: deploy/render
Instance Type: Free (or pilih sesuai kebutuhan)
```

#### C. Environment Variables
Copy dan paste ke Render Environment tab:

**Required Variables:**
```bash
APP_NAME=Studify
APP_ENV=production
APP_KEY=base64:TlwykZgt8c5c8WIEiifNgZn0F1kW1ghq3m1MVlOkAtc=
APP_DEBUG=false
APP_URL=https://studify-backend.onrender.com

DB_CONNECTION=mysql
DB_HOST=your-mysql-host.aivencloud.com
DB_PORT=your-port
DB_DATABASE=your-database-name
DB_USERNAME=your-username
DB_PASSWORD=your-password-from-aiven

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
LOG_LEVEL=error

JWT_SECRET=your-jwt-secret-key-generate-new-one
```

⚠️ **Penting:** 
- Ganti `APP_URL` dengan URL actual dari Render
- Generate JWT_SECRET baru: `php artisan jwt:secret --show`

### 3. Deploy
Click **"Create Web Service"** dan tunggu build selesai (5-10 menit).

### 4. Verify Deployment

#### Test Health Check
```bash
curl https://studify-backend.onrender.com/api/health
```

Expected response:
```json
{
  "status": "ok",
  "timestamp": "2025-11-15T...",
  "database": "connected",
  "app": "Studify",
  "environment": "production"
}
```

#### Test Authentication
```bash
# Register user
curl -X POST https://studify-backend.onrender.com/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'

# Login
curl -X POST https://studify-backend.onrender.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

## 📁 Files Created

```
studify_backend/
├── Dockerfile                    # Main Docker configuration
├── .dockerignore                 # Files to exclude from Docker
├── render.yaml                   # Render configuration
├── DEPLOY_RENDER.md             # Detailed deployment guide
├── docker/
│   ├── nginx.conf               # Nginx main config
│   ├── default.conf             # Nginx site config
│   ├── supervisord.conf         # Process manager config
│   └── start.sh                 # Startup script
└── routes/
    └── api.php                  # Updated with health check
```

## 🔧 Troubleshooting

### Build Failed
Check Render logs:
- Dependency issues → Verify `composer.json`
- Node build issues → Check `package.json` and `vite.config.js`

### Database Connection Error
- Verify Aiven whitelist includes Render IPs
- Double-check credentials di environment variables
- Test connection manual:
  ```bash
  mysql -h YOUR_DB_HOST -P YOUR_DB_PORT -u YOUR_DB_USER -p
  ```

### 502 Bad Gateway
- Check Render logs untuk PHP/Nginx errors
- Verify port 8080 exposed correctly
- Check supervisor processes running

### JWT Error
Generate new JWT secret:
```bash
# Local
php artisan jwt:secret --show

# Copy output ke Render environment variable JWT_SECRET
```

## 📊 Monitoring

### View Logs
Render Dashboard → Your Service → Logs

### Check Service Health
```bash
# Health endpoint
curl https://studify-backend.onrender.com/api/health

# Check response time
curl -w "@-" -o /dev/null -s https://studify-backend.onrender.com/api/health <<'EOF'
    time_namelookup:  %{time_namelookup}\n
       time_connect:  %{time_connect}\n
    time_appconnect:  %{time_appconnect}\n
      time_redirect:  %{time_redirect}\n
   time_pretransfer:  %{time_pretransfer}\n
 time_starttransfer:  %{time_starttransfer}\n
                    ----------\n
         time_total:  %{time_total}\n
EOF
```

## 🔄 Auto-Deploy

Auto-deploy is enabled. Every push to `deploy/render` branch will trigger automatic deployment.

```bash
# Make changes
git add .
git commit -m "Update feature"
git push origin deploy/render

# Render will automatically rebuild and deploy
```

## 💰 Cost Considerations

**Free Tier:**
- 750 hours/month
- Sleeps after 15 min inactivity
- Slower cold starts (~30s)

**Upgrade Options:**
- Starter: $7/month - Always on, faster
- Standard: $25/month - More resources

## 📝 Notes

- Database remains on Aiven (unchanged)
- SSL/HTTPS automatically handled by Render
- Environment variables secure and encrypted
- Automatic container health checks
- Queue worker included in container

## ✅ Checklist

- [ ] Branch `deploy/render` pushed to GitHub
- [ ] Render web service created
- [ ] All environment variables set
- [ ] Database credentials verified
- [ ] Health check returns 200 OK
- [ ] Test authentication works
- [ ] Frontend updated with new API URL

## 🆘 Support

If issues persist:
1. Check Render logs
2. Verify Aiven database access
3. Review environment variables
4. Test locally with Docker:
   ```bash
   docker build -t studify-backend .
   docker run -p 8080:8080 --env-file .env studify-backend
   ```
