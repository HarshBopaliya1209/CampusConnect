<?php

session_start();

include("db.php");

/* =========================
   CHECK LOGIN
========================= */

if (!isset($_SESSION['faculty_id'])) {
    header("Location: faculty_login.php");
    exit();
}


/* =========================
   GET LIBRARIAN DETAILS
========================= */

$faculty_id = $_SESSION['faculty_id'];

$sql = "SELECT * FROM faculty WHERE faculty_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "s",
    $faculty_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$librarian = mysqli_fetch_assoc($result);


/* =========================
   CHECK LIBRARIAN ROLE
========================= */

if (
    !$librarian ||
    strtolower(trim($librarian['designation'])) != 'librarian'
) {
    header("Location: faculty_dashboard.php");
    exit();
}


/* =========================
   LIBRARY STATISTICS
========================= */

$total_books = 0;
$total_quantity = 0;
$available_books = 0;
$pending_requests = 0;


/* Total different books */

$sql = "SELECT COUNT(*) AS total_books
        FROM library";

$result = mysqli_query($conn, $sql);

if ($result) {

    $data = mysqli_fetch_assoc($result);

    $total_books = $data['total_books'];
}


/* Total copies */

$sql = "SELECT COALESCE(SUM(quantity), 0) AS total_quantity
        FROM library";

$result = mysqli_query($conn, $sql);

if ($result) {

    $data = mysqli_fetch_assoc($result);

    $total_quantity = $data['total_quantity'];
}


/* Available books */

$sql = "SELECT COUNT(*) AS available_books
        FROM library
        WHERE LOWER(status) IN ('available', 'active')";

$result = mysqli_query($conn, $sql);

if ($result) {

    $data = mysqli_fetch_assoc($result);

    $available_books = $data['available_books'];
}


/* Pending requests */

$pending_requests = 0;
?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>CampusConnect | Librarian Dashboard</title>


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
        href="/CampusConnect/assets/css/style.css?v=2"
    >


    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: #212325;
            color: #d3dbe6;
        }

        .librarian-page {
            min-height: 100vh;
        }

        /* =========================
           HEADER
        ========================= */

        .librarian-header {
            background: linear-gradient(135deg, #3c70e3, #554ce6);
            color: #ffffff;
            padding: 35px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .header-left h1 {
            margin: 0;
            font-size: 30px;
        }

        .header-left p {
            margin: 8px 0 0;
            opacity: 0.9;
        }

        .librarian-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .librarian-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #262626;
            color: #5586f1;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 20px;
            font-weight: 700;
        }

        .profile-info strong {
            display: block;
        }

        .profile-info span {
            font-size: 13px;
            opacity: 0.85;
        }


        /* =========================
           MAIN
        ========================= */

        .dashboard-container {
            padding: 35px 50px;
        }


        /* =========================
           STATISTICS
        ========================= */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: #262626;
            border-radius: 16px;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 18px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
        }

        .stat-icon {
            width: 55px;
            height: 55px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .blue {
            background: #1d1e20;
            color: #5586f1;
        }

        .green {
            background: #1c201e;
            color: #13d85c;
        }

        .purple {
            background: #201f23;
            color: #9d6cf3;
        }

        .orange {
            background: #1f1e1c;
            color: #ef793c;
        }

        .stat-card span {
            font-size: 13px;
            color: #999ea9;
        }

        .stat-card h2 {
            margin: 4px 0 0;
            font-size: 27px;
        }


        /* =========================
           SECTION TITLE
        ========================= */

        .section-title {
            margin-bottom: 20px;
        }

        .section-title h2 {
            margin: 0;
            font-size: 23px;
        }

        .section-title p {
            margin: 5px 0 0;
            color: #999ea9;
        }


        /* =========================
           ACTION CARDS
        ========================= */

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }

        .action-card {
            background: #262626;
            border-radius: 16px;
            padding: 28px;
            text-decoration: none;
            color: #d3dbe6;
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
            transition: 0.2s;
            border: 1px solid #1f2023;
        }

        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .action-icon {
            width: 55px;
            height: 55px;
            border-radius: 14px;
            background: #212225;
            color: #7871f2;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 18px;
        }

        .action-card h3 {
            margin: 0 0 7px;
            font-size: 18px;
        }

        .action-card p {
            margin: 0;
            color: #999ea9;
            font-size: 13px;
            line-height: 1.6;
        }


        /* =========================
           LOGOUT
        ========================= */

        .logout-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 35px;
            padding: 12px 20px;
            background: #e64c4c;
            color: #ffffff;
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
        }

        .logout-btn:hover {
            background: #e13030;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1000px) {

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }


        @media (max-width: 650px) {

            .librarian-header {
                padding: 25px;
                flex-direction: column;
                align-items: flex-start;
            }

            .dashboard-container {
                padding: 25px;
            }

            .stats-grid,
            .actions-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>

<div class="librarian-page">


    <!-- =========================
         HEADER
    ========================== -->

    <header class="librarian-header">

        <div class="header-left">

            <h1>

                <i class="fa-solid fa-book-open"></i>

                Librarian Dashboard

            </h1>

            <p>
                Manage the CampusConnect library and book requests.
            </p>

        </div>


        <div class="librarian-profile">

            <div class="librarian-avatar">

                <?php

                echo strtoupper(
                    substr($librarian['name'], 0, 1)
                );

                ?>

            </div>


            <div class="profile-info">

                <strong>

                    <?php

                    echo htmlspecialchars(
                        $librarian['name']
                    );

                    ?>

                </strong>

                <span>
                    Librarian
                </span>

            </div>

        </div>

    </header>



    <!-- =========================
         DASHBOARD
    ========================== -->

    <main class="dashboard-container">


        <!-- =========================
             STATISTICS
        ========================== -->

        <div class="stats-grid">


            <!-- Total Books -->

            <div class="stat-card">

                <div class="stat-icon blue">

                    <i class="fa-solid fa-book"></i>

                </div>

                <div>

                    <span>
                        Different Books
                    </span>

                    <h2>
                        <?php echo $total_books; ?>
                    </h2>

                </div>

            </div>



            <!-- Total Copies -->

            <div class="stat-card">

                <div class="stat-icon green">

                    <i class="fa-solid fa-copy"></i>

                </div>

                <div>

                    <span>
                        Total Copies
                    </span>

                    <h2>
                        <?php echo $total_quantity; ?>
                    </h2>

                </div>

            </div>



            <!-- Available -->

            <div class="stat-card">

                <div class="stat-icon purple">

                    <i class="fa-solid fa-circle-check"></i>

                </div>

                <div>

                    <span>
                        Available Books
                    </span>

                    <h2>
                        <?php echo $available_books; ?>
                    </h2>

                </div>

            </div>



            <!-- Requests -->

            <div class="stat-card">

                <div class="stat-icon orange">

                    <i class="fa-solid fa-clock"></i>

                </div>

                <div>

                    <span>
                        Pending Requests
                    </span>

                    <h2>
                        <?php echo $pending_requests; ?>
                    </h2>

                </div>

            </div>

        </div>



        <!-- =========================
             MANAGEMENT
        ========================== -->

        <div class="section-title">

            <h2>
                Library Management
            </h2>

            <p>
                Manage books and student library requests.
            </p>

        </div>



        <div class="actions-grid">


            <!-- Manage Books -->

            <a
                href="manage_library.php"
                class="action-card"
            >

                <div class="action-icon">
                    
                    <i class="fa-solid fa-book"></i>

                </div>

                <h3>
                    Manage Books
                </h3>

                <p>
                    Add, edit and remove books from the library.
                </p>

            </a>



            <!-- Add Book -->

            <a
                href="add_book.php"
                class="action-card"
            >

                <div class="action-icon">

                    <i class="fa-solid fa-plus"></i>

                </div>

                <h3>
                    Add New Book
                </h3>

                <p>
                    Add a new book and its available quantity.
                </p>

            </a>



            <!-- Requests -->

            <a
                href="library_requests.php"
                class="action-card"
            >

                <div class="action-icon">

                    <i class="fa-solid fa-list-check"></i>

                </div>

                <h3>
                    Book Requests
                </h3>

                <p>
                    Review, approve or reject student book requests.
                </p>

            </a>



            <!-- Available Books -->

            <a
                href="library.php"
                class="action-card"
            >

                <div class="action-icon">

                    <i class="fa-solid fa-book-open"></i>

                </div>

                <h3>
                    View Library
                </h3>

                <p>
                    View all books currently listed in the library.
                </p>

            </a>



            <!-- Request History -->

            <a
                href="library_requests.php?status=all"
                class="action-card"
            >

                <div class="action-icon">

                    <i class="fa-solid fa-clock-rotate-left"></i>

                </div>

                <h3>
                    Request History
                </h3>

                <p>
                    View previously approved and rejected requests.
                </p>

            </a>



            <!-- Profile -->

            <a
                href="faculty_profile.php"
                class="action-card"
            >

                <div class="action-icon">

                    <i class="fa-solid fa-user"></i>

                </div>

                <h3>
                    My Profile
                </h3>

                <p>
                    View your librarian account information.
                </p>

            </a>

        </div>



        <!-- Logout -->

        <a
            href="logout.php"
            class="logout-btn"
        >

            <i class="fa-solid fa-right-from-bracket"></i>

            Logout

        </a>

    </main>

</div>

</body>

</html>


<?php

mysqli_close($conn);

?>