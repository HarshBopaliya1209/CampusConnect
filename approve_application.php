<?php

session_start();
include("db.php");


/* =========================================================
   FACULTY LOGIN CHECK
========================================================= */

if (!isset($_SESSION['faculty_id'])) {

    header("Location: faculty_login.php");
    exit();

}

$faculty_id = $_SESSION['faculty_id'];


/* =========================================================
   GET FACULTY DETAILS
========================================================= */

$faculty_sql = "
    SELECT
        faculty_id,
        name,
        email,
        department,
        designation
    FROM faculty
    WHERE faculty_id = ?
";


$faculty_stmt = mysqli_prepare(
    $conn,
    $faculty_sql
);


if (!$faculty_stmt) {

    die(
        "Database Error: " .
        mysqli_error($conn)
    );

}


mysqli_stmt_bind_param(
    $faculty_stmt,
    "s",
    $faculty_id
);


mysqli_stmt_execute(
    $faculty_stmt
);


$faculty_result =
    mysqli_stmt_get_result(
        $faculty_stmt
    );


$faculty =
    mysqli_fetch_assoc(
        $faculty_result
    );


mysqli_stmt_close(
    $faculty_stmt
);


/* =========================================================
   FACULTY NOT FOUND
========================================================= */

if (!$faculty) {

    session_unset();
    session_destroy();

    header("Location: faculty_login.php");
    exit();

}


/* =========================================================
   FACULTY INITIAL
========================================================= */

$initial = strtoupper(
    substr(
        trim($faculty['name']),
        0,
        1
    )
);


/* =========================================================
   APPROVE / REJECT APPLICATION
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    $application_id =
        isset($_POST['application_id'])
        ? (int)$_POST['application_id']
        : 0;


    $action =
        isset($_POST['action'])
        ? $_POST['action']
        : "";


    $response =
        isset($_POST['response'])
        ? trim($_POST['response'])
        : "";


    if (
        $application_id > 0 &&
        (
            $action === "approve" ||
            $action === "reject"
        )
    ) {


        if ($action === "approve") {

            $status = "Approved";

        } else {

            $status = "Rejected";

        }


        /*
           IMPORTANT:

           The WHERE condition contains faculty_id.

           This means one faculty cannot approve/reject
           another faculty's application.
        */

        $update_sql = "
            UPDATE applications
            SET
                status = ?,
                response = ?
            WHERE
                application_id = ?
                AND faculty_id = ?
        ";


        $update_stmt = mysqli_prepare(
            $conn,
            $update_sql
        );


        if ($update_stmt) {


            mysqli_stmt_bind_param(
                $update_stmt,
                "ssis",
                $status,
                $response,
                $application_id,
                $faculty_id
            );


            mysqli_stmt_execute(
                $update_stmt
            );


            mysqli_stmt_close(
                $update_stmt
            );

        }

    }


    header(
        "Location: approve_application.php"
    );

    exit();

}


/* =========================================================
   GET ONLY THIS FACULTY'S APPLICATIONS
========================================================= */

$applications = [];


$app_sql = "
    SELECT
        a.application_id,
        a.student_id,
        a.faculty_id,
        a.application_type,
        a.description,
        a.status,
        a.response,

        s.name,
        s.email,
        s.course,
        s.semester

    FROM applications a

    LEFT JOIN students s
        ON a.student_id = s.student_id

    WHERE a.faculty_id = ?

    ORDER BY a.application_id DESC
";


$app_stmt = mysqli_prepare(
    $conn,
    $app_sql
);


if (!$app_stmt) {

    die(
        "Database Error: " .
        mysqli_error($conn)
    );

}


mysqli_stmt_bind_param(
    $app_stmt,
    "s",
    $faculty_id
);


mysqli_stmt_execute(
    $app_stmt
);


$app_result =
    mysqli_stmt_get_result(
        $app_stmt
    );


while (
    $row =
    mysqli_fetch_assoc(
        $app_result
    )
) {

    $applications[] = $row;

}


mysqli_stmt_close(
    $app_stmt
);


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
        CampusConnect | Approve Applications
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
        href="assets/css/style.css?v=61"
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

            background: #f0fdf9;

            color: #1f2937;

        }


        .approve-page {

            min-height: 100vh;

            width: 100%;

            padding: 30px 40px;

        }


        /* ==========================================
           HEADER
        ========================================== */

        .approve-header {

            display: flex;

            justify-content:
                space-between;

            align-items: center;

            margin-bottom: 30px;

        }


        .approve-heading h1 {

            margin: 0;

            font-size: 28px;

            color: #064e3b;

        }


        .approve-heading p {

            margin: 5px 0 0;

            color: #6b7280;

            font-size: 13px;

        }


        .faculty-profile {

            display: flex;

            align-items: center;

            gap: 10px;

            background: white;

            padding: 8px 15px 8px 8px;

            border-radius: 40px;

            box-shadow:
                0 5px 20px rgba(0,0,0,.06);

        }


        .faculty-avatar {

            width: 42px;

            height: 42px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #047857,
                    #14b8a6
                );

            color: white;

            font-weight: 700;

        }


        .faculty-profile strong {

            display: block;

            font-size: 13px;

        }


        .faculty-profile span {

            display: block;

            font-size: 11px;

            color: #6b7280;

        }


        /* ==========================================
           STATS
        ========================================== */

        .approve-stats {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 18px;

            margin-bottom: 25px;

        }


        .approve-stat {

            background: white;

            padding: 20px;

            border-radius: 18px;

            box-shadow:
                0 7px 25px rgba(0,0,0,.05);

        }


        .approve-stat-icon {

            width: 43px;

            height: 43px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #d1fae5;

            color: #047857;

            margin-bottom: 12px;

        }


        .approve-stat h3 {

            margin: 0;

            font-size: 25px;

            color: #064e3b;

        }


        .approve-stat p {

            margin: 4px 0 0;

            font-size: 12px;

            color: #6b7280;

        }


        /* ==========================================
           APPLICATION GRID
        ========================================== */

        .applications-list {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 22px;

        }


        .application-card {

            background: white;

            border-radius: 20px;

            padding: 22px;

            box-shadow:
                0 7px 30px rgba(0,0,0,.06);

        }


        /* ==========================================
           TOP
        ========================================== */

        .application-top {

            display: flex;

            justify-content:
                space-between;

            align-items:
                flex-start;

            gap: 15px;

            margin-bottom: 18px;

        }


        .student-info {

            display: flex;

            align-items: center;

            gap: 12px;

        }


        .student-avatar {

            width: 48px;

            height: 48px;

            border-radius: 14px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #d1fae5;

            color: #047857;

            font-weight: 700;

            font-size: 18px;

        }


        .student-info h3 {

            margin: 0;

            font-size: 16px;

        }


        .student-info p {

            margin: 3px 0 0;

            color: #6b7280;

            font-size: 11px;

        }


        /* ==========================================
           STATUS
        ========================================== */

        .status {

            padding: 6px 12px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 600;

        }


        .status.pending {

            background: #fef3c7;

            color: #92400e;

        }


        .status.approved {

            background: #dcfce7;

            color: #166534;

        }


        .status.rejected {

            background: #fee2e2;

            color: #991b1b;

        }


        /* ==========================================
           DETAILS
        ========================================== */

        .application-details {

            border-top:
                1px solid #eef2f7;

            border-bottom:
                1px solid #eef2f7;

            padding: 15px 0;

            margin-bottom: 18px;

        }


        .detail-row {

            display: flex;

            margin-bottom: 8px;

            font-size: 12px;

        }


        .detail-row:last-child {

            margin-bottom: 0;

        }


        .detail-label {

            width: 100px;

            font-weight: 600;

            color: #374151;

        }


        .detail-value {

            flex: 1;

            color: #6b7280;

        }


        .description {

            background: #f8fafc;

            border-radius: 12px;

            padding: 13px;

            margin-bottom: 18px;

            font-size: 12px;

            line-height: 1.6;

            color: #4b5563;

            white-space: pre-line;

        }


        /* ==========================================
           RESPONSE
        ========================================== */

        .response-box {

            background: #ecfdf5;

            border-left:
                4px solid #10b981;

            border-radius: 10px;

            padding: 12px;

            margin-bottom: 18px;

            font-size: 12px;

            line-height: 1.6;

        }


        .response-box strong {

            color: #065f46;

        }


        /* ==========================================
           ACTION FORM
        ========================================== */

        .action-form textarea {

            width: 100%;

            min-height: 80px;

            resize: vertical;

            border:
                1px solid #d1d5db;

            border-radius: 11px;

            padding: 11px 13px;

            font-family:
                'Poppins',
                sans-serif;

            font-size: 12px;

            outline: none;

        }


        .action-form textarea:focus {

            border-color: #10b981;

            box-shadow:
                0 0 0 3px
                rgba(16,185,129,.10);

        }


        .action-buttons {

            display: flex;

            gap: 10px;

            margin-top: 10px;

        }


        .btn {

            border: none;

            padding: 10px 16px;

            border-radius: 10px;

            font-family:
                'Poppins',
                sans-serif;

            font-size: 12px;

            font-weight: 600;

            cursor: pointer;

        }


        .btn-approve {

            background: #047857;

            color: white;

        }


        .btn-approve:hover {

            background: #065f46;

        }


        .btn-reject {

            background: #fee2e2;

            color: #b91c1c;

        }


        .btn-reject:hover {

            background: #fecaca;

        }


        /* ==========================================
           EMPTY
        ========================================== */

        .no-applications {

            background: white;

            border-radius: 20px;

            padding: 60px 20px;

            text-align: center;

            color: #6b7280;

            box-shadow:
                0 7px 30px rgba(0,0,0,.05);

        }


        .no-applications i {

            font-size: 45px;

            color: #10b981;

            margin-bottom: 15px;

        }


        .no-applications h2 {

            margin: 0 0 5px;

            color: #064e3b;

            font-size: 20px;

        }


        .no-applications p {

            margin: 0;

            font-size: 12px;

        }


        /* ==========================================
           RESPONSIVE
        ========================================== */

        @media (max-width: 1000px) {

            .applications-list {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 750px) {

            .approve-page {

                padding: 20px;

            }

            .approve-stats {

                grid-template-columns: 1fr;

            }

            .approve-header {

                align-items: flex-start;

            }

        }


        @media (max-width: 550px) {

            .approve-page {

                padding: 15px;

            }

            .approve-heading h1 {

                font-size: 22px;

            }

            .faculty-profile span {

                display: none;

            }

            .application-top {

                flex-direction: column;

            }

            .action-buttons {

                flex-direction: column;

            }

            .btn {

                width: 100%;

            }

        }

    </style>

</head>


<body>


<div class="approve-page">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <header class="approve-header">


        <div class="approve-heading">

            <h1>
                Approve Applications
            </h1>

            <p>

                Applications assigned to you

            </p>

        </div>


        <div class="faculty-profile">


            <div class="faculty-avatar">

                <?php
                echo htmlspecialchars(
                    $initial
                );
                ?>

            </div>


            <div>

                <strong>

                    <?php
                    echo htmlspecialchars(
                        $faculty['name']
                    );
                    ?>

                </strong>


                <span>

                    <?php
                    echo htmlspecialchars(
                        $faculty['department']
                    );
                    ?>

                    •

                    <?php
                    echo htmlspecialchars(
                        $faculty['designation']
                    );
                    ?>

                </span>

            </div>


        </div>


    </header>



    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <section class="approve-stats">


        <div class="approve-stat">

            <div class="approve-stat-icon">

                <i class="fa-solid fa-file-lines"></i>

            </div>

            <h3>
                <?php
                echo $total_applications;
                ?>
            </h3>

            <p>
                Total Applications
            </p>

        </div>


        <div class="approve-stat">

            <div class="approve-stat-icon">

                <i class="fa-solid fa-clock"></i>

            </div>

            <h3>
                <?php
                echo $pending_applications;
                ?>
            </h3>

            <p>
                Pending Applications
            </p>

        </div>


        <div class="approve-stat">

            <div class="approve-stat-icon">

                <i class="fa-solid fa-circle-check"></i>

            </div>

            <h3>
                <?php
                echo $approved_applications;
                ?>
            </h3>

            <p>
                Approved Applications
            </p>

        </div>


    </section>



    <!-- =====================================================
         APPLICATIONS
    ====================================================== -->

    <?php if (
        count($applications) > 0
    ): ?>


        <div class="applications-list">


            <?php foreach (
                $applications
                as $application
            ): ?>


                <?php

                $student_name =
                    $application['name']
                    ?? 'Unknown Student';


                $student_initial =
                    strtoupper(
                        substr(
                            trim(
                                $student_name
                            ),
                            0,
                            1
                        )
                    );


                $status =
                    $application['status']
                    ?? 'Pending';


                $status_class =
                    strtolower($status);

                ?>


                <div class="application-card">


                    <!-- STUDENT -->

                    <div class="application-top">


                        <div class="student-info">


                            <div class="student-avatar">

                                <?php
                                echo htmlspecialchars(
                                    $student_initial
                                );
                                ?>

                            </div>


                            <div>

                                <h3>

                                    <?php
                                    echo htmlspecialchars(
                                        $student_name
                                    );
                                    ?>

                                </h3>


                                <p>

                                    Student ID:

                                    <?php
                                    echo htmlspecialchars(
                                        $application[
                                            'student_id'
                                        ]
                                    );
                                    ?>

                                </p>

                            </div>


                        </div>


                        <span
                            class="status
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



                    <!-- DETAILS -->

                    <div class="application-details">


                        <div class="detail-row">

                            <div class="detail-label">
                                Application
                            </div>

                            <div class="detail-value">

                                <?php
                                echo htmlspecialchars(
                                    $application[
                                        'application_type'
                                    ]
                                );
                                ?>

                            </div>

                        </div>


                        <div class="detail-row">

                            <div class="detail-label">
                                Course
                            </div>

                            <div class="detail-value">

                                <?php
                                echo htmlspecialchars(
                                    $application[
                                        'course'
                                    ] ?? '-'
                                );
                                ?>

                            </div>

                        </div>


                        <div class="detail-row">

                            <div class="detail-label">
                                Semester
                            </div>

                            <div class="detail-value">

                                <?php
                                echo htmlspecialchars(
                                    $application[
                                        'semester'
                                    ] ?? '-'
                                );
                                ?>

                            </div>

                        </div>


                        <div class="detail-row">

                            <div class="detail-label">
                                Email
                            </div>

                            <div class="detail-value">

                                <?php
                                echo htmlspecialchars(
                                    $application[
                                        'email'
                                    ] ?? '-'
                                );
                                ?>

                            </div>

                        </div>


                    </div>



                    <!-- DESCRIPTION -->

                    <div class="description">

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



                    <!-- EXISTING RESPONSE -->

                    <?php if (
                        !empty(
                            $application[
                                'response'
                            ]
                        )
                    ): ?>


                        <div class="response-box">


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



                    <!-- ACTION -->

                    <?php if (
                        $status === "Pending"
                    ): ?>


                        <form
                            method="POST"
                            class="action-form"
                        >


                            <input
                                type="hidden"
                                name="application_id"
                                value="<?php
                                echo (int)
                                    $application[
                                        'application_id'
                                    ];
                                ?>"
                            >


                            <textarea
                                name="response"
                                placeholder="Write a response to the student (optional)..."
                            ></textarea>


                            <div class="action-buttons">


                                <button
                                    type="submit"
                                    name="action"
                                    value="approve"
                                    class="btn btn-approve"
                                >

                                    <i class="fa-solid fa-check"></i>

                                    Approve

                                </button>


                                <button
                                    type="submit"
                                    name="action"
                                    value="reject"
                                    class="btn btn-reject"
                                >

                                    <i class="fa-solid fa-xmark"></i>

                                    Reject

                                </button>


                            </div>


                        </form>


                    <?php endif; ?>


                </div>


            <?php endforeach; ?>


        </div>


    <?php else: ?>


        <div class="no-applications">


            <i class="fa-solid fa-file-circle-check"></i>


            <h2>
                No Applications
            </h2>


            <p>

                No student applications have
                been assigned to you yet.

            </p>


        </div>


    <?php endif; ?>


</div>


</body>

</html>


<?php

mysqli_close($conn);

?>