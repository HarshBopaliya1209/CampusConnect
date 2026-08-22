<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header('Location: student_login.php');
    exit();
}

require_once 'db.php';

$studentId = $_SESSION['student_id'];
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $courses = ['BCA', 'BBA', 'B.Com', 'B.Sc', 'MCA', 'M.Com', 'B.Ed', 'LLB'];

    if ($name === '' || $email === '' || $course === '' || $semester === '') {
        $message = 'Please complete all profile fields.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } elseif (!in_array($course, $courses, true) || !ctype_digit($semester) || (int) $semester < 1 || (int) $semester > 8) {
        $message = 'Please select a valid course and semester.';
        $messageType = 'error';
    } else {
        $statement = mysqli_prepare($conn, 'UPDATE students SET name = ?, email = ?, course = ?, semester = ? WHERE student_id = ?');

        if ($statement) {
            mysqli_stmt_bind_param($statement, 'sssis', $name, $email, $course, $semester, $studentId);

            if (mysqli_stmt_execute($statement)) {
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['course'] = $course;
                $_SESSION['semester'] = $semester;
                $message = 'Your profile has been updated successfully.';
                $messageType = 'success';
            } else {
                $message = 'We could not update your profile. Please try again.';
                $messageType = 'error';
            }
            mysqli_stmt_close($statement);
        } else {
            $message = 'We could not update your profile. Please try again.';
            $messageType = 'error';
        }
    }
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$courses = ['BCA', 'BBA', 'B.Com', 'B.Sc', 'MCA', 'M.Com', 'B.Ed', 'LLB'];
$name = $_SESSION['name'];
$email = $_SESSION['email'];
$course = $_SESSION['course'];
$semester = $_SESSION['semester'];
$initial = strtoupper(substr($name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusConnect | My Profile</title>
    <link rel="stylesheet" href="assets/css/style.css?v=10">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .profile-page { min-height: 100vh; padding: 46px 6%; background: radial-gradient(circle at 8% 10%, rgba(99,102,241,.14), transparent 28%), radial-gradient(circle at 92% 15%, rgba(168,85,247,.12), transparent 27%), #f6f8ff; }
        .profile-top { max-width: 1120px; margin: 0 auto 28px; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .profile-top h1 { margin: 5px 0 0; color: #172033; font-size: 38px; }
        .profile-top p { margin: 0; color: #68738a; font-size: 14px; }
        .profile-eyebrow { color: #4f46e5 !important; font-size: 12px !important; font-weight: 800; letter-spacing: 1.8px; text-transform: uppercase; }
        .back-dashboard { padding: 11px 16px; border-radius: 12px; color: #4f46e5; background: #fff; border: 1px solid #e4e8f1; font-size: 14px; font-weight: 700; white-space: nowrap; }
        .back-dashboard:hover { color: #fff; background: #4f46e5; }
        .profile-layout { max-width: 1120px; margin: 0 auto; display: grid; grid-template-columns: 330px minmax(0, 1fr); gap: 25px; }
        .profile-card, .profile-form-card { background: rgba(255,255,255,.9); border: 1px solid #e6eaf2; border-radius: 23px; box-shadow: 0 14px 35px rgba(20,30,60,.08); }
        .profile-card { padding: 34px 28px; text-align: center; }
        .student-avatar { width: 104px; height: 104px; margin: 0 auto 18px; border-radius: 50%; display: grid; place-items: center; color: #fff; background: linear-gradient(135deg, #2563eb, #7c3aed); box-shadow: 0 13px 25px rgba(79,70,229,.28); font-size: 38px; font-weight: 800; }
        .profile-card h2 { margin: 0; color: #172033; font-size: 23px; }
        .profile-card > p { margin: 7px 0 25px; color: #788297; font-size: 13px; }
        .profile-id { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 11px; border-radius: 12px; background: #eef2ff; color: #4f46e5; font-size: 13px; font-weight: 700; }
        .profile-details { margin-top: 25px; text-align: left; border-top: 1px solid #edf0f5; }
        .profile-details div { padding: 15px 0; border-bottom: 1px solid #edf0f5; }
        .profile-details span { display: block; margin-bottom: 3px; color: #8992a4; font-size: 11px; font-weight: 700; letter-spacing: .7px; text-transform: uppercase; }
        .profile-details strong { color: #374151; font-size: 14px; }
        .profile-form-card { padding: 34px; }
        .form-heading { margin-bottom: 25px; }
        .form-heading h2 { margin: 0 0 6px; color: #172033; font-size: 25px; }
        .form-heading p { margin: 0; color: #788297; font-size: 14px; }
        .profile-alert { margin-bottom: 20px; padding: 13px 15px; border-radius: 12px; font-size: 13px; font-weight: 600; }
        .profile-alert.success { color: #047857; border: 1px solid #a7f3d0; background: #ecfdf5; }
        .profile-alert.error { color: #b91c1c; border: 1px solid #fecaca; background: #fef2f2; }
        .profile-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 19px; }
        .profile-field { display: flex; flex-direction: column; gap: 7px; }
        .profile-field.full { grid-column: 1 / -1; }
        .profile-field label { color: #374151; font-size: 13px; font-weight: 700; }
        .profile-field input, .profile-field select { width: 100%; height: 48px; padding: 0 14px; border: 1px solid #dbe2eb; border-radius: 11px; outline: none; color: #172033; background: #fff; font-family: inherit; font-size: 14px; }
        .profile-field input:focus, .profile-field select:focus { border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99,102,241,.11); }
        .profile-field input[readonly] { color: #788297; background: #f8faff; cursor: not-allowed; }
        .profile-submit { margin-top: 27px; padding: 13px 22px; border: 0; border-radius: 12px; color: #fff; background: linear-gradient(135deg, #2563eb, #7c3aed); box-shadow: 0 10px 22px rgba(79,70,229,.22); font-family: inherit; font-size: 14px; font-weight: 700; cursor: pointer; }
        .profile-submit:hover { transform: translateY(-2px); }
        @media (max-width: 780px) { .profile-page { padding: 28px 18px; } .profile-top { align-items: flex-start; } .profile-top h1 { font-size: 31px; } .profile-layout { grid-template-columns: 1fr; } .profile-form-card { padding: 27px 22px; } }
        @media (max-width: 500px) { .profile-top { flex-direction: column; } .profile-form-grid { grid-template-columns: 1fr; } .profile-field.full { grid-column: auto; } .back-dashboard { width: 100%; text-align: center; } }
    </style>
</head>
<body>
    <main class="profile-page">
        <header class="profile-top">
            <div>
                <p class="profile-eyebrow">Student portal</p>
                <h1>My Profile</h1>
                <p>Keep your academic and contact details up to date.</p>
            </div>
            <a class="back-dashboard" href="student_dashboard.php"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
        </header>

        <section class="profile-layout">
            <aside class="profile-card">
                <div class="student-avatar"><?php echo e($initial); ?></div>
                <h2><?php echo e($name); ?></h2>
                <p><?php echo e($course); ?> &middot; Semester <?php echo e($semester); ?></p>
                <div class="profile-id"><i class="fa-solid fa-id-card"></i> <?php echo e($studentId); ?></div>
                <div class="profile-details">
                    <div><span>Email address</span><strong><?php echo e($email); ?></strong></div>
                    <div><span>Programme</span><strong><?php echo e($course); ?></strong></div>
                    <div><span>Current semester</span><strong>Semester <?php echo e($semester); ?></strong></div>
                </div>
            </aside>

            <section class="profile-form-card">
                <div class="form-heading">
                    <h2>Personal information</h2>
                    <p>Edit the details that appear across your CampusConnect account.</p>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="profile-alert <?php echo e($messageType); ?>"><i class="fa-solid <?php echo $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i> <?php echo e($message); ?></div>
                <?php endif; ?>

                <form method="post" action="student_profile.php">
                    <div class="profile-form-grid">
                        <div class="profile-field full">
                            <label for="student_id">Student ID</label>
                            <input id="student_id" value="<?php echo e($studentId); ?>" readonly>
                        </div>
                        <div class="profile-field full">
                            <label for="name">Full name</label>
                            <input id="name" name="name" type="text" value="<?php echo e($name); ?>" required maxlength="100">
                        </div>
                        <div class="profile-field full">
                            <label for="email">Email address</label>
                            <input id="email" name="email" type="email" value="<?php echo e($email); ?>" required maxlength="150">
                        </div>
                        <div class="profile-field">
                            <label for="course">Course</label>
                            <select id="course" name="course" required>
                                <?php foreach ($courses as $courseOption): ?>
                                    <option value="<?php echo e($courseOption); ?>" <?php echo $course === $courseOption ? 'selected' : ''; ?>><?php echo e($courseOption); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="profile-field">
                            <label for="semester">Semester</label>
                            <select id="semester" name="semester" required>
                                <?php for ($number = 1; $number <= 8; $number++): ?>
                                    <option value="<?php echo $number; ?>" <?php echo (string) $semester === (string) $number ? 'selected' : ''; ?>>Semester <?php echo $number; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <button class="profile-submit" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
                </form>
            </section>
        </section>
    </main>
</body>
</html>
