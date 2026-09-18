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

$faculty_stmt = mysqli_prepare($conn, $faculty_sql);

if (!$faculty_stmt) {
    die("Database Error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $faculty_stmt,
    "s",
    $faculty_id
);

mysqli_stmt_execute($faculty_stmt);

$faculty_result = mysqli_stmt_get_result($faculty_stmt);

$faculty = mysqli_fetch_assoc($faculty_result);

mysqli_stmt_close($faculty_stmt);


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

$faculty_name = trim($faculty['name'] ?? '');

$initial = $faculty_name !== ''
    ? strtoupper(substr($faculty_name, 0, 1))
    : 'F';


/* =========================================================
   APPROVE / REJECT APPLICATION
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $application_id = isset($_POST['application_id'])
        ? (int) $_POST['application_id']
        : 0;

    $action = isset($_POST['action'])
        ? $_POST['action']
        : "";

    $response = isset($_POST['response'])
        ? trim($_POST['response'])
        : "";


    if (
        $application_id > 0 &&
        (
            $action === "approve" ||
            $action === "reject"
        )
    ) {

        $status = (
            $action === "approve"
            ? "Approved"
            : "Rejected"
        );


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

            mysqli_stmt_execute($update_stmt);

            mysqli_stmt_close($update_stmt);

        }

    }


    header("Location: approve_application.php");
    exit();

}


/* =========================================================
   GET APPLICATIONS
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

$app_stmt = mysqli_prepare($conn, $app_sql);

if (!$app_stmt) {
    die("Database Error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $app_stmt,
    "s",
    $faculty_id
);

mysqli_stmt_execute($app_stmt);

$app_result = mysqli_stmt_get_result($app_stmt);


while ($row = mysqli_fetch_assoc($app_result)) {

    $applications[] = $row;

}

mysqli_stmt_close($app_stmt);


/* =========================================================
   STATISTICS
========================================================= */

$total_applications = count($applications);

$pending_applications = 0;
$approved_applications = 0;
$rejected_applications = 0;


foreach ($applications as $application) {

    $status = $application['status'] ?? 'Pending';

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
        CampusConnect | Approve Applications
    </title>


    <!-- GOOGLE FONT -->

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


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    >



<style>

/* =========================================================
   APPROVE APPLICATIONS PAGE
   ========================================================= */

html, body {
    margin: 0;
    padding: 0;
    background: #212523;
}

.approve-page {
    min-height: 100vh;
    width: 100%;
    margin: 0;
    padding: 30px 40px 60px;
    background:
        radial-gradient(circle at 100% 0%, rgba(27, 189, 136, 0.10), transparent 30%),
        #212523;
    color: #d3dbe6;
    font-family: 'Inter', sans-serif;
    box-sizing: border-box;
}

/* Apply box sizing only inside this page */
.approve-page *,
.approve-page *::before,
.approve-page *::after {
    box-sizing: border-box;
}

/* =========================================================
   MAIN CONTENT
   ========================================================= */

.approve-page .approve-main {
    width: 100%;
    max-width: 1250px;
    min-height: calc(100vh - 60px);
    margin: 0 auto;
    padding: 0;
    background: transparent;
}

/* =========================================================
   BACK TO DASHBOARD
   ========================================================= */

.approve-page .back-dashboard {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    padding: 10px 18px;
    margin-bottom: 25px;

    background: #143d28;
    color: #9ffce1;

    border: 1px solid #184832;
    border-radius: 18px;

    text-decoration: none;
    font-size: 13px;
    font-weight: 600;

    transition: all 0.2s ease;
}

.approve-page .back-dashboard:hover {
    background: #184832;
    color: #b3fae6;
    transform: translateY(-2px);
}

/* =========================================================
   HEADER
   ========================================================= */

.approve-page .approve-header {
    width: 100%;

    display: flex;
    justify-content: space-between;
    align-items: center;

    gap: 30px;
    margin-bottom: 30px;
}

.approve-page .approve-heading {
    min-width: 0;
}

.approve-page .approve-heading h1 {
    margin: 0 0 6px;

    font-size: 38px;
    line-height: 1.2;
    font-weight: 800;

    color: #c1faeb;
}

.approve-page .approve-heading p {
    margin: 0;

    font-size: 14px;
    color: #999ea9;
}

/* =========================================================
   FACULTY PROFILE
   ========================================================= */

.approve-page .faculty-profile {
    display: flex;
    align-items: center;
    gap: 12px;

    padding: 10px 16px;

    background: #17191e;
    border: 1px solid #23302b;
    border-radius: 16px;

    box-shadow: 0 8px 25px rgba(15, 118, 110, 0.08);

    flex-shrink: 0;
}

.approve-page .faculty-avatar {
    width: 42px;
    height: 42px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: #143d28;
    color: #9ffce1;

    font-size: 16px;
    font-weight: 700;

    flex-shrink: 0;
}

.approve-page .faculty-info {
    min-width: 0;
}

.approve-page .faculty-info strong {
    display: block;

    font-size: 14px;
    color: #e0e5f1;

    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.approve-page .faculty-info span {
    display: block;

    margin-top: 2px;

    font-size: 12px;
    color: #999ea9;
}

/* =========================================================
   STATISTICS
========================================================= */

.approve-page .stats {
    width: 100%;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 30px;
}

.approve-page .stat-card {
    min-width: 0;

    display: flex;
    align-items: center;
    gap: 15px;

    padding: 20px;

    background: #17191e;

    border: 1px solid #23302b;
    border-radius: 18px;

    box-shadow: 0 8px 25px rgba(15, 118, 110, 0.08);

    transition: all 0.25s ease;
}

.approve-page .stat-card:hover {
    transform: translateY(-4px);

    box-shadow: 0 14px 32px rgba(15, 118, 110, 0.13);
}

.approve-page .stat-card .icon {
    width: 48px;
    height: 48px;

    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    border-radius: 14px;

    background: #202422;
    color: #11d498;

    font-size: 20px;
}

.approve-page .stat-card h3 {
    margin: 0;

    font-size: 26px;
    line-height: 1.1;
    font-weight: 800;

    color: #c1faeb;
}

.approve-page .stat-card p {
    margin: 4px 0 0;

    font-size: 12px;
    color: #999ea9;
}
/* =========================================================
   APPLICATION LIST
   ========================================================= */

.approve-page .applications-list {
    width: 100%;

    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));

    gap: 22px;
}

/* =========================================================
   APPLICATION CARD
   ========================================================= */

.approve-page .application-card {
    width: 100%;
    min-width: 0;

    padding: 24px;

    background: #17191e;

    border: 1px solid #23302b;
    border-radius: 20px;

    box-shadow: 0 8px 25px rgba(15, 118, 110, 0.08);

    overflow: hidden;

    transition:
        transform 0.25s ease,
        box-shadow 0.25s ease;
}

.approve-page .application-card:hover {
    transform: translateY(-5px);

    box-shadow: 0 16px 35px rgba(15, 118, 110, 0.14);
}

/* =========================================================
   APPLICATION TOP
   ========================================================= */

.approve-page .application-top {
    display: flex;
    justify-content: space-between;
    align-items: center;

    gap: 15px;

    padding-bottom: 18px;
    margin-bottom: 18px;

    border-bottom: 1px solid #1e2220;
}

.approve-page .student-info {
    display: flex;
    align-items: center;
    gap: 12px;

    min-width: 0;
}

.approve-page .student-avatar {
    width: 44px;
    height: 44px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: #143d28;
    color: #9ffce1;

    font-size: 15px;
    font-weight: 700;

    flex-shrink: 0;
}

.approve-page .student-details {
    min-width: 0;
}

.approve-page .student-details h3 {
    margin: 0;

    font-size: 16px;
    font-weight: 700;

    color: #e0e5f1;

    overflow-wrap: anywhere;
}

.approve-page .student-details p {
    margin: 3px 0 0;

    font-size: 12px;
    color: #999ea9;

    overflow-wrap: anywhere;
}

/* =========================================================
   STATUS
   ========================================================= */

.approve-page .status {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    padding: 6px 11px;

    border-radius: 20px;

    font-size: 11px;
    font-weight: 700;

    white-space: nowrap;
    flex-shrink: 0;
}

.approve-page .status.pending {
    background: #3f3615;
    color: #d45b11;
}

.approve-page .status.approved {
    background: #143d28;
    color: #9ffce1;
}

.approve-page .status.rejected {
    background: #221e1e;
    color: #e82121;
}

/* =========================================================
   APPLICATION DETAILS
   ========================================================= */

.approve-page .application-details {
    width: 100%;
}

.approve-page .detail-row {
    width: 100%;

    display: grid;
    grid-template-columns: 125px minmax(0, 1fr);

    gap: 15px;

    margin-bottom: 11px;

    align-items: start;
}

.approve-page .detail-label {
    font-size: 12px;
    font-weight: 600;

    color: #999ea9;
}

.approve-page .detail-value {
    min-width: 0;

    font-size: 13px;
    font-weight: 500;

    color: #d3dbe6;

    overflow-wrap: anywhere;
    word-break: break-word;
}

/* =========================================================
   DESCRIPTION
   ========================================================= */

.approve-page .description {
    width: 100%;

    margin-top: 15px;
    padding: 15px;

    background: #222624;

    border: 1px solid #1b1f1d;
    border-radius: 12px;

    font-size: 13px;
    line-height: 1.6;

    color: #4b699b;

    overflow-wrap: anywhere;
    word-break: break-word;
}

/* =========================================================
   RESPONSE BOX
   ========================================================= */

.approve-page .response-box {
    width: 100%;

    margin-top: 15px;
    padding: 15px;

    background: #212522;

    border-left: 4px solid #1bbd88;
    border-radius: 10px;

    font-size: 13px;
    line-height: 1.6;

    color: #4b699b;

    overflow-wrap: anywhere;
}

/* =========================================================
   ACTION FORM
   ========================================================= */

.approve-page .action-form {
    width: 100%;

    margin-top: 18px;
    padding-top: 18px;

    border-top: 1px solid #1e2220;
}

.approve-page .action-form textarea {
    width: 100%;
    min-height: 85px;

    padding: 12px 14px;

    border: 1px solid #141516;
    border-radius: 12px;

    background: #17191e;

    font-family: 'Inter', sans-serif;
    font-size: 13px;

    color: #d3dbe6;

    resize: vertical;

    outline: none;

    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.approve-page .action-form textarea:focus {
    border-color: #1bbd88;

    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12);
}

.approve-page .action-form textarea::placeholder {
    color: #9ca3af;
}

/* =========================================================
   BUTTONS
   ========================================================= */

.approve-page .action-buttons {
    display: flex;
    gap: 10px;

    margin-top: 12px;
}

.approve-page .btn {
    min-height: 40px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    padding: 9px 18px;

    border: none;
    border-radius: 10px;

    font-family: 'Inter', sans-serif;
    font-size: 12px;
    font-weight: 600;

    cursor: pointer;

    transition: all 0.2s ease;
}

.approve-page .btn-approve {
    background: #169c73;
    color: #ffffff;
}

.approve-page .btn-approve:hover {
    background: #169c76;
    transform: translateY(-2px);
}

.approve-page .btn-reject {
    background: #221e1e;
    color: #e82121;
}

.approve-page .btn-reject:hover {
    background: #3e1515;
    transform: translateY(-2px);
}

/* =========================================================
   NO APPLICATIONS
   ========================================================= */

.approve-page .no-applications {
    width: 100%;
    max-width: 1250px;
    margin: 0 auto;

    padding: 60px 30px;

    text-align: center;

    background: #17191e;

    border: 1px solid #23302b;
    border-radius: 20px;

    box-shadow: 0 8px 25px rgba(15, 118, 110, 0.08);
}

.approve-page .no-applications h3 {
    margin: 0 0 8px;

    font-size: 20px;
    color: #c1faeb;
}

.approve-page .no-applications p {
    margin: 0;

    font-size: 13px;
    color: #999ea9;
}

/* =========================================================
   TABLET
   ========================================================= */

@media (max-width: 950px) {

    .approve-page {
        padding: 25px 25px 50px;
    }

    .approve-page .applications-list {
        grid-template-columns: 1fr;
    }

    .approve-page .approve-heading h1 {
        font-size: 32px;
    }
}

/* =========================================================
   MOBILE
   ========================================================= */

@media (max-width: 700px) {

    .approve-page {
        padding: 20px 16px 40px;
    }

    .approve-page .approve-header {
        flex-direction: column;
        align-items: flex-start;

        gap: 18px;
    }

    .approve-page .faculty-profile {
        width: 100%;
    }

    .approve-page .stats {
    grid-template-columns: 1fr;
}

    .approve-page .application-card {
        padding: 18px;
    }

    .approve-page .application-top {
        align-items: flex-start;
    }
}

/* =========================================================
   SMALL MOBILE
   ========================================================= */

@media (max-width: 500px) {

    .approve-page {
        padding: 18px 12px 35px;
    }

    .approve-page .approve-heading h1 {
        font-size: 28px;
    }

    .approve-page .approve-heading p {
        font-size: 12px;
    }

    .approve-page .application-top {
        flex-direction: column;
        align-items: flex-start;
    }

    .approve-page .detail-row {
        grid-template-columns: 1fr;
        gap: 3px;
        margin-bottom: 14px;
    }

    .approve-page .action-buttons {
        flex-direction: column;
    }

    .approve-page .btn {
        width: 100%;
    }
}

</style>
</head>


<body>


<div class="approve-page">


    <main class="approve-main">


        <!-- =================================================
             BACK TO DASHBOARD
        ================================================== -->

        <a
            href="faculty_dashboard.php"
            class="back-dashboard"
        >

            <i class="fa-solid fa-arrow-left"></i>

            <span>
                Back to Dashboard
            </span>

        </a>



        <!-- =================================================
             HEADER
        ================================================== -->

        <header class="approve-header">


            <div class="approve-heading">

                <h1>
                    Approve Applications
                </h1>

                <p>
                    Applications assigned to you
                </p>

            </div>


            <!-- FACULTY PROFILE -->

            <div class="faculty-profile">


                <div class="faculty-avatar">

                    <?php
                    echo htmlspecialchars($initial);
                    ?>

                </div>


                <div class="faculty-info">

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
                        <?php
                        echo htmlspecialchars(
                            $faculty['designation']
                        );
                        ?>

                    </span>

                </div>


            </div>


        </header>



        <!-- =================================================
             STATISTICS
        ================================================== -->

        <section class="stats">


            <!-- TOTAL -->

            <div class="stat-card">

                <div class="icon">

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


            <!-- PENDING -->

            <div class="stat-card">

                <div class="icon">

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


            <!-- APPROVED -->

            <div class="stat-card">

                <div class="icon">

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



        <!-- =================================================
             APPLICATIONS
        ================================================== -->

        <?php if (count($applications) > 0): ?>


            <section class="applications-list">


                <?php foreach ($applications as $application): ?>


                    <?php

                    $student_name =
                        $application['name']
                        ?? 'Unknown Student';


                    $student_initial =
                        $student_name !== ''
                        ? strtoupper(
                            substr(
                                trim($student_name),
                                0,
                                1
                            )
                        )
                        : 'S';


                    $status =
                        $application['status']
                        ?? 'Pending';


                    $status_class =
                        strtolower($status);

                    ?>


                    <article class="application-card">


                        <!-- APPLICATION TOP -->

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
                                            $application['student_id']
                                        );
                                        ?>

                                    </p>

                                </div>


                            </div>


                            <!-- STATUS -->

                            <span
                                class="status <?php
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



                        <!-- APPLICATION DETAILS -->

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
                                        ] ?? '-'
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

                        <?php if (
                            !empty(
                                $application['description']
                            )
                        ): ?>

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

                        <?php endif; ?>



                        <!-- FACULTY RESPONSE -->

                        <?php if (
                            !empty(
                                $application['response']
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



                        <!-- ACTION FORM -->

                        <?php if ($status === "Pending"): ?>

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


                    </article>


                <?php endforeach; ?>


            </section>


        <?php else: ?>


            <section class="no-applications">

                <i class="fa-solid fa-file-circle-check"></i>

                <h2>
                    No Applications
                </h2>

                <p>
                    No student applications have been
                    assigned to you yet.
                </p>

            </section>


        <?php endif; ?>


    </main>


</div>


</body>

</html>

<?php

mysqli_close($conn);

?>