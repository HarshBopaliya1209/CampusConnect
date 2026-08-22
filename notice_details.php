<?php

session_start();

include("db.php");

if (!isset($_GET['notice_id'])) {
    header("Location: notices.php");
    exit();
}

$notice_id = intval($_GET['notice_id']);

/*
    notices.faculty_id = INT
    faculty.faculty_id = VARCHAR like F001

    This matches:
    F001 -> 1
    F002 -> 2
    F003 -> 3
*/

$sql = "
    SELECT
        n.notice_id,
        n.title,
        n.description,
        n.notice_date,
        f.name AS faculty_name,
        f.designation AS faculty_designation
    FROM notices n
    LEFT JOIN faculty f
        ON CAST(SUBSTRING(f.faculty_id, 2) AS UNSIGNED) = n.faculty_id
    WHERE n.notice_id = ?
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $notice_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$notice = mysqli_fetch_assoc($result);

if (!$notice) {
    header("Location: notices.php");
    exit();
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
        CampusConnect | Notice
    </title>

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
        href="/CampusConnect/assets/css/style.css?v=101"
    >

</head>


<body>

<div class="notice-details-page">


    <!-- Back -->

    <a
        href="notices.php"
        class="notice-details-back"
    >

        <i class="fa-solid fa-arrow-left"></i>

        Back to Notices

    </a>


    <!-- Notice Card -->

    <div class="notice-details-card">


        <!-- Icon -->

        <div class="notice-details-icon">

            <i class="fa-solid fa-bullhorn"></i>

        </div>


        <!-- Date -->

        <div class="notice-details-date">

            <i class="fa-regular fa-calendar"></i>

            <?php

            echo date(
                "d M Y, h:i A",
                strtotime($notice['notice_date'])
            );

            ?>

        </div>


        <!-- Title -->

        <h1>

            <?php

            echo htmlspecialchars(
                $notice['title']
            );

            ?>

        </h1>


        <!-- Description -->

        <div class="notice-details-content">

            <?php

            echo nl2br(
                htmlspecialchars(
                    $notice['description']
                )
            );

            ?>

        </div>


        <!-- Posted By -->

        <div class="notice-author-box">

            <div class="notice-author-avatar">

                <?php

                if (!empty($notice['faculty_name'])) {

                    echo strtoupper(
                        substr(
                            $notice['faculty_name'],
                            0,
                            1
                        )
                    );

                } else {

                    echo "C";

                }

                ?>

            </div>


            <div class="notice-author-info">

                <span>
                    Posted by
                </span>


                <strong>

                    <?php

                    if (!empty($notice['faculty_name'])) {

                        echo htmlspecialchars(
                            $notice['faculty_name']
                        );

                    } else {

                        echo "College Administration";

                    }

                    ?>

                </strong>


                <small>

                    <?php

                    if (!empty($notice['faculty_designation'])) {

                        echo htmlspecialchars(
                            $notice['faculty_designation']
                        );

                    } else {

                        echo "Faculty";

                    }

                    ?>

                </small>

            </div>

        </div>


        <!-- Footer -->

        <div class="notice-details-footer">

            <span>

                <i class="fa-solid fa-bullhorn"></i>

                Campus Notice

            </span>


            <span>

                Notice ID:

                <?php

                echo $notice['notice_id'];

                ?>

            </span>

        </div>


    </div>

</div>

</body>

</html>

<?php

mysqli_close($conn);

?>