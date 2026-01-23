<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hello Saudi Rial Font</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f6fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            flex-direction: column;
        }

        @font-face {
            font-family: 'SaudiRial';
            /* ensure the font file is located at public/fonts/SaudiRial.ttf */
            src: url("{{ asset('fonts/SaudiRial.ttf') }}") format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        .currency {
            font-family: 'SaudiRial', sans-serif;
            font-size: 64px;
            color: #2d3436;
        }

        h1 {
            color: #333;
            font-weight: normal;
        }
    </style>
</head>
<body>
    <h1>Hello from Laravel 👋</h1>
    <div class="currency">$</div>
    <img src="http://ma3rood.datainovate.com/backend/public/images/RialSignn.png" 
     alt="SAR" 
     width="14" 
     height="14" 
     style="vertical-align:middle;"> 
    <p>This dollar sign should appear as your custom Saudi Riyal symbol if the font is loaded correctly.</p>
</body>
</html>
