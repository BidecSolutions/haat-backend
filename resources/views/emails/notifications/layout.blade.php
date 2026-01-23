<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Ma3rood Notification' }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .tagline {
            font-size: 16px;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
            background: white;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #333;
        }
        .message {
            margin-bottom: 20px;
            color: #555;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            color: #666;
            font-size: 14px;
            border-top: 1px solid #e9ecef;
        }
        .footer-links {
            margin-bottom: 15px;
        }
        .footer-links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
        }
        .footer-links a:hover {
            text-decoration: underline;
        }
        .copyright {
            color: #999;
            font-size: 12px;
        }
        @font-face {
            font-family: 'SaudiRial';
            src: url('/fonts/SaudiRial.ttf') format('woff2');
            font-weight: normal;
            font-style: normal;
        }

        .currency {
            font-family: 'SaudiRial', sans-serif;
        }

    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo"><img height="50" width="50" src="http://ma3rood.datainovate.com/backend/public/images/icon.png" alt=""></div>
            <div class="tagline">Find Everything You Need in One Place</div>
        </div>
        
        <div class="content">
            @yield('content')
        </div>
        
        <div class="footer">
            <div class="footer-links">
                <a href="{{ config('app.frontend_url') }}">Home</a>
                <a href="{{ config('app.frontend_url') }}/about">About</a>
                <a href="{{ config('app.frontend_url') }}/contact">Contact</a>
                <a href="{{ config('app.frontend_url') }}/help">Help</a>
            </div>
            <div class="copyright">
                &copy; {{ date('Y') }} Ma3rood. All rights reserved.<br>
                This email was sent to {{ $notifiable->email ?? 'you' }}
            </div>
        </div>
    </div>
</body>
</html>
