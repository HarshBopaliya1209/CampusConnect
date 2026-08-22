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

$faculty_sql = "
    SELECT
        faculty_id,
        name,
        department,
        designation
    FROM faculty
    WHERE faculty_id = ?
";

$faculty_stmt = mysqli_prepare($conn, $faculty_sql);

mysqli_stmt_bind_param(
    $faculty_stmt,
    "s",
    $faculty_id
);

mysqli_stmt_execute($faculty_stmt);

$faculty_result = mysqli_stmt_get_result($faculty_stmt);

$faculty = mysqli_fetch_assoc($faculty_result);

if (!$faculty) {
    session_unset();
    session_destroy();

    header("Location: faculty_login.php");
    exit();
}

/* =========================================================
   VARIABLES
========================================================= */

$message = "";
$message_type = "";

$title = "";
$description = "";
$department = "";

/* =========================================================
   POST NOTICE
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $department = trim($_POST['department'] ?? '');

    if ($title === '') {

        $message = "Please enter a notice title.";
        $message_type = "error";

    } elseif ($department === '') {

        $message = "Please select a department.";
        $message_type = "error";

    } elseif ($description === '') {

        $message = "Please enter the notice description.";
        $message_type = "error";

    } else {

        /*
         * notices.faculty_id is INT
         * faculty.faculty_id is VARCHAR such as F001
         */

        $numeric_faculty_id = intval(
            preg_replace(
                '/[^0-9]/',
                '',
                $faculty['faculty_id']
            )
        );

        $sql = "
            INSERT INTO notices
            (
                faculty_id,
                title,
                description,
                department
            )
            VALUES (?, ?, ?, ?)
        ";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "isss",
            $numeric_faculty_id,
            $title,
            $description,
            $department
        );

        if (mysqli_stmt_execute($stmt)) {

            $message = "Notice published successfully!";
            $message_type = "success";

            $title = "";
            $description = "";
            $department = "";

        } else {

            $message = "Error while publishing notice: " . mysqli_error($conn);
            $message_type = "error";
        }

        mysqli_stmt_close($stmt);
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
        CampusConnect | Post Notice
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
        href="assets/css/style.css?v=60"
    >

</head>


<body>


<div class="post-notice-page">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="post-notice-header">


        <div>

            <span class="post-notice-label">
                FACULTY PORTAL
            </span>

            <h1>

                <i class="fa-solid fa-bullhorn"></i>

                Post Notice

            </h1>

            <p>
                Publish important announcements for students.
            </p>

        </div>


        <!-- Faculty -->

        <div class="post-notice-profile">


            <div class="post-notice-avatar">

                <?php

                echo strtoupper(
                    substr(
                        $faculty['name'],
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

    </div>



    <!-- =====================================================
         ACTIONS
    ====================================================== -->

    <div class="post-notice-actions">

        <a
            href="faculty_dashboard.php"
            class="post-back-btn"
        >

            <i class="fa-solid fa-arrow-left"></i>

            Dashboard

        </a>


        <a
            href="notices.php"
            class="post-view-btn"
        >

            <i class="fa-solid fa-eye"></i>

            View Notices

        </a>

    </div>



    <!-- =====================================================
         MAIN CARD
    ====================================================== -->

    <div class="post-notice-card">


        <!-- LEFT INFORMATION -->

        <div class="post-notice-info">


            <div class="post-notice-icon">

                <i class="fa-solid fa-bullhorn"></i>

            </div>


            <h2>
                Create an Announcement
            </h2>


            <p>

                Share important information,
                examination updates, events,
                holidays and other campus announcements
                with students.

            </p>


            <div class="post-info-list">

                <div>

                    <i class="fa-solid fa-user"></i>

                    <span>
                        <?php
                        echo htmlspecialchars(
                            $faculty['name']
                        );
                        ?>
                    </span>

                </div>


                <div>

                    <i class="fa-solid fa-building"></i>

                    <span>
                        <?php
                        echo htmlspecialchars(
                            $faculty['department']
                        );
                        ?>
                    </span>

                </div>


                <div>

                    <i class="fa-solid fa-user-tie"></i>

                    <span>
                        <?php
                        echo htmlspecialchars(
                            $faculty['designation']
                        );
                        ?>
                    </span>

                </div>

            </div>

        </div>



        <!-- FORM -->

        <div class="post-notice-form-area">


            <h2>
                Notice Details
            </h2>


            <?php if ($message !== "") { ?>

                <div
                    class="post-notice-message <?php echo $message_type; ?>"
                >

                    <i
                        class="fa-solid
                        <?php
                        echo $message_type === "success"
                            ? "fa-circle-check"
                            : "fa-circle-exclamation";
                        ?>"
                    ></i>

                    <span>

                        <?php

                        echo htmlspecialchars(
                            $message
                        );

                        ?>

                    </span>

                </div>

            <?php } ?>



            <form
                method="POST"
                autocomplete="off"
            >


                <!-- TITLE -->

                <div class="post-form-group">

                    <label for="title">

                        Notice Title

                    </label>

                    <input
                        type="text"
                        id="title"
                        name="title"
                        maxlength="255"
                        placeholder="Enter notice title"
                        value="<?php
                            echo htmlspecialchars(
                                $title
                            );
                        ?>"
                        required
                    >

                </div>

                <div class="post-form-group">

                <label for="department">
                    Send Notice To
                </label>

                <select name="department" id="department" required>


                    <option value="">Select Department</option>

                    <option value="All">All Departments</option>
                    <option value="BCA">BCA</option>
                    <option value="BBA">BBA</option>
                    <option value="B.Com">B.Com</option>
                    <option value="B.Sc">B.Sc</option>
                    <option value="MCA">MCA</option>
                    <option value="M.Com">M.Com</option>
                    <option value="LLB">LLB</option>
                    <option value="B.Ed">B.Ed</option>

                    </select>

                            </div>


                <!-- DESCRIPTION -->

                <div class="post-form-group">

                    <label for="description">

                        Notice Description

                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="8"
                        placeholder="Write the notice details here..."
                        required
                    ><?php
                        echo htmlspecialchars(
                            $description
                        );
                    ?></textarea>

                    <small>
                        Provide clear and complete information
                        for students.
                    </small>

                </div>



                <!-- BUTTONS -->

                <div class="post-form-buttons">

                    <button
                        type="reset"
                        class="post-reset-btn"
                    >

                        <i class="fa-solid fa-rotate-left"></i>

                        Clear

                    </button>


                    <button
                        type="submit"
                        class="post-submit-btn"
                    >

                        <i class="fa-solid fa-paper-plane"></i>

                        Publish Notice

                    </button>

                </div>


            </form>

        </div>

    </div>


</div>


</body>

</html>

<?php

mysqli_close($conn);

?>