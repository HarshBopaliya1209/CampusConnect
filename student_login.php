<?php

session_start();

include("db.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $student_id = trim($_POST['student_id']);
    $password = $_POST['password'];

    $sql = "
        SELECT *
        FROM students
        WHERE student_id = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $student_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {

        $student = mysqli_fetch_assoc($result);

        if ($password === $student['password']) {

            /* Remove any old student/faculty session */
            session_unset();

            /* Create student session */
            $_SESSION['student_id'] = $student['student_id'];

            /* Go to student dashboard */
            header("Location: student_dashboard.php");
            exit();

        } else {

            $error = "Invalid Student ID or Password.";

        }

    } else {

        $error = "Invalid Student ID or Password.";

    }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">

    <title>Login Error</title>

</head>

<body>

<?php

if ($error != "") {

    echo "<script>
            alert('" . addslashes($error) . "');
            window.location='student_login.php';
          </script>";

}

?>

</body>
</html>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CampusConnect - Student Login</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css?v=10">

<body>

    <!-- Login Section -->

    <section class="login-section">

        <div class="login-container">

            <!-- Left Side -->

            <div class="login-left">

                <h1>Student Portal</h1>

                <p>
                    Access notices, applications, library services,
                    canteen orders, announcements and payment records
                    through one secure platform.
                </p>

            </div>

            <!-- Right Side -->

            <div class="login-right">

                <h2>Student Login</h2>

                <p class="subtitle">
                    Login to continue
                </p>

                       <form action="login.php" method="POST" autocomplete="off">

    <label>Student ID</label>

    <input
        type="text"
        name="student_id"
        placeholder="Enter Student ID"
        autocomplete="off"
        required
    >

    <label>Password</label>

    <input
        type="password"
        name="password"
        placeholder="Enter Password"
        autocomplete="new-password"
        spellcheck="false"
        required
    >

    <button type="submit">
        Login
    </button>

</form>

                <div class="signup">

                    Don't have an account?

                    <a href="student_register.php">
                        Register Now
                    </a>

                </div>

            </div>

        </div>

    </section>

</body>

</html>