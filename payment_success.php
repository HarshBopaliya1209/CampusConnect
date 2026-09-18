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
   CHECK PAYMENT
========================================================= */

if (
    !isset($_SESSION['last_payment'])
    || !is_array($_SESSION['last_payment'])
) {

    header("Location: payments.php");
    exit();

}


$payment =
    $_SESSION['last_payment'];


/* =========================================================
   SECURITY CHECK
========================================================= */

if (
    (int) $payment['student_id']
    !== $student_id
) {

    unset($_SESSION['last_payment']);

    header("Location: payments.php");
    exit();

}


/* =========================================================
   PAYMENT DETAILS
========================================================= */

$payment_id =
    (int) $payment['payment_id'];

$student_name =
    $payment['student_name'];

$payment_type =
    $payment['payment_type'];

$amount =
    (float) $payment['amount'];

$payment_method =
    $payment['payment_method'];

$payment_date =
    $payment['payment_date'];

$status =
    $payment['status'];


/* =========================================================
   FOOD ITEMS
========================================================= */

$items =
    isset($payment['items'])
    && is_array($payment['items'])
        ? $payment['items']
        : [];


/* =========================================================
   RECEIPT NUMBER
========================================================= */

$receipt_number =
    "CC-" .
    date(
        "Ymd",
        strtotime($payment_date)
    ) .
    "-" .
    str_pad(
        $payment_id,
        5,
        "0",
        STR_PAD_LEFT
    );

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
        CampusConnect | Payment Receipt
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
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

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

            min-height: 100vh;

            font-family:
                'Poppins',
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #f5f7ff,
                    #eef2ff
                );

            color: #172033;

            display: flex;

            justify-content: center;

            padding: 35px 20px;

        }


        .receipt-container {

            width: 100%;

            max-width: 720px;

        }


        /* =================================================
           RECEIPT CARD
        ================================================= */

        .receipt-card {

            background: white;

            border-radius: 24px;

            overflow: hidden;

            box-shadow:
                0 20px 60px
                rgba(15,23,42,.12);

        }


        /* =================================================
           HEADER
        ================================================= */

        .receipt-header {

            text-align: center;

            padding: 35px 25px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #7c3aed
                );

            color: white;

        }


        .success-icon {

            width: 70px;

            height: 70px;

            margin: 0 auto 15px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background: white;

            color: #16a34a;

            font-size: 34px;

        }


        .receipt-header h1 {

            margin: 0;

            font-size: 25px;

        }


        .receipt-header p {

            margin: 6px 0 0;

            font-size: 11px;

            opacity: .9;

        }


        /* =================================================
           CONTENT
        ================================================= */

        .receipt-content {

            padding: 30px;

        }


        .receipt-top {

            display: flex;

            justify-content:
                space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 20px;

        }


        .receipt-top h2 {

            margin: 0;

            font-size: 19px;

        }


        .paid-badge {

            padding: 7px 12px;

            border-radius: 20px;

            background: #dcfce7;

            color: #166534;

            font-size: 9px;

            font-weight: 700;

        }


        /* =================================================
           PAYMENT ID
        ================================================= */

        .payment-id {

            padding: 14px;

            text-align: center;

            background: #f8fafc;

            border: 1px dashed #cbd5e1;

            border-radius: 12px;

            margin-bottom: 22px;

        }


        .payment-id span {

            display: block;

            color: #64748b;

            font-size: 9px;

            margin-bottom: 4px;

        }


        .payment-id strong {

            font-size: 17px;

        }


        /* =================================================
           STUDENT DETAILS
        ================================================= */

        .details {

            border-top:
                1px solid #eef2f7;

            margin-bottom: 25px;

        }


        .detail-row {

            display: flex;

            justify-content:
                space-between;

            padding: 11px 0;

            border-bottom:
                1px solid #eef2f7;

            gap: 20px;

        }


        .detail-row span {

            color: #64748b;

            font-size: 10px;

        }


        .detail-row strong {

            font-size: 11px;

            text-align: right;

        }


        /* =================================================
           ITEMS
        ================================================= */

        .items-title {

            font-size: 15px;

            margin: 0 0 12px;

        }


        .items-table {

            width: 100%;

            border-collapse:
                collapse;

            margin-bottom: 20px;

        }


        .items-table th {

            background: #f8fafc;

            color: #64748b;

            font-size: 9px;

            font-weight: 600;

            padding: 11px 9px;

            text-align: left;

        }


        .items-table td {

            padding: 12px 9px;

            border-bottom:
                1px solid #eef2f7;

            font-size: 10px;

        }


        .items-table td:last-child,
        .items-table th:last-child {

            text-align: right;

        }


        .item-name {

            font-weight: 600;

            color: #172033;

        }


        .item-category {

            display: block;

            margin-top: 2px;

            font-size: 8px;

            color: #94a3b8;

        }


        /* =================================================
           TOTAL
        ================================================= */

        .total-box {

            display: flex;

            justify-content:
                space-between;

            align-items: center;

            padding: 17px;

            border-radius: 13px;

            background: #eef2ff;

        }


        .total-box span {

            color: #4f46e5;

            font-size: 12px;

            font-weight: 600;

        }


        .total-box strong {

            color: #312e81;

            font-size: 23px;

        }


        /* =================================================
           NOTICE
        ================================================= */

        .demo-notice {

            margin-top: 18px;

            padding: 10px;

            text-align: center;

            border-radius: 9px;

            background: #fff7ed;

            color: #9a3412;

            font-size: 8px;

        }


        /* =================================================
           BUTTONS
        ================================================= */

        .actions {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 12px;

            margin-top: 22px;

        }


        .btn {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            padding: 12px;

            border-radius: 11px;

            text-decoration: none;

            border: none;

            font-family: inherit;

            font-size: 10px;

            font-weight: 600;

            cursor: pointer;

        }


        .btn-primary {

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #7c3aed
                );

            color: white;

        }


        .btn-secondary {

            background: #f1f5f9;

            color: #334155;

        }


        .receipt-footer {

            text-align: center;

            padding: 0 25px 22px;

            color: #94a3b8;

            font-size: 8px;

        }


        /* =================================================
           PRINT
        ================================================= */

        @media print {

            body {

                background: white;

                padding: 0;

            }


            .receipt-container {

                max-width: 100%;

            }


            .receipt-card {

                box-shadow: none;

                border: 1px solid #ddd;

            }


            .actions,
            .demo-notice {

                display: none;

            }

        }


        @media (max-width: 550px) {

            .receipt-content {

                padding: 20px;

            }


            .receipt-top {

                align-items: flex-start;

                flex-direction: column;

            }


            .actions {

                grid-template-columns: 1fr;

            }


            .items-table th,
            .items-table td {

                padding: 9px 5px;

            }

        }

    </style>

</head>


<body>


<div class="receipt-container">


    <div class="receipt-card">


        <!-- =================================================
             SUCCESS HEADER
        ================================================== -->

        <div class="receipt-header">


            <div class="success-icon">

                <i
                    class="fa-solid fa-check"
                ></i>

            </div>


            <h1>
                Payment Successful!
            </h1>


            <p>
                Your canteen order has been paid successfully.
            </p>


        </div>



        <div class="receipt-content">


            <!-- =================================================
                 RECEIPT TITLE
            ================================================== -->

            <div class="receipt-top">


                <h2>
                    Canteen Payment Receipt
                </h2>


                <span
                    class="paid-badge"
                >

                    <i
                        class="fa-solid fa-circle-check"
                    ></i>

                    PAID

                </span>


            </div>



            <!-- =================================================
                 PAYMENT ID
            ================================================== -->

            <div class="payment-id">


                <span>
                    Payment ID
                </span>


                <strong>

                    #<?php

                    echo $payment_id;

                    ?>

                </strong>


            </div>



            <!-- =================================================
                 STUDENT / PAYMENT DETAILS
            ================================================== -->

            <div class="details">


                <div class="detail-row">

                    <span>
                        Receipt Number
                    </span>


                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $receipt_number
                        );

                        ?>

                    </strong>

                </div>


                <div class="detail-row">

                    <span>
                        Student Name
                    </span>


                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $student_name
                        );

                        ?>

                    </strong>

                </div>


                <div class="detail-row">

                    <span>
                        Student ID
                    </span>


                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $student_id
                        );

                        ?>

                    </strong>

                </div>


                <div class="detail-row">

                    <span>
                        Payment Method
                    </span>


                    <strong>

                        <?php

                        echo htmlspecialchars(
                            $payment_method
                        );

                        ?>

                    </strong>

                </div>


                <div class="detail-row">

                    <span>
                        Payment Date
                    </span>


                    <strong>

                        <?php

                        echo htmlspecialchars(
                            date(
                                "d M Y",
                                strtotime(
                                    $payment_date
                                )
                            )
                        );

                        ?>

                    </strong>

                </div>


            </div>



            <!-- =================================================
                 FOOD ITEMS
            ================================================== -->

            <h3 class="items-title">

                <i
                    class="fa-solid fa-utensils"
                ></i>

                Ordered Items

            </h3>


            <?php if (
                count($items) > 0
            ): ?>


                <div
                    style="overflow-x:auto;"
                >

                    <table
                        class="items-table"
                    >

                        <thead>

                            <tr>

                                <th>
                                    Item
                                </th>

                                <th>
                                    Price
                                </th>

                                <th>
                                    Qty
                                </th>

                                <th>
                                    Subtotal
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach (
                                $items
                                as $item
                            ): ?>


                                <tr>


                                    <td>

                                        <span
                                            class="item-name"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $item[
                                                    'item_name'
                                                ]
                                            );

                                            ?>

                                        </span>


                                        <span
                                            class="item-category"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $item[
                                                    'category'
                                                ]
                                            );

                                            ?>

                                        </span>

                                    </td>


                                    <td>

                                        ₹<?php

                                        echo number_format(
                                            (float)
                                            $item[
                                                'price'
                                            ],
                                            2
                                        );

                                        ?>

                                    </td>


                                    <td>

                                        <?php

                                        echo (int)
                                            $item[
                                                'quantity'
                                            ];

                                        ?>

                                    </td>


                                    <td>

                                        ₹<?php

                                        echo number_format(
                                            (float)
                                            $item[
                                                'subtotal'
                                            ],
                                            2
                                        );

                                        ?>

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
                        font-size:10px;
                    "
                >

                    Food item details are not available
                    for this receipt.

                </p>


            <?php endif; ?>



            <!-- =================================================
                 TOTAL
            ================================================== -->

            <div class="total-box">


                <span>
                    Total Paid
                </span>


                <strong>

                    ₹<?php

                    echo number_format(
                        $amount,
                        2
                    );

                    ?>

                </strong>


            </div>



            <!-- =================================================
                 DEMO NOTICE
            ================================================== -->

            <div
                class="demo-notice"
            >

                <i
                    class="fa-solid fa-circle-info"
                ></i>

                Demo payment — no real money was charged.

            </div>



            <!-- =================================================
                 ACTIONS
            ================================================== -->

            <div class="actions">


                <button
                    type="button"
                    class="btn btn-primary"
                    onclick="window.print()"
                >

                    <i
                        class="fa-solid fa-print"
                    ></i>

                    Print Receipt

                </button>


                <a
                    href="canteen.php"
                    class="btn btn-secondary"
                >

                    <i
                        class="fa-solid fa-utensils"
                    ></i>

                    Back to Canteen

                </a>


            </div>


        </div>



        <div class="receipt-footer">

            CampusConnect • Campus Canteen

        </div>


    </div>


</div>


</body>

</html>