<!DOCTYPE html>
<html>

<head>
    <title>{{ __('messages.download_title') }}</title>
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
        <h1>{{ __('messages.download_heading') }}</h1>
        <p>{{ __('messages.app_in_development') }}</p>

        <!-- You can replace this href with a direct link to your APK file in public folder or Google Drive -->
        <a href="{{ asset('studify.apk') }}" class="btn" download>{{ __('messages.download_apk') }}</a>

        <p class="note">
            {{ __('messages.manual_install_note') }}
        </p>
    </div>
</body>

</html>
