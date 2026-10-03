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

$sql = "
    SELECT
        rr.request_id,
        rr.email_id,
        rr.requested_at,
        rr.status,
        rr.reviewed_at,
        rr.admin_note,
        re.email,
        re.student_id,
        re.expires_at
    FROM renewal_requests rr
    INNER JOIN registered_emails re
        ON rr.email_id = re.email_id
    ORDER BY rr.requested_at DESC
";

$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - NUVerify</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
        }

        .header {
            background: #222;
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            margin: 0;
        }

        .logout {
            color: white;
            text-decoration: none;
            background: #dc3545;
            padding: 10px 15px;
            border-radius: 6px;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .welcome {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            overflow-x: auto;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f1f1f1;
        }

        .pending {
            color: #856404;
            font-weight: bold;
        }

        .approved {
            color: #155724;
            font-weight: bold;
        }

        .rejected {
            color: #721c24;
            font-weight: bold;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .approve,
        .reject {
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            color: white;
            cursor: pointer;
        }

        .approve {
            background: #28a745;
        }

        .reject {
            background: #dc3545;
        }

        .no-requests {
            text-align: center;
            padding: 30px;
            color: #666;
        }
    </style>
</head>

<body>

<div class="header">

    <h1>NUVerify Admin Dashboard</h1>

    <a href="admin_logout.php" class="logout">Logout</a>

</div>

<div class="container">

    <div class="welcome">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION["admin_username"]); ?>!</h2>
        <p>Manage student account renewal requests below.</p>
    </div>

    <div class="table-container">

        <h2>Renewal Requests</h2>

        <?php if ($result->num_rows > 0): ?>

            <table>

                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Student ID</th>
                        <th>Email</th>
                        <th>Current Expiration</th>
                        <th>Requested At</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php while ($row = $result->fetch_assoc()): ?>

                    <tr>

                        <td>
                            <?php echo htmlspecialchars($row["request_id"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["student_id"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["email"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["expires_at"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row["requested_at"]); ?>
                        </td>

                        <td class="<?php echo htmlspecialchars($row["status"]); ?>">
                            <?php echo strtoupper(htmlspecialchars($row["status"])); ?>
                        </td>

                        <td>

                            <?php if ($row["status"] === "pending"): ?>

                                <div class="actions">

                                    <form method="POST" action="process_renewal.php">

                                        <input
                                            type="hidden"
                                            name="request_id"
                                            value="<?php echo $row["request_id"]; ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="approve"
                                        >

                                        <button type="submit" class="approve">
                                            Approve
                                        </button>

                                    </form>

                                    <form method="POST" action="process_renewal.php">

                                        <input
                                            type="hidden"
                                            name="request_id"
                                            value="<?php echo $row["request_id"]; ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="reject"
                                        >

                                        <button type="submit" class="reject">
                                            Reject
                                        </button>

                                    </form>

                                </div>

                            <?php else: ?>

                                Already reviewed

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        <?php else: ?>

            <div class="no-requests">
                No renewal requests found.
            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>

<?php
$conn->close();
?>