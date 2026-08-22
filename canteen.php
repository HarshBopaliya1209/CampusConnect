<?php

session_start();

include("db.php");

/* =====================================================
   CHECK LOGIN
===================================================== */

if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit();
}

$student_id = $_SESSION['student_id'];


/* =====================================================
   GET STUDENT DETAILS
===================================================== */

$student_sql = "
    SELECT student_id, name, course
    FROM students
    WHERE student_id = ?
";

$student_stmt = mysqli_prepare($conn, $student_sql);

mysqli_stmt_bind_param(
    $student_stmt,
    "s",
    $student_id
);

mysqli_stmt_execute($student_stmt);

$student_result = mysqli_stmt_get_result($student_stmt);

$student = mysqli_fetch_assoc($student_result);

if (!$student) {
    session_destroy();
    header("Location: student_login.php");
    exit();
}


/* =====================================================
   CREATE SESSION CART
===================================================== */

if (!isset($_SESSION['canteen_cart'])) {
    $_SESSION['canteen_cart'] = [];
}


/* =====================================================
   ADD ITEM TO CART
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {

    $item_id = intval($_POST['item_id']);

    /* Check item exists */

    $check_sql = "
        SELECT item_id, item_name, price, availability
        FROM canteen
        WHERE item_id = ?
    ";

    $check_stmt = mysqli_prepare($conn, $check_sql);

    mysqli_stmt_bind_param(
        $check_stmt,
        "i",
        $item_id
    );

    mysqli_stmt_execute($check_stmt);

    $check_result = mysqli_stmt_get_result($check_stmt);

    $item = mysqli_fetch_assoc($check_result);


    if ($item) {

        $availability = strtolower(trim($item['availability']));

        if ($availability === "available") {

            if (isset($_SESSION['canteen_cart'][$item_id])) {

                $_SESSION['canteen_cart'][$item_id]++;

            } else {

                $_SESSION['canteen_cart'][$item_id] = 1;

            }
        }
    }

    header("Location: canteen.php?added=1");
    exit();
}


/* =====================================================
   UPDATE CART
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {

    $item_id = intval($_POST['item_id']);
    $quantity = intval($_POST['quantity']);

    if ($quantity <= 0) {

        unset($_SESSION['canteen_cart'][$item_id]);

    } else {

        $_SESSION['canteen_cart'][$item_id] = $quantity;

    }

    header("Location: canteen.php");
    exit();
}


/* =====================================================
   REMOVE ITEM
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_cart'])) {

    $item_id = intval($_POST['item_id']);

    unset($_SESSION['canteen_cart'][$item_id]);

    header("Location: canteen.php");
    exit();
}


/* =====================================================
   CLEAR CART
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cart'])) {

    $_SESSION['canteen_cart'] = [];

    header("Location: canteen.php");
    exit();
}


/* =====================================================
   SEARCH
===================================================== */

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : "";


/* =====================================================
   CATEGORY
===================================================== */

$category = isset($_GET['category'])
    ? trim($_GET['category'])
    : "";


/* =====================================================
   GET CATEGORIES
===================================================== */

$category_sql = "
    SELECT DISTINCT category
    FROM canteen
    WHERE category IS NOT NULL
    AND category != ''
    ORDER BY category
";

$category_result = mysqli_query(
    $conn,
    $category_sql
);


/* =====================================================
   GET CANTEEN ITEMS
===================================================== */

if ($search !== "" && $category !== "") {

    $item_sql = "
        SELECT *
        FROM canteen
        WHERE item_name LIKE ?
        AND category = ?
        ORDER BY item_id DESC
    ";

    $item_stmt = mysqli_prepare(
        $conn,
        $item_sql
    );

    $search_value = "%" . $search . "%";

    mysqli_stmt_bind_param(
        $item_stmt,
        "ss",
        $search_value,
        $category
    );

    mysqli_stmt_execute($item_stmt);

    $item_result = mysqli_stmt_get_result(
        $item_stmt
    );

} elseif ($search !== "") {

    $item_sql = "
        SELECT *
        FROM canteen
        WHERE item_name LIKE ?
        ORDER BY item_id DESC
    ";

    $item_stmt = mysqli_prepare(
        $conn,
        $item_sql
    );

    $search_value = "%" . $search . "%";

    mysqli_stmt_bind_param(
        $item_stmt,
        "s",
        $search_value
    );

    mysqli_stmt_execute($item_stmt);

    $item_result = mysqli_stmt_get_result(
        $item_stmt
    );

} elseif ($category !== "") {

    $item_sql = "
        SELECT *
        FROM canteen
        WHERE category = ?
        ORDER BY item_id DESC
    ";

    $item_stmt = mysqli_prepare(
        $conn,
        $item_sql
    );

    mysqli_stmt_bind_param(
        $item_stmt,
        "s",
        $category
    );

    mysqli_stmt_execute($item_stmt);

    $item_result = mysqli_stmt_get_result(
        $item_stmt
    );

} else {

    $item_sql = "
        SELECT *
        FROM canteen
        ORDER BY item_id DESC
    ";

    $item_result = mysqli_query(
        $conn,
        $item_sql
    );
}


/* =====================================================
   BUILD CART FROM DATABASE ITEMS
===================================================== */

$cart_items = [];

$cart_total = 0;

$cart_count = 0;

foreach ($_SESSION['canteen_cart'] as $item_id => $quantity) {

    $item_id = intval($item_id);
    $quantity = intval($quantity);

    $cart_item_sql = "
        SELECT
            item_id,
            item_name,
            price,
            category,
            availability
        FROM canteen
        WHERE item_id = ?
    ";

    $cart_item_stmt = mysqli_prepare(
        $conn,
        $cart_item_sql
    );

    mysqli_stmt_bind_param(
        $cart_item_stmt,
        "i",
        $item_id
    );

    mysqli_stmt_execute($cart_item_stmt);

    $cart_item_result = mysqli_stmt_get_result(
        $cart_item_stmt
    );

    $cart_item = mysqli_fetch_assoc(
        $cart_item_result
    );

    if ($cart_item) {

        $cart_item['quantity'] = $quantity;

        $cart_items[] = $cart_item;

        $cart_count += $quantity;

        $cart_total +=
            $cart_item['price'] * $quantity;
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

    <title>CampusConnect | Canteen</title>


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


    <!-- Your CSS -->

    <link
        rel="stylesheet"
        href="assets/css/style.css?v=20"
    >

</head>


<body>


<div class="canteen-page">


    <!-- =================================================
         HEADER
    ================================================== -->

    <header class="canteen-header">

        <div class="canteen-title">

            <span class="canteen-label">
                CAMPUSCONNECT
            </span>

            <h1>

                <i class="fa-solid fa-utensils"></i>

                Campus Canteen

            </h1>

            <p>
                Delicious food and refreshing
                beverages right on campus.
            </p>

        </div>


        <!-- STUDENT PROFILE -->

        <div class="canteen-profile">

            <div class="canteen-avatar">

                <?php

                echo strtoupper(
                    substr(
                        $student['name'],
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

    </header>


    <!-- =================================================
         ACTION BAR
    ================================================== -->

    <div class="canteen-actions">

        <a
            href="student_dashboard.php"
            class="canteen-back"
        >

            <i class="fa-solid fa-arrow-left"></i>

            Dashboard

        </a>


        <button
            type="button"
            class="cart-open-btn"
            onclick="openCart()"
        >

            <i class="fa-solid fa-cart-shopping"></i>

            Cart

            <span>
                <?php echo $cart_count; ?>
            </span>

        </button>

    </div>


    <!-- =================================================
         SUCCESS MESSAGE
    ================================================== -->

    <?php if (isset($_GET['added'])) { ?>

        <div class="canteen-success">

            <i class="fa-solid fa-circle-check"></i>

            Item added to cart successfully!

        </div>

    <?php } ?>


    <!-- =================================================
         SEARCH
    ================================================== -->

    <section class="canteen-search">

        <form method="GET">

            <div class="canteen-search-box">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    name="search"
                    placeholder="Search food..."
                    value="<?php
                        echo htmlspecialchars($search);
                    ?>"
                >

            </div>


            <select name="category">

                <option value="">
                    All Categories
                </option>

                <?php

                while (
                    $cat =
                    mysqli_fetch_assoc(
                        $category_result
                    )
                ) {

                ?>

                    <option
                        value="<?php
                            echo htmlspecialchars(
                                $cat['category']
                            );
                        ?>"
                        <?php

                        if (
                            $category ===
                            $cat['category']
                        ) {
                            echo "selected";
                        }

                        ?>
                    >

                        <?php

                        echo htmlspecialchars(
                            $cat['category']
                        );

                        ?>

                    </option>

                <?php

                }

                ?>

            </select>


            <button type="submit">

                <i class="fa-solid fa-magnifying-glass"></i>

                Search

            </button>

        </form>

    </section>


    <!-- =================================================
         MENU
    ================================================== -->

    <section class="food-section">

        <div class="food-heading">

            <span class="section-label">
                TODAY'S MENU
            </span>

            <h2>
                What would you like to eat?
            </h2>

            <p>
                Choose from the food and beverages
                available at your campus canteen.
            </p>

        </div>


        <div class="food-grid">

            <?php

            if (
                $item_result &&
                mysqli_num_rows($item_result) > 0
            ) {

                while (
                    $item =
                    mysqli_fetch_assoc(
                        $item_result
                    )
                ) {

                    $availability =
                        strtolower(
                            trim(
                                $item['availability']
                            )
                        );

                    $is_available =
                        ($availability === "available");

            ?>

                <div class="food-card">


                    <!-- FOOD ICON -->

                    <div class="food-image">

                        <?php

                        $cat_name =
                            strtolower(
                                $item['category']
                            );

                        if (
                            strpos(
                                $cat_name,
                                "drink"
                            ) !== false ||
                            strpos(
                                $cat_name,
                                "beverage"
                            ) !== false
                        ) {

                        ?>

                            <i class="fa-solid fa-mug-hot"></i>

                        <?php

                        } elseif (
                            strpos(
                                $cat_name,
                                "pizza"
                            ) !== false
                        ) {

                        ?>

                            <i class="fa-solid fa-pizza-slice"></i>

                        <?php

                        } elseif (
                            strpos(
                                $cat_name,
                                "burger"
                            ) !== false
                        ) {

                        ?>

                            <i class="fa-solid fa-burger"></i>

                        <?php

                        } else {

                        ?>

                            <i class="fa-solid fa-bowl-food"></i>

                        <?php

                        }

                        ?>

                    </div>


                    <!-- DETAILS -->

                    <div class="food-details">

                        <span class="food-category">

                            <?php

                            echo htmlspecialchars(
                                $item['category']
                            );

                            ?>

                        </span>


                        <h3>

                            <?php

                            echo htmlspecialchars(
                                $item['item_name']
                            );

                            ?>

                        </h3>


                        <div class="food-bottom">

                            <strong>

                                ₹<?php

                                echo number_format(
                                    $item['price'],
                                    2
                                );

                                ?>

                            </strong>


                            <?php if ($is_available) { ?>

                                <form method="POST">

                                    <input
                                        type="hidden"
                                        name="item_id"
                                        value="<?php
                                            echo $item['item_id'];
                                        ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="add_to_cart"
                                        class="add-cart-btn"
                                    >

                                        <i class="fa-solid fa-plus"></i>

                                        Add

                                    </button>

                                </form>

                            <?php } else { ?>

                                <button
                                    type="button"
                                    class="out-stock-btn"
                                    disabled
                                >

                                    Out of Stock

                                </button>

                            <?php } ?>

                        </div>

                    </div>

                </div>

            <?php

                }

            } else {

            ?>

                <div class="canteen-empty">

                    <i class="fa-solid fa-utensils"></i>

                    <h2>
                        No Food Found
                    </h2>

                    <p>
                        No items match your search.
                    </p>

                </div>

            <?php } ?>

        </div>

    </section>

</div>


<!-- =====================================================
     CART OVERLAY
====================================================== -->

<div
    id="cartOverlay"
    class="cart-overlay"
    onclick="closeCart()"
></div>


<!-- =====================================================
     CART SIDEBAR
====================================================== -->

<div
    id="cartSidebar"
    class="cart-sidebar"
>

    <div class="cart-header">

        <div>

            <h2>

                <i class="fa-solid fa-cart-shopping"></i>

                Your Cart

            </h2>

            <p>
                <?php echo $cart_count; ?> item(s)
            </p>

        </div>


        <button
            type="button"
            class="cart-close"
            onclick="closeCart()"
        >

            <i class="fa-solid fa-xmark"></i>

        </button>

    </div>


    <!-- CART ITEMS -->

    <div class="cart-items">

        <?php if (count($cart_items) > 0) { ?>

            <?php foreach ($cart_items as $cart_item) { ?>

                <div class="cart-item">


                    <div class="cart-item-icon">

                        <i class="fa-solid fa-utensils"></i>

                    </div>


                    <div class="cart-item-details">

                        <h3>

                            <?php

                            echo htmlspecialchars(
                                $cart_item['item_name']
                            );

                            ?>

                        </h3>


                        <p>

                            ₹<?php

                            echo number_format(
                                $cart_item['price'],
                                2
                            );

                            ?>

                        </p>


                        <!-- QUANTITY -->

                        <form
                            method="POST"
                            class="quantity-form"
                        >

                            <input
                                type="hidden"
                                name="item_id"
                                value="<?php
                                    echo $cart_item['item_id'];
                                ?>"
                            >


                            <input
                                type="hidden"
                                name="update_cart"
                                value="1"
                            >


                            <button
                                type="button"
                                onclick="changeQuantity(this, -1)"
                            >
                                −
                            </button>


                            <input
                                type="number"
                                name="quantity"
                                min="1"
                                value="<?php
                                    echo $cart_item['quantity'];
                                ?>"
                                onchange="this.form.submit()"
                            >


                            <button
                                type="button"
                                onclick="changeQuantity(this, 1)"
                            >
                                +
                            </button>

                        </form>

                    </div>


                    <div class="cart-item-right">

                        <strong>

                            ₹<?php

                            echo number_format(
                                $cart_item['price']
                                *
                                $cart_item['quantity'],
                                2
                            );

                            ?>

                        </strong>


                        <form method="POST">

                            <input
                                type="hidden"
                                name="item_id"
                                value="<?php
                                    echo $cart_item['item_id'];
                                ?>"
                            >


                            <button
                                type="submit"
                                name="remove_cart"
                                class="remove-cart"
                            >

                                <i class="fa-solid fa-trash"></i>

                            </button>

                        </form>

                    </div>

                </div>

            <?php } ?>


        <?php } else { ?>

            <div class="cart-empty">

                <i class="fa-solid fa-cart-shopping"></i>

                <h3>
                    Your cart is empty
                </h3>

                <p>
                    Add something delicious!
                </p>

            </div>

        <?php } ?>

    </div>


    <!-- CART FOOTER -->

    <?php if (count($cart_items) > 0) { ?>

        <div class="cart-footer">

            <div class="cart-total">

                <span>
                    Total
                </span>

                <strong>

                    ₹<?php

                    echo number_format(
                        $cart_total,
                        2
                    );

                    ?>

                </strong>

            </div>


            <button
                type="button"
                class="checkout-btn"
                onclick="checkout()"
            >

                <i class="fa-solid fa-credit-card"></i>

                Proceed to Payment

            </button>


            <form method="POST">

                <button
                    type="submit"
                    name="clear_cart"
                    class="clear-cart-btn"
                >

                    <i class="fa-solid fa-trash"></i>

                    Clear Cart

                </button>

            </form>

        </div>

    <?php } ?>

</div>


<!-- =====================================================
     PAYMENT POPUP
====================================================== -->

<div
    id="paymentPopup"
    class="payment-popup"
>

    <div class="payment-box">

        <div class="payment-icon">

            <i class="fa-solid fa-credit-card"></i>

        </div>

        <h2>
            Payment
        </h2>

        <p>
            The payment system will be connected
            in the next step.
        </p>

        <button
            type="button"
            onclick="closePayment()"
        >
            OK
        </button>

    </div>

</div>


<script>

/* =====================================================
   OPEN CART
===================================================== */

function openCart() {

    document
        .getElementById("cartSidebar")
        .classList.add("active");

    document
        .getElementById("cartOverlay")
        .classList.add("active");

}


/* =====================================================
   CLOSE CART
===================================================== */

function closeCart() {

    document
        .getElementById("cartSidebar")
        .classList.remove("active");

    document
        .getElementById("cartOverlay")
        .classList.remove("active");

}


/* =====================================================
   CHANGE QUANTITY
===================================================== */

function changeQuantity(button, amount) {

    const form =
        button.closest("form");

    const input =
        form.querySelector(
            'input[name="quantity"]'
        );

    let quantity =
        parseInt(input.value);

    quantity += amount;

    if (quantity < 1) {
        quantity = 1;
    }

    input.value = quantity;

    form.submit();
}


/* =====================================================
   PAYMENT
===================================================== */

function checkout() {

    document
        .getElementById("paymentPopup")
        .classList.add("active");

}


function closePayment() {

    document
        .getElementById("paymentPopup")
        .classList.remove("active");

}


/* =====================================================
   ESC KEY
===================================================== */

document.addEventListener(
    "keydown",
    function(event) {

        if (event.key === "Escape") {

            closeCart();

            closePayment();

        }

    }
);

</script>


</body>

</html>

<?php

mysqli_close($conn);

?>