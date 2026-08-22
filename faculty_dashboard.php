<?php

session_start();

include("db.php");

/* =========================================================
   CHECK FACULTY LOGIN
========================================================= */

if (!isset($_SESSION['faculty_id'])) {

    header("Location: faculty_login.php");
    exit();

}

$faculty_id = $_SESSION['faculty_id'];


/* =========================================================
   GET FACULTY DETAILS
========================================================= */

$sql = "
    SELECT
        faculty_id,
        name,
        email,
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

$faculty = mysqli_fetch_assoc($result);


/* =========================================================
   CHECK FACULTY EXISTS
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
    substr($faculty['name'], 0, 1)
);

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
        CampusConnect | Faculty Dashboard
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


    <!-- CampusConnect CSS -->

    <link
        rel="stylesheet"
        href="assets/css/style.css?v=40"
    >

</head>


<body>


<div class="faculty-dashboard">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="faculty-sidebar">


        <!-- Logo -->

        <div class="faculty-brand">

            <h2>
                Campus<span>Connect</span>
            </h2>

            <p>
                Faculty Portal
            </p>

        </div>



        <!-- Navigation -->

        <nav class="faculty-nav">


            <!-- Dashboard -->

            <a
                href="faculty_dashboard.php"
                class="faculty-nav-item active"
            >

                <i class="fa-solid fa-house"></i>

                <span>
                    Dashboard
                </span>

            </a>


            <!-- Notices -->

            <a
                href="notices.php"
                class="faculty-nav-item"
            >

                <i class="fa-solid fa-bullhorn"></i>

                <span>
                    Notices
                </span>

            </a>


            <!-- Post Notice -->

            <a
                href="post_notice.php"
                class="faculty-nav-item"
            >

                <i class="fa-solid fa-pen-to-square"></i>

                <span>
                    Post Notice
                </span>

            </a>


            <!-- Approve Applications -->

            <a
                href="approve_application.php"
                class="faculty-nav-item"
            >

                <i class="fa-solid fa-circle-check"></i>

                <span>
                    Approve Applications
                </span>

            </a>


            <!-- Profile -->

            <a
                href="faculty_profile.php"
                class="faculty-nav-item"
            >

                <i class="fa-solid fa-user"></i>

                <span>
                    Profile
                </span>

            </a>


            <!-- Logout -->

            <a
                href="logout.php"
                class="faculty-nav-item logout-link"
            >

                <i class="fa-solid fa-right-from-bracket"></i>

                <span>
                    Logout
                </span>

            </a>


        </nav>


        <!-- Sidebar Bottom -->

        <div class="faculty-sidebar-bottom">

            <i class="fa-solid fa-chalkboard-user"></i>

            <div>

                <strong>
                    Faculty Portal
                </strong>

                <span>
                    CampusConnect
                </span>

            </div>

        </div>


    </aside>



    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="faculty-main">


        <!-- TOP BAR -->

        <header class="faculty-topbar">


            <!-- Profile -->

            <div class="faculty-user">


                <div class="faculty-avatar">

                    <?php

                    echo htmlspecialchars($initial);

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
                            $faculty['designation']
                        );

                        ?>

                    </span>

                </div>


            </div>


        </header>



        <!-- =================================================
             WELCOME CARD
        ================================================== -->

        <section class="faculty-welcome">


            <div class="faculty-welcome-content">


                <span class="faculty-welcome-tag">

                    <i class="fa-solid fa-shield-halved"></i>

                    Faculty Portal

                </span>


                <h2>

                    Welcome,

                    <?php

                    echo htmlspecialchars(
                        $faculty['name']
                    );

                    ?>

                    👋

                </h2>


                <p>

                    Manage notices, student applications
                    and academic activities from one place.

                </p>


                <div class="faculty-welcome-info">


                    <!-- Faculty ID -->

                    <span>

                        <i class="fa-solid fa-id-card"></i>

                        Faculty ID:

                        <?php

                        echo htmlspecialchars(
                            $faculty['faculty_id']
                        );

                        ?>

                    </span>


                    <!-- Department -->

                    <span>

                        <i class="fa-solid fa-building"></i>

                        <?php

                        echo htmlspecialchars(
                            $faculty['department']
                        );

                        ?>

                    </span>


                    <!-- Designation -->

                    <span>

                        <i class="fa-solid fa-user-tie"></i>

                        <?php

                        echo htmlspecialchars(
                            $faculty['designation']
                        );

                        ?>

                    </span>


                </div>


            </div>


            <div class="faculty-welcome-icon">

                <i class="fa-solid fa-chalkboard-user"></i>

            </div>


        </section>



        <!-- =================================================
             QUICK ACTIONS
        ================================================== -->

        <section class="faculty-section">


            <div class="faculty-section-heading">

                <div>

                    <span>
                        QUICK ACCESS
                    </span>

                    <h2>
                        Manage Campus Activities
                    </h2>

                </div>

            </div>



            <div class="faculty-action-grid">


                <!-- POST NOTICE -->

                <a
                    href="post_notice.php"
                    class="faculty-action-card green"
                >

                    <div class="faculty-action-icon">

                        <i class="fa-solid fa-bullhorn"></i>

                    </div>


                    <h3>
                        Post Notice
                    </h3>


                    <p>
                        Publish important announcements
                        for students.
                    </p>


                    <span class="faculty-action-link">

                        Open

                        <i class="fa-solid fa-arrow-right"></i>

                    </span>

                </a>



                <!-- APPROVE APPLICATIONS -->

                <a
                    href="approve_application.php"
                    class="faculty-action-card purple"
                >

                    <div class="faculty-action-icon">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>


                    <h3>
                        Approve Applications
                    </h3>


                    <p>
                        Approve or reject student
                        applications.
                    </p>


                    <span class="faculty-action-link">

                        Open

                        <i class="fa-solid fa-arrow-right"></i>

                    </span>

                </a>



                <!-- VIEW NOTICES -->

                <a
                    href="notices.php"
                    class="faculty-action-card orange"
                >

                    <div class="faculty-action-icon">

                        <i class="fa-solid fa-newspaper"></i>

                    </div>


                    <h3>
                        View Notices
                    </h3>


                    <p>
                        View and manage campus
                        announcements.
                    </p>


                    <span class="faculty-action-link">

                        Open

                        <i class="fa-solid fa-arrow-right"></i>

                    </span>

                </a>


            </div>

        </section>


    </main>


</div>


</body>

</html>


<?php

mysqli_close($conn);

?>