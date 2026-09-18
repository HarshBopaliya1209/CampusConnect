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
   GET LIBRARIAN DETAILS
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

    die("Database error.");

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
   ADD / UPDATE / DELETE
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $action = $_POST['action'] ?? "";


    /* =========================
       ADD BOOK
    ========================= */

    if ($action == "add") {

        $book_name = trim($_POST['book_name'] ?? "");
        $author    = trim($_POST['author'] ?? "");
        $quantity  = intval($_POST['quantity'] ?? 0);
        $status    = trim($_POST['status'] ?? "Available");


        if (
            $book_name == "" ||
            $author == "" ||
            $quantity < 0 ||
            $status == ""
        ) {

            $message = "Please enter valid book details.";
            $message_type = "error";

        } else {

            $sql = "
                INSERT INTO library
                (
                    book_name,
                    author,
                    quantity,
                    status
                )
                VALUES (?, ?, ?, ?)
            ";

            $stmt = mysqli_prepare(
                $conn,
                $sql
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ssis",
                $book_name,
                $author,
                $quantity,
                $status
            );


            if (mysqli_stmt_execute($stmt)) {

                $message = "Book added successfully.";
                $message_type = "success";

            } else {

                $message = "Unable to add book.";
                $message_type = "error";

            }

            mysqli_stmt_close($stmt);
        }
    }


    /* =========================
       UPDATE BOOK
    ========================= */

    elseif ($action == "update") {

        $book_id   = intval($_POST['book_id'] ?? 0);
        $book_name = trim($_POST['book_name'] ?? "");
        $author    = trim($_POST['author'] ?? "");
        $quantity  = intval($_POST['quantity'] ?? 0);
        $status    = trim($_POST['status'] ?? "Available");


        if (
            $book_id <= 0 ||
            $book_name == "" ||
            $author == "" ||
            $quantity < 0 ||
            $status == ""
        ) {

            $message = "Please enter valid book details.";
            $message_type = "error";

        } else {

            $sql = "
                UPDATE library
                SET
                    book_name = ?,
                    author = ?,
                    quantity = ?,
                    status = ?
                WHERE book_id = ?
            ";

            $stmt = mysqli_prepare(
                $conn,
                $sql
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ssisi",
                $book_name,
                $author,
                $quantity,
                $status,
                $book_id
            );


            if (mysqli_stmt_execute($stmt)) {

                $message = "Book updated successfully.";
                $message_type = "success";

            } else {

                $message = "Unable to update book.";
                $message_type = "error";

            }

            mysqli_stmt_close($stmt);
        }
    }


    /* =========================
       DELETE BOOK
    ========================= */

    elseif ($action == "delete") {

        $book_id = intval($_POST['book_id'] ?? 0);


        if ($book_id <= 0) {

            $message = "Invalid book.";
            $message_type = "error";

        } else {

            $sql = "
                DELETE FROM library
                WHERE book_id = ?
            ";

            $stmt = mysqli_prepare(
                $conn,
                $sql
            );

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $book_id
            );


            if (mysqli_stmt_execute($stmt)) {

                $message = "Book deleted successfully.";
                $message_type = "success";

            } else {

                $message =
                    "Unable to delete book. It may be connected to another record.";

                $message_type = "error";

            }

            mysqli_stmt_close($stmt);
        }
    }
}


/* =========================
   SEARCH
========================= */

$search = trim($_GET['search'] ?? "");


if ($search != "") {

    $search_value = "%" . $search . "%";

    $sql = "
        SELECT
            book_id,
            book_name,
            author,
            quantity,
            status
        FROM library
        WHERE book_name LIKE ?
           OR author LIKE ?
           OR status LIKE ?
        ORDER BY book_id DESC
    ";

    $stmt = mysqli_prepare(
        $conn,
        $sql
    );

    mysqli_stmt_bind_param(
        $stmt,
        "sss",
        $search_value,
        $search_value,
        $search_value
    );

    mysqli_stmt_execute($stmt);

    $books = mysqli_stmt_get_result($stmt);

} else {

    $sql = "
        SELECT
            book_id,
            book_name,
            author,
            quantity,
            status
        FROM library
        ORDER BY book_id DESC
    ";

    $books = mysqli_query(
        $conn,
        $sql
    );
}


/* =========================
   STATISTICS
========================= */

$total_books = 0;
$total_copies = 0;
$available_books = 0;


$sql = "
    SELECT
        COUNT(*) AS total_books,
        COALESCE(SUM(quantity), 0) AS total_copies
    FROM library
";

$result = mysqli_query(
    $conn,
    $sql
);

if ($result) {

    $data = mysqli_fetch_assoc($result);

    $total_books = $data['total_books'];
    $total_copies = $data['total_copies'];

}


$sql = "
    SELECT COUNT(*) AS available_books
    FROM library
    WHERE LOWER(status) IN ('available', 'active')
";

$result = mysqli_query(
    $conn,
    $sql
);

if ($result) {

    $data = mysqli_fetch_assoc($result);

    $available_books = $data['available_books'];

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
        CampusConnect | Manage Library
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


        /* =========================
           HEADER
        ========================== */

        .page-header {

            background: linear-gradient(
                    135deg,
                    #3c70e3,
                    #554ce6
                );

            color: #ffffff;

            padding: 30px 50px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

        }


        .header-left h1 {

            margin: 0;

            font-size: 29px;

        }


        .header-left p {

            margin: 7px 0 0;

            opacity: .9;

        }


        .profile {

            display: flex;

            align-items: center;

            gap: 12px;

        }


        .avatar {

            width: 48px;

            height: 48px;

            border-radius: 50%;

            background: #262626;

            color: #5586f1;

            display: flex;

            align-items: center;

            justify-content: center;

            font-weight: 700;

            font-size: 19px;

        }


        .profile strong {

            display: block;

        }


        .profile span {

            font-size: 12px;

            opacity: .85;

        }


        /* =========================
           MAIN
        ========================== */

        .container {

            padding: 35px 50px;

        }


        /* =========================
           BACK BUTTON
        ========================== */

        .back-button {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            color: #5586f1;

            text-decoration: none;

            font-weight: 500;

            margin-bottom: 25px;

        }


        .back-button:hover {

            text-decoration: underline;

        }


        /* =========================
           MESSAGE
        ========================== */

        .message {

            padding: 14px 18px;

            border-radius: 10px;

            margin-bottom: 25px;

            font-size: 14px;

        }


        .message.success {

            background: #1c201e;

            color: #aeedc6;

        }


        .message.error {

            background: #221e1e;

            color: #cb1a1a;

        }


        /* =========================
           STATS
        ========================== */

        .stats {

            display: grid;

            grid-template-columns: repeat(3, 1fr);

            gap: 20px;

            margin-bottom: 30px;

        }


        .stat {

            background: #262626;

            border-radius: 16px;

            padding: 22px;

            display: flex;

            align-items: center;

            gap: 16px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.06);

        }


        .stat-icon {

            width: 52px;

            height: 52px;

            border-radius: 14px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 21px;

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


        .stat span {

            color: #999ea9;

            font-size: 13px;

        }


        .stat h2 {

            margin: 3px 0 0;

            font-size: 25px;

        }


        /* =========================
           ADD BOOK
        ========================== */

        .add-section {

            background: #262626;

            border-radius: 18px;

            padding: 28px;

            margin-bottom: 30px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.06);

        }


        .section-heading {

            margin-bottom: 20px;

        }


        .section-heading h2 {

            margin: 0;

            font-size: 21px;

        }


        .section-heading p {

            margin: 5px 0 0;

            color: #999ea9;

            font-size: 13px;

        }


        .form-grid {

            display: grid;

            grid-template-columns: 2fr 2fr 1fr 1fr auto;

            gap: 14px;

            align-items: end;

        }


        .field label {

            display: block;

            font-size: 13px;

            font-weight: 600;

            margin-bottom: 7px;

        }


        .field input,

        .field select {

            width: 100%;

            height: 45px;

            border: 1px solid #212631;

            border-radius: 9px;

            padding: 0 13px;

            font-family: inherit;

            outline: none;

        }


        .field input:focus,

        .field select:focus {

            border-color: #554ce6;

        }


        .add-button {

            height: 45px;

            border: none;

            border-radius: 9px;

            background: linear-gradient(
                    135deg,
                    #3c70e3,
                    #554ce6
                );

            color: #ffffff;

            padding: 0 20px;

            font-family: inherit;

            font-weight: 600;

            cursor: pointer;

        }


        .add-button:hover {

            opacity: .92;

        }


        /* =========================
           SEARCH
        ========================== */

        .books-section {

            background: #262626;

            border-radius: 18px;

            padding: 28px;

            box-shadow:
                0 5px 20px
                rgba(0,0,0,.06);

        }


        .search-form {

            display: flex;

            gap: 10px;

            margin-bottom: 25px;

        }


        .search-input {

            flex: 1;

            height: 45px;

            border: 1px solid #212631;

            border-radius: 9px;

            padding: 0 15px;

            font-family: inherit;

            outline: none;

        }


        .search-button {

            border: none;

            border-radius: 9px;

            padding: 0 22px;

            background: #3c70e3;

            color: #ffffff;

            font-family: inherit;

            font-weight: 600;

            cursor: pointer;

        }


        .clear-button {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 0 18px;

            border-radius: 9px;

            background: #202224;

            color: #4b6c9b;

            text-decoration: none;

            font-size: 14px;

        }


        /* =========================
           TABLE
        ========================== */

        .table-wrapper {

            overflow-x: auto;

        }


        table {

            width: 100%;

            border-collapse: collapse;

            min-width: 800px;

        }


        th {

            background: #222426;

            color: #4b6c9b;

            font-size: 13px;

            text-align: left;

            padding: 15px;

            border-bottom: 1px solid #1b1c1e;

        }


        td {

            padding: 15px;

            border-bottom: 1px solid #1e2022;

            font-size: 14px;

        }


        tbody tr:hover {

            background: #232427;

        }


        .book-id {

            color: #909daf;

            font-weight: 600;

        }


        .status {

            display: inline-flex;

            padding: 5px 11px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;

        }


        .status.available {

            background: #1c201e;

            color: #aeedc6;

        }


        .status.unavailable {

            background: #221e1e;

            color: #cb1a1a;

        }


        .actions {

            display: flex;

            gap: 8px;

        }


        .edit-button,

        .delete-button {

            border: none;

            width: 36px;

            height: 36px;

            border-radius: 8px;

            cursor: pointer;

            display: flex;

            align-items: center;

            justify-content: center;

        }


        .edit-button {

            background: #1d1e20;

            color: #5586f1;

        }


        .delete-button {

            background: #221e1e;

            color: #eb4d4d;

        }


        .edit-button:hover,

        .delete-button:hover {

            transform: translateY(-1px);

        }


        .empty {

            text-align: center;

            padding: 50px 20px;

            color: #909daf;

        }


        .empty i {

            font-size: 45px;

            margin-bottom: 15px;

            color: #9fb3d1;

        }


        /* =========================
           MODAL
        ========================== */

        .modal {

            display: none;

            position: fixed;

            inset: 0;

            background: rgba(11, 14, 24, .6);

            align-items: center;

            justify-content: center;

            padding: 20px;

            z-index: 1000;

        }


        .modal.show {

            display: flex;

        }


        .modal-box {

            width: 100%;

            max-width: 500px;

            background: #262626;

            border-radius: 18px;

            padding: 28px;

        }


        .modal-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 22px;

        }


        .modal-header h2 {

            margin: 0;

            font-size: 21px;

        }


        .close-button {

            border: none;

            background: #202224;

            width: 35px;

            height: 35px;

            border-radius: 50%;

            cursor: pointer;

            font-size: 16px;

        }


        .modal-form .field {

            margin-bottom: 16px;

        }


        .modal-actions {

            display: flex;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 20px;

        }


        .cancel-button {

            border: none;

            background: #1b1d1f;

            color: #4b6c9b;

            padding: 11px 18px;

            border-radius: 8px;

            cursor: pointer;

            font-family: inherit;

        }


        .save-button {

            border: none;

            background: #3c70e3;

            color: #ffffff;

            padding: 11px 18px;

            border-radius: 8px;

            cursor: pointer;

            font-family: inherit;

            font-weight: 600;

        }


        /* =========================
           RESPONSIVE
        ========================== */

        @media (max-width: 1100px) {

            .form-grid {

                grid-template-columns: repeat(2, 1fr);

            }

            .add-button {

                width: 100%;

            }

        }


        @media (max-width: 800px) {

            .page-header {

                padding: 25px;

                flex-direction: column;

                align-items: flex-start;

            }

            .container {

                padding: 25px;

            }

            .stats {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 600px) {

            .form-grid {

                grid-template-columns: 1fr;

            }

            .search-form {

                flex-wrap: wrap;

            }

            .search-input {

                flex-basis: 100%;

            }

        }

    </style>

</head>


<body>


<!-- =========================
     HEADER
========================== -->

<header class="page-header">

    <div class="header-left">

        <h1>

            <i class="fa-solid fa-books"></i>

            Manage Library

        </h1>

        <p>
            Add, edit and manage CampusConnect library books.
        </p>

    </div>


    <div class="profile">

        <div class="avatar">

            <?php

            echo strtoupper(
                substr($librarian['name'], 0, 1)
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



<main class="container">


    <!-- =========================
         BACK
    ========================== -->

    <a
        href="librarian_dashboard.php"
        class="back-button"
    >

        <i class="fa-solid fa-arrow-left"></i>

        Back to Librarian Dashboard

    </a>



    <!-- =========================
         MESSAGE
    ========================== -->

    <?php if ($message != "") { ?>

        <div
            class="message
            <?php echo htmlspecialchars($message_type); ?>"
        >

            <?php if ($message_type == "success") { ?>

                <i class="fa-solid fa-circle-check"></i>

            <?php } else { ?>

                <i class="fa-solid fa-circle-exclamation"></i>

            <?php } ?>

            &nbsp;

            <?php

            echo htmlspecialchars($message);

            ?>

        </div>

    <?php } ?>



    <!-- =========================
         STATISTICS
    ========================== -->

    <div class="stats">


        <div class="stat">

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


        <div class="stat">

            <div class="stat-icon green">

                <i class="fa-solid fa-copy"></i>

            </div>

            <div>

                <span>
                    Total Copies
                </span>

                <h2>
                    <?php echo $total_copies; ?>
                </h2>

            </div>

        </div>


        <div class="stat">

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

    </div>



    <!-- =========================
         ADD BOOK
    ========================== -->

    <section class="add-section">


        <div class="section-heading">

            <h2>

                <i class="fa-solid fa-plus"></i>

                Add New Book

            </h2>

            <p>
                Add a new book to the CampusConnect library.
            </p>

        </div>


        <form
            method="POST"
            class="form-grid"
        >

            <input
                type="hidden"
                name="action"
                value="add"
            >


            <div class="field">

                <label>
                    Book Name
                </label>

                <input
                    type="text"
                    name="book_name"
                    placeholder="Enter book name"
                    required
                >

            </div>


            <div class="field">

                <label>
                    Author
                </label>

                <input
                    type="text"
                    name="author"
                    placeholder="Enter author name"
                    required
                >

            </div>


            <div class="field">

                <label>
                    Quantity
                </label>

                <input
                    type="number"
                    name="quantity"
                    min="0"
                    value="1"
                    required
                >

            </div>


            <div class="field">

                <label>
                    Status
                </label>

                <select
                    name="status"
                    required
                >

                    <option value="Available">
                        Available
                    </option>

                    <option value="Unavailable">
                        Unavailable
                    </option>

                </select>

            </div>


            <button
                type="submit"
                class="add-button"
            >

                <i class="fa-solid fa-plus"></i>

                Add Book

            </button>

        </form>

    </section>



    <!-- =========================
         BOOK LIST
    ========================== -->

    <section class="books-section">


        <div class="section-heading">

            <h2>

                <i class="fa-solid fa-list"></i>

                Library Books

            </h2>

            <p>
                View and manage all books in the library.
            </p>

        </div>



        <!-- Search -->

        <form
            method="GET"
            class="search-form"
        >

            <input
                type="text"
                name="search"
                class="search-input"
                placeholder="Search by book name, author or status..."
                value="<?php
                    echo htmlspecialchars($search);
                ?>"
            >


            <button
                type="submit"
                class="search-button"
            >

                <i class="fa-solid fa-magnifying-glass"></i>

                Search

            </button>


            <?php if ($search != "") { ?>

                <a
                    href="manage_library.php"
                    class="clear-button"
                >

                    Clear

                </a>

            <?php } ?>

        </form>



        <!-- Table -->

        <div class="table-wrapper">

            <?php if ($books && mysqli_num_rows($books) > 0) { ?>

                <table>

                    <thead>

                        <tr>

                            <th>
                                ID
                            </th>

                            <th>
                                Book Name
                            </th>

                            <th>
                                Author
                            </th>

                            <th>
                                Quantity
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php

                    while ($book = mysqli_fetch_assoc($books)) {

                        $status =
                            strtolower(
                                trim($book['status'])
                            );

                    ?>

                        <tr>

                            <td class="book-id">

                                #
                                <?php
                                echo htmlspecialchars(
                                    $book['book_id']
                                );
                                ?>

                            </td>


                            <td>

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $book['book_name']
                                    );

                                    ?>

                                </strong>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $book['author']
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $book['quantity']
                                );

                                ?>

                                copies

                            </td>


                            <td>

                                <span
                                    class="status
                                    <?php
                                    echo (
                                        $status == "available" ||
                                        $status == "active"
                                    )
                                        ? "available"
                                        : "unavailable";
                                    ?>"
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $book['status']
                                    );

                                    ?>

                                </span>

                            </td>


                            <td>

                                <div class="actions">


                                    <!-- Edit -->

                                    <button
                                        type="button"
                                        class="edit-button"
                                        title="Edit Book"
                                        onclick='openEditModal(
                                            <?php echo json_encode($book["book_id"]); ?>,
                                            <?php echo json_encode($book["book_name"]); ?>,
                                            <?php echo json_encode($book["author"]); ?>,
                                            <?php echo json_encode($book["quantity"]); ?>,
                                            <?php echo json_encode($book["status"]); ?>
                                        )'
                                    >

                                        <i
                                            class="fa-solid fa-pen"
                                        ></i>

                                    </button>


                                    <!-- Delete -->

                                    <form
                                        method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this book?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete"
                                        >

                                        <input
                                            type="hidden"
                                            name="book_id"
                                            value="<?php
                                                echo htmlspecialchars(
                                                    $book['book_id']
                                                );
                                            ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="delete-button"
                                            title="Delete Book"
                                        >

                                            <i
                                                class="fa-solid fa-trash"
                                            ></i>

                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php } ?>

                    </tbody>

                </table>

            <?php } else { ?>

                <div class="empty">

                    <i
                        class="fa-solid fa-book-open"
                    ></i>

                    <h3>
                        No Books Found
                    </h3>

                    <p>

                        <?php

                        if ($search != "") {

                            echo "No books matched your search.";

                        } else {

                            echo "There are no books in the library yet.";

                        }

                        ?>

                    </p>

                </div>

            <?php } ?>

        </div>

    </section>

</main>



<!-- =========================
     EDIT MODAL
========================== -->

<div
    id="editModal"
    class="modal"
>

    <div class="modal-box">


        <div class="modal-header">

            <h2>
                Edit Book
            </h2>


            <button
                type="button"
                class="close-button"
                onclick="closeEditModal()"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>



        <form
            method="POST"
            class="modal-form"
        >

            <input
                type="hidden"
                name="action"
                value="update"
            >


            <input
                type="hidden"
                name="book_id"
                id="editBookId"
            >


            <div class="field">

                <label>
                    Book Name
                </label>

                <input
                    type="text"
                    name="book_name"
                    id="editBookName"
                    required
                >

            </div>


            <div class="field">

                <label>
                    Author
                </label>

                <input
                    type="text"
                    name="author"
                    id="editAuthor"
                    required
                >

            </div>


            <div class="field">

                <label>
                    Quantity
                </label>

                <input
                    type="number"
                    name="quantity"
                    id="editQuantity"
                    min="0"
                    required
                >

            </div>


            <div class="field">

                <label>
                    Status
                </label>

                <select
                    name="status"
                    id="editStatus"
                    required
                >

                    <option value="Available">
                        Available
                    </option>

                    <option value="Unavailable">
                        Unavailable
                    </option>

                </select>

            </div>


            <div class="modal-actions">

                <button
                    type="button"
                    class="cancel-button"
                    onclick="closeEditModal()"
                >

                    Cancel

                </button>


                <button
                    type="submit"
                    class="save-button"
                >

                    <i class="fa-solid fa-check"></i>

                    Save Changes

                </button>

            </div>

        </form>

    </div>

</div>



<script>

/* =========================
   EDIT MODAL
========================= */

function openEditModal(
    id,
    name,
    author,
    quantity,
    status
) {

    document.getElementById("editBookId").value =
        id;

    document.getElementById("editBookName").value =
        name;

    document.getElementById("editAuthor").value =
        author;

    document.getElementById("editQuantity").value =
        quantity;

    document.getElementById("editStatus").value =
        status;

    document
        .getElementById("editModal")
        .classList.add("show");
}


function closeEditModal() {

    document
        .getElementById("editModal")
        .classList.remove("show");
}


/* Close when clicking outside */

document
    .getElementById("editModal")
    .addEventListener(
        "click",
        function(event) {

            if (event.target === this) {

                closeEditModal();

            }

        }
    );

</script>


</body>

</html>


<?php

mysqli_close($conn);

?>