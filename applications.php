<?php

session_start();
include("db.php");


/* =========================================================
   STUDENT / FACULTY LOGIN CHECK
========================================================= */

if (isset($_SESSION['faculty_id'])) {

    header("Location: approve_application.php");
    exit();

}


if (!isset($_SESSION['student_id'])) {

    header("Location: student_login.php");
    exit();

}


$student_id = (int) $_SESSION['student_id'];


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

    die(
        "Database Error: " .
        mysqli_error($conn)
    );

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
    substr(
        trim($student['name']),
        0,
        1
    )
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

    while (
        $faculty = mysqli_fetch_assoc(
            $faculty_result
        )
    ) {

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

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {

    $application_type =
        isset($_POST['application_type'])
        ? trim($_POST['application_type'])
        : "";


    $title =
        isset($_POST['title'])
        ? trim($_POST['title'])
        : "";


    $description =
        isset($_POST['description'])
        ? trim($_POST['description'])
        : "";


    $faculty_id =
        isset($_POST['faculty_id'])
        ? trim($_POST['faculty_id'])
        : "";


    /* =====================================================
       VALIDATION
    ===================================================== */

    if (
        $application_type === "" ||
        $title === "" ||
        $description === "" ||
        $faculty_id === ""
    ) {

        $message =
            "Please fill in all fields and select a faculty.";

        $message_type =
            "error";

    } else {


        /* =================================================
           CHECK FACULTY
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


        if (!$check_stmt) {

            $message =
                "Unable to verify selected faculty.";

            $message_type =
                "error";

        } else {


            mysqli_stmt_bind_param(
                $check_stmt,
                "s",
                $faculty_id
            );


            mysqli_stmt_execute(
                $check_stmt
            );


            $check_result =
                mysqli_stmt_get_result(
                    $check_stmt
                );


            $faculty_exists =
                mysqli_num_rows(
                    $check_result
                ) > 0;


            mysqli_stmt_close(
                $check_stmt
            );


            if (!$faculty_exists) {

                $message =
                    "Selected faculty does not exist.";

                $message_type =
                    "error";

            } else {


                /* =========================================
                   CREATE DESCRIPTION
                ========================================= */

                $final_description =
                    "Title: " .
                    $title .
                    "\n\n" .
                    $description;


                /* =========================================
                   INSERT APPLICATION
                ========================================= */

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


                $insert_stmt =
                    mysqli_prepare(
                        $conn,
                        $insert_sql
                    );


                if (!$insert_stmt) {

                    $message =
                        "Unable to prepare application.";

                    $message_type =
                        "error";

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

                        $message_type =
                            "success";

                    } else {

                        $message =
                            "Failed to submit application.";

                        $message_type =
                            "error";

                    }


                    mysqli_stmt_close(
                        $insert_stmt
                    );

                }

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


$app_stmt =
    mysqli_prepare(
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

        $applications[] =
            $row;

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
    $applications
    as $application
) {

    $status =
        $application['status']
        ?? 'Pending';


    if ($status === "Pending") {

        $pending_applications++;

    }


    if ($status === "Approved") {

        $approved_applications++;

    }


    if ($status === "Rejected") {

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
        href="https://fonts.googleapis.com"
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


    <!-- MAIN CSS -->
    <link
        rel="stylesheet"
        href="assets/css/style.css?v=61"
    >

</head>


<body>


<div class="student-app-page">


    <!-- =====================================================
         BACK TO DASHBOARD
    ====================================================== -->

   <a
    href="student_dashboard.php"
    class="back-dashboard"
>
    <i class="fa-solid fa-arrow-left"></i>
    <span>Back to Dashboard</span>
</a>



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

                echo htmlspecialchars(
                    $initial
                );

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

    <?php if (
        !empty($message)
    ): ?>


        <div
            class="app-message
            <?php

            echo htmlspecialchars(
                $message_type
            );

            ?>"
        >

            <?php

            echo htmlspecialchars(
                $message
            );

            ?>

        </div>


    <?php endif; ?>



    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <section class="app-stats">


        <div class="app-stat-card">

            <div class="app-stat-icon blue">

                <i
                    class="fa-solid fa-file-lines"
                ></i>

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



        <div class="app-stat-card">

            <div class="app-stat-icon orange">

                <i
                    class="fa-solid fa-clock"
                ></i>

            </div>


            <h3>

                <?php

                echo $pending_applications;

                ?>

            </h3>


            <p>
                Pending
            </p>

        </div>



        <div class="app-stat-card">

            <div class="app-stat-icon green">

                <i
                    class="fa-solid fa-circle-check"
                ></i>

            </div>


            <h3>

                <?php

                echo $approved_applications;

                ?>

            </h3>


            <p>
                Approved
            </p>

        </div>



        <div class="app-stat-card">

            <div class="app-stat-icon red">

                <i
                    class="fa-solid fa-circle-xmark"
                ></i>

            </div>


            <h3>

                <?php

                echo $rejected_applications;

                ?>

            </h3>


            <p>
                Rejected
            </p>

        </div>


    </section>



    <!-- =====================================================
         FORM + APPLICATION LIST
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


                    <div
                        class="faculty-info"
                    >

                        Your application will be
                        sent to the selected faculty.

                    </div>

                </div>



                <!-- SUBMIT -->

                <button
                    type="submit"
                    class="app-submit-btn"
                >

                    <i
                        class="fa-solid fa-paper-plane"
                    ></i>

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
                        strtolower(
                            $status
                        );

                    ?>


                    <div
                        class="application-item"
                    >


                        <div
                            class="application-item-top"
                        >


                            <div
                                class="application-name"
                            >


                                <div
                                    class="application-icon"
                                >

                                    <i
                                        class="fa-solid fa-file-lines"
                                    ></i>

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



                        <div
                            class="application-description"
                        >

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



                        <div
                            class="application-faculty"
                        >

                            <i
                                class="fa-solid fa-user-tie"
                            ></i>

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
                                $application[
                                    'response'
                                ]
                            )
                        ): ?>


                            <div
                                class="application-response"
                            >

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


                <div
                    class="app-empty"
                >

                    <i
                        class="fa-solid fa-file-circle-plus"
                    ></i>


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