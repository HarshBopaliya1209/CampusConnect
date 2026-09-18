<?php

session_start();

include("db.php");

$message = "";
$message_type = "";


/* =========================
   CHECK LOGIN
========================= */

if (!isset($_SESSION['faculty_id'])) {
    header("Location: faculty_login.php");
    exit();
}


/* =========================
   GET LIBRARIAN
========================= */

$faculty_id = $_SESSION['faculty_id'];

$sql = "
    SELECT
        faculty_id,
        name,
        department,
        designation
    FROM faculty
    WHERE faculty_id = ?
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "s",
    $faculty_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$librarian = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================
   CHECK LIBRARIAN
========================= */

if (
    !$librarian ||
    strtolower(trim($librarian['designation'])) != "librarian"
) {
    header("Location: faculty_dashboard.php");
    exit();
}


/* =========================
   APPROVE / REJECT
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $action = $_POST['action'] ?? "";
    $request_id = intval($_POST['request_id'] ?? 0);


    if ($request_id <= 0) {

        $message = "Invalid request.";
        $message_type = "error";

    } elseif ($action == "approve") {

        /* Get request */

        $sql = "
            SELECT
                request_id,
                book_id,
                status
            FROM library_requests
            WHERE request_id = ?
        ";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $request_id
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $request = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);


        if (!$request) {

            $message = "Request not found.";
            $message_type = "error";

        } elseif ($request['status'] != "Pending") {

            $message = "This request has already been processed.";
            $message_type = "error";

        } else {

            /* Check book quantity */

            $sql = "
                SELECT quantity, status
                FROM library
                WHERE book_id = ?
            ";

            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $request['book_id']
            );

            mysqli_stmt_execute($stmt);

            $book_result = mysqli_stmt_get_result($stmt);

            $book = mysqli_fetch_assoc($book_result);

            mysqli_stmt_close($stmt);


            if (!$book) {

                $message = "Book not found.";
                $message_type = "error";

            } elseif (
                intval($book['quantity']) <= 0 ||
                !in_array(
                    strtolower(trim($book['status'])),
                    ["available", "active"]
                )
            ) {

                $message =
                    "This book is currently unavailable.";

                $message_type = "error";

            } else {

                /*
                 * Reduce quantity by 1
                 */

                $sql = "
                    UPDATE library
                    SET quantity = quantity - 1
                    WHERE book_id = ?
                    AND quantity > 0
                ";

                $stmt = mysqli_prepare($conn, $sql);

                mysqli_stmt_bind_param(
                    $stmt,
                    "i",
                    $request['book_id']
                );

                mysqli_stmt_execute($stmt);

                $changed = mysqli_stmt_affected_rows($stmt);

                mysqli_stmt_close($stmt);


                if ($changed > 0) {

                    /* Update request */

                    $sql = "
                        UPDATE library_requests
                        SET status = 'Approved'
                        WHERE request_id = ?
                    ";

                    $stmt = mysqli_prepare(
                        $conn,
                        $sql
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "i",
                        $request_id
                    );

                    mysqli_stmt_execute($stmt);

                    mysqli_stmt_close($stmt);


                    /* Update book status */

                    $sql = "
                        UPDATE library
                        SET status =
                            CASE
                                WHEN quantity > 0
                                THEN 'Available'
                                ELSE 'Unavailable'
                            END
                        WHERE book_id = ?
                    ";

                    $stmt = mysqli_prepare(
                        $conn,
                        $sql
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "i",
                        $request['book_id']
                    );

                    mysqli_stmt_execute($stmt);

                    mysqli_stmt_close($stmt);


                    $message =
                        "Book request approved successfully.";

                    $message_type = "success";

                } else {

                    $message =
                        "Unable to approve this request.";

                    $message_type = "error";
                }
            }
        }

    } elseif ($action == "reject") {

        $sql = "
            UPDATE library_requests
            SET status = 'Rejected'
            WHERE request_id = ?
            AND status = 'Pending'
        ";

        $stmt = mysqli_prepare(
            $conn,
            $sql
        );

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $request_id
        );

        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) > 0) {

            $message =
                "Book request rejected.";

            $message_type = "success";

        } else {

            $message =
                "Request was already processed.";

            $message_type = "error";
        }

        mysqli_stmt_close($stmt);
    }
}


/* =========================
   FILTER
========================= */

$status_filter = $_GET['status'] ?? "pending";


if ($status_filter == "all") {

    $sql = "
        SELECT
            lr.request_id,
            lr.student_id,
            lr.book_id,
            lr.status,
            lr.request_date,
            l.book_name,
            l.author
        FROM library_requests lr
        LEFT JOIN library l
            ON lr.book_id = l.book_id
        ORDER BY lr.request_id DESC
    ";

    $requests = mysqli_query(
        $conn,
        $sql
    );

} else {

    $sql = "
        SELECT
            lr.request_id,
            lr.student_id,
            lr.book_id,
            lr.status,
            lr.request_date,
            l.book_name,
            l.author
        FROM library_requests lr
        LEFT JOIN library l
            ON lr.book_id = l.book_id
        WHERE lr.status = 'Pending'
        ORDER BY lr.request_id DESC
    ";

    $requests = mysqli_query(
        $conn,
        $sql
    );
}


/* =========================
   COUNTS
========================= */

$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;

$sql = "
    SELECT
        SUM(status = 'Pending') AS pending,
        SUM(status = 'Approved') AS approved,
        SUM(status = 'Rejected') AS rejected
    FROM library_requests
";

$result = mysqli_query(
    $conn,
    $sql
);

if ($result) {

    $data = mysqli_fetch_assoc($result);

    $pending_count =
        intval($data['pending'] ?? 0);

    $approved_count =
        intval($data['approved'] ?? 0);

    $rejected_count =
        intval($data['rejected'] ?? 0);
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

    <title>
        CampusConnect | Book Requests
    </title>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Inter, sans-serif;
            background: #212325;
            color: #d3dbe6;
        }

        .header {
            background: linear-gradient(
                135deg,
                #3c70e3,
                #554ce6
            );

            color: #ffffff;
            padding: 30px 50px;

            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            margin: 0;
            font-size: 29px;
        }

        .header p {
            margin: 7px 0 0;
            opacity: .9;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #262626;
            color: #5586f1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .profile span {
            display: block;
            font-size: 12px;
            opacity: .8;
        }

        .container {
            padding: 35px 50px;
        }

        .back {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            color: #5586f1;
            text-decoration: none;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .message {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .success {
            background: #1c201e;
            color: #aeedc6;
        }

        .error {
            background: #221e1e;
            color: #cb1a1a;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat {
            background: #262626;
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 5px 20px rgba(0,0,0,.06);
        }

        .stat span {
            color: #909daf;
            font-size: 13px;
        }

        .stat h2 {
            margin: 5px 0 0;
            font-size: 28px;
        }

        .pending {
            color: #ef793c;
        }

        .approved {
            color: #13d85c;
        }

        .rejected {
            color: #eb4d4d;
        }

        .card {
            background: #262626;
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 5px 20px rgba(0,0,0,.06);
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }

        .tab {
            padding: 10px 18px;
            border-radius: 9px;
            background: #202224;
            text-decoration: none;
            color: #4b6c9b;
            font-size: 14px;
        }

        .tab.active {
            background: #3c70e3;
            color: #ffffff;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        th {
            text-align: left;
            background: #222426;
            padding: 14px;
            font-size: 13px;
            color: #4b6c9b;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #1e2022;
            font-size: 14px;
        }

        .status {
            padding: 5px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending {
            background: #1f1e1c;
            color: #ec5415;
        }

        .status-approved {
            background: #1c201e;
            color: #aeedc6;
        }

        .status-rejected {
            background: #221e1e;
            color: #cb1a1a;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .approve,
        .reject {
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            color: #ffffff;
            cursor: pointer;
            font-family: inherit;
            font-size: 12px;
            font-weight: 600;
        }

        .approve {
            background: #19af50;
        }

        .reject {
            background: #e13030;
        }

        .empty {
            text-align: center;
            padding: 55px 20px;
            color: #909daf;
        }

        .empty i {
            font-size: 45px;
            margin-bottom: 15px;
        }

        @media (max-width: 800px) {

            .header {
                padding: 25px;
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }

            .container {
                padding: 25px;
            }

            .stats {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>


<header class="header">

    <div>

        <h1>
            <i class="fa-solid fa-list-check"></i>
            Book Requests
        </h1>

        <p>
            Review and manage student library requests.
        </p>

    </div>

    <div class="profile">

        <div class="avatar">

            <?php

            echo strtoupper(
                substr($librarian['name'], 0, 1)
            );

            ?>

        </div>

        <div>

            <strong>
                <?php
                echo htmlspecialchars(
                    $librarian['name']
                );
                ?>
            </strong>

            <span>
                Librarian
            </span>

        </div>

    </div>

</header>


<main class="container">


<a
    href="librarian_dashboard.php"
    class="back"
>

    <i class="fa-solid fa-arrow-left"></i>

    Back to Dashboard

</a>


<?php if ($message != "") { ?>

    <div class="message <?php echo $message_type; ?>">

        <i class="fa-solid
            <?php
            echo $message_type == "success"
                ? "fa-circle-check"
                : "fa-circle-exclamation";
            ?>">
        </i>

        <?php
        echo htmlspecialchars($message);
        ?>

    </div>

<?php } ?>


<div class="stats">

    <div class="stat">

        <span>
            Pending Requests
        </span>

        <h2 class="pending">
            <?php echo $pending_count; ?>
        </h2>

    </div>

    <div class="stat">

        <span>
            Approved Requests
        </span>

        <h2 class="approved">
            <?php echo $approved_count; ?>
        </h2>

    </div>

    <div class="stat">

        <span>
            Rejected Requests
        </span>

        <h2 class="rejected">
            <?php echo $rejected_count; ?>
        </h2>

    </div>

</div>


<section class="card">


    <div class="tabs">

        <a
            href="library_requests.php"
            class="tab <?php echo $status_filter != 'all' ? 'active' : ''; ?>"
        >
            Pending
        </a>

        <a
            href="library_requests.php?status=all"
            class="tab <?php echo $status_filter == 'all' ? 'active' : ''; ?>"
        >
            All Requests
        </a>

    </div>


    <div class="table-wrapper">

        <?php if ($requests && mysqli_num_rows($requests) > 0) { ?>

            <table>

                <thead>

                    <tr>

                        <th>
                            Request ID
                        </th>

                        <th>
                            Student ID
                        </th>

                        <th>
                            Book
                        </th>

                        <th>
                            Author
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php while ($request = mysqli_fetch_assoc($requests)) { ?>

                    <tr>

                        <td>
                            #<?php
                            echo htmlspecialchars(
                                $request['request_id']
                            );
                            ?>
                        </td>

                        <td>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $request['student_id']
                                );
                                ?>
                            </strong>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $request['book_name'] ?? 'Book unavailable'
                            );
                            ?>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $request['author'] ?? '-'
                            );
                            ?>

                        </td>

                        <td>

                            <?php
                            echo htmlspecialchars(
                                $request['request_date']
                            );
                            ?>

                        </td>

                        <td>

                            <span class="status
                                <?php

                                if ($request['status'] == 'Pending') {
                                    echo 'status-pending';
                                } elseif ($request['status'] == 'Approved') {
                                    echo 'status-approved';
                                } else {
                                    echo 'status-rejected';
                                }

                                ?>
                            ">

                                <?php
                                echo htmlspecialchars(
                                    $request['status']
                                );
                                ?>

                            </span>

                        </td>

                        <td>

                            <?php if ($request['status'] == 'Pending') { ?>

                                <div class="actions">

                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="approve"
                                        >

                                        <input
                                            type="hidden"
                                            name="request_id"
                                            value="<?php
                                                echo htmlspecialchars(
                                                    $request['request_id']
                                                );
                                            ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="approve"
                                        >

                                            <i class="fa-solid fa-check"></i>

                                            Approve

                                        </button>

                                    </form>


                                    <form
                                        method="POST"
                                        onsubmit="return confirm('Reject this request?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="reject"
                                        >

                                        <input
                                            type="hidden"
                                            name="request_id"
                                            value="<?php
                                                echo htmlspecialchars(
                                                    $request['request_id']
                                                );
                                            ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="reject"
                                        >

                                            <i class="fa-solid fa-xmark"></i>

                                            Reject

                                        </button>

                                    </form>

                                </div>

                            <?php } else { ?>

                                <span>
                                    Completed
                                </span>

                            <?php } ?>

                        </td>

                    </tr>

                <?php } ?>

                </tbody>

            </table>

        <?php } else { ?>

            <div class="empty">

                <i class="fa-solid fa-book-open"></i>

                <h3>
                    No Requests Found
                </h3>

                <p>
                    There are currently no
                    <?php echo $status_filter == "all" ? "" : "pending"; ?>
                    book requests.
                </p>

            </div>

        <?php } ?>

    </div>

</section>

</main>

</body>

</html>

<?php

mysqli_close($conn);

?>