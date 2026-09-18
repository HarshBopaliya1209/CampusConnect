<?php

session_start();

include("db.php");

$message = "";
$message_type = "";


/* =========================
   REGISTRATION
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $faculty_id  = trim($_POST['faculty_id'] ?? '');
    $name        = trim($_POST['name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $department  = trim($_POST['department'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $password    = $_POST['password'] ?? '';
    $secret_code = trim($_POST['secret_code'] ?? '');


    /* =========================
       VALIDATION
    ========================= */

    if (
        $faculty_id == "" ||
        $name == "" ||
        $email == "" ||
        $department == "" ||
        $designation == "" ||
        $password == "" ||
        $secret_code == ""
    ) {

        $message = "Please fill in all fields.";
        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    } else {


        /* =========================
           CHECK DUPLICATE FACULTY ID
        ========================= */

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

            $message = "Database error. Please try again.";
            $message_type = "error";

        } else {

            mysqli_stmt_bind_param(
                $check_stmt,
                "s",
                $faculty_id
            );

            mysqli_stmt_execute(
                $check_stmt
            );

            $check_result = mysqli_stmt_get_result(
                $check_stmt
            );


            /* =========================
               DUPLICATE FOUND
            ========================= */

            if (mysqli_num_rows($check_result) > 0) {

                $message =
                    "Faculty ID already exists. Please use a different Faculty ID.";

                $message_type = "error";

            } else {


                /* =========================
                   INSERT FACULTY
                ========================= */

                $insert_sql = "
                    INSERT INTO faculty
                    (
                        faculty_id,
                        name,
                        email,
                        department,
                        designation,
                        password,
                        secret_code
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ";


                $insert_stmt = mysqli_prepare(
                    $conn,
                    $insert_sql
                );


                if (!$insert_stmt) {

                    $message =
                        "Unable to create account. Please try again.";

                    $message_type = "error";

                } else {

                    mysqli_stmt_bind_param(
                        $insert_stmt,
                        "sssssss",
                        $faculty_id,
                        $name,
                        $email,
                        $department,
                        $designation,
                        $password,
                        $secret_code
                    );


                    /* =========================
                       SAVE ACCOUNT
                    ========================= */

                    if (mysqli_stmt_execute($insert_stmt)) {

                        echo "
                        <script>
                            alert('Faculty Registration Successful!');
                            window.location='faculty_login.php';
                        </script>
                        ";

                        mysqli_stmt_close($insert_stmt);
                        mysqli_stmt_close($check_stmt);
                        mysqli_close($conn);

                        exit();

                    } else {

                        $message =
                            "Registration failed. Please try again.";

                        $message_type = "error";
                    }


                    mysqli_stmt_close($insert_stmt);
                }
            }


            mysqli_stmt_close($check_stmt);
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

    <title>
        CampusConnect | Faculty Registration
    </title>


    <!-- =========================
         GOOGLE FONT
    ========================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- =========================
         FONT AWESOME
    ========================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    >


    <!-- =========================
         MAIN CSS
    ========================== -->

    <link
        rel="stylesheet"
        href="assets/css/style.css?v=32"
    >

</head>


<body>


<section class="login-section">


    <div class="login-container faculty-register">


        <!-- =========================
             LEFT SIDE
        ========================== -->

        <div class="login-left">


            <div class="faculty-icon">

                <i class="fa-solid fa-chalkboard-user"></i>

            </div>


            <h1>
                Faculty Registration
            </h1>


            <p>

                Create your CampusConnect faculty account
                and manage notices, applications and
                academic activities from one secure platform.

            </p>


        </div>



        <!-- =========================
             RIGHT SIDE
        ========================== -->

        <div class="login-right">


            <h2>
                Create Faculty Account
            </h2>


            <p class="subtitle">
                Register to continue
            </p>



            <!-- =========================
                 MESSAGE
            ========================== -->

            <?php if ($message != "") { ?>

                <div
                    class="register-message
                    <?php echo htmlspecialchars($message_type); ?>"
                >

                    <i
                        class="fa-solid
                        <?php
                        echo $message_type == "error"
                            ? "fa-circle-exclamation"
                            : "fa-circle-check";
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



            <!-- =========================
                 REGISTRATION FORM
            ========================== -->

            <form
                method="POST"
                autocomplete="off"
            >


                <!-- =========================
                     FACULTY ID
                ========================== -->

                <div class="form-field">

                    <label>
                        Faculty ID
                    </label>

                    <input
                        type="text"
                        name="faculty_id"
                        placeholder="Enter Faculty ID"
                        value="<?php
                            echo isset($_POST['faculty_id'])
                                ? htmlspecialchars($_POST['faculty_id'])
                                : '';
                        ?>"
                        required
                    >

                </div>



                <!-- =========================
                     FULL NAME
                ========================== -->

                <div class="form-field">

                    <label>
                        Full Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        placeholder="Enter Full Name"
                        value="<?php
                            echo isset($_POST['name'])
                                ? htmlspecialchars($_POST['name'])
                                : '';
                        ?>"
                        required
                    >

                </div>



                <!-- =========================
                     EMAIL
                ========================== -->

                <div class="form-field">

                    <label>
                        Email Address
                    </label>

                    <input
                        type="email"
                        name="email"
                        placeholder="Enter Email Address"
                        value="<?php
                            echo isset($_POST['email'])
                                ? htmlspecialchars($_POST['email'])
                                : '';
                        ?>"
                        required
                    >

                </div>



                <!-- =========================
                     DEPARTMENT
                ========================== -->

                <div class="form-field">

                    <label>
                        Department
                    </label>


                    <select
                        name="department"
                        required
                    >

                        <option value="">
                            Select Department
                        </option>

                        <option value="Computer Science">
                            Computer Science
                        </option>

                        <option value="BCA">
                            BCA
                        </option>

                        <option value="BBA">
                            BBA
                        </option>

                        <option value="B.Com">
                            B.Com
                        </option>

                        <option value="B.Sc">
                            B.Sc
                        </option>

                        <option value="MCA">
                            MCA
                        </option>

                        <option value="M.Com">
                            M.Com
                        </option>

                        <option value="LLB">
                            LLB
                        </option>

                        <option value="B.Ed">
                            B.Ed
                        </option>

                        <!-- NEW -->

                        <option value="Library">
                            Library
                        </option>

                        <option value="Other">
                            Other
                        </option>

                    </select>

                </div>



                <!-- =========================
                     DESIGNATION
                ========================== -->

                <div class="form-field">

                    <label>
                        Designation
                    </label>


                    <select
                        name="designation"
                        required
                    >

                        <option value="">
                            Select Designation
                        </option>

                        <option value="Professor">
                            Professor
                        </option>

                        <option value="Associate Professor">
                            Associate Professor
                        </option>

                        <option value="Assistant Professor">
                            Assistant Professor
                        </option>

                        <option value="Lecturer">
                            Lecturer
                        </option>

                        <option value="HOD">
                            HOD
                        </option>

                        <option value="Dean">
                            Dean
                        </option>

                        <option value="Lab Assistant">
                            Lab Assistant
                        </option>

                        <option value="Visiting Faculty">
                            Visiting Faculty
                        </option>

                        <!-- NEW -->

                        <option value="Librarian">
                            Librarian
                        </option>

                    </select>

                </div>



                <!-- =========================
                     PASSWORD
                ========================== -->

                <div class="form-field">

                    <label>
                        Password
                    </label>


                    <input
                        type="password"
                        name="password"
                        placeholder="Create Password"
                        autocomplete="new-password"
                        required
                    >

                </div>



                <!-- =========================
                     SECRET CODE
                ========================== -->

                <div class="form-field">

                    <label>
                        Secret Code
                    </label>


                    <input
                        type="text"
                        name="secret_code"
                        placeholder="Create Secret Code"
                        autocomplete="off"
                        required
                    >

                </div>



                <!-- =========================
                     SUBMIT
                ========================== -->

                <button
                    type="submit"
                >

                    <i class="fa-solid fa-user-plus"></i>

                    Create Faculty Account

                </button>


            </form>



            <!-- =========================
                 LOGIN LINK
            ========================== -->

            <div class="signup">

                Already have a faculty account?

                <a href="faculty_login.php">
                    Login
                </a>

            </div>


        </div>

    </div>

</section>


</body>

</html>


<?php

mysqli_close($conn);

?>