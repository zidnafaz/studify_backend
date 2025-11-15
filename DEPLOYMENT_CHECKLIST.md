# ✅ Pre-Deployment Checklist

## 📋 Before Pushing to GitHub

### 1. Code & Dependencies
- [ ] All code committed to `deploy/render` branch
- [ ] `composer.json` dependencies up to date
- [ ] `package.json` dependencies up to date
- [ ] No local-only dependencies in code

### 2. Environment Configuration
- [ ] `.env` file configured correctly (for reference)
- [ ] Database credentials verified (Aiven)
- [ ] All required env variables documented
- [ ] JWT secret will be regenerated for production

### 3. Docker Configuration
- [ ] `Dockerfile` created and tested
- [ ] `.dockerignore` excludes unnecessary files
- [ ] `docker-compose.yml` for local testing
- [ ] All docker config files in `docker/` folder

### 4. Database
- [ ] Migrations are up to date
- [ ] No pending migrations
- [ ] Database accessible from external IPs
- [ ] Aiven whitelist configured (if needed)

### 5. Application Code
- [ ] Health check endpoint works (`/api/health`)
- [ ] CORS configured for frontend domain
- [ ] File upload paths configured correctly
- [ ] Session driver set to `database`
- [ ] Cache driver set to `database`
- [ ] Queue driver set to `database`

### 6. Security
- [ ] `APP_DEBUG=false` for production
- [ ] `APP_ENV=production` 
- [ ] Sensitive data not hardcoded
- [ ] `.env` in `.gitignore`
- [ ] Strong `APP_KEY` generated

## 🚀 Render Setup Checklist

### 1. Service Creation
- [ ] Logged into Render Dashboard
- [ ] Repository connected to Render
- [ ] New Web Service created
- [ ] Correct branch selected (`deploy/render`)
- [ ] Environment set to `Docker`

### 2. Service Configuration
- [ ] Service name: `studify-backend`
- [ ] Region selected (Singapore recommended)
- [ ] Instance type selected (Free/Starter/Standard)
- [ ] Dockerfile path: `./Dockerfile`

### 3. Environment Variables Set
- [ ] `APP_NAME` = Studify
- [ ] `APP_ENV` = production
- [ ] `APP_KEY` = (from local)
- [ ] `APP_DEBUG` = false
- [ ] `APP_URL` = (Render URL)
- [ ] `DB_CONNECTION` = mysql
- [ ] `DB_HOST` = (from Aiven dashboard)
- [ ] `DB_PORT` = (from Aiven dashboard)
- [ ] `DB_DATABASE` = (from Aiven dashboard)
- [ ] `DB_USERNAME` = (from Aiven dashboard)
- [ ] `DB_PASSWORD` = (from Aiven)
- [ ] `SESSION_DRIVER` = database
- [ ] `CACHE_STORE` = database
- [ ] `QUEUE_CONNECTION` = database
- [ ] `LOG_LEVEL` = error
- [ ] `JWT_SECRET` = (generate new)

### 4. Advanced Settings
- [ ] Auto-deploy enabled
- [ ] Health check path: `/api/health`
- [ ] Docker command: default (from Dockerfile)

## 🧪 Post-Deployment Testing

### 1. Basic Connectivity
- [ ] Service deployed successfully
- [ ] No build errors in logs
- [ ] Container running

### 2. Health Check
```bash
curl https://studify-backend.onrender.com/api/health
```
- [ ] Returns 200 OK
- [ ] JSON response correct
- [ ] Database status: "connected"

### 3. Database Connection
- [ ] Migrations ran successfully
- [ ] Can query database
- [ ] No connection errors in logs

### 4. API Endpoints
- [ ] `/api/users` (POST) - Registration works
- [ ] `/api/auth/login` (POST) - Login works
- [ ] `/api/auth/user` (GET) - Get user works
- [ ] `/api/auth/logout` (DELETE) - Logout works
- [ ] `/api/auth/refresh` (POST) - Token refresh works

### 5. Performance
- [ ] Response time acceptable (< 2s for health check)
- [ ] Cold start time acceptable (< 30s on free tier)
- [ ] No memory issues
- [ ] No timeout errors

### 6. Logs & Monitoring
- [ ] Application logs visible in Render
- [ ] No critical errors
- [ ] PHP-FPM running
- [ ] Nginx running
- [ ] Queue worker running

## 🔧 Troubleshooting Checklist

### If Build Fails
- [ ] Check Render build logs
- [ ] Verify Dockerfile syntax
- [ ] Check composer dependencies
- [ ] Verify npm dependencies
- [ ] Test build locally with Docker

### If Database Connection Fails
- [ ] Verify Aiven credentials
- [ ] Check Aiven IP whitelist
- [ ] Test connection from local machine
- [ ] Verify database port accessible
- [ ] Check DB_HOST format (no http://)

### If 502 Bad Gateway
- [ ] Check if services started (php-fpm, nginx)
- [ ] Verify port 8080 exposed
- [ ] Check supervisor logs
- [ ] Verify nginx configuration
- [ ] Check file permissions

### If JWT Errors
- [ ] JWT_SECRET is set
- [ ] JWT_SECRET is not empty
- [ ] Regenerate with `php artisan jwt:secret`
- [ ] Clear config cache

### If Slow Performance
- [ ] Upgrade from free tier
- [ ] Enable Redis for cache (optional)
- [ ] Optimize queries
- [ ] Check Aiven database performance

## 📱 Frontend Integration

### Update Frontend Configuration
- [ ] Update API base URL in Flutter app
- [ ] Update CORS origins in backend
- [ ] Test all API calls from app
- [ ] Verify authentication flow
- [ ] Test file uploads (if any)

### Flutter Config Example
```dart
// lib/core/config/api_config.dart
class ApiConfig {
  static const String baseUrl = 'https://studify-backend.onrender.com/api';
}
```

## 🎯 Final Verification

### Smoke Tests
- [ ] Register new user
- [ ] Login with user
- [ ] Access protected routes
- [ ] Logout
- [ ] Refresh token

### Load Testing (Optional)
- [ ] Test with 10 concurrent users
- [ ] Monitor response times
- [ ] Check for errors under load
- [ ] Verify auto-scaling (if enabled)

## 📊 Monitoring Setup

### After Deployment
- [ ] Set up uptime monitoring (UptimeRobot, etc.)
- [ ] Configure error alerting
- [ ] Monitor database usage on Aiven
- [ ] Monitor Render metrics

### Regular Checks
- [ ] Weekly log review
- [ ] Database size monitoring
- [ ] Performance metrics review
- [ ] Security updates check

## 🎉 Go Live

- [ ] All tests passing
- [ ] Performance acceptable
- [ ] No critical errors
- [ ] Monitoring active
- [ ] Documentation updated
- [ ] Team notified
- [ ] Frontend updated with new URL

---

## Notes
- Save this checklist and check off items as you complete them
- If any item fails, refer to troubleshooting docs
- Keep credentials secure and never commit to Git
- Regular backups recommended (Aiven handles this)

**Last Updated:** 2025-11-15
**Deployment Target:** Render.com
**Database:** Aiven MySQL
