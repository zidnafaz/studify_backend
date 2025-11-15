# 🔐 SECURITY NOTICE - Credentials Management

## ⚠️ IMPORTANT: Never Commit Real Credentials to Git

### Files That Should NEVER Be Committed
- `.env` (local development)
- `.env.production` (production secrets)
- Any file containing:
  - Database passwords
  - API keys
  - JWT secrets
  - OAuth credentials
  - Service passwords

### ✅ Safe to Commit
- `.env.example` - Template dengan placeholder values
- Documentation dengan placeholder credentials
- Configuration files tanpa secrets

---

## 📋 Setup Credentials di Render

### 1. Get Your Database Credentials
Login ke Aiven Dashboard dan copy:
- `DB_HOST` - MySQL host address
- `DB_PORT` - MySQL port number
- `DB_DATABASE` - Database name
- `DB_USERNAME` - Database username
- `DB_PASSWORD` - Database password

### 2. Set Environment Variables di Render
Go to: Render Dashboard → Your Service → Environment

**Add each variable individually:**

```
APP_NAME=Studify
APP_ENV=production
APP_KEY=base64:your-app-key-from-local-env
APP_DEBUG=false
APP_URL=https://your-app.onrender.com

DB_CONNECTION=mysql
DB_HOST=<COPY FROM AIVEN>
DB_PORT=<COPY FROM AIVEN>
DB_DATABASE=<COPY FROM AIVEN>
DB_USERNAME=<COPY FROM AIVEN>
DB_PASSWORD=<COPY FROM AIVEN>

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
LOG_LEVEL=error

JWT_SECRET=<GENERATE NEW: php artisan jwt:secret --show>
```

### 3. Never Use These Values
❌ DO NOT use example/placeholder values from documentation:
- `your-database-name`
- `your-username`
- `your-password`
- `YOUR_DB_HOST`

✅ Always use REAL values from your Aiven dashboard

---

## 🔒 Best Practices

### For Development (.env file)
```bash
# Copy example
cp .env.example .env

# Edit with your LOCAL database credentials
# NEVER commit this file
```

### For Production (Render Environment)
1. Set variables directly in Render Dashboard
2. Use Render's "Secret Files" feature for sensitive configs
3. Never hardcode credentials in code
4. Use environment variables: `env('DB_PASSWORD')`

### For Documentation
✅ Use placeholders:
```
DB_PASSWORD=your-password-from-aiven
DB_HOST=your-host.aivencloud.com
```

❌ Never use actual values:
```
DB_PASSWORD=actual-real-password-123  # DON'T DO THIS!
DB_HOST=real-host.aivencloud.com       # DON'T DO THIS!
```

---

## 🚨 If You Accidentally Committed Secrets

### Step 1: Rotate Credentials Immediately
1. Go to Aiven Dashboard
2. Reset database password
3. Update password in Render environment variables

### Step 2: Remove from Git History
```powershell
# If just committed (not pushed)
git reset --soft HEAD~1
git restore --staged <file-with-secrets>

# If already pushed - need to clean history
# Consider using git-filter-repo or BFG Repo Cleaner
# Or create new repository if feasible
```

### Step 3: Update .gitignore
Ensure these are in `.gitignore`:
```
.env
.env.*
!.env.example
*.key
*.pem
secrets/
```

---

## 📝 Credential Checklist

Before committing:
- [ ] No passwords in any files
- [ ] No API keys exposed
- [ ] `.env` is gitignored
- [ ] Documentation uses placeholders
- [ ] No hardcoded credentials in code
- [ ] Render environment variables set correctly

Before deploying:
- [ ] All real credentials set in Render Dashboard
- [ ] Database password is correct
- [ ] APP_KEY is generated and set
- [ ] JWT_SECRET is generated and set
- [ ] No test/example credentials used

---

## 🔐 Password Generation

### Generate Strong Passwords
```powershell
# Generate random password (PowerShell)
-join ((65..90) + (97..122) + (48..57) | Get-Random -Count 32 | ForEach-Object {[char]$_})

# Or use online generators:
# https://passwordsgenerator.net/
```

### Generate Laravel APP_KEY
```bash
php artisan key:generate --show
```

### Generate JWT Secret
```bash
php artisan jwt:secret --show
```

---

## 📞 If Credentials Are Compromised

1. **Immediately** rotate all affected credentials
2. Check Aiven logs for unauthorized access
3. Review Render deployment logs
4. Update all environment variables
5. Monitor for suspicious activity
6. Consider enabling 2FA on all services

---

## ✅ This Repository is Now Safe

All documentation files have been cleaned of sensitive credentials.

### What was removed:
- Actual database passwords
- Real database hostnames
- Specific port numbers
- Real database names
- Real usernames

### What remains:
- Placeholder examples for documentation
- Safe configuration templates
- Instructions to get real values from Aiven

**Remember: Credentials belong in Render Environment Variables, NOT in Git!**
