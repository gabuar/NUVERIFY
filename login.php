<?php

session_start();

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

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "") {

        $message = "Email is required.";
        $messageType = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "error";

    } elseif ($password === "") {

        $message = "Password is required.";
        $messageType = "error";

    } else {

        $stmt = $conn->prepare(
            "SELECT
                email_id,
                email,
                password,
                student_id,
                expires_at
             FROM registered_emails
             WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {

                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["email_id"];
                $_SESSION["user_email"] = $user["email"];
                $_SESSION["student_id"] = $user["student_id"];

                header("Location: dashboard.php");
                exit();

            } else {

                $message = "Invalid email or password.";
                $messageType = "error";

            }

        } else {

            $message = "Invalid email or password.";
            $messageType = "error";

        }

        $stmt->close();
    }
}

$conn->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>NUVerify Login</title>

</head>

<body>

    <h1>NUVerify</h1>

    <h2>Login</h2>

    <form
        method="POST"
        action="login.php"
    >

        <label for="email">
            Email:
        </label>

        <br>

        <input
            type="email"
            id="email"
            name="email"
            placeholder="example@gmail.com"
            required
        >

        <br><br>

        <label for="password">
            Password:
        </label>

        <br>

        <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter your password"
            required
        >

        <br><br>

        <button type="submit">
            Login
        </button>

    </form>

    <?php if ($message !== ""): ?>

        <p class="<?php echo htmlspecialchars($messageType); ?>">
            <?php echo htmlspecialchars($message); ?>
        </p>

    <?php endif; ?>

    <p>
        Don't have an account?
        <a href="index.php">
            Register
        </a>
    </p>

    <p>
        <a href="welcomepage.php">
            Back
        </a>
    </p>

</body>

</html>
