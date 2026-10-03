<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>NUVerify</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background: white;
            width: 400px;
            padding: 40px;
            text-align: center;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        h1 {
            margin-bottom: 10px;
        }

        p {
            color: #666;
            margin-bottom: 30px;
        }

        .button {
            display: block;
            width: 100%;
            padding: 12px 0;
            margin: 12px 0;
            border-radius: 6px;
            text-decoration: none;
            font-size: 16px;
            box-sizing: border-box;
        }

        .login {
            background: #007bff;
            color: white;
        }

        .register {
            background: #28a745;
            color: white;
        }

        .button:hover {
            opacity: 0.85;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Welcome to NUVerify</h1>

    <p>Smart Student Credential Verification System</p>

    <a href="login.php" class="button login">
        User Login
    </a>

    <a href="index.php" class="button register">
        Register
    </a>

</div>

</body>
</html>