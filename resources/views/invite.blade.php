<!DOCTYPE html>
<html>
<head>
    <title>Join Class - Studify</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify_content: center;
            height: 100vh;
            margin: 0;
            background-color: #FAFAFA; /* AppColor.backgroundPrimary */
            text-align: center;
        }
        .container {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-width: 90%;
            width: 400px;
        }
        h1 { color: #141458; margin-bottom: 0.5rem; } /* AppColor.primary */
        p { color: #777777; margin-bottom: 2rem; } /* AppColor.textSecondary */
        .btn {
            display: inline-block;
            background-color: #141458; /* AppColor.primary */
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 12px; /* Rounded, not capsule */
            font-weight: 600;
            transition: background-color 0.2s;
            font-size: 1.2rem;
            vertical-align: middle;
        }
        .btn:hover { background-color: #0f0f42; } /* Darker shade of primary */
        .code {
            font-family: monospace;
            font-size: 1.5rem;
            background: #5CD9C1; /* AppColor.secondary */
            padding: 10px 20px;
            border-radius: 12px; /* Rounded, not capsule */
            color: #141458; /* AppColor.textPrimary */
            display: inline-block;
            font-weight: bold;
            vertical-align: middle;
        }
        .action-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Join Class</h1>
        <p>You've been invited to join a class on Studify.</p>
        
        <div class="action-container">
            <div class="code">{{ $code }}</div>
            <a href="{{ $deepLink }}" class="btn" id="joinBtn">Open in App</a>
        </div>
        
        <p style="font-size: 0.8rem; margin-top: 2rem;">
            If the app doesn't open automatically, click the button above.
            <br><br>
            Don't have the app? <a href="{{ $fallbackUrl }}">Download here</a>
        </p>
    </div>

    <script>
        // Attempt to auto-redirect
        window.onload = function() {
            window.location.href = "{{ $deepLink }}";
        };
    </script>
</body>
</html>