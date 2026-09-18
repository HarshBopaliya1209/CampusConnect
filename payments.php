<?php

session_start();
include("db.php");


/* =========================================================
   STUDENT LOGIN CHECK
========================================================= */

if (!isset($_SESSION['student_id'])) {

    header("Location: student_login.php");
    exit();

}

$student_id = (int) $_SESSION['student_id'];


/* =========================================================
   GET STUDENT DETAILS
========================================================= */

$student_sql = "
    SELECT
        student_id,
        name,
        email,
        course,
        semester
    FROM students
    WHERE student_id = ?
";

$student_stmt = mysqli_prepare(
    $conn,
    $student_sql
);

if (!$student_stmt) {

    die("Database Error: " . mysqli_error($conn));

}

mysqli_stmt_bind_param(
    $student_stmt,
    "i",
    $student_id
);

mysqli_stmt_execute($student_stmt);

$student_result =
    mysqli_stmt_get_result($student_stmt);

$student =
    mysqli_fetch_assoc($student_result);

mysqli_stmt_close($student_stmt);


if (!$student) {

    session_unset();
    session_destroy();

    header("Location: student_login.php");
    exit();

}


/* =========================================================
   CHECK CART
========================================================= */
$has_cart = (
    isset($_SESSION['canteen_cart'])
    && is_array($_SESSION['canteen_cart'])
    && count($_SESSION['canteen_cart']) > 0
);


/* =========================================================
   BUILD CART FROM DATABASE
========================================================= */

$cart_items = [];

$cart_total = 0;


foreach (
    ($_SESSION['canteen_cart'] ?? [])
    as $item_id => $quantity
) {

    $item_id = (int) $item_id;

    $quantity = (int) $quantity;


    if (
        $item_id <= 0 ||
        $quantity <= 0
    ) {

        continue;

    }


    $item_sql = "
        SELECT
            item_id,
            item_name,
            price,
            category,
            availability
        FROM canteen
        WHERE item_id = ?
    ";


    $item_stmt =
        mysqli_prepare(
            $conn,
            $item_sql
        );


    if (!$item_stmt) {

        continue;

    }


    mysqli_stmt_bind_param(
        $item_stmt,
        "i",
        $item_id
    );


    mysqli_stmt_execute(
        $item_stmt
    );


    $item_result =
        mysqli_stmt_get_result(
            $item_stmt
        );


    $item =
        mysqli_fetch_assoc(
            $item_result
        );


    mysqli_stmt_close(
        $item_stmt
    );


    if (!$item) {

        continue;

    }


    $price =
        (float) $item['price'];


    $subtotal =
        $price * $quantity;


    $item['quantity'] =
        $quantity;


    $item['subtotal'] =
        $subtotal;


    $cart_items[] =
        $item;


    $cart_total +=
        $subtotal;

}


/* =========================================================
   CART VALIDATION
========================================================= */

$has_cart = (
    count($cart_items) > 0 &&
    $cart_total > 0
);

/* =========================================================
   PAYMENT MESSAGE
========================================================= */

$message = "";

$message_type = "";


/* =========================================================
   PROCESS DEMO PAYMENT
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['pay_now'])
) {

    $payment_method =
        trim(
            $_POST['payment_method']
            ?? ""
        );


    $allowed_methods = [

        "UPI",

        "Card",

        "Net Banking",

        "Cash"

    ];


    /* =====================================================
       VALIDATE PAYMENT METHOD
    ===================================================== */

    if (
        !in_array(
            $payment_method,
            $allowed_methods,
            true
        )
    ) {

        $message =
            "Please select a payment method.";

        $message_type =
            "error";

    } else {


        /* =================================================
           PAYMENT DETAILS
        ================================================= */

        $payment_type =
            "Canteen Order";


        $payment_date =
            date("Y-m-d");


        /*
         * Demo payment.
         *
         * No real money is transferred.
         */

        $status =
            "Paid";


        /* =================================================
           SAVE PAYMENT
        ================================================= */

        $payment_sql = "
            INSERT INTO payments
            (
                student_id,
                payment_type,
                amount,
                payment_date,
                status
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ";


        $payment_stmt =
            mysqli_prepare(
                $conn,
                $payment_sql
            );


        if (!$payment_stmt) {

            $message =
                "Unable to process payment.";

            $message_type =
                "error";

        } else {


            mysqli_stmt_bind_param(
                $payment_stmt,
                "isdss",
                $student_id,
                $payment_type,
                $cart_total,
                $payment_date,
                $status
            );


            if (
                mysqli_stmt_execute(
                    $payment_stmt
                )
            ) {


                $payment_id =
                    mysqli_insert_id(
                        $conn
                    );


                /* =========================================
                   SAVE PAYMENT + FOOD ITEMS
                ========================================= */

                $_SESSION['last_payment'] = [

                    "payment_id" =>
                        $payment_id,

                    "student_id" =>
                        $student_id,

                    "student_name" =>
                        $student['name'],

                    "payment_type" =>
                        $payment_type,

                    "amount" =>
                        $cart_total,

                    "payment_method" =>
                        $payment_method,

                    "payment_date" =>
                        $payment_date,

                    "status" =>
                        $status,

                    "items" =>
                        $cart_items

                ];


                /* =========================================
                   CLEAR CART
                ========================================= */

                $_SESSION['canteen_cart'] = [];


                /* =========================================
                   GO TO SUCCESS PAGE
                ========================================= */

                header(
                    "Location: payment_success.php"
                );

                exit();

            } else {

                $message =
                    "Payment could not be completed.";

                $message_type =
                    "error";

            }


            mysqli_stmt_close(
                $payment_stmt
            );

        }

    }

}


/* =========================================================
   GET PAYMENT HISTORY
========================================================= */

$payments = [];


$history_sql = "
    SELECT
        payment_id,
        payment_type,
        amount,
        payment_date,
        status
    FROM payments
    WHERE student_id = ?
    ORDER BY payment_id DESC
";


$history_stmt =
    mysqli_prepare(
        $conn,
        $history_sql
    );


if ($history_stmt) {

    mysqli_stmt_bind_param(
        $history_stmt,
        "i",
        $student_id
    );


    mysqli_stmt_execute(
        $history_stmt
    );


    $history_result =
        mysqli_stmt_get_result(
            $history_stmt
        );


    while (
        $row =
        mysqli_fetch_assoc(
            $history_result
        )
    ) {

        $payments[] =
            $row;

    }


    mysqli_stmt_close(
        $history_stmt
    );

}


/* =========================================================
   TOTAL PAID
========================================================= */

$total_paid = 0;


foreach (
    $payments as $payment
) {

    if (
        strtolower(
            trim(
                $payment['status']
            )
        ) === "paid"
    ) {

        $total_paid +=
            (float) $payment['amount'];

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
        CampusConnect | Payment
    </title>


    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >


    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
        crossorigin
    >


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


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family: 'Inter',
                sans-serif;

            background: #222326;

            color: #d6dded;

        }


        .payment-page {

            min-height: 100vh;

            padding: 35px 40px 60px;

        }


        /* =================================================
           HEADER
        ================================================= */

        .payment-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 30px;

        }


        .payment-header h1 {

            margin: 0;

            font-size: 30px;

        }


        .payment-header p {

            margin: 6px 0 0;

            color: #909daf;

            font-size: 13px;

        }


        .student-card {

            display: flex;

            align-items: center;

            gap: 10px;

            background: #262626;

            padding: 9px 16px 9px 9px;

            border-radius: 40px;

            box-shadow:
                0 7px 25px
                rgba(0,0,0,.06);

        }


        .student-avatar {

            width: 45px;

            height: 45px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background: linear-gradient(
                    135deg,
                    #3c70e3,
                    #854ce6
                );

            color: #ffffff;

            font-weight: 700;

        }


        .student-info strong {

            display: block;

            font-size: 13px;

        }


        .student-info span {

            color: #909daf;

            font-size: 10px;

        }


        /* =================================================
           MAIN GRID
        ================================================= */

        .payment-grid {

            display: grid;

            grid-template-columns: 1.1fr .9fr;

            gap: 25px;

            align-items: start;

        }


        .payment-card {

            background: #262626;

            border-radius: 20px;

            padding: 25px;

            box-shadow:
                0 7px 30px
                rgba(0,0,0,.06);

        }


        .payment-card h2 {

            margin: 0;

            font-size: 20px;

        }


        .payment-card-subtitle {

            margin: 5px 0 20px;

            color: #909daf;

            font-size: 11px;

        }


        /* =================================================
           ORDER ITEMS
        ================================================= */

        .order-item {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            padding: 14px 0;

            border-bottom: 1px solid #1f2123;

        }


        .order-item:last-child {

            border-bottom: none;

        }


        .order-item-left {

            display: flex;

            align-items: center;

            gap: 11px;

        }


        .order-icon {

            width: 42px;

            height: 42px;

            border-radius: 11px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #212225;

            color: #7871f2;

        }


        .order-item h3 {

            margin: 0;

            font-size: 13px;

        }


        .order-item p {

            margin: 3px 0 0;

            color: #909daf;

            font-size: 10px;

        }


        .order-price {

            text-align: right;

        }


        .order-price strong {

            display: block;

            font-size: 13px;

        }


        .order-price span {

            color: #909daf;

            font-size: 9px;

        }


        /* =================================================
           TOTAL
        ================================================= */

        .total-box {

            margin-top: 18px;

            padding-top: 18px;

            border-top: 2px solid #1f2123;

            display: flex;

            justify-content: space-between;

            align-items: center;

        }


        .total-box span {

            font-size: 13px;

            font-weight: 600;

            color: #909daf;

        }


        .total-box strong {

            font-size: 25px;

        }


        /* =================================================
           PAYMENT METHODS
        ================================================= */

        .method-title {

            font-size: 13px;

            font-weight: 600;

            margin-bottom: 12px;

        }


        .method {

            position: relative;

            margin-bottom: 10px;

        }


        .method input {

            position: absolute;

            opacity: 0;

        }


        .method label {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 14px;

            border: 1px solid #1b1d1f;

            border-radius: 12px;

            cursor: pointer;

            transition: .2s;

        }


        .method label:hover {

            border-color: #4c50e6;

            background: #232427;

        }


        .method input:checked + label {

            border-color: #4c50e6;

            background: #212225;

        }


        .method-icon {

            width: 38px;

            height: 38px;

            border-radius: 10px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #262626;

            color: #7871f2;

        }


        .method-info strong {

            display: block;

            font-size: 12px;

        }


        .method-info span {

            display: block;

            color: #909daf;

            font-size: 9px;

            margin-top: 2px;

        }


        /* =================================================
           PAY BUTTON
        ================================================= */

        .pay-now-btn {

            width: 100%;

            border: none;

            padding: 14px;

            border-radius: 12px;

            background: linear-gradient(
                    135deg,
                    #3c70e3,
                    #854ce6
                );

            color: #ffffff;

            font-family: inherit;

            font-size: 13px;

            font-weight: 700;

            cursor: pointer;

            margin-top: 15px;

        }


        .pay-now-btn:hover {

            opacity: .92;

        }


        .demo-note {

            margin-top: 12px;

            padding: 10px;

            background: #252320;

            color: #d44211;

            border-radius: 9px;

            font-size: 9px;

            line-height: 1.5;

            text-align: center;

        }


        /* =================================================
           HISTORY
        ================================================= */

        .history {

            margin-top: 25px;

        }


        .history-table {

            width: 100%;

            border-collapse: collapse;

        }


        .history-table th,
        .history-table td {

            padding: 12px 8px;

            text-align: left;

            border-bottom: 1px solid #1f2123;

            font-size: 10px;

        }


        .history-table th {

            color: #909daf;

        }


        .status {

            padding: 5px 8px;

            border-radius: 20px;

            font-size: 8px;

            font-weight: 700;

        }


        .status-paid {

            background: #1c201e;

            color: #aeedc6;

        }


        .status-pending {

            background: #3f3615;

            color: #d45b11;

        }


        .status-failed {

            background: #221e1e;

            color: #cb1a1a;

        }


        .alert {

            padding: 13px 16px;

            border-radius: 11px;

            margin-bottom: 20px;

            font-size: 12px;

        }


        .alert-error {

            background: #221e1e;

            color: #cb1a1a;

        }


        .back-btn {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            margin-bottom: 20px;

            color: #bab9c6;

            text-decoration: none;

            font-size: 11px;

            font-weight: 600;

        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 850px) {

            .payment-grid {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 600px) {

            .payment-page {

                padding: 20px;

            }


            .payment-header {

                flex-direction: column;

                align-items: flex-start;

            }

        }

    </style>

</head>


<body>


<div class="payment-page">


    <!-- =================================================
         HEADER
    ================================================== -->

    <div class="payment-header">


        <div>

            <h1>
                Payment
            </h1>

            <p>
                Complete your CampusConnect canteen order
            </p>

        </div>


        <div class="student-card">


            <div class="student-avatar">

                <?php

                echo htmlspecialchars(
                    strtoupper(
                        substr(
                            $student['name'],
                            0,
                            1
                        )
                    )
                );

                ?>

            </div>


            <div class="student-info">

                <strong>

                    <?php

                    echo htmlspecialchars(
                        $student['name']
                    );

                    ?>

                </strong>


                <span>

                    Student ID:

                    <?php

                    echo htmlspecialchars(
                        $student_id
                    );

                    ?>

                </span>

            </div>


        </div>


    </div>



    <!-- BACK TO DASHBOARD -->

    <a
        href="student_dashboard.php"
        class="back-btn"
    >

        <i class="fa-solid fa-arrow-left"></i>

        Back to Dashboard

    </a>



    <!-- =================================================
         ERROR MESSAGE
    ================================================== -->

    <?php if (
        !empty($message)
    ): ?>

        <div
            class="alert alert-error"
        >

            <?php

            echo htmlspecialchars(
                $message
            );

            ?>

        </div>

    <?php endif; ?>



    <!-- =================================================
         PAYMENT GRID
    ================================================== -->

    <div class="payment-grid">


        <!-- =================================================
             ORDER SUMMARY
        ================================================== -->

        <section
            class="payment-card"
        >


            <h2>
                Order Summary
            </h2>


            <p
                class="payment-card-subtitle"
            >
                Items from your canteen cart
            </p>


            <?php foreach (
                $cart_items
                as $item
            ): ?>


                <div
                    class="order-item"
                >


                    <div
                        class="order-item-left"
                    >


                        <div
                            class="order-icon"
                        >

                            <i
                                class="fa-solid fa-utensils"
                            ></i>

                        </div>


                        <div>

                            <h3>

                                <?php

                                echo htmlspecialchars(
                                    $item['item_name']
                                );

                                ?>

                            </h3>


                            <p>

                                ₹<?php

                                echo number_format(
                                    $item['price'],
                                    2
                                );

                                ?>

                                ×

                                <?php

                                echo (int)
                                    $item['quantity'];

                                ?>

                            </p>

                        </div>


                    </div>


                    <div
                        class="order-price"
                    >

                        <strong>

                            ₹<?php

                            echo number_format(
                                $item['subtotal'],
                                2
                            );

                            ?>

                        </strong>


                        <span>

                            <?php

                            echo htmlspecialchars(
                                $item['category']
                            );

                            ?>

                        </span>

                    </div>


                </div>


            <?php endforeach; ?>


            <!-- TOTAL -->

            <div
                class="total-box"
            >

                <span>
                    Total Amount
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


        </section>



        <!-- =================================================
             PAYMENT METHOD
        ================================================== -->

        <section
            class="payment-card"
        >


            <h2>
                Choose Payment Method
            </h2>


            <p
                class="payment-card-subtitle"
            >
                Select how you want to pay
            </p>


            <!-- =================================================
                 FORM STARTS BEFORE PAYMENT OPTIONS
            ================================================== -->

            <form
                method="POST"
                action="payments.php"
                onsubmit="return confirmPayment();"
            >


                <div
                    class="method-title"
                >

                    Payment Method

                </div>



                <!-- =================================================
                     UPI
                ================================================== -->

                <div class="method">

                    <input
                        type="radio"
                        id="upi"
                        name="payment_method"
                        value="UPI"
                        required
                    >


                    <label for="upi">


                        <div
                            class="method-icon"
                        >

                            <i
                                class="fa-solid fa-mobile-screen-button"
                            ></i>

                        </div>


                        <div
                            class="method-info"
                        >

                            <strong>
                                UPI
                            </strong>


                            <span>
                                Google Pay / PhonePe / Paytm
                            </span>

                        </div>


                    </label>

                </div>



                <!-- =================================================
                     CARD
                ================================================== -->

                <div class="method">

                    <input
                        type="radio"
                        id="card"
                        name="payment_method"
                        value="Card"
                    >


                    <label for="card">


                        <div
                            class="method-icon"
                        >

                            <i
                                class="fa-solid fa-credit-card"
                            ></i>

                        </div>


                        <div
                            class="method-info"
                        >

                            <strong>
                                Debit / Credit Card
                            </strong>


                            <span>
                                Visa / Mastercard / RuPay
                            </span>

                        </div>


                    </label>

                </div>



                <!-- =================================================
                     NET BANKING
                ================================================== -->

                <div class="method">

                    <input
                        type="radio"
                        id="banking"
                        name="payment_method"
                        value="Net Banking"
                    >


                    <label for="banking">


                        <div
                            class="method-icon"
                        >

                            <i
                                class="fa-solid fa-building-columns"
                            ></i>

                        </div>


                        <div
                            class="method-info"
                        >

                            <strong>
                                Net Banking
                            </strong>


                            <span>
                                Pay using your bank account
                            </span>

                        </div>


                    </label>

                </div>



                <!-- =================================================
                     CASH
                ================================================== -->

                <div class="method">

                    <input
                        type="radio"
                        id="cash"
                        name="payment_method"
                        value="Cash"
                    >


                    <label for="cash">


                        <div
                            class="method-icon"
                        >

                            <i
                                class="fa-solid fa-money-bill"
                            ></i>

                        </div>


                        <div
                            class="method-info"
                        >

                            <strong>
                                Cash at Canteen
                            </strong>


                            <span>
                                Demo payment option
                            </span>

                        </div>


                    </label>

                </div>



                <!-- =================================================
                     PAY BUTTON
                ================================================== -->

                <button
                    type="submit"
                    name="pay_now"
                    class="pay-now-btn"
                >

                    <i
                        class="fa-solid fa-lock"
                    ></i>

                    Pay ₹<?php

                    echo number_format(
                        $cart_total,
                        2
                    );

                    ?>

                </button>



                <!-- =================================================
                     DEMO NOTE
                ================================================== -->

                <div
                    class="demo-note"
                >

                    <i
                        class="fa-solid fa-circle-info"
                    ></i>

                    This is a demo payment system
                    for the CampusConnect project.
                    No real money will be charged.

                </div>


            </form>


        </section>


    </div>



    <!-- =================================================
         PAYMENT HISTORY
    ================================================== -->

    <section
        class="payment-card history"
    >


        <h2>
            Payment History
        </h2>


        <p
            class="payment-card-subtitle"
        >
            Your previous CampusConnect payments
        </p>


        <?php if (
            count($payments) > 0
        ): ?>


            <div
                style="overflow-x:auto;"
            >


                <table
                    class="history-table"
                >


                    <thead>

                        <tr>

                            <th>
                                Payment ID
                            </th>

                            <th>
                                Type
                            </th>

                            <th>
                                Amount
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php foreach (
                            $payments
                            as $payment
                        ): ?>


                            <?php

                            $status =
                                strtolower(
                                    trim(
                                        $payment[
                                            'status'
                                        ]
                                    )
                                );


                            $status_class =
                                "status-paid";


                            if (
                                $status ===
                                "pending"
                            ) {

                                $status_class =
                                    "status-pending";

                            } elseif (
                                $status ===
                                "failed"
                            ) {

                                $status_class =
                                    "status-failed";

                            }

                            ?>


                            <tr>


                                <td>

                                    #<?php

                                    echo (int)
                                        $payment[
                                            'payment_id'
                                        ];

                                    ?>

                                </td>


                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $payment[
                                            'payment_type'
                                        ]
                                    );

                                    ?>

                                </td>


                                <td>

                                    ₹<?php

                                    echo number_format(
                                        (float)
                                        $payment[
                                            'amount'
                                        ],
                                        2
                                    );

                                    ?>

                                </td>


                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        date(
                                            "d M Y",
                                            strtotime(
                                                $payment[
                                                    'payment_date'
                                                ]
                                            )
                                        )
                                    );

                                    ?>

                                </td>


                                <td>

                                    <span
                                        class="status
                                        <?php

                                        echo $status_class;

                                        ?>"
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $payment[
                                                'status'
                                            ]
                                        );

                                        ?>

                                    </span>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    </tbody>


                </table>


            </div>


        <?php else: ?>


            <p
                style="
                    color:#64748b;
                    font-size:11px;
                "
            >

                No previous payments found.

            </p>


        <?php endif; ?>


    </section>


</div>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

function confirmPayment() {

    return confirm(
        "Are you sure you want to complete this demo payment?"
    );

}

</script>


</body>

</html>


<?php

mysqli_close($conn);

?>