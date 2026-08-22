<?php

session_start();
include("db.php");


/* =========================================================
   STUDENT LOGIN CHECK
========================================================= */

if (isset($_SESSION['faculty_id'])) {

    header("Location: approve_application.php");
    exit();

}

if (!isset($_SESSION['student_id'])) {

    header("Location: student_login.php");
    exit();

}

$student_id = $_SESSION['student_id'];


/* =========================================================
   GET STUDENT DETAILS
========================================================= */

$sql = "
    SELECT
        student_id,
        name,
        email,
        course,
        semester
    FROM students
    WHERE student_id = ?
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database Error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $student_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$student = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$student) {

    session_unset();
    session_destroy();

    header("Location: student_login.php");
    exit();

}


/* =========================================================
   STUDENT INITIAL
========================================================= */

$initial = strtoupper(
    substr(trim($student['name']), 0, 1)
);


/* =========================================================
   GET FACULTY LIST
========================================================= */

$faculty_list = [];

$faculty_sql = "
    SELECT
        faculty_id,
        name,
        department,
        designation
    FROM faculty
    ORDER BY name ASC
";

$faculty_result = mysqli_query(
    $conn,
    $faculty_sql
);

if ($faculty_result) {

    while ($faculty = mysqli_fetch_assoc($faculty_result)) {

        $faculty_list[] = $faculty;

    }

}


/* =========================================================
   MESSAGE
========================================================= */

$message = "";
$message_type = "";


/* =========================================================
   SUBMIT APPLICATION
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    $application_type = isset($_POST['application_type'])
        ? trim($_POST['application_type'])
        : "";


    $title = isset($_POST['title'])
        ? trim($_POST['title'])
        : "";


    $description = isset($_POST['description'])
        ? trim($_POST['description'])
        : "";


    $faculty_id = isset($_POST['faculty_id'])
        ? trim($_POST['faculty_id'])
        : "";


    /* =====================================================
       VALIDATION
    ===================================================== */

    if (
        empty($application_type) ||
        empty($title) ||
        empty($description) ||
        empty($faculty_id)
    ) {

        $message =
            "Please fill in all fields and select a faculty.";

        $message_type = "error";

    } else {


        /* =================================================
           CHECK SELECTED FACULTY EXISTS
        ================================================= */

        $check_sql = "
            SELECT faculty_id
            FROM faculty
            WHERE faculty_id = ?
        ";

        $check_stmt = mysqli_prepare(
            $conn,
            $check_sql
        );

        mysqli_stmt_bind_param(
            $check_stmt,
            "s",
            $faculty_id
        );

        mysqli_stmt_execute($check_stmt);

        $check_result = mysqli_stmt_get_result(
            $check_stmt
        );

        $faculty_exists =
            mysqli_num_rows($check_result) > 0;

        mysqli_stmt_close($check_stmt);


        if (!$faculty_exists) {

            $message =
                "Selected faculty does not exist.";

            $message_type = "error";

        } else {


            /* =============================================
               STORE TITLE + DESCRIPTION
            ============================================= */

            $final_description =
                "Title: " . $title . "\n\n" .
                $description;


            /* =============================================
               INSERT APPLICATION
            ============================================= */

            $insert_sql = "
                INSERT INTO applications
                (
                    student_id,
                    faculty_id,
                    application_type,
                    description,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    'Pending'
                )
            ";


            $insert_stmt = mysqli_prepare(
                $conn,
                $insert_sql
            );


            if (!$insert_stmt) {

                $message =
                    "Unable to prepare application.";

                $message_type = "error";

            } else {


                mysqli_stmt_bind_param(
                    $insert_stmt,
                    "isss",
                    $student_id,
                    $faculty_id,
                    $application_type,
                    $final_description
                );


                if (
                    mysqli_stmt_execute(
                        $insert_stmt
                    )
                ) {

                    $message =
                        "Application submitted successfully.";

                    $message_type = "success";

                } else {

                    $message =
                        "Failed to submit application.";

                    $message_type = "error";

                }


                mysqli_stmt_close(
                    $insert_stmt
                );

            }

        }

    }

}


/* =========================================================
   GET STUDENT APPLICATIONS
========================================================= */

$applications = [];

$app_sql = "
    SELECT
        application_id,
        student_id,
        faculty_id,
        application_type,
        description,
        status,
        response
    FROM applications
    WHERE student_id = ?
    ORDER BY application_id DESC
";


$app_stmt = mysqli_prepare(
    $conn,
    $app_sql
);


if ($app_stmt) {

    mysqli_stmt_bind_param(
        $app_stmt,
        "i",
        $student_id
    );

    mysqli_stmt_execute(
        $app_stmt
    );

    $app_result = mysqli_stmt_get_result(
        $app_stmt
    );


    while (
        $row = mysqli_fetch_assoc(
            $app_result
        )
    ) {

        $applications[] = $row;

    }


    mysqli_stmt_close(
        $app_stmt
    );

}


/* =========================================================
   STATISTICS
========================================================= */

$total_applications =
    count($applications);

$pending_applications = 0;
$approved_applications = 0;
$rejected_applications = 0;


foreach (
    $applications as $application
) {

    if (
        $application['status']
        === "Pending"
    ) {

        $pending_applications++;

    }


    if (
        $application['status']
        === "Approved"
    ) {

        $approved_applications++;

    }


    if (
        $application['status']
        === "Rejected"
    ) {

        $rejected_applications++;

    }

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
        CampusConnect | Applications
    </title>


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


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    >


    <link
        rel="stylesheet"
        href="assets/css/style.css?v=60"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;

            font-family:
                'Poppins',
                sans-serif;

            background: #f5f7ff;

            color: #1f2937;
        }


        .student-app-page {

            min-height: 100vh;

            width: 100%;

            padding: 30px 40px;

        }


        /* ==========================================
           HEADER
        ========================================== */

        .student-app-topbar {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 28px;

        }


        .student-app-title h1 {

            margin: 0;

            font-size: 28px;

            color: #111827;

        }


        .student-app-title p {

            margin: 5px 0 0;

            color: #6b7280;

            font-size: 13px;

        }


        .student-app-user {

            display: flex;

            align-items: center;

            gap: 10px;

            background: white;

            padding: 8px 15px 8px 8px;

            border-radius: 40px;

            box-shadow:
                0 5px 20px rgba(0,0,0,.06);

        }


        .student-app-avatar {

            width: 42px;

            height: 42px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #7c3aed
                );

            color: white;

            font-weight: 700;

        }


        .student-app-user strong {

            display: block;

            font-size: 13px;

        }


        .student-app-user span {

            display: block;

            font-size: 11px;

            color: #6b7280;

        }


        /* ==========================================
           MESSAGE
        ========================================== */

        .app-message {

            padding: 14px 18px;

            border-radius: 12px;

            margin-bottom: 22px;

            font-size: 13px;

        }


        .app-message.success {

            background: #dcfce7;

            color: #166534;

        }


        .app-message.error {

            background: #fee2e2;

            color: #991b1b;

        }


        /* ==========================================
           STATS
        ========================================== */

        .app-stats {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

            margin-bottom: 25px;

        }


        .app-stat-card {

            background: white;

            border-radius: 18px;

            padding: 20px;

            box-shadow:
                0 7px 25px rgba(0,0,0,.05);

        }


        .app-stat-icon {

            width: 42px;

            height: 42px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            margin-bottom: 12px;

        }


        .app-stat-icon.blue {

            background: #dbeafe;

            color: #2563eb;

        }


        .app-stat-icon.orange {

            background: #fef3c7;

            color: #d97706;

        }


        .app-stat-icon.green {

            background: #dcfce7;

            color: #16a34a;

        }


        .app-stat-icon.red {

            background: #fee2e2;

            color: #dc2626;

        }


        .app-stat-card h3 {

            margin: 0;

            font-size: 25px;

        }


        .app-stat-card p {

            margin: 4px 0 0;

            font-size: 12px;

            color: #6b7280;

        }


        /* ==========================================
           CONTENT
        ========================================== */

        .app-content-grid {

            display: grid;

            grid-template-columns:
                minmax(320px, .8fr)
                minmax(450px, 1.2fr);

            gap: 25px;

            align-items: start;

        }


        .app-form-card,
        .app-list-card {

            background: white;

            border-radius: 20px;

            padding: 25px;

            box-shadow:
                0 7px 30px rgba(0,0,0,.06);

        }


        .app-card-heading {

            margin-bottom: 22px;

        }


        .app-card-heading h2 {

            margin: 0;

            font-size: 19px;

        }


        .app-card-heading p {

            margin: 5px 0 0;

            font-size: 12px;

            color: #6b7280;

        }


        /* ==========================================
           FORM
        ========================================== */

        .app-form-group {

            margin-bottom: 17px;

        }


        .app-form-group label {

            display: block;

            margin-bottom: 7px;

            font-size: 12px;

            font-weight: 600;

            color: #374151;

        }


        .app-form-group input,
        .app-form-group select,
        .app-form-group textarea {

            width: 100%;

            padding: 11px 13px;

            border: 1px solid #dbe2ea;

            border-radius: 11px;

            outline: none;

            font-family:
                'Poppins',
                sans-serif;

            font-size: 12px;

            background: white;

        }


        .app-form-group textarea {

            min-height: 120px;

            resize: vertical;

        }


        .app-form-group input:focus,
        .app-form-group select:focus,
        .app-form-group textarea:focus {

            border-color: #6366f1;

            box-shadow:
                0 0 0 3px
                rgba(99,102,241,.10);

        }


        .faculty-info {

            margin-top: 6px;

            font-size: 10px;

            color: #6b7280;

        }


        .app-submit-btn {

            width: 100%;

            border: none;

            padding: 12px 18px;

            border-radius: 11px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #7c3aed
                );

            color: white;

            font-family:
                'Poppins',
                sans-serif;

            font-size: 13px;

            font-weight: 600;

            cursor: pointer;

        }


        /* ==========================================
           APPLICATION LIST
        ========================================== */

        .application-item {

            padding: 18px 0;

            border-bottom:
                1px solid #eef2f7;

        }


        .application-item:last-child {

            border-bottom: none;

        }


        .application-item-top {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 15px;

            margin-bottom: 12px;

        }


        .application-name {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .application-icon {

            width: 40px;

            height: 40px;

            border-radius: 11px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #eef2ff;

            color: #4f46e5;

        }


        .application-name h3 {

            margin: 0;

            font-size: 14px;

        }


        .application-name p {

            margin: 3px 0 0;

            font-size: 11px;

            color: #6b7280;

        }


        .app-status {

            padding: 6px 11px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 600;

        }


        .app-status.pending {

            background: #fef3c7;

            color: #92400e;

        }


        .app-status.approved {

            background: #dcfce7;

            color: #166534;

        }


        .app-status.rejected {

            background: #fee2e2;

            color: #991b1b;

        }


        .application-description {

            padding: 12px;

            background: #f8fafc;

            border-radius: 10px;

            color: #4b5563;

            font-size: 12px;

            line-height: 1.6;

            white-space: pre-line;

        }


        .application-response {

            margin-top: 10px;

            padding: 11px 13px;

            background: #eff6ff;

            border-left: 3px solid #2563eb;

            border-radius: 8px;

            font-size: 11px;

            line-height: 1.6;

        }


        .application-response strong {

            color: #1d4ed8;

        }


        .application-faculty {

            margin-top: 8px;

            font-size: 11px;

            color: #6b7280;

        }


        /* ==========================================
           EMPTY
        ========================================== */

        .app-empty {

            text-align: center;

            padding: 45px 20px;

            color: #6b7280;

        }


        .app-empty i {

            font-size: 42px;

            color: #6366f1;

            margin-bottom: 12px;

        }


        .app-empty h3 {

            margin: 0 0 5px;

            color: #111827;

        }


        /* ==========================================
           RESPONSIVE
        ========================================== */

        @media (max-width: 1100px) {

            .app-content-grid {

                grid-template-columns: 1fr;

            }

            .app-stats {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 650px) {

            .student-app-page {

                padding: 20px;

            }

            .app-stats {

                grid-template-columns: 1fr;

            }

            .student-app-topbar {

                align-items: flex-start;

            }

            .student-app-user span {

                display: none;

            }

            .application-item-top {

                flex-direction: column;

            }

        }

    </style>

</head>


<body>


<div class="student-app-page">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <header class="student-app-topbar">


        <div class="student-app-title">

            <h1>
                Applications
            </h1>

            <p>
                Submit and track your college applications
            </p>

        </div>


        <div class="student-app-user">

            <div class="student-app-avatar">

                <?php
                echo htmlspecialchars($initial);
                ?>

            </div>

            <div>

                <strong>

                    <?php
                    echo htmlspecialchars(
                        $student['name']
                    );
                    ?>

                </strong>

                <span>

                    <?php
                    echo htmlspecialchars(
                        $student['course']
                    );
                    ?>

                    • Semester

                    <?php
                    echo htmlspecialchars(
                        $student['semester']
                    );
                    ?>

                </span>

            </div>

        </div>


    </header>



    <!-- =====================================================
         MESSAGE
    ====================================================== -->

    <?php if (!empty($message)): ?>

        <div
            class="app-message
            <?php echo $message_type; ?>"
        >

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <section class="app-stats">


        <div class="app-stat-card">

            <div class="app-stat-icon blue">

                <i class="fa-solid fa-file-lines"></i>

            </div>

            <h3>
                <?php echo $total_applications; ?>
            </h3>

            <p>
                Total Applications
            </p>

        </div>


        <div class="app-stat-card">

            <div class="app-stat-icon orange">

                <i class="fa-solid fa-clock"></i>

            </div>

            <h3>
                <?php echo $pending_applications; ?>
            </h3>

            <p>
                Pending
            </p>

        </div>


        <div class="app-stat-card">

            <div class="app-stat-icon green">

                <i class="fa-solid fa-circle-check"></i>

            </div>

            <h3>
                <?php echo $approved_applications; ?>
            </h3>

            <p>
                Approved
            </p>

        </div>


        <div class="app-stat-card">

            <div class="app-stat-icon red">

                <i class="fa-solid fa-circle-xmark"></i>

            </div>

            <h3>
                <?php echo $rejected_applications; ?>
            </h3>

            <p>
                Rejected
            </p>

        </div>


    </section>



    <!-- =====================================================
         FORM + APPLICATIONS
    ====================================================== -->

    <div class="app-content-grid">


        <!-- =================================================
             NEW APPLICATION
        ================================================== -->

        <section class="app-form-card">


            <div class="app-card-heading">

                <h2>
                    Submit New Application
                </h2>

                <p>
                    Select a faculty and submit your application
                </p>

            </div>


            <form
                method="POST"
                action="applications.php"
            >


                <!-- APPLICATION TYPE -->

                <div class="app-form-group">

                    <label>
                        Application Type
                    </label>

                    <select
                        name="application_type"
                        required
                    >

                        <option value="">
                            Select application type
                        </option>

                        <option value="Leave Application">
                            Leave Application
                        </option>

                        <option value="Bonafide Certificate">
                            Bonafide Certificate
                        </option>

                        <option value="Character Certificate">
                            Character Certificate
                        </option>

                        <option value="Transfer Certificate">
                            Transfer Certificate
                        </option>

                        <option value="Scholarship Application">
                            Scholarship Application
                        </option>

                        <option value="Exam Form Correction">
                            Exam Form Correction
                        </option>

                        <option value="Other">
                            Other
                        </option>

                    </select>

                </div>


                <!-- TITLE -->

                <div class="app-form-group">

                    <label>
                        Application Title
                    </label>

                    <input
                        type="text"
                        name="title"
                        placeholder="Enter application title"
                        maxlength="150"
                        required
                    >

                </div>


                <!-- DESCRIPTION -->

                <div class="app-form-group">

                    <label>
                        Description / Reason
                    </label>

                    <textarea
                        name="description"
                        placeholder="Explain your application..."
                        required
                    ></textarea>

                </div>


                <!-- FACULTY -->

                <div class="app-form-group">

                    <label>
                        Select Faculty
                    </label>

                    <select
                        name="faculty_id"
                        required
                    >

                        <option value="">
                            Select faculty
                        </option>


                        <?php foreach (
                            $faculty_list
                            as $faculty
                        ): ?>

                            <option
                                value="<?php
                                echo htmlspecialchars(
                                    $faculty['faculty_id']
                                );
                                ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $faculty['name']
                                );
                                ?>

                                -

                                <?php
                                echo htmlspecialchars(
                                    $faculty['department']
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>


                    </select>


                    <div class="faculty-info">

                        Your application will be sent
                        to the selected faculty.

                    </div>

                </div>


                <!-- SUBMIT -->

                <button
                    type="submit"
                    class="app-submit-btn"
                >

                    <i class="fa-solid fa-paper-plane"></i>

                    Submit Application

                </button>


            </form>


        </section>



        <!-- =================================================
             MY APPLICATIONS
        ================================================== -->

        <section class="app-list-card">


            <div class="app-card-heading">

                <h2>
                    My Applications
                </h2>

                <p>
                    Track your submitted applications
                </p>

            </div>


            <?php if (
                count($applications) > 0
            ): ?>


                <?php foreach (
                    $applications
                    as $application
                ): ?>


                    <?php

                    $status =
                        $application['status']
                        ?? 'Pending';

                    $status_class =
                        strtolower($status);

                    ?>


                    <div class="application-item">


                        <div class="application-item-top">


                            <div class="application-name">


                                <div class="application-icon">

                                    <i class="fa-solid fa-file-lines"></i>

                                </div>


                                <div>

                                    <h3>

                                        <?php
                                        echo htmlspecialchars(
                                            $application[
                                                'application_type'
                                            ]
                                        );
                                        ?>

                                    </h3>

                                    <p>

                                        Application ID:

                                        <?php
                                        echo (int)
                                            $application[
                                                'application_id'
                                            ];
                                        ?>

                                    </p>

                                </div>


                            </div>


                            <span
                                class="app-status
                                <?php
                                echo htmlspecialchars(
                                    $status_class
                                );
                                ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $status
                                );
                                ?>

                            </span>


                        </div>


                        <div class="application-description">

                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $application[
                                        'description'
                                    ]
                                )
                            );
                            ?>

                        </div>


                        <div class="application-faculty">

                            <i class="fa-solid fa-user-tie"></i>

                            Sent to Faculty ID:

                            <?php
                            echo htmlspecialchars(
                                $application[
                                    'faculty_id'
                                ]
                            );
                            ?>

                        </div>


                        <?php if (
                            !empty(
                                $application['response']
                            )
                        ): ?>

                            <div class="application-response">

                                <strong>
                                    Faculty Response:
                                </strong>

                                <br>

                                <?php
                                echo nl2br(
                                    htmlspecialchars(
                                        $application[
                                            'response'
                                        ]
                                    )
                                );
                                ?>

                            </div>

                        <?php endif; ?>


                    </div>


                <?php endforeach; ?>


            <?php else: ?>


                <div class="app-empty">

                    <i class="fa-solid fa-file-circle-plus"></i>

                    <h3>
                        No Applications Yet
                    </h3>

                    <p>
                        Submit your first application.
                    </p>

                </div>


            <?php endif; ?>


        </section>


    </div>


</div>


</body>

</html>

<?php

mysqli_close($conn);

?>