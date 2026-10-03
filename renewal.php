<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("127.0.0.1", "root", "Bapbap12705!", "nuverify", 3306);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$userId = $_SESSION["user_id"];

$stmt = $conn->prepare("
    SELECT email_id, email, student_id, expires_at
    FROM registered_emails
    WHERE email_id = ?
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $checkStmt = $conn->prepare("
        SELECT request_id
        FROM renewal_requests
        WHERE email_id = ?
        AND status = 'pending'
        LIMIT 1
    ");

    $checkStmt->bind_param("i", $userId);
    $checkStmt->execute();

    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        $message = "You already have a pending renewal request.";
        $messageType = "error";
    } else {

        $insertStmt = $conn->prepare("
            INSERT INTO renewal_requests (email_id, requested_at, status)
            VALUES (?, NOW(), 'pending')
        ");

        $insertStmt->bind_param("i", $userId);

        if ($insertStmt->execute()) {
            $message = "Renewal request submitted successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to submit renewal request.";
            $messageType = "error";
        }

        $insertStmt->close();
    }

    $checkStmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Renewal - NUVerify</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 40px;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        h1 {
            margin-top: 0;
        }

        .info {
            background: #f1f3f5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info p {
            margin: 8px 0;
        }

        .message {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background: #007bff;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #0056b3;
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 15px;
            text-decoration: none;
            color: #333;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Request Account Renewal</h1>

    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="info">
        <p><strong>Student ID:</strong>
            <?php echo htmlspecialchars($user["student_id"]); ?>
        </p>

        <p><strong>Email:</strong>
            <?php echo htmlspecialchars($user["email"]); ?>
        </p>

        <p><strong>Current Expiration:</strong>
            <?php echo htmlspecialchars($user["expires_at"]); ?>
        </p>
    </div>

    <form method="POST">
        <button type="submit">Submit Renewal Request</button>
    </form>

    <a class="back" href="dashboard.php">← Back to Dashboard</a>

</div>

</body>
</html>