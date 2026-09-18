<?php

session_start();

include("db.php");

$message = "";
$message_type = "";


/* =========================
   CHECK LOGIN
========================= */

if (!isset($_SESSION['faculty_id'])) {

    header("Location: faculty_login.php");
    exit();

}

$faculty_id = $_SESSION['faculty_id'];


/* =========================
   GET FACULTY DETAILS
========================= */

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
";

$stmt = mysqli_prepare(
    $conn,
    $sql
);

if (!$stmt) {

    die("Database error: " . mysqli_error($conn));

}

mysqli_stmt_bind_param(
    $stmt,
    "s",
    $faculty_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$faculty = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================
   CHECK FACULTY
========================= */

if (!$faculty) {

    session_unset();
    session_destroy();

    header("Location: faculty_login.php");
    exit();

}


/* =========================
   UPDATE PROFILE
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $action = $_POST['action'] ?? "";


    /* =========================
       CHANGE PASSWORD
    ========================== */

    if ($action == "password") {

        $current_password =
            $_POST['current_password'] ?? "";

        $new_password =
            $_POST['new_password'] ?? "";

        $confirm_password =
            $_POST['confirm_password'] ?? "";


        if (
            $current_password == "" ||
            $new_password == "" ||
            $confirm_password == ""
        ) {

            $message =
                "Please fill in all password fields.";

            $message_type = "error";

        } elseif (
            $current_password !== $faculty['password']
        ) {

            $message =
                "Current password is incorrect.";

            $message_type = "error";

        } elseif (
            strlen($new_password) < 4
        ) {

            $message =
                "New password must be at least 4 characters.";

            $message_type = "error";

        } elseif (
            $new_password !== $confirm_password
        ) {

            $message =
                "New password and confirm password do not match.";

            $message_type = "error";

        } else {

            $sql = "
                UPDATE faculty
                SET password = ?
                WHERE faculty_id = ?
            ";

            $stmt = mysqli_prepare(
                $conn,
                $sql
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ss",
                $new_password,
                $faculty_id
            );


            if (mysqli_stmt_execute($stmt)) {

                $message =
                    "Password changed successfully.";

                $message_type = "success";

                $faculty['password'] =
                    $new_password;

            } else {

                $message =
                    "Unable to change password.";

                $message_type = "error";

            }

            mysqli_stmt_close($stmt);
        }
    }


    /* =========================
       CHANGE SECRET CODE
    ========================== */

    elseif ($action == "secret_code") {

        $current_secret =
            trim(
                $_POST['current_secret'] ?? ""
            );

        $new_secret =
            trim(
                $_POST['new_secret'] ?? ""
            );

        $confirm_secret =
            trim(
                $_POST['confirm_secret'] ?? ""
            );


        if (
            $current_secret == "" ||
            $new_secret == "" ||
            $confirm_secret == ""
        ) {

            $message =
                "Please fill in all secret code fields.";

            $message_type = "error";

        } elseif (
            $current_secret !== $faculty['secret_code']
        ) {

            $message =
                "Current secret code is incorrect.";

            $message_type = "error";

        } elseif (
            $new_secret !== $confirm_secret
        ) {

            $message =
                "New secret codes do not match.";

            $message_type = "error";

        } else {

            $sql = "
                UPDATE faculty
                SET secret_code = ?
                WHERE faculty_id = ?
            ";

            $stmt = mysqli_prepare(
                $conn,
                $sql
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ss",
                $new_secret,
                $faculty_id
            );


            if (mysqli_stmt_execute($stmt)) {

                $message =
                    "Secret code changed successfully.";

                $message_type = "success";

                $faculty['secret_code'] =
                    $new_secret;

            } else {

                $message =
                    "Unable to change secret code.";

                $message_type = "error";

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

    <title>
        CampusConnect | Faculty Profile
    </title>


    <!-- Google Font -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    >

    <link
    rel="stylesheet"
    href="assets/css/style.css">
<style>

/* =========================================================
   CAMPUSCONNECT - FACULTY PROFILE | GREEN THEME
========================================================= */

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: "Inter", sans-serif;
    background: #212522;
    color: #d3dbe6;
    min-height: 100vh;
}

/* =========================================================
   HEADER
========================================================= */

.header {
    width: 100%;
    background: linear-gradient(135deg, #1d954b, #19af50);
    color: #ffffff;
    padding: 28px 6%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 25px;
    box-shadow: 0 4px 18px rgba(22, 101, 52, 0.18);
}

.header h1 {
    font-size: 30px;
    font-weight: 700;
    margin-bottom: 5px;
}

.header h1 i {
    margin-right: 10px;
}

.header p {
    font-size: 14px;
    opacity: 0.9;
}

/* =========================================================
   MINI PROFILE
========================================================= */

.profile-mini {
    display: flex;
    align-items: center;
    gap: 12px;
    background: rgba(38, 38, 38, 0.13);
    padding: 9px 15px;
    border-radius: 14px;
    backdrop-filter: blur(8px);
}

.profile-mini strong {
    display: block;
    font-size: 14px;
    font-weight: 600;
}

.profile-mini span {
    display: block;
    font-size: 12px;
    opacity: 0.85;
    margin-top: 2px;
}

.avatar {
    width: 43px;
    height: 43px;
    border-radius: 50%;
    background: #17191e;
    color: #13d85c;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 700;
}

/* =========================================================
   MAIN CONTAINER
========================================================= */

.container {
    width: min(1180px, 92%);
    margin: 0 auto;
    padding: 30px 0 50px;
}

/* =========================================================
   BACK BUTTON
========================================================= */

.back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    color: #18cd5c;
    background: #17191e;
    border: 1px solid #213427;
    padding: 10px 17px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 22px;
    transition: all 0.25s ease;
    box-shadow: 0 3px 10px rgba(15, 23, 42, 0.05);
}

.back:hover {
    background: #19af50;
    color: #ffffff;
    border-color: #19af50;
    transform: translateX(-3px);
}

/* =========================================================
   MESSAGE
========================================================= */

.message {
    display: flex;
    align-items: center;
    padding: 13px 17px;
    border-radius: 11px;
    margin-bottom: 22px;
    font-size: 14px;
    font-weight: 500;
}

.message.success {
    background: #202421;
    color: #18cd5c;
    border: 1px solid #164326;
}

.message.error {
    background: #252121;
    color: #eb4d4d;
    border: 1px solid #3e1515;
}

/* =========================================================
   PROFILE CARD
========================================================= */

.profile-card {
    background: #17191e;
    border: 1px solid #191d1b;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 8px 25px rgba(15, 23, 42, 0.06);
    margin-bottom: 25px;
}

/* =========================================================
   PROFILE TOP
========================================================= */

.profile-top {
    display: flex;
    align-items: center;
    gap: 20px;
    padding-bottom: 25px;
    border-bottom: 1px solid #1e221f;
}

.large-avatar {
    width: 82px;
    height: 82px;
    flex-shrink: 0;
    border-radius: 20px;
    background: linear-gradient(135deg, #19af50, #169c48);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 34px;
    font-weight: 700;
    box-shadow: 0 8px 18px rgba(22, 163, 74, 0.22);
}

.profile-top h2 {
    font-size: 25px;
    font-weight: 700;
    color: #e0e5f1;
    margin-bottom: 4px;
}

.profile-top p {
    color: #909daf;
    font-size: 14px;
    margin-bottom: 8px;
}

.designation {
    display: inline-block;
    background: #202421;
    color: #18cd5c;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

/* =========================================================
   PROFILE DETAILS
========================================================= */

.details {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0;
    margin-top: 5px;
}

.detail {
    padding: 19px 20px 15px 0;
    border-bottom: 1px solid #1e221f;
}

.detail:nth-child(odd) {
    margin-right: 20px;
}

.detail:nth-last-child(-n+2) {
    border-bottom: none;
}

.detail-label {
    color: #909daf;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 6px;
}

.detail-value {
    color: #d0d9e7;
    font-size: 15px;
    font-weight: 500;
    word-break: break-word;
}

/* =========================================================
   SECURITY GRID
========================================================= */

.security-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 25px;
}

/* =========================================================
   SECURITY CARD
========================================================= */

.security-card {
    background: #17191e;
    border: 1px solid #191d1b;
    border-radius: 20px;
    padding: 27px;
    box-shadow: 0 8px 25px rgba(15, 23, 42, 0.06);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.security-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.09);
}

/* =========================================================
   SECURITY HEADER
========================================================= */

.security-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 25px;
}

.security-icon {
    width: 48px;
    height: 48px;
    flex-shrink: 0;
    border-radius: 13px;
    background: #202421;
    color: #13d85c;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
}

.security-header h3 {
    font-size: 18px;
    font-weight: 700;
    color: #e0e5f1;
    margin-bottom: 3px;
}

.security-header p {
    color: #909daf;
    font-size: 12px;
}

/* =========================================================
   FORM
========================================================= */

.field {
    margin-bottom: 17px;
}

.field label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #4b699b;
    margin-bottom: 7px;
}

.field input {
    width: 100%;
    height: 46px;
    padding: 0 14px;
    border: 1px solid #161917;
    border-radius: 10px;
    outline: none;
    background: #222725;
    color: #d3dbe6;
    font-family: "Inter", sans-serif;
    font-size: 13px;
    transition: all 0.2s ease;
}

.field input::placeholder {
    color: #9ca3af;
}

.field input:focus {
    background: #17191e;
    border-color: #19af50;
    box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.10);
}

/* =========================================================
   SAVE BUTTON
========================================================= */

.save-button {
    width: 100%;
    height: 46px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #19af50, #169c48);
    color: #ffffff;
    font-family: "Inter", sans-serif;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 4px;
    transition: all 0.25s ease;
    box-shadow: 0 5px 14px rgba(22, 163, 74, 0.20);
}

.save-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 18px rgba(22, 163, 74, 0.28);
}

.save-button:active {
    transform: translateY(0);
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 850px) {

    .header {
        padding: 24px 5%;
    }

    .security-grid {
        grid-template-columns: 1fr;
    }

}

@media (max-width: 650px) {

    .header {
        flex-direction: column;
        align-items: flex-start;
    }

    .profile-mini {
        width: 100%;
    }

    .container {
        width: 94%;
        padding-top: 22px;
    }

    .profile-card,
    .security-card {
        padding: 20px;
        border-radius: 16px;
    }

    .profile-top {
        align-items: flex-start;
    }

    .large-avatar {
        width: 65px;
        height: 65px;
        font-size: 27px;
        border-radius: 16px;
    }

    .profile-top h2 {
        font-size: 21px;
    }

    .details {
        grid-template-columns: 1fr;
    }

    .detail {
        margin-right: 0 !important;
        border-bottom: 1px solid #1e221f !important;
    }

    .detail:last-child {
        border-bottom: none !important;
    }

}

</style>
</head>
<body>


<!-- =========================
     HEADER
========================== -->

<header class="header">


    <div>

        <h1>

            <i class="fa-solid fa-user-circle"></i>

            Faculty Profile

        </h1>


        <p>

            View your faculty information and security settings.

        </p>

    </div>



    <div class="profile-mini">


        <div class="avatar">

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

</header>



<main class="container">


    <!-- =========================
         BACK
    ========================== -->

    <?php

    if (
        strtolower(
            trim(
                $faculty['designation']
            )
        ) == "librarian"
    ) {

        $back_page =
            "librarian_dashboard.php";

    } else {

        $back_page =
            "faculty_dashboard.php";

    }

    ?>


    <a
        href="<?php echo $back_page; ?>"
        class="back"
    >

        <i class="fa-solid fa-arrow-left"></i>

        Back to Dashboard

    </a>



    <!-- =========================
         MESSAGE
    ========================== -->

    <?php if ($message != "") { ?>

        <div
            class="message
            <?php

            echo htmlspecialchars(
                $message_type
            );

            ?>"
        >

            <i
                class="fa-solid
                <?php

                echo $message_type == "success"
                    ? "fa-circle-check"
                    : "fa-circle-exclamation";

                ?>"
            ></i>

            &nbsp;

            <?php

            echo htmlspecialchars(
                $message
            );

            ?>

        </div>

    <?php } ?>



    <!-- =========================
         PROFILE INFORMATION
    ========================== -->

    <section class="profile-card">


        <div class="profile-top">


            <div class="large-avatar">

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

                <h2>

                    <?php

                    echo htmlspecialchars(
                        $faculty['name']
                    );

                    ?>

                </h2>


                <p>

                    Faculty ID:

                    <?php

                    echo htmlspecialchars(
                        $faculty['faculty_id']
                    );

                    ?>

                </p>


                <span class="designation">

                    <?php

                    echo htmlspecialchars(
                        $faculty['designation']
                    );

                    ?>

                </span>

            </div>

        </div>



        <div class="details">


            <div class="detail">

                <div class="detail-label">
                    Faculty ID
                </div>

                <div class="detail-value">

                    <?php

                    echo htmlspecialchars(
                        $faculty['faculty_id']
                    );

                    ?>

                </div>

            </div>


            <div class="detail">

                <div class="detail-label">
                    Full Name
                </div>

                <div class="detail-value">

                    <?php

                    echo htmlspecialchars(
                        $faculty['name']
                    );

                    ?>

                </div>

            </div>


            <div class="detail">

                <div class="detail-label">
                    Email Address
                </div>

                <div class="detail-value">

                    <?php

                    echo htmlspecialchars(
                        $faculty['email']
                    );

                    ?>

                </div>

            </div>


            <div class="detail">

                <div class="detail-label">
                    Department
                </div>

                <div class="detail-value">

                    <?php

                    echo htmlspecialchars(
                        $faculty['department']
                    );

                    ?>

                </div>

            </div>


            <div class="detail">

                <div class="detail-label">
                    Designation
                </div>

                <div class="detail-value">

                    <?php

                    echo htmlspecialchars(
                        $faculty['designation']
                    );

                    ?>

                </div>

            </div>


        </div>

    </section>



    <!-- =========================
         SECURITY
    ========================== -->

    <div class="security-grid">


        <!-- =========================
             CHANGE PASSWORD
        ========================== -->

        <section class="security-card">


            <div class="security-header">


                <div class="security-icon">

                    <i
                        class="fa-solid fa-lock"
                    ></i>

                </div>


                <div>

                    <h3>
                        Change Password
                    </h3>

                    <p>
                        Update your login password.
                    </p>

                </div>

            </div>



            <form method="POST">


                <input
                    type="hidden"
                    name="action"
                    value="password"
                >


                <div class="field">

                    <label>
                        Current Password
                    </label>

                    <input
                        type="password"
                        name="current_password"
                        placeholder="Enter current password"
                        required
                    >

                </div>


                <div class="field">

                    <label>
                        New Password
                    </label>

                    <input
                        type="password"
                        name="new_password"
                        placeholder="Enter new password"
                        required
                    >

                </div>


                <div class="field">

                    <label>
                        Confirm New Password
                    </label>

                    <input
                        type="password"
                        name="confirm_password"
                        placeholder="Confirm new password"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="save-button"
                >

                    <i
                        class="fa-solid fa-key"
                    ></i>

                    &nbsp;

                    Change Password

                </button>

            </form>

        </section>



        <!-- =========================
             CHANGE SECRET CODE
        ========================== -->

        <section class="security-card">


            <div class="security-header">


                <div class="security-icon">

                    <i
                        class="fa-solid fa-shield-halved"
                    ></i>

                </div>


                <div>

                    <h3>
                        Change Secret Code
                    </h3>

                    <p>
                        Update your login secret code.
                    </p>

                </div>

            </div>



            <form method="POST">


                <input
                    type="hidden"
                    name="action"
                    value="secret_code"
                >


                <div class="field">

                    <label>
                        Current Secret Code
                    </label>

                    <input
                        type="password"
                        name="current_secret"
                        placeholder="Enter current secret code"
                        required
                    >

                </div>


                <div class="field">

                    <label>
                        New Secret Code
                    </label>

                    <input
                        type="password"
                        name="new_secret"
                        placeholder="Enter new secret code"
                        required
                    >

                </div>


                <div class="field">

                    <label>
                        Confirm Secret Code
                    </label>

                    <input
                        type="password"
                        name="confirm_secret"
                        placeholder="Confirm new secret code"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="save-button"
                >

                    <i
                        class="fa-solid fa-shield-halved"
                    ></i>

                    &nbsp;

                    Change Secret Code

                </button>

            </form>

        </section>

    </div>

</main>


</body>

</html>


<?php

mysqli_close($conn);

?>