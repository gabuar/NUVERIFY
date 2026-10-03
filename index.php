<?php

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

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["register"])) {

    $student_id = trim($_POST["student_id"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    // =========================
    // STUDENT ID VALIDATION
    // =========================

    if ($student_id === "") {

        $message = "Student ID is required.";
        $messageType = "error";

    } elseif (!preg_match('/^\d{4}-\d{7}$/', $student_id)) {

        $message = "Invalid Student ID format. Use: 2024-1009043";
        $messageType = "error";

    }

    // =========================
    // COR VALIDATION
    // =========================

    elseif (
        !isset($_FILES["cor"]) ||
        $_FILES["cor"]["error"] !== UPLOAD_ERR_OK
    ) {

        $message = "Please upload a picture of your COR.";
        $messageType = "error";

    } else {

        $cor = $_FILES["cor"];

        // Maximum size: 5 MB
        if ($cor["size"] > 5 * 1024 * 1024) {

            $message = "COR image must be 5 MB or smaller.";
            $messageType = "error";

        } else {

            // Check actual file type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $cor["tmp_name"]);
            finfo_close($finfo);

            $allowedTypes = [
                "image/jpeg" => "jpg",
                "image/png"  => "png",
                "image/webp" => "webp"
            ];

            if (!isset($allowedTypes[$mimeType])) {

                $message = "COR must be a JPG, PNG, or WEBP image.";
                $messageType = "error";

            } elseif (getimagesize($cor["tmp_name"]) === false) {

                $message = "The uploaded file is not a valid image.";
                $messageType = "error";

            }

            // =========================
            // EMAIL VALIDATION
            // =========================

            elseif ($email === "") {

                $message = "Personal email is required.";
                $messageType = "error";

            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

                $message = "Please enter a valid email address.";
                $messageType = "error";

            }

            // =========================
            // PASSWORD VALIDATION
            // =========================

            elseif ($password === "") {

                $message = "Password is required.";
                $messageType = "error";

            } elseif (strlen($password) < 8) {

                $message = "Password must be at least 8 characters.";
                $messageType = "error";

            } else {

                // =========================
                // CHECK STUDENT ID
                // =========================

                $stmt = $conn->prepare(
                    "SELECT email_id
                     FROM registered_emails
                     WHERE student_id = ?"
                );

                $stmt->bind_param("s", $student_id);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {

                    $message = "Student ID is already registered.";
                    $messageType = "error";

                    $stmt->close();

                } else {

                    $stmt->close();

                    // =========================
                    // CHECK EMAIL
                    // =========================

                    $stmt = $conn->prepare(
                        "SELECT email_id
                         FROM registered_emails
                         WHERE email = ?"
                    );

                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $stmt->store_result();

                    if ($stmt->num_rows > 0) {

                        $message = "Email is already registered.";
                        $messageType = "error";

                        $stmt->close();

                    } else {

                        $stmt->close();

                        // =========================
                        // HASH PASSWORD
                        // =========================

                        $hashedPassword = password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                        // =========================
                        // SAVE COR
                        // =========================

                        $uploadDirectory = __DIR__ . "/uploads/cor/";

                        if (!is_dir($uploadDirectory)) {
                            mkdir($uploadDirectory, 0777, true);
                        }

                        $extension = $allowedTypes[$mimeType];

                        $filename =
                            date("YmdHis") . "_" .
                            bin2hex(random_bytes(8)) . "." .
                            $extension;

                        $filePath = $uploadDirectory . $filename;

                        $databasePath = "uploads/cor/" . $filename;

                        if (!move_uploaded_file(
                            $cor["tmp_name"],
                            $filePath
                        )) {

                            $message = "Failed to upload COR.";
                            $messageType = "error";

                        } else {

                            // =========================
                            // INSERT USER
                            // =========================
                            // Account expires 30 days
                            // after registration.

                            $stmt = $conn->prepare(
                                "INSERT INTO registered_emails
                                (
                                    student_id,
                                    cor_image,
                                    email,
                                    password,
                                    registered_at,
                                    expires_at
                                )
                                VALUES
                                (
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    NOW(),
                                    DATE_ADD(NOW(), INTERVAL 30 DAY)
                                )"
                            );

                            $stmt->bind_param(
                                "ssss",
                                $student_id,
                                $databasePath,
                                $email,
                                $hashedPassword
                            );

                            if ($stmt->execute()) {

                                $message = "Registration successful!";
                                $messageType = "success";

                            } else {

                                // Remove COR if database insert fails
                                if (file_exists($filePath)) {
                                    unlink($filePath);
                                }

                                $message =
                                    "Registration failed: " .
                                    $stmt->error;

                                $messageType = "error";
                            }

                            $stmt->close();
                        }
                    }
                }
            }
        }
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

    <title>NUVerify Registration Test</title>

</head>

<body>

<h1>NUVerify Registration Test</h1>

<form
    method="POST"
    enctype="multipart/form-data"
>

    <!-- =========================
         STEP 1: STUDENT ID
         ========================= -->

    <label for="student_id">
        Student ID:
    </label>

    <input
        type="text"
        id="student_id"
        name="student_id"
        placeholder="2024-1009043"
        maxlength="12"
        required
        autocomplete="off"
    >

    <p id="studentStatus"></p>

    <br>


    <!-- =========================
         STEP 2: COR
         ========================= -->

    <p>
        Please upload a picture of your COR.
    </p>

    <label for="cor">
        COR Picture:
    </label>

    <br><br>

    <input
        type="file"
        id="cor"
        name="cor"
        accept=".jpg,.jpeg,.png,.webp"
        disabled
        required
    >

    <p id="corStatus"></p>

    <br>


    <!-- =========================
         STEP 3: EMAIL
         ========================= -->

    <label for="email">
        Personal Email:
    </label>

    <br><br>

    <input
        type="email"
        id="email"
        name="email"
        placeholder="example@gmail.com"
        disabled
        required
    >

    <p id="emailStatus"></p>

    <br>


    <!-- =========================
         STEP 4: PASSWORD
         ========================= -->

    <label for="password">
        Password:
    </label>

    <br><br>

    <input
        type="password"
        id="password"
        name="password"
        placeholder="Enter your password"
        minlength="8"
        disabled
        required
    >

    <p>
        Password must be at least 8 characters.
    </p>

    <br>


    <!-- =========================
         REGISTER
         ========================= -->

    <button
        type="submit"
        name="register"
        id="register"
        disabled
    >
        Register
    </button>

</form>


<?php if ($message !== ""): ?>

    <p class="<?php echo htmlspecialchars($messageType); ?>">
        <?php echo htmlspecialchars($message); ?>
    </p>

<?php endif; ?>


<script>

// =========================
// GET ELEMENTS
// =========================

const studentId =
    document.getElementById("student_id");

const cor =
    document.getElementById("cor");

const email =
    document.getElementById("email");

const password =
    document.getElementById("password");

const register =
    document.getElementById("register");

const studentStatus =
    document.getElementById("studentStatus");

const corStatus =
    document.getElementById("corStatus");

const emailStatus =
    document.getElementById("emailStatus");


// =========================
// STUDENT ID
// =========================

studentId.addEventListener("input", function () {

    const value = studentId.value.trim();

    const valid =
        /^\d{4}-\d{7}$/.test(value);

    if (valid) {

        studentStatus.textContent =
            "Valid Student ID format.";

        cor.disabled = false;

    } else {

        studentStatus.textContent =
            "Student ID must follow: 2024-1009043";

        cor.disabled = true;
        cor.value = "";

        email.disabled = true;
        email.value = "";

        password.disabled = true;
        password.value = "";

        register.disabled = true;

        corStatus.textContent = "";
        emailStatus.textContent = "";
    }

});


// =========================
// COR
// =========================

cor.addEventListener("change", function () {

    if (cor.files.length === 0) {

        email.disabled = true;
        password.disabled = true;
        register.disabled = true;

        corStatus.textContent = "";

        return;
    }

    const file = cor.files[0];

    const allowedTypes = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];

    const maxSize =
        5 * 1024 * 1024;


    // Check file type

    if (!allowedTypes.includes(file.type)) {

        corStatus.textContent =
            "COR must be JPG, PNG, or WEBP.";

        cor.value = "";

        email.disabled = true;
        password.disabled = true;
        register.disabled = true;

        return;
    }


    // Check file size

    if (file.size > maxSize) {

        corStatus.textContent =
            "COR image must be 5 MB or smaller.";

        cor.value = "";

        email.disabled = true;
        password.disabled = true;
        register.disabled = true;

        return;
    }


    // Valid COR

    corStatus.textContent =
        "COR uploaded successfully.";

    email.disabled = false;

});


// =========================
// EMAIL
// =========================

email.addEventListener("input", function () {

    if (
        email.value.trim() !== "" &&
        email.checkValidity()
    ) {

        emailStatus.textContent =
            "Valid email.";

        password.disabled = false;

    } else {

        emailStatus.textContent =
            "Please enter a valid email.";

        password.disabled = true;
        password.value = "";

        register.disabled = true;
    }

});


// =========================
// PASSWORD
// =========================

password.addEventListener("input", function () {

    if (password.value.length >= 8) {

        register.disabled = false;

    } else {

        register.disabled = true;

    }

});

</script>

</body>

</html>