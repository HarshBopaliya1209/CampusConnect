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


/* =========================
   GET LIBRARIAN
========================= */

$faculty_id = $_SESSION['faculty_id'];

$sql = "
    SELECT
        faculty_id,
        name,
        department,
        designation
    FROM faculty
    WHERE faculty_id = ?
";

$stmt = mysqli_prepare($conn, $sql);

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

$librarian = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================
   CHECK LIBRARIAN
========================= */

if (
    !$librarian ||
    strtolower(trim($librarian['designation'])) != "librarian"
) {

    header("Location: faculty_dashboard.php");
    exit();

}


/* =========================
   ADD BOOK
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $book_name = trim(
        $_POST['book_name'] ?? ''
    );

    $author = trim(
        $_POST['author'] ?? ''
    );

    $quantity = intval(
        $_POST['quantity'] ?? 0
    );

    $status = trim(
        $_POST['status'] ?? 'Available'
    );


    /* =========================
       VALIDATION
    ========================== */

    if ($book_name == "") {

        $message = "Please enter the book name.";
        $message_type = "error";

    } elseif ($author == "") {

        $message = "Please enter the author name.";
        $message_type = "error";

    } elseif ($quantity <= 0) {

        $message = "Quantity must be greater than 0.";
        $message_type = "error";

    } elseif (
        $status != "Available" &&
        $status != "Unavailable"
    ) {

        $message = "Invalid book status.";
        $message_type = "error";

    } else {


        /* =========================
           INSERT BOOK
        ========================== */

        $sql = "
            INSERT INTO library
            (
                book_name,
                author,
                quantity,
                status
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?
            )
        ";

        $stmt = mysqli_prepare(
            $conn,
            $sql
        );


        if (!$stmt) {

            $message =
                "Database error: " .
                mysqli_error($conn);

            $message_type = "error";

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "ssis",
                $book_name,
                $author,
                $quantity,
                $status
            );


            if (
                mysqli_stmt_execute($stmt)
            ) {

                $message =
                    "Book added successfully.";

                $message_type = "success";


                /* Clear form values */

                $book_name = "";
                $author = "";
                $quantity = "";

            } else {

                $message =
                    "Unable to add book. Please try again.";

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
        CampusConnect | Add Book
    </title>


    <!-- Google Font -->

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: #17191e;
            color: #dbdfe8;
        }

        /* =========================
           HEADER
        ========================= */

        .header {
            background: linear-gradient(135deg, #3c70e3, #554ce6);
            color: #ffffff;
            padding: 35px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header p {
            margin: 8px 0 0;
            opacity: 0.9;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #17191e;
            color: #5586f1;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 20px;
            font-weight: 700;
        }

        .profile span {
            display: block;
            font-size: 13px;
            opacity: 0.85;
        }

        /* =========================
           CONTAINER
        ========================= */

        .container {
            max-width: 760px;
            margin: 0 auto;
            padding: 45px 25px;
        }

        .back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 25px;
            color: #909daf;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .back:hover {
            color: #5586f1;
        }

        /* =========================
           CARD
        ========================= */

        .card {
            background: #212523;
            border-radius: 18px;
            padding: 34px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 25px;
        }

        .card-header .icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: #1d1e20;
            color: #5586f1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .card-header h2 {
            margin: 0;
            font-size: 21px;
            color: #dbdfe8;
        }

        .card-header p {
            margin: 4px 0 0;
            font-size: 13px;
            color: #909daf;
        }

        /* =========================
           MESSAGE
        ========================= */

        .message {
            margin-bottom: 20px;
            padding: 13px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
        }

        .message.success {
            background: #1c201e;
            color: #aeedc6;
            border: 1px solid #23302b;
        }

        .message.error {
            background: #221e1e;
            color: #f2a89e;
            border: 1px solid #3a2422;
        }

        /* =========================
           FORM
        ========================= */

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .field label {
            font-size: 13px;
            font-weight: 700;
            color: #c3c7ce;
        }

        .field input,
        .field select {
            width: 100%;
            height: 46px;
            padding: 0 14px;
            border: 1px solid #2e3138;
            border-radius: 11px;
            outline: none;
            color: #dbdfe8;
            background: #17191e;
            font-family: inherit;
            font-size: 14px;
        }

        .field input:focus,
        .field select:focus {
            border-color: #5586f1;
            box-shadow: 0 0 0 4px rgba(85, 134, 241, 0.18);
        }

        .field .hint {
            font-size: 12px;
            color: #8b919d;
        }

        /* =========================
           BUTTONS
        ========================= */

        .buttons {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 10px;
        }

        .cancel {
            padding: 12px 20px;
            border-radius: 11px;
            border: 1px solid #2e3138;
            color: #c3c7ce;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }

        .cancel:hover {
            background: #1c1e22;
        }

        .save {
            padding: 12px 22px;
            border: 0;
            border-radius: 11px;
            color: #ffffff;
            background: linear-gradient(135deg, #3c70e3, #554ce6);
            box-shadow: 0 10px 22px rgba(85, 76, 230, 0.30);
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .save:hover {
            transform: translateY(-2px);
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 600px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                padding: 28px 22px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .field.full {
                grid-column: auto;
            }

            .buttons {
                flex-direction: column-reverse;
            }

            .cancel,
            .save {
                width: 100%;
                justify-content: center;
                text-align: center;
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

            <i class="fa-solid fa-book-medical"></i>

            Add New Book

        </h1>


        <p>

            Add a new book to the CampusConnect library.

        </p>

    </div>



    <div class="profile">


        <div class="avatar">

            <?php

            echo strtoupper(
                substr(
                    $librarian['name'],
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
     MAIN
========================== -->

<main class="container">


    <!-- Back -->

    <a
        href="librarian_dashboard.php"
        class="back"
    >

        <i class="fa-solid fa-arrow-left"></i>

        Back to Librarian Dashboard

    </a>



    <!-- Card -->

    <section class="card">


        <div class="card-header">


            <div class="icon">

                <i
                    class="fa-solid fa-book-medical"
                ></i>

            </div>


            <div>

                <h2>
                    Add New Book
                </h2>


                <p>
                    Enter the details of the new library book.
                </p>

            </div>

        </div>



        <!-- Message -->

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



        <!-- Form -->

        <form
            method="POST"
            class="form-grid"
        >


            <!-- Book Name -->

            <div class="field full">

                <label>
                    Book Name
                </label>


                <input
                    type="text"
                    name="book_name"
                    placeholder="Enter book name"
                    value="<?php

                        echo htmlspecialchars(
                            $book_name ?? ''
                        );

                    ?>"
                    required
                >


                <span class="hint">

                    Enter the complete name of the book.

                </span>

            </div>



            <!-- Author -->

            <div class="field">

                <label>
                    Author
                </label>


                <input
                    type="text"
                    name="author"
                    placeholder="Enter author name"
                    value="<?php

                        echo htmlspecialchars(
                            $author ?? ''
                        );

                    ?>"
                    required
                >

            </div>



            <!-- Quantity -->

            <div class="field">

                <label>
                    Quantity
                </label>


                <input
                    type="number"
                    name="quantity"
                    min="1"
                    placeholder="Enter quantity"
                    value="<?php

                        echo htmlspecialchars(
                            $quantity ?? ''
                        );

                    ?>"
                    required
                >


                <span class="hint">

                    Number of copies available.

                </span>

            </div>



            <!-- Status -->

            <div class="field">

                <label>
                    Status
                </label>


                <select
                    name="status"
                    required
                >

                    <option
                        value="Available"
                    >
                        Available
                    </option>


                    <option
                        value="Unavailable"
                    >
                        Unavailable
                    </option>

                </select>

            </div>



            <!-- Buttons -->

            <div class="buttons">


                <a
                    href="librarian_dashboard.php"
                    class="cancel"
                >

                    <i
                        class="fa-solid fa-xmark"
                    ></i>

                    &nbsp;

                    Cancel

                </a>


                <button
                    type="submit"
                    class="save"
                >

                    <i
                        class="fa-solid fa-plus"
                    ></i>

                    &nbsp;

                    Add Book

                </button>

            </div>


        </form>

    </section>

</main>


</body>

</html>


<?php

mysqli_close($conn);

?>