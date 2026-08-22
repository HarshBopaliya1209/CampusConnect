<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clear all session data for the current student.
    $_SESSION = [];

    // Remove the session cookie when PHP is configured to use one.
    if (ini_get('session.use_cookies')) {
        $cookieParams = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $cookieParams['path'],
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );
    }

    session_destroy();

    header('Location: student_login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusConnect | Log Out</title>
    <link rel="stylesheet" href="assets/css/style.css?v=10">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .logout-page { min-height: 100vh; display: grid; place-items: center; padding: 24px; background: radial-gradient(circle at 15% 15%, rgba(99,102,241,.18), transparent 30%), radial-gradient(circle at 85% 80%, rgba(168,85,247,.15), transparent 30%), #f6f8ff; }
        .logout-card { width: min(100%, 430px); padding: 42px 36px; text-align: center; background: #fff; border: 1px solid #e6eaf2; border-radius: 24px; box-shadow: 0 22px 55px rgba(20,30,60,.13); }
        .logout-icon { width: 72px; height: 72px; margin: 0 auto 19px; display: grid; place-items: center; border-radius: 50%; color: #4f46e5; background: #eef2ff; font-size: 28px; }
        .logout-card h1 { margin: 0 0 9px; color: #172033; font-size: 27px; }
        .logout-card p { margin: 0; color: #68738a; font-size: 14px; line-height: 1.7; }
        .logout-actions { display: flex; gap: 12px; margin-top: 29px; }
        .logout-actions a, .logout-actions button { flex: 1; padding: 12px 16px; border-radius: 12px; font: 700 14px 'Poppins', sans-serif; cursor: pointer; text-align: center; }
        .logout-actions a { color: #4f46e5; background: #f4f5ff; border: 1px solid #e0e4ff; }
        .logout-actions button { color: #fff; background: linear-gradient(135deg, #2563eb, #7c3aed); border: 0; }
        .logout-actions a:hover, .logout-actions button:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <main class="logout-page">
        <section class="logout-card" aria-labelledby="logout-heading">
            <div class="logout-icon"><i class="fa-solid fa-right-from-bracket"></i></div>
            <h1 id="logout-heading">Log out?</h1>
            <p>Are you sure you want to log out of your CampusConnect account?</p>
            <form method="post" class="logout-actions">
                <a href="student_dashboard.php">Cancel</a>
                <button type="submit">Yes, log out</button>
            </form>
        </section>
    </main>
</body>
</html>
