# Render Deployment Helper Scripts

## Test Docker Locally

### Windows PowerShell
```powershell
# Build image
docker build -t studify-backend .

# Run container
docker run -p 8080:8080 --env-file .env studify-backend

# Or use docker-compose
docker-compose up --build
```

### Test Health Check
```powershell
# Wait a bit for container to start, then test
Start-Sleep -Seconds 10
curl http://localhost:8080/api/health
```

## Deployment Commands

### Push to Render Branch
```powershell
git checkout deploy/render
git add .
git commit -m "Deploy configuration updates"
git push origin deploy/render
```

### Generate JWT Secret
```powershell
php artisan jwt:secret --show
```

### Clear All Caches
```powershell
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### Create Optimized Caches
```powershell
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Database Management

### Run Migrations
```bash
# Local
php artisan migrate

# Via Render Shell (in Render dashboard)
php artisan migrate --force
```

### Seed Database
```bash
php artisan db:seed --force
```

### Check Migration Status
```bash
php artisan migrate:status
```

## Monitoring Commands

### Test API Endpoints
```powershell
# Health check
Invoke-WebRequest -Uri "https://studify-backend.onrender.com/api/health"

# With JSON output
(Invoke-WebRequest -Uri "https://studify-backend.onrender.com/api/health").Content | ConvertFrom-Json
```

### Check Response Time
```powershell
$url = "https://studify-backend.onrender.com/api/health"
$response = Measure-Command { Invoke-WebRequest -Uri $url }
Write-Host "Response time: $($response.TotalMilliseconds) ms"
```

## Rollback

### Rollback to Previous Version
In Render Dashboard:
1. Go to your service
2. Click "Manual Deploy"
3. Select previous commit
4. Deploy

### Or via Git
```powershell
# Revert last commit
git revert HEAD
git push origin deploy/render

# Or reset to specific commit
git reset --hard <commit-hash>
git push -f origin deploy/render
```

## Environment Variables Management

### Export Current .env to Render Format
```powershell
# Read .env and format for Render
Get-Content .env | Where-Object { $_ -notmatch '^#' -and $_ -match '=' } | ForEach-Object {
    $key, $value = $_ -split '=', 2
    Write-Host "$key=$value"
}
```

### Validate Required Env Vars
```powershell
$required = @(
    'APP_KEY',
    'DB_HOST',
    'DB_PORT',
    'DB_DATABASE',
    'DB_USERNAME',
    'DB_PASSWORD',
    'JWT_SECRET'
)

foreach ($var in $required) {
    $value = [System.Environment]::GetEnvironmentVariable($var)
    if ([string]::IsNullOrEmpty($value)) {
        Write-Host "⚠️  Missing: $var" -ForegroundColor Yellow
    } else {
        Write-Host "✓ $var is set" -ForegroundColor Green
    }
}
```

## Troubleshooting Commands

### Check Docker Image Size
```powershell
docker images studify-backend
```

### Inspect Running Container
```powershell
# List containers
docker ps

# Get container logs
docker logs <container-id>

# Execute command in container
docker exec -it <container-id> sh

# Check processes in container
docker exec <container-id> ps aux
```

### Test Database Connection from Container
```powershell
docker exec -it <container-id> php artisan migrate:status
```

### Clean Docker Resources
```powershell
# Remove unused images
docker image prune -a

# Remove all stopped containers
docker container prune

# Remove build cache
docker builder prune
```

## Performance Testing

### Load Testing with Apache Bench
```bash
# Install Apache Bench first
# Windows: Download from Apache website
# Or use Render Shell

# 100 requests, 10 concurrent
ab -n 100 -c 10 https://studify-backend.onrender.com/api/health
```

### Simple Stress Test
```powershell
# PowerShell simple load test
1..100 | ForEach-Object -Parallel {
    Invoke-WebRequest -Uri "https://studify-backend.onrender.com/api/health"
} -ThrottleLimit 10
```

## Backup and Restore

### Backup Database (Aiven)
```bash
# From Aiven dashboard or CLI
# Aiven handles automated backups
```

### Export Database
```bash
mysqldump -h YOUR_DB_HOST \
  -P YOUR_DB_PORT \
  -u YOUR_DB_USER \
  -p \
  YOUR_DB_NAME > backup.sql
```

## CI/CD Integration (Optional)

### GitHub Actions Example
Create `.github/workflows/deploy.yml`:
```yaml
name: Deploy to Render

on:
  push:
    branches: [ deploy/render ]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Build Docker image
        run: docker build -t studify-backend .
      - name: Test image
        run: |
          docker run -d -p 8080:8080 studify-backend
          sleep 10
          curl -f http://localhost:8080/api/health || exit 1
```
