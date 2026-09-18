<?php

session_start();

include("db.php");

if(isset($_POST['student_id']) && isset($_POST['password']))
{

    $student_id = $_POST['student_id'];
    $password   = $_POST['password'];

    $sql = "SELECT * FROM students
            WHERE student_id='$student_id'
            AND password='$password'";

    $result = mysqli_query($conn, $sql);

    if(mysqli_num_rows($result) == 1)
{
    $row = mysqli_fetch_assoc($result);

    /* =========================
       CLEAR OLD SESSION
    ========================= */

    session_unset();
    session_destroy();

    session_start();
    session_regenerate_id(true);


    /* =========================
       CREATE STUDENT SESSION
    ========================= */

    $_SESSION['role'] = 'student';

    $_SESSION['student_id'] = $row['student_id'];
    $_SESSION['name'] = $row['name'];
    $_SESSION['email'] = $row['email'];
    $_SESSION['course'] = $row['course'];
    $_SESSION['semester'] = $row['semester'];


    /* =========================
       STUDENT DASHBOARD
    ========================= */

    header("Location: student_dashboard.php");
    exit();
}
    else
    {

        echo "<script>
                alert('Invalid Student ID or Password');
                window.location='student_login.php';
              </script>";

    }

}
else
{

    header("Location: student_login.php");
    exit();

}

?>