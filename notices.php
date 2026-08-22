<?php

session_start();

include("db.php");


/* ==============================
   DETERMINE USER TYPE
============================== */

$is_student = isset($_SESSION['student_id']);
$is_faculty = isset($_SESSION['faculty_id']);


/* ==============================
   CHECK LOGIN
============================== */

if (!$is_student && !$is_faculty) {

    header("Location: index.php");
    exit();

}


/* ==============================
   STUDENT
============================== */

if ($is_student && !$is_faculty) {

    $student_id = $_SESSION['student_id'];

    $student_sql = "
        SELECT course
        FROM students
        WHERE student_id = ?
    ";

    $student_stmt = mysqli_prepare(
        $conn,
        $student_sql
    );

    mysqli_stmt_bind_param(
        $student_stmt,
        "s",
        $student_id
    );

    mysqli_stmt_execute($student_stmt);

    $student_result = mysqli_stmt_get_result(
        $student_stmt
    );

    $student = mysqli_fetch_assoc(
        $student_result
    );

    $student_course = $student['course'];


    /* STUDENT SEES ONLY THEIR DEPARTMENT + ALL */

    $sql = "
        SELECT
            n.notice_id,
            n.faculty_id,
            n.title,
            n.description,
            n.notice_date,
            n.department,
            f.name AS faculty_name,
            f.designation AS faculty_designation
        FROM notices n

        LEFT JOIN faculty f
            ON CAST(
                SUBSTRING(f.faculty_id, 2)
                AS UNSIGNED
            ) = n.faculty_id

        WHERE n.department = ?
           OR n.department = 'All'

        ORDER BY n.notice_id DESC
    ";

    $stmt = mysqli_prepare(
        $conn,
        $sql
    );

    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $student_course
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result(
        $stmt
    );

}


/* ==============================
   FACULTY
============================== */

elseif ($is_faculty && !$is_student) {

    /* FACULTY SEES ALL NOTICES */

    $sql = "
        SELECT
            n.notice_id,
            n.faculty_id,
            n.title,
            n.description,
            n.notice_date,
            n.department,
            f.name AS faculty_name,
            f.designation AS faculty_designation
        FROM notices n

        LEFT JOIN faculty f
            ON CAST(
                SUBSTRING(f.faculty_id, 2)
                AS UNSIGNED
            ) = n.faculty_id

        ORDER BY n.notice_id DESC
    ";

    $stmt = mysqli_prepare(
        $conn,
        $sql
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result(
        $stmt
    );

}


/* ==============================
   INVALID MIXED SESSION
============================== */

else {

    /*
       Both student and faculty sessions
       exist at the same time.
    */

    session_unset();
    session_destroy();

    header("Location: index.php");
    exit();

}

?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CampusConnect | Notices</title>

    <link rel="stylesheet" href="assets/css/style.css?v=10">
    <!-- Google Font -->
     
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    >

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f4f7fc;
            color: #172033;
        }

        /* =========================
           MAIN
        ========================= */

        .notice-page {
            min-height: 100vh;
            padding: 40px;
        }

        /* =========================
           HEADER
        ========================= */

        .notice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;

            margin-bottom: 35px;
        }

        .heading h1 {
            font-size: 36px;
            font-weight: 700;

            color: #172554;
        }

        .heading p {
            margin-top: 6px;

            color: #64748b;
            font-size: 15px;
        }

        .back-btn {
            text-decoration: none;

            display: inline-flex;
            align-items: center;
            gap: 8px;

            padding: 12px 20px;

            border-radius: 10px;

            background: #172554;
            color: white;

            font-size: 14px;
            font-weight: 500;

            transition: 0.3s;
        }

        .back-btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        /* =========================
           SEARCH
        ========================= */

        .search-box {
            background: white;

            padding: 18px 20px;

            border-radius: 16px;

            box-shadow: 0 8px 25px rgba(15, 23, 42, 0.07);

            margin-bottom: 30px;
        }

        .search-wrapper {
            position: relative;
        }

        .search-wrapper i {
            position: absolute;

            left: 18px;
            top: 50%;

            transform: translateY(-50%);

            color: #64748b;
        }

        .search-wrapper input {
            width: 100%;

            padding: 14px 18px 14px 48px;

            border: 1px solid #e2e8f0;

            border-radius: 10px;

            outline: none;

            font-size: 15px;

            transition: 0.3s;
        }

        .search-wrapper input:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        /* =========================
           NOTICE CONTAINER
        ========================= */

        .notice-container {
            display: grid;

            grid-template-columns:
                repeat(auto-fit, minmax(330px, 1fr));

            gap: 25px;
        }

        /* =========================
           NOTICE CARD
        ========================= */

        .notice-card {
            background: white;

            border-radius: 18px;

            padding: 25px;

            border: 1px solid #e8edf5;

            box-shadow:
                0 8px 25px rgba(15, 23, 42, 0.06);

            transition: 0.35s;

            position: relative;

            overflow: hidden;
        }

        .notice-card::before {
            content: "";

            position: absolute;

            top: 0;
            left: 0;

            width: 100%;
            height: 4px;

            background:
                linear-gradient(
                    90deg,
                    #2563eb,
                    #7c3aed
                );
        }

        .notice-card:hover {
            transform: translateY(-7px);

            box-shadow:
                0 18px 40px rgba(15, 23, 42, 0.12);
        }

        /* =========================
           NOTICE TOP
        ========================= */

        .notice-top {
            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 15px;

            margin-bottom: 18px;
        }

        .notice-icon {
            width: 48px;
            height: 48px;

            border-radius: 12px;

            display: flex;

            align-items: center;
            justify-content: center;

            background: #eff6ff;

            color: #2563eb;

            font-size: 20px;
        }

        .notice-date {
            background: #f1f5f9;

            color: #64748b;

            padding: 7px 11px;

            border-radius: 8px;

            font-size: 12px;

            white-space: nowrap;
        }

        /* =========================
           NOTICE CONTENT
        ========================= */

        .notice-card h2 {
            font-size: 20px;

            color: #172554;

            margin-bottom: 12px;
        }

        .notice-card p {
            color: #64748b;

            font-size: 14px;

            line-height: 1.8;

            margin-bottom: 20px;
        }

        /* =========================
           FOOTER INFO
        ========================= */

        .notice-footer {
            border-top: 1px solid #edf1f6;

            padding-top: 15px;

            display: flex;

            justify-content: space-between;

            align-items: center;
        }

        .posted-by {
            display: flex;

            align-items: center;

            gap: 8px;

            color: #64748b;

            font-size: 12px;
        }

        .posted-by i {
            color: #2563eb;
        }

        .read-more {
            color: #2563eb;

            font-size: 13px;

            font-weight: 600;

            text-decoration: none;
        }

        /* =========================
           NO NOTICES
        ========================= */

        .no-notices {
            grid-column: 1 / -1;

            background: white;

            border-radius: 18px;

            padding: 70px 30px;

            text-align: center;

            box-shadow:
                0 8px 25px rgba(15, 23, 42, 0.06);
        }

        .no-notices i {
            font-size: 50px;

            color: #94a3b8;

            margin-bottom: 20px;
        }

        .no-notices h2 {
            color: #334155;

            margin-bottom: 8px;
        }

        .no-notices p {
            color: #64748b;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 768px) {

            .notice-page {
                padding: 25px 18px;
            }

            .notice-header {
                flex-direction: column;

                align-items: flex-start;

                gap: 20px;
            }

            .heading h1 {
                font-size: 30px;
            }

            .notice-container {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<div class="notice-page">


    <!-- HEADER -->

    <div class="notice-header">

        <div class="notice-header">

    <div class="heading">

        <h1>
            <i class="fa-solid fa-bullhorn"></i>
            Campus Notices
        </h1>

        <p>
            Stay updated with the latest college announcements.
        </p>

    </div>
</div>

    <?php if ($is_faculty) { ?>

        <a
            href="faculty_dashboard.php"
            class="back-dashboard"
        >
            <i class="fa-solid fa-arrow-left"></i>
            Back to Dashboard
        </a>

    <?php } else { ?>

        <a
            href="student_dashboard.php"
            class="back-dashboard"
        >
            <i class="fa-solid fa-arrow-left"></i>
            Back to Dashboard
        </a>

    <?php } ?>

</div>

    <!-- SEARCH -->

    <div class="search-box">

        <div class="search-wrapper">

            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="text"
                id="noticeSearch"
                placeholder="Search notices..."
                onkeyup="searchNotices()"
            >

        </div>

    </div>



    <!-- NOTICES -->

    <div class="notice-container" id="noticeContainer">


        <?php

        if ($result && mysqli_num_rows($result) > 0) {

            while ($row = mysqli_fetch_assoc($result)) {

                ?>

                <div class="notice-card">


                    <div class="notice-top">

                        <div class="notice-icon">

                            <i class="fa-solid fa-bullhorn"></i>

                        </div>


                        <div class="notice-date">

                            <i class="fa-regular fa-calendar"></i>

                            <?php

                            if (isset($row['created_at'])) {

                                echo date(
                                    "d M Y",
                                    strtotime($row['created_at'])
                                );

                            } else {

                                echo "Recent";

                            }

                            ?>

                        </div>

                    </div>



                    <h2 class="notice-title">

                        <?php

                        echo htmlspecialchars(
                            $row['title']
                        );

                        ?>

                    </h2>



                    <p class="notice-description">

                        <?php

                        echo nl2br(
                            htmlspecialchars(
                                $row['description']
                            )
                        );

                        ?>

                    </p>



                    <div class="notice-footer">


                        <div class="posted-by">

                            <i class="fa-solid fa-user"></i>

                            <span>

                                <?php

                                if (isset($row['posted_by'])) {

                                    echo htmlspecialchars(
                                        $row['posted_by']
                                    );

                                } else {

                                    echo "College Administration";

                                }

                                ?>

                            </span>

                        </div>


                        
                    <a href="notice_details.php?notice_id=<?php echo $row['notice_id']; ?>"
                     class="read-more"
                    >
                        Read Notice
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>

                    </div>


                </div>


                <?php

            }

        } else {

            ?>


            <div class="no-notices">

                <i class="fa-regular fa-bell-slash"></i>

                <h2>No Notices Available</h2>

                <p>
                    There are currently no notices posted.
                </p>

            </div>


            <?php

        }

        ?>


    </div>


</div>



<script>

function searchNotices() {

    let input =
        document.getElementById("noticeSearch")
        .value
        .toLowerCase();

    let cards =
        document.querySelectorAll(".notice-card");


    cards.forEach(function(card) {

        let title =
            card.querySelector(".notice-title")
            .textContent
            .toLowerCase();

        let description =
            card.querySelector(".notice-description")
            .textContent
            .toLowerCase();


        if (
            title.includes(input) ||
            description.includes(input)
        ) {

            card.style.display = "";

        } else {

            card.style.display = "none";

        }

    });

}

</script>


</body>

</html>