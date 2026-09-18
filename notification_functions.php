<?php

require_once __DIR__ . '/send_email.php';


// =====================================================
// SEND EMAIL TO STUDENT
// =====================================================

function notifyStudent($conn, $student_id, $subject, $message)
{
    $student_id = mysqli_real_escape_string($conn, $student_id);

    $sql = "SELECT name, email 
            FROM students 
            WHERE student_id = '$student_id'
            LIMIT 1";

    $result = mysqli_query($conn, $sql);

    if (!$result || mysqli_num_rows($result) == 0) {
        return false;
    }

    $student = mysqli_fetch_assoc($result);

    if (empty($student['email'])) {
        return false;
    }

    return sendEmail(
        $student['email'],
        $subject,
        $message
    );
}


// =====================================================
// SEND EMAIL TO FACULTY
// =====================================================

function notifyFaculty($conn, $faculty_id, $subject, $message)
{
    $faculty_id = mysqli_real_escape_string($conn, $faculty_id);

    $sql = "SELECT name, email 
            FROM faculty 
            WHERE faculty_id = '$faculty_id'
            LIMIT 1";

    $result = mysqli_query($conn, $sql);

    if (!$result || mysqli_num_rows($result) == 0) {
        return false;
    }

    $faculty = mysqli_fetch_assoc($result);

    if (empty($faculty['email'])) {
        return false;
    }

    return sendEmail(
        $faculty['email'],
        $subject,
        $message
    );
}
?>