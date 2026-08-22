<?php

session_start();

include("db.php");

/* =========================
   CHECK LOGIN
========================= */

if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit();
}


/* =========================
   GET STUDENT DETAILS
========================= */

$student_id = $_SESSION['student_id'];

$student_sql = "SELECT student_id, name, course
                FROM students
                WHERE student_id = ?";

$student_stmt = mysqli_prepare($conn, $student_sql);

mysqli_stmt_bind_param(
    $student_stmt,
    "s",
    $student_id
);

mysqli_stmt_execute($student_stmt);

$student_result = mysqli_stmt_get_result($student_stmt);

$student = mysqli_fetch_assoc($student_result);


/* =========================
   SEARCH
========================= */

$search = "";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}


/* =========================
   GET BOOKS
========================= */

if ($search != "") {

    $search_value = "%" . $search . "%";

    $sql = "SELECT *
            FROM library
            WHERE book_name LIKE ?
               OR author LIKE ?
               OR status LIKE ?
            ORDER BY book_id DESC";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "sss",
        $search_value,
        $search_value,
        $search_value
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

} else {

    $sql = "SELECT *
            FROM library
            ORDER BY book_id DESC";

    $result = mysqli_query($conn, $sql);
}


/* =========================
   LIBRARY STATISTICS
========================= */

$total_books = 0;
$total_quantity = 0;

$stats_sql = "SELECT
                COUNT(*) AS total_books,
                COALESCE(SUM(quantity), 0) AS total_quantity
              FROM library";

$stats_result = mysqli_query($conn, $stats_sql);

if ($stats_result) {

    $stats = mysqli_fetch_assoc($stats_result);

    $total_books = $stats['total_books'];
    $total_quantity = $stats['total_quantity'];
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

    <title>CampusConnect | Library</title>


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

</head>


<body>


<div class="library-page">


    <!-- =========================
         HEADER
    ========================== -->

    <div class="library-header">

        <div>

            <h1>

                <i class="fa-solid fa-book-open"></i>

                Library

            </h1>

            <p>
                Explore books available in the CampusConnect library.
            </p>

        </div>


        <!-- Student Profile -->

        <div class="library-profile">

            <div class="library-avatar">

                <?php

                echo strtoupper(
                    substr($student['name'], 0, 1)
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

                    ID:

                    <?php

                    echo htmlspecialchars(
                        $student['student_id']
                    );

                    ?>

                </span>

            </div>

        </div>

    </div>



    <!-- =========================
         BACK TO DASHBOARD
    ========================== -->

    <a
        href="student_dashboard.php"
        class="library-back"
    >

        <i class="fa-solid fa-arrow-left"></i>

        Back to Dashboard

    </a>



    <!-- =========================
         STATISTICS
    ========================== -->

    <div class="library-stats">


        <!-- Total Books -->

        <div class="library-stat-card">

            <div class="library-stat-icon blue">

                <i class="fa-solid fa-book"></i>

            </div>


            <div>

                <span>
                    Different Books
                </span>

                <h2>

                    <?php

                    echo $total_books;

                    ?>

                </h2>

            </div>

        </div>



        <!-- Total Copies -->

        <div class="library-stat-card">

            <div class="library-stat-icon green">

                <i class="fa-solid fa-layer-group"></i>

            </div>


            <div>

                <span>
                    Total Copies
                </span>

                <h2>

                    <?php

                    echo $total_quantity;

                    ?>

                </h2>

            </div>

        </div>



        <!-- Course -->

        <div class="library-stat-card">

            <div class="library-stat-icon purple">

                <i class="fa-solid fa-graduation-cap"></i>

            </div>


            <div>

                <span>
                    Your Course
                </span>

                <h2 class="course-name">

                    <?php

                    echo htmlspecialchars(
                        $student['course']
                    );

                    ?>

                </h2>

            </div>

        </div>

    </div>



    <!-- =========================
         SEARCH
    ========================== -->

    <div class="library-search">

        <form method="GET">

            <div class="library-search-box">

                <i class="fa-solid fa-magnifying-glass"></i>


                <input
                    type="text"
                    name="search"
                    placeholder="Search by book name, author or status..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >


                <button type="submit">

                    Search

                </button>

            </div>

        </form>

    </div>



    <!-- =========================
         BOOK SECTION
    ========================== -->

    <section class="books-section">


        <div class="books-title">

            <h2>
                Library Books
            </h2>

            <p>
                Browse books currently listed in the college library.
            </p>

        </div>



        <div class="books-grid">


            <?php

            if ($result && mysqli_num_rows($result) > 0) {

                while ($book = mysqli_fetch_assoc($result)) {

            ?>


                <!-- =========================
                     BOOK CARD
                ========================== -->

                <div class="book-card">


                    <!-- Book Cover -->

                    <div class="book-cover">

                        <i class="fa-solid fa-book"></i>

                    </div>



                    <!-- Book Information -->

                    <div class="book-info">


                        <span class="book-category">

                            BOOK #

                            <?php

                            echo htmlspecialchars(
                                $book['book_id']
                            );

                            ?>

                        </span>


                        <h3>

                            <?php

                            echo htmlspecialchars(
                                $book['book_name']
                            );

                            ?>

                        </h3>


                        <p class="book-author">

                            <i class="fa-solid fa-user-pen"></i>

                            <?php

                            echo htmlspecialchars(
                                $book['author']
                            );

                            ?>

                        </p>



                        <!-- Quantity -->

                        <div class="book-quantity">

                            <i class="fa-solid fa-copy"></i>

                            <?php

                            echo htmlspecialchars(
                                $book['quantity']
                            );

                            ?>

                            copies

                        </div>



                        <!-- Status -->

                        <?php

                        $status = strtolower(
                            trim($book['status'])
                        );


                        if (
                            $status == "available" ||
                            $status == "active"
                        ) {

                        ?>

                            <div class="book-available">

                                <i class="fa-solid fa-circle-check"></i>

                                <?php

                                echo htmlspecialchars(
                                    $book['status']
                                );

                                ?>

                            </div>

                        <?php

                        } else {

                        ?>

                            <div class="book-unavailable">

                                <i class="fa-solid fa-circle-xmark"></i>

                                <?php

                                echo htmlspecialchars(
                                    $book['status']
                                );

                                ?>

                            </div>

                        <?php

                        }


                        ?>



                        <!-- Footer -->

                        <div class="book-footer">

                            <span>

                                Quantity:

                                <?php

                                echo htmlspecialchars(
                                    $book['quantity']
                                );

                                ?>

                            </span>


                            <?php

                            if (
                                $status == "available" ||
                                $status == "active"
                            ) {

                            ?>

                                <button
                                    type="button"
                                    class="borrow-btn"
                                    onclick="requestBook('<?php echo htmlspecialchars($book['book_name']); ?>')"
                                >

                                    <i class="fa-solid fa-book-open-reader"></i>

                                    Request

                                </button>

                            <?php

                            } else {

                            ?>

                                <button
                                    type="button"
                                    class="borrow-btn disabled"
                                    disabled
                                >

                                    Unavailable

                                </button>

                            <?php

                            }

                            ?>

                        </div>


                    </div>

                </div>


            <?php

                }

            } else {

            ?>


                <!-- No Books -->

                <div class="library-empty">

                    <i class="fa-solid fa-book-open"></i>

                    <h2>
                        No Books Found
                    </h2>

                    <p>

                        <?php

                        if ($search != "") {

                            echo "No books matched your search.";

                        } else {

                            echo "No books are currently available in the library.";

                        }

                        ?>

                    </p>


                    <?php

                    if ($search != "") {

                    ?>

                        <a href="library.php">

                            View All Books

                        </a>

                    <?php

                    }

                    ?>

                </div>


            <?php

            }

            ?>


        </div>

    </section>


</div>



<!-- =========================
     REQUEST MESSAGE
========================== -->

<div
    id="borrowMessage"
    class="library-message"
>

    <div class="message-box">

        <i class="fa-solid fa-book-open-reader"></i>

        <h3>
            Book Request
        </h3>

        <p id="requestText">

            Book request functionality will be available soon.

        </p>

        <button
            onclick="closeBorrowMessage()"
        >

            OK

        </button>

    </div>

</div>



<script>

function requestBook(bookName) {

    document.getElementById("requestText").innerText =
        "Your request for \"" + bookName +
        "\" has been noted. Book request functionality can be connected next.";

    document
        .getElementById("borrowMessage")
        .classList.add("show");

}


function closeBorrowMessage() {

    document
        .getElementById("borrowMessage")
        .classList.remove("show");

}

</script>


</body>

</html>


<?php

mysqli_close($conn);

?>