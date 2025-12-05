<!DOCTYPE html>
<html>

<head>
    <title>Download Studify</title>
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
            background-color: #FAFAFA;
            /* AppColor.backgroundPrimary */
            text-align: center;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 100%;
            width: 400px;
        }

        h1 {
            color: #141458;
            margin-bottom: 0.5rem;
        }

        /* AppColor.primary */
        p {
            color: #777777;
            margin-bottom: 2rem;
        }

        /* AppColor.textSecondary */
        .btn {
            display: inline-block;
            background-color: #141458;
            /* AppColor.primary */
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 24px;
            font-weight: 600;
            transition: background-color 0.2s;
            margin-bottom: 1rem;
        }

        .btn:hover {
            background-color: #0f0f42;
        }

        /* Darker shade of primary */
        .note {
            font-size: 0.8rem;
            color: #888;
            margin-top: 1rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Download Studify</h1>
        <p>The app is currently in development.</p>

        <!-- You can replace this href with a direct link to your APK file in public folder or Google Drive -->
        <a href="#" class="btn" onclick="alert('Please ask the developer for the APK file!'); return false;">Download
            APK</a>

        <p class="note">
            Since the app is not on Play Store yet, you need to install the APK manually.
        </p>
    </div>
</body>

</html>