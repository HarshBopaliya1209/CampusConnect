<?php
session_start();

include("db.php");

if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit();
}

$student_id = (int) $_SESSION['student_id'];
$student_name = $_SESSION['name'] ?? '';
$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_payment'])) {
    $payment_type = trim($_POST['payment_type'] ?? '');
    $amount = trim($_POST['amount'] ?? '');
    $payment_date = trim($_POST['payment_date'] ?? '');
    $status = trim($_POST['status'] ?? '');

    if ($payment_type === '' || $amount === '' || !is_numeric($amount) || $payment_date === '' || $status === '') {
        $_SESSION['payment_message'] = "Please fill in all fields with valid values.";
        $_SESSION['payment_message_type'] = "error";
    } else {
        $sql = "INSERT INTO payments (student_id, payment_type, amount, payment_date, status) VALUES (?, ?, ?, ?, ?);";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                "isdss",
                $student_id,
                $payment_type,
                $amount,
                $payment_date,
                $status
            );

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['payment_message'] = "Payment record added successfully.";
                $_SESSION['payment_message_type'] = "success";
            } else {
                $_SESSION['payment_message'] = "Unable to save payment record. Please try again.";
                $_SESSION['payment_message_type'] = "error";
            }

            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['payment_message'] = "Database error. Please contact your administrator.";
            $_SESSION['payment_message_type'] = "error";
        }
    }

    header("Location: payments.php");
    exit();
}

if (isset($_SESSION['payment_message'])) {
    $message = $_SESSION['payment_message'];
    $message_type = $_SESSION['payment_message_type'];
    unset($_SESSION['payment_message'], $_SESSION['payment_message_type']);
}

$sql = "SELECT payment_id, payment_type, amount, payment_date, status
        FROM payments
        WHERE student_id = ?
        ORDER BY payment_date DESC, payment_id DESC";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = false;
}

$total_paid = 0.0;
$total_pending = 0.0;
$total_failed = 0.0;
$payment_count = 0;
$payments = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $payments[] = $row;
        $payment_count++;

        $status_lower = strtolower(trim($row['status']));
        $amount_value = (float) $row['amount'];

        if ($status_lower === 'paid') {
            $total_paid += $amount_value;
        } elseif ($status_lower === 'pending') {
            $total_pending += $amount_value;
        } elseif ($status_lower === 'failed') {
            $total_failed += $amount_value;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusConnect | Payments</title>

    <link rel="stylesheet" href="assets/css/style.css?v=10">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <style>
        .payments-page {
            min-height: 100vh;
            padding: 40px 30px 60px;
            background: #f4f7fc;
            color: #172033;
        }

        .payments-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 32px;
        }

        .payments-header h1 {
            margin: 0;
            font-size: 34px;
            font-weight: 700;
        }

        .payments-header p {
            margin: 8px 0 0;
            color: #64748b;
            max-width: 620px;
            line-height: 1.8;
        }

        .student-card {
            display: flex;
            align-items: center;
            gap: 16px;
            background: white;
            padding: 18px 24px;
            border-radius: 22px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }

        .student-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 28px;
            font-weight: 700;
            color: white;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
        }

        .student-info strong {
            display: block;
            font-size: 18px;
            margin-bottom: 4px;
            color: #172033;
        }

        .student-info span {
            color: #64748b;
            font-size: 14px;
        }

        .payment-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 34px;
        }

        .stat-card {
            background: white;
            border-radius: 22px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        .stat-card h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #64748b;
        }

        .stat-card strong {
            display: block;
            margin-top: 14px;
            font-size: 30px;
            color: #172033;
        }

        .payments-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 28px;
        }

        .payment-history,
        .payment-form-box {
            background: white;
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        .payment-history h2,
        .payment-form-box h2 {
            margin-top: 0;
            margin-bottom: 18px;
            color: #172033;
            font-size: 24px;
        }

        .payment-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 18px;
        }

        .payment-form-row.full-width {
            grid-column: 1 / -1;
        }

        .payment-form-box label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
        }

        .payment-form-box input,
        .payment-form-box select {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            background: #f8fafc;
        }

        .payment-form-box button {
            width: 100%;
            border: none;
            border-radius: 14px;
            padding: 14px 0;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
        }

        .payment-table th,
        .payment-table td {
            padding: 16px 14px;
            text-align: left;
            border-bottom: 1px solid #eef2ff;
            font-size: 14px;
        }

        .payment-table th {
            color: #64748b;
            font-weight: 600;
        }

        .payment-table tbody tr:hover {
            background: #f8fafc;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 90px;
            padding: 8px 10px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
        }

        .badge-paid {
            background: #ecfdf5;
            color: #047857;
        }

        .badge-pending {
            background: #fef3c7;
            color: #b45309;
        }

        .badge-failed {
            background: #fee2e2;
            color: #b91c1c;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 16px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #d1fae5;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecdd3;
        }

        @media (max-width: 980px) {
            .payments-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .payments-header {
                flex-direction: column;
                align-items: stretch;
            }

            .payment-form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="payments-page">

    <div class="payments-header">
        <div>
            <h1>Payments</h1>
            <p>View your fee and transaction history, track payment status, and add a new payment record for your student account.</p>
        </div>

        <div class="student-card">
            <div class="student-avatar"><?php echo htmlspecialchars(strtoupper(substr($student_name, 0, 1))); ?></div>
            <div class="student-info">
                <strong><?php echo htmlspecialchars($student_name ?: 'Student'); ?></strong>
                <span>ID: <?php echo htmlspecialchars((string) $student_id); ?></span>
            </div>
        </div>
    </div>

    <?php if ($message !== ''): ?>
        <div class="alert <?php echo $message_type === 'success' ? 'alert-success' : 'alert-error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="payment-stats">
        <div class="stat-card">
            <h3>Total Payments</h3>
            <strong><?php echo $payment_count; ?></strong>
        </div>
        <div class="stat-card">
            <h3>Paid Total</h3>
            <strong>₹ <?php echo number_format($total_paid, 2); ?></strong>
        </div>
        <div class="stat-card">
            <h3>Pending / Failed</h3>
            <strong>₹ <?php echo number_format($total_pending + $total_failed, 2); ?></strong>
        </div>
    </div>

    <div class="payments-grid">

        <section class="payment-history">
            <h2>Payment History</h2>

            <?php if (count($payments) === 0): ?>
                <p style="color: #64748b; line-height: 1.8;">No payments have been recorded yet. Use the form to add a payment transaction for your account.</p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table class="payment-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('d M Y', strtotime($payment['payment_date']))); ?></td>
                                <td><?php echo htmlspecialchars($payment['payment_type']); ?></td>
                                <td>₹ <?php echo number_format((float) $payment['amount'], 2); ?></td>
                                <td>
                                    <?php
                                        $status_text = htmlspecialchars($payment['status']);
                                        $status_class = 'badge-paid';
                                        $lower = strtolower(trim($payment['status']));
                                        if ($lower === 'pending') {
                                            $status_class = 'badge-pending';
                                        } elseif ($lower === 'failed') {
                                            $status_class = 'badge-failed';
                                        }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="payment-form-box">
            <h2>Add Payment</h2>

            <form action="payments.php" method="POST" autocomplete="off">
                <div class="payment-form-row">
                    <div>
                        <label for="payment_type">Payment Type</label>
                        <input type="text" id="payment_type" name="payment_type" placeholder="Fee / Tuition / Canteen" required>
                    </div>
                    <div>
                        <label for="amount">Amount</label>
                        <input type="number" id="amount" name="amount" placeholder="0.00" step="0.01" min="0" required>
                    </div>
                </div>

                <div class="payment-form-row">
                    <div>
                        <label for="payment_date">Payment Date</label>
                        <input type="date" id="payment_date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div>
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="Paid">Paid</option>
                            <option value="Pending">Pending</option>
                            <option value="Failed">Failed</option>
                        </select>
                    </div>
                </div>

                <input type="hidden" name="new_payment" value="1">
                <button type="submit">Save Payment</button>
            </form>
        </section>

    </div>
</div>

</body>
</html>
