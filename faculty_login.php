<?php

session_start();

include("db.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $faculty_id  = trim($_POST['faculty_id'] ?? '');
    $password    = $_POST['password'] ?? '';
    $secret_code = trim($_POST['secret_code'] ?? '');

    if (
        $faculty_id == "" ||
        $password == "" ||
        $secret_code == ""
    ) {

        $error = "Please fill in all fields.";

    } else {

        $sql = "
            SELECT
                faculty_id,
                name,
                email,
                department,
                designation,
                password,
                secret_code
            FROM faculty
            WHERE faculty_id = ?
            AND secret_code = ?
        ";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {

            $error = "Database error: " . mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "ss",
                $faculty_id,
                $secret_code
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);


            /* Check Faculty */

            if (mysqli_num_rows($result) == 1) {

                $faculty = mysqli_fetch_assoc($result);


                /* Check Password */

                if ($password === $faculty['password']) {

                    /* Clear previous session */
                    session_unset();


                    /* Create Faculty Session */

                    $_SESSION['faculty_id'] =
                        $faculty['faculty_id'];

                    $_SESSION['faculty_name'] =
                        $faculty['name'];

                    $_SESSION['faculty_email'] =
                        $faculty['email'];

                    $_SESSION['faculty_department'] =
                        $faculty['department'];

                    $_SESSION['faculty_designation'] =
                        $faculty['designation'];


                    /* Redirect */

                    header(
                        "Location: faculty_dashboard.php"
                    );

                    exit();

                } else {

                    $error =
                        "Invalid Faculty ID or Password.";

                }

            } else {

                $error =
                    "Invalid Faculty ID or Secret Code.";

            }

            mysqli_stmt_close($stmt);
        }
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

    <title>CampusConnect | Faculty Login</title>

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
        href="assets/css/style.css?v=30"
    >

</head>

<body>

<section class="login-section">

    <div class="login-container faculty-login">

        <!-- LEFT SIDE -->

        <div class="login-left">

            <div class="faculty-icon">

                <i class="fa-solid fa-chalkboard-user"></i>

            </div>

            <h1>
                Faculty Portal
            </h1>

            <p>
                Manage notices, student applications,
                academic activities and campus services
                from one secure platform.
            </p>

        </div>


        <!-- RIGHT SIDE -->

        <div class="login-right">

            <h2>
                Faculty Login
            </h2>

            <p class="subtitle">
                Login to continue
            </p>


            <?php if ($error != "") { ?>

                <div class="login-error">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <?php
                    echo htmlspecialchars($error);
                    ?>

                </div>

            <?php } ?>


            <form method="POST">

                <div class="form-field">

                    <label>
                        Faculty ID
                    </label>

                    <input
                        type="text"
                        name="faculty_id"
                        placeholder="Enter Faculty ID"
                        autocomplete="off"
                        required
                    >

                </div>


                <div class="form-field">

                    <label>
                        Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        placeholder="Enter Password"
                        autocomplete="new-password"
                        required
                    >

                </div>


                <div class="form-field">

                    <label>
                        Secret Code
                    </label>

                    <input
                        type="text"
                        name="secret_code"
                        placeholder="Enter Secret Code"
                        autocomplete="off"
                        required
                    >

                </div>


                <button type="submit">

                    <i class="fa-solid fa-right-to-bracket"></i>

                    Login

                </button>

            </form>


            <div class="signup">

                Don't have a faculty account?

                <a href="faculty_register.php">
                    Register Now
                </a>

            </div>

        </div>

    </div>

</section>

</body>
</html>