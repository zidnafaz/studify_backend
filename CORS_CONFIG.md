# CORS Configuration (Optional)

Jika frontend Flutter Web atau aplikasi lain memerlukan akses CORS:

## Install Laravel CORS Package

Laravel 12 sudah include HandleCors middleware by default, tapi jika perlu konfigurasi custom:

### 1. Publish CORS Config (Opsional)
```bash
php artisan config:publish cors
```

### 2. Update `config/cors.php`
```php
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    
    'allowed_methods' => ['*'],
    
    'allowed_origins' => [
        'http://localhost:3000',
        'https://your-flutter-web-app.com',
        // Add your frontend domains
    ],
    
    'allowed_origins_patterns' => [],
    
    'allowed_headers' => ['*'],
    
    'exposed_headers' => [],
    
    'max_age' => 0,
    
    'supports_credentials' => true,
];
```

### 3. Or Allow All Origins (Development Only)
```php
'allowed_origins' => ['*'],
'supports_credentials' => false,
```

## For Production

### Render Environment Variables
Add to Render environment:
```bash
FRONTEND_URL=https://your-app.com
```

### Update CORS Config
```php
'allowed_origins' => [
    env('FRONTEND_URL', 'http://localhost:3000'),
],
```

## Testing CORS

### Test with cURL
```bash
curl -H "Origin: http://localhost:3000" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: X-Requested-With" \
  -X OPTIONS \
  https://studify-backend.onrender.com/api/auth/login -v
```

Should return:
```
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Methods: POST, GET, OPTIONS...
```

## Flutter App Configuration

### Update API Client
```dart
// lib/core/services/api_client.dart
import 'package:dio/dio.dart';

class ApiClient {
  static const String baseUrl = 'https://studify-backend.onrender.com/api';
  
  late Dio dio;
  
  ApiClient() {
    dio = Dio(BaseOptions(
      baseUrl: baseUrl,
      connectTimeout: const Duration(seconds: 30),
      receiveTimeout: const Duration(seconds: 30),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      // Important for CORS
      validateStatus: (status) {
        return status! < 500;
      },
    ));
    
    // Add interceptors for auth token
    dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        // Add token from storage
        final token = await getToken();
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        return handler.next(options);
      },
    ));
  }
}
```

## Common CORS Issues

### Issue: "CORS policy: No 'Access-Control-Allow-Origin' header"
**Solution:** 
- Ensure CORS middleware is active
- Check `allowed_origins` includes your domain
- Verify API is accessible

### Issue: "preflight request doesn't pass"
**Solution:**
- Ensure OPTIONS method allowed
- Check `allowed_headers` includes required headers
- Verify `allowed_methods` includes your HTTP method

### Issue: Credentials not working
**Solution:**
- Set `supports_credentials: true`
- Don't use `allowed_origins: ['*']` with credentials
- Specify exact origins

## Notes

- By default, Laravel handles CORS for `/api/*` routes
- Make sure frontend URL is whitelisted in production
- Use specific origins instead of `*` in production
- Test CORS before deploying frontend changes
