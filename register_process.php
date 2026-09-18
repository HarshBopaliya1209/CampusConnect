<?php

include("db.php");

$student_id = $_POST['student_id'];
$name       = $_POST['fullname'];
$email      = $_POST['email'];
$course     = $_POST['course'];
$semester   = $_POST['semester'];
$password   = $_POST['password'];

// Check if Student ID already exists
$check = "SELECT * FROM students WHERE student_id='$student_id'";
$result = mysqli_query($conn, $check);

if(mysqli_num_rows($result) > 0)
{
    echo "<script>
            alert('Student ID already exists!');
            window.location='student_register.php';
          </script>";
    exit();
}

// Insert new student
$sql = "INSERT INTO students
(student_id, name, email, course, semester, password)
VALUES
('$student_id',
 '$name',
 '$email',
 '$course',
 '$semester',
 '$password')";

if(mysqli_query($conn, $sql))
{
    echo "<script>
            alert('Registration Successful!');
            window.location='student_dashboard.php';
          </script>";
}
else
{
    echo "Error: " . mysqli_error($conn);
}

mysqli_close($conn);

?>