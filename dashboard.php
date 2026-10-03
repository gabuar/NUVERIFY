<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli(
    "127.0.0.1",
    "root",
    "Bapbap12705!",
    "nuverify",
    3306
);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare(
    "SELECT
        email,
        student_id,
        expires_at
     FROM registered_emails
     WHERE email_id = ?"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$user = $result->fetch_assoc();

$stmt->close();
$conn->close();

$expirationTime = strtotime($user["expires_at"]);
$currentTime = time();

if ($expirationTime <= $currentTime) {
    $status = "Expired";
} else {
    $status = "Active";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>NUVerify Dashboard</title>

</head>

<body>

    <h1>NUVerify</h1>

    <h2>Student Dashboard</h2>

    <p>
        Welcome!
    </p>

    <hr>

    <h3>Account Information</h3>

    <p>
        <strong>Student ID:</strong>
        <?php echo htmlspecialchars($user["student_id"]); ?>
    </p>

    <p>
        <strong>Email:</strong>
        <?php echo htmlspecialchars($user["email"]); ?>
    </p>

    <p>
        <strong>Account Status:</strong>
        <?php echo htmlspecialchars($status); ?>
    </p>

    <p>
        <strong>Expires:</strong>
        <?php echo htmlspecialchars($user["expires_at"]); ?>
    </p>

    <hr>

    <?php if ($status === "Active"): ?>

        <h3>Renewal</h3>

        <p>
            Need to extend your account?
        </p>

        <a href="renewal.php">
            <button type="button">
                Request Renewal
            </button>
        </a>

    <?php else: ?>

        <h3>Your account has expired.</h3>

        <a href="renewal.php">
            <button type="button">
                Request Renewal
            </button>
        </a>

    <?php endif; ?>

    <br><br>

    <a href="logout.php">
        <button type="button">
            Logout
        </button>
    </a>

</body>

</html>
