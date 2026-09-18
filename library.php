<?php

session_start();

include("db.php");

$message = "";
$message_type = "";


/* =========================
   CHECK LOGIN + DETECT ROLE
========================= */

$user_role = "";
$dashboard_link = "";
$display_name = "";
$display_id = "";
$display_course = "-";

/*
   IMPORTANT:
   Librarian is stored in the faculty table.
   Therefore, faculty_id is checked against faculty_designation.
*/

if (isset($_SESSION['faculty_id'])) {

    $faculty_id = $_SESSION['faculty_id'];

    /*
       Use SELECT * because the librarian is stored in the faculty table
       and the exact column names may be different in your database.
    */
    $faculty_sql = "
        SELECT *
        FROM faculty
        WHERE faculty_id = ?
        LIMIT 1
    ";

    $faculty_stmt = mysqli_prepare($conn, $faculty_sql);

    if (!$faculty_stmt) {
        die("Database error: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($faculty_stmt, "s", $faculty_id);
    mysqli_stmt_execute($faculty_stmt);

    $faculty_result = mysqli_stmt_get_result($faculty_stmt);
    $faculty = mysqli_fetch_assoc($faculty_result);

    mysqli_stmt_close($faculty_stmt);

    if (!$faculty) {
        session_unset();
        header("Location: faculty_login.php");
        exit();
    }

    /*
       Support the common column names used in the faculty table.
       Your login/session may use faculty_name, while the database
       may use name, department, or designation.
    */
    $designation = strtolower(
        trim(
            $faculty['faculty_designation']
            ?? $faculty['designation']
            ?? $faculty['role']
            ?? ''
        )
    );

    if (strpos($designation, "librarian") !== false) {
        $user_role = "librarian";
        $dashboard_link = "librarian_dashboard.php";
    } else {
        $user_role = "faculty";
        $dashboard_link = "faculty_dashboard.php";
    }

    $display_name =
        $faculty['faculty_name']
        ?? $faculty['name']
        ?? $faculty['full_name']
        ?? "Faculty";

    $display_id =
        $faculty['faculty_id']
        ?? $faculty['id']
        ?? $faculty_id;

    $display_course =
        $faculty['faculty_department']
        ?? $faculty['department']
        ?? "-";

} elseif (isset($_SESSION['student_id'])) {

    $user_role = "student";
    $dashboard_link = "student_dashboard.php";

    $student_id = $_SESSION['student_id'];

    $student_sql = "
        SELECT
            student_id,
            name,
            course
        FROM students
        WHERE student_id = ?
        LIMIT 1
    ";

    $student_stmt = mysqli_prepare($conn, $student_sql);

    if (!$student_stmt) {
        die("Database error: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($student_stmt, "s", $student_id);
    mysqli_stmt_execute($student_stmt);

    $student_result = mysqli_stmt_get_result($student_stmt);
    $student = mysqli_fetch_assoc($student_result);

    mysqli_stmt_close($student_stmt);

    if (!$student) {
        session_unset();
        header("Location: student_login.php");
        exit();
    }

    $display_name = $student['name'] ?? "Student";
    $display_id = $student['student_id'] ?? $student_id;
    $display_course = $student['course'] ?? "-";

} else {

    header("Location: student_login.php");
    exit();
}


/* =========================
   STUDENT REQUEST ID
========================= */

$student_id = $_SESSION['student_id'] ?? "";


/* =========================
   BOOK REQUEST
========================= */

/* =========================
   BOOK REQUEST
========================= */

if (
    $_SERVER["REQUEST_METHOD"] == "POST" &&
    $user_role === "student"
) {

    $action = $_POST['action'] ?? "";
    $book_id = intval($_POST['book_id'] ?? 0);


    if ($action == "request_book") {

        // Only students can request/borrow books.
        if ($user_role != "student") {
            $message = "Only students can request books.";
            $message_type = "error";
        } else {


        /* =========================
           VALIDATE BOOK
        ========================== */

        if ($book_id <= 0) {

            $message = "Invalid book selected.";
            $message_type = "error";

        } else {


            /* =========================
               GET BOOK
            ========================== */

            $book_sql = "
                SELECT
                    book_id,
                    book_name,
                    quantity,
                    status
                FROM library
                WHERE book_id = ?
            ";

            $book_stmt = mysqli_prepare(
                $conn,
                $book_sql
            );

            mysqli_stmt_bind_param(
                $book_stmt,
                "i",
                $book_id
            );

            mysqli_stmt_execute(
                $book_stmt
            );

            $book_result = mysqli_stmt_get_result(
                $book_stmt
            );

            $book = mysqli_fetch_assoc(
                $book_result
            );

            mysqli_stmt_close(
                $book_stmt
            );


            /* =========================
               BOOK NOT FOUND
            ========================== */

            if (!$book) {

                $message = "Book not found.";
                $message_type = "error";

            } else {


                /* =========================
                   CHECK AVAILABILITY
                ========================== */

                $book_status = strtolower(
                    trim($book['status'])
                );


                if (
                    intval($book['quantity']) <= 0 ||
                    (
                        $book_status != "available" &&
                        $book_status != "active"
                    )
                ) {

                    $message =
                        "This book is currently unavailable.";

                    $message_type = "error";

                } else {


                    /* =========================
                       CHECK PREVIOUS REQUEST
                    ========================== */

                    $check_sql = "
                        SELECT
                            request_id,
                            status
                        FROM library_requests
                        WHERE student_id = ?
                        AND book_id = ?
                        ORDER BY request_id DESC
                        LIMIT 1
                    ";

                    $check_stmt = mysqli_prepare(
                        $conn,
                        $check_sql
                    );

                    mysqli_stmt_bind_param(
                        $check_stmt,
                        "si",
                        $student_id,
                        $book_id
                    );

                    mysqli_stmt_execute(
                        $check_stmt
                    );

                    $check_result =
                        mysqli_stmt_get_result(
                            $check_stmt
                        );

                    $existing_request =
                        mysqli_fetch_assoc(
                            $check_result
                        );

                    mysqli_stmt_close(
                        $check_stmt
                    );


                    /* =========================
                       PENDING / APPROVED
                    ========================== */

                    if (
                        $existing_request &&
                        (
                            $existing_request['status'] == "Pending" ||
                            $existing_request['status'] == "Approved"
                        )
                    ) {

                        if (
                            $existing_request['status'] == "Pending"
                        ) {

                            $message =
                                "You have already requested this book. Your request is pending.";

                        } else {

                            $message =
                                "You have already been approved for this book.";

                        }

                        $message_type = "error";

                    } else {


                        /* =========================
                           CREATE NEW REQUEST
                        ========================== */

                        $insert_sql = "
                            INSERT INTO library_requests
                            (
                                student_id,
                                book_id,
                                status
                            )
                            VALUES
                            (
                                ?,
                                ?,
                                'Pending'
                            )
                        ";

                        $insert_stmt = mysqli_prepare(
                            $conn,
                            $insert_sql
                        );

                        mysqli_stmt_bind_param(
                            $insert_stmt,
                            "si",
                            $student_id,
                            $book_id
                        );


                        if (
                            mysqli_stmt_execute(
                                $insert_stmt
                            )
                        ) {

                            $message =
                                "Your request for \"" .
                                $book['book_name'] .
                                "\" has been submitted successfully.";

                            $message_type = "success";

                        } else {

                            $message =
                                "Unable to submit your request. Please try again.";

                            $message_type = "error";

                        }

                        mysqli_stmt_close(
                            $insert_stmt
                        );
                    }
                }
            }
        }
        }
    }
}


/* =========================
   SEARCH
========================= */

$search = trim(
    $_GET['search'] ?? ''
);


/* =========================
   GET BOOKS
========================= */

if ($search != "") {

    $search_value =
        "%" . $search . "%";


    $sql = "
        SELECT
            l.*,

            (
                SELECT lr.status
                FROM library_requests lr
                WHERE lr.student_id = ?
                AND lr.book_id = l.book_id
                ORDER BY lr.request_id DESC
                LIMIT 1
            ) AS request_status

        FROM library l

        WHERE
            l.book_name LIKE ?
            OR l.author LIKE ?
            OR l.status LIKE ?

        ORDER BY l.book_id DESC
    ";


    $stmt = mysqli_prepare(
        $conn,
        $sql
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ssss",
        $student_id,
        $search_value,
        $search_value,
        $search_value
    );

    mysqli_stmt_execute(
        $stmt
    );

    $result = mysqli_stmt_get_result(
        $stmt
    );

} else {


    $sql = "
        SELECT
            l.*,

            (
                SELECT lr.status
                FROM library_requests lr
                WHERE lr.student_id = ?
                AND lr.book_id = l.book_id
                ORDER BY lr.request_id DESC
                LIMIT 1
            ) AS request_status

        FROM library l

        ORDER BY l.book_id DESC
    ";


    $stmt = mysqli_prepare(
        $conn,
        $sql
    );

    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $student_id
    );

    mysqli_stmt_execute(
        $stmt
    );

    $result = mysqli_stmt_get_result(
        $stmt
    );
}


/* =========================
   LIBRARY STATISTICS
========================= */

$total_books = 0;
$total_quantity = 0;

$stats_sql = "
    SELECT
        COUNT(*) AS total_books,
        COALESCE(SUM(quantity), 0) AS total_quantity
    FROM library
";

$stats_result = mysqli_query(
    $conn,
    $stats_sql
);

if ($stats_result) {

    $stats = mysqli_fetch_assoc(
        $stats_result
    );

    $total_books =
        $stats['total_books'];

    $total_quantity =
        $stats['total_quantity'];
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
        CampusConnect | Library
    </title>


    <!-- Google Font -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    >


    <!-- Main CSS -->

    <link
        rel="stylesheet"
        href="/CampusConnect/assets/css/style.css?v=2"
    >


    <style>

        /* =========================
           REQUEST STATUS
        ========================== */

        .request-pending {

            background: #f59e0b !important;

            color: white !important;

            cursor: not-allowed;

        }


        .request-approved {

            background: #16a34a !important;

            color: white !important;

            cursor: not-allowed;

        }


        .request-rejected {

            background: #dc2626 !important;

            color: white !important;

            cursor: not-allowed;

        }


        .borrow-btn.disabled {

            cursor: not-allowed;

            opacity: .75;

        }

    </style>

</head>


<body>


<div class="library-page">


    <!-- =========================
         HEADER
    ========================== -->

    <div class="library-header">


        <div>

            <h1 style="
                    display: block !important;
                    background: none !important;
                    color: #111827 !important;
                    -webkit-text-fill-color: #eaf0fc !important;
                    -webkit-background-clip: initial !important;
                    background-clip: initial !important;
                ">
                    <i class="fa-solid fa-book-open" style="
                        color: #2563eb !important;
                        -webkit-text-fill-color: #2563eb !important;
                    "></i>
                    Library
                </h1>


            <p>

                Explore books available in the CampusConnect library.

            </p>

        </div>



        <!-- Logged-in User Profile -->

        <div class="library-profile">


            <div class="library-avatar">

                <?php

                echo strtoupper(
                    substr(
                        $student['name'] ?? 'S',
                        0,
                        1
                    )
                );

                ?>

            </div>



            <div>

                <strong>

                    <?php

                    echo htmlspecialchars(
                        $student['name'] ?? 'Student'
                    );

                    ?>

                </strong>


                <span>

                    ID:

                    <?php

                    echo htmlspecialchars(
                        $student['student_id'] ??
                        $student_id
                    );

                    ?>

                </span>

            </div>

        </div>

    </div>



    <!-- =========================
         BACK TO DASHBOARD
    ========================== -->

    <?php
        if ($user_role == "librarian") {
            $dashboard_link = "librarian_dashboard.php";
        } elseif ($user_role == "faculty") {
            $dashboard_link = "faculty_dashboard.php";
        } else {
            $dashboard_link = "student_dashboard.php";
        }
    ?>

    <a
        href="<?php echo htmlspecialchars($dashboard_link); ?>"
        class="library-back"
    >

        <i class="fa-solid fa-arrow-left"></i>

        Back to Dashboard

    </a>



    <!-- =========================
         MESSAGE
    ========================== -->

    <?php if ($message != "") { ?>

        <div
            style="
                margin:20px 0;
                padding:15px 20px;
                border-radius:10px;

                background:
                <?php
                echo $message_type == "success"
                    ? "#dcfce7"
                    : "#fee2e2";
                ?>;

                color:
                <?php
                echo $message_type == "success"
                    ? "#166534"
                    : "#991b1b";
                ?>;

                font-weight:500;
            "
        >

            <i
                class="fa-solid
                <?php

                echo $message_type == "success"
                    ? "fa-circle-check"
                    : "fa-circle-exclamation";

                ?>"
            ></i>

            &nbsp;

            <?php

            echo htmlspecialchars(
                $message
            );

            ?>

        </div>

    <?php } ?>



    <!-- =========================
         STATISTICS
    ========================== -->

    <div class="library-stats">


        <!-- Different Books -->

        <div class="library-stat-card">


            <div class="library-stat-icon blue">

                <i class="fa-solid fa-book"></i>

            </div>


            <div>

                <span>
                    Different Books
                </span>


                <h2>

                    <?php

                    echo $total_books;

                    ?>

                </h2>

            </div>

        </div>



        <!-- Total Copies -->

        <div class="library-stat-card">


            <div class="library-stat-icon green">

                <i class="fa-solid fa-layer-group"></i>

            </div>


            <div>

                <span>
                    Total Copies
                </span>


                <h2>

                    <?php

                    echo $total_quantity;

                    ?>

                </h2>

            </div>

        </div>



        <!-- Course -->

        <div class="library-stat-card">


            <div class="library-stat-icon purple">

                <i class="fa-solid fa-graduation-cap"></i>

            </div>


            <div>

                <span>
                    Your Course
                </span>


                <h2 class="course-name">

                    <?php

                    echo htmlspecialchars(
                        $user_role == "librarian"
                            ? "Librarian"
                            : ($student['course'] ?? '-')
                    );

                    ?>

                </h2>

            </div>

        </div>

    </div>



    <!-- =========================
         SEARCH
    ========================== -->

    <div class="library-search">


        <form method="GET">


            <div class="library-search-box">


                <i
                    class="fa-solid fa-magnifying-glass"
                ></i>


                <input
                    type="text"
                    name="search"
                    placeholder="Search by book name, author or status..."
                    value="<?php

                        echo htmlspecialchars(
                            $search
                        );

                    ?>"
                >


                <button type="submit">

                    Search

                </button>

            </div>

        </form>

    </div>



    <!-- =========================
         BOOK SECTION
    ========================== -->

    <section class="books-section">


        <div class="books-title">


            <h2>

                Library Books

            </h2>


            <p>

                Browse books currently listed in the college library.

            </p>

        </div>



        <div class="books-grid">


            <?php

            if (
                $result &&
                mysqli_num_rows($result) > 0
            ) {


                while (
                    $book =
                    mysqli_fetch_assoc($result)
                ) {


                    $status =
                        strtolower(
                            trim(
                                $book['status']
                            )
                        );


                    $request_status =
                        $book['request_status'] ??
                        null;

            ?>


                <!-- =========================
                     BOOK CARD
                ========================== -->

                <div class="book-card">


                    <!-- Book Cover -->

                    <div class="book-cover">

                        <i
                            class="fa-solid fa-book"
                        ></i>

                    </div>



                    <!-- Book Information -->

                    <div class="book-info">


                        <span
                            class="book-category"
                        >

                            BOOK #

                            <?php

                            echo htmlspecialchars(
                                $book['book_id']
                            );

                            ?>

                        </span>



                        <h3>

                            <?php

                            echo htmlspecialchars(
                                $book['book_name']
                            );

                            ?>

                        </h3>



                        <!-- Author -->

                        <p
                            class="book-author"
                        >

                            <i
                                class="fa-solid fa-user-pen"
                            ></i>


                            <?php

                            echo htmlspecialchars(
                                $book['author']
                            );

                            ?>

                        </p>



                        <!-- Quantity -->

                        <div
                            class="book-quantity"
                        >

                            <i
                                class="fa-solid fa-copy"
                            ></i>


                            <?php

                            echo htmlspecialchars(
                                $book['quantity']
                            );

                            ?>

                            copies

                        </div>



                        <!-- =========================
                             BOOK AVAILABILITY
                        ========================== -->

                        <?php

                        if (
                            $status == "available" ||
                            $status == "active"
                        ) {

                        ?>

                            <div
                                class="book-available"
                            >

                                <i
                                    class="fa-solid fa-circle-check"
                                ></i>


                                <?php

                                echo htmlspecialchars(
                                    $book['status']
                                );

                                ?>

                            </div>

                        <?php

                        } else {

                        ?>

                            <div
                                class="book-unavailable"
                            >

                                <i
                                    class="fa-solid fa-circle-xmark"
                                ></i>


                                <?php

                                echo htmlspecialchars(
                                    $book['status']
                                );

                                ?>

                            </div>

                        <?php

                        }



                        /* =========================
                           BOOK FOOTER
                        ========================== */

                        ?>

                        <div
                            class="book-footer"
                        >


                            <span>

                                Quantity:

                                <?php

                                echo htmlspecialchars(
                                    $book['quantity']
                                );

                                ?>

                            </span>



                            <?php

                            /* =========================
                               PENDING
                            ========================== */

                            if (
                                $request_status ==
                                "Pending"
                            ) {

                            ?>

                                <button
                                    type="button"
                                    class="borrow-btn request-pending"
                                    disabled
                                >

                                    <i
                                        class="fa-solid fa-clock"
                                    ></i>

                                    Request Pending

                                </button>



                            <?php

                            /* =========================
                               APPROVED
                            ========================== */

                            } elseif (
                                $request_status ==
                                "Approved"
                            ) {

                            ?>

                                <button
                                    type="button"
                                    class="borrow-btn request-approved"
                                    disabled
                                >

                                    <i
                                        class="fa-solid fa-circle-check"
                                    ></i>

                                    Approved

                                </button>



                            <?php

                            /* =========================
                               REJECTED
                            ========================== */

                            } elseif (
                                $request_status ==
                                "Rejected"
                            ) {

                            ?>

                                <button
                                    type="button"
                                    class="borrow-btn request-rejected"
                                    disabled
                                >

                                    <i
                                        class="fa-solid fa-circle-xmark"
                                    ></i>

                                    Rejected

                                </button>



                            <?php

                            /* =========================
                               REQUEST BOOK
                            ========================== */

                            } elseif (
                                $user_role == "student" &&
                                (
                                    $status ==
                                    "available" ||

                                    $status ==
                                    "active"
                                ) &&

                                intval(
                                    $book['quantity']
                                ) > 0
                            ) {

                            ?>

                                <form
                                    method="POST"
                                    style="display:inline;"
                                >


                                    <input
                                        type="hidden"
                                        name="action"
                                        value="request_book"
                                    >


                                    <input
                                        type="hidden"
                                        name="book_id"
                                        value="<?php

                                            echo htmlspecialchars(
                                                $book['book_id']
                                            );

                                        ?>"
                                    >


                                    <button
                                        type="submit"
                                        class="borrow-btn"
                                    >

                                        <i
                                            class="fa-solid fa-book-open-reader"
                                        ></i>

                                        Request

                                    </button>

                                </form>



                            <?php

                            /* =========================
                               UNAVAILABLE
                            ========================== */

                            } else {

                            ?>

                                <button
                                    type="button"
                                    class="borrow-btn disabled"
                                    disabled
                                >

                                    <i
                                        class="fa-solid fa-circle-info"
                                    ></i>

                                    <?php
                                    echo $user_role == "student"
                                        ? "Unavailable"
                                        : "Browse Only";
                                    ?>

                                </button>

                            <?php

                            }

                            ?>

                        </div>


                    </div>

                </div>


            <?php

                }


            } else {

            ?>


                <!-- =========================
                     NO BOOKS
                ========================== -->

                <div
                    class="library-empty"
                >


                    <i
                        class="fa-solid fa-book-open"
                    ></i>


                    <h2>

                        No Books Found

                    </h2>


                    <p>

                        <?php

                        if ($search != "") {

                            echo
                                "No books matched your search.";

                        } else {

                            echo
                                "No books are currently available in the library.";

                        }

                        ?>

                    </p>



                    <?php

                    if ($search != "") {

                    ?>

                        <a
                            href="library.php"
                        >

                            View All Books

                        </a>

                    <?php

                    }

                    ?>


                </div>


            <?php

            }

            ?>


        </div>

    </section>


</div>


</body>

</html>


<?php

mysqli_close($conn);

?>