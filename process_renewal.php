<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin_login.php");
    exit();
}

$conn = new mysqli("127.0.0.1", "root", "Bapbap12705!", "nuverify", 3306);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin_dashboard.php");
    exit();
}

$requestId = isset($_POST["request_id"]) ? (int)$_POST["request_id"] : 0;
$action = $_POST["action"] ?? "";

if ($requestId <= 0 || !in_array($action, ["approve", "reject"])) {
    header("Location: admin_dashboard.php");
    exit();
}

$conn->begin_transaction();

try {

    $stmt = $conn->prepare("
        SELECT request_id, email_id, status
        FROM renewal_requests
        WHERE request_id = ?
        FOR UPDATE
    ");

    $stmt->bind_param("i", $requestId);
    $stmt->execute();

    $result = $stmt->get_result();
    $request = $result->fetch_assoc();

    $stmt->close();

    if (!$request) {
        throw new Exception("Renewal request not found.");
    }

    if ($request["status"] !== "pending") {
        throw new Exception("This renewal request has already been reviewed.");
    }

    $emailId = (int)$request["email_id"];

    if ($action === "approve") {

        $updateAccount = $conn->prepare("
            UPDATE registered_emails
            SET expires_at = DATE_ADD(expires_at, INTERVAL 30 DAY)
            WHERE email_id = ?
        ");

        $updateAccount->bind_param("i", $emailId);

        if (!$updateAccount->execute() || $updateAccount->affected_rows !== 1) {
            throw new Exception("Failed to extend the account expiration.");
        }

        $updateAccount->close();

        $updateRequest = $conn->prepare("
            UPDATE renewal_requests
            SET
                status = 'approved',
                reviewed_at = NOW()
            WHERE request_id = ?
            AND status = 'pending'
        ");

        $updateRequest->bind_param("i", $requestId);

        if (!$updateRequest->execute() || $updateRequest->affected_rows !== 1) {
            throw new Exception("Failed to approve the renewal request.");
        }

        $updateRequest->close();

    } else {

        $updateRequest = $conn->prepare("
            UPDATE renewal_requests
            SET
                status = 'rejected',
                reviewed_at = NOW()
            WHERE request_id = ?
            AND status = 'pending'
        ");

        $updateRequest->bind_param("i", $requestId);

        if (!$updateRequest->execute() || $updateRequest->affected_rows !== 1) {
            throw new Exception("Failed to reject the renewal request.");
        }

        $updateRequest->close();
    }

    $conn->commit();

    header("Location: admin_dashboard.php");
    exit();

} catch (Exception $e) {

    $conn->rollback();

    die("Renewal processing failed: " . htmlspecialchars($e->getMessage()));
}

$conn->close();
?>