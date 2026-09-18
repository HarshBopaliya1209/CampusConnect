<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CampusConnect</title>

    <link rel="stylesheet" href="assets/css/style.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    >


<style>
/* Make the entire Student and Faculty portal cards clickable */
.portal-card-link {
    display: block;
    text-decoration: none;
    color: inherit;
    cursor: pointer;
}

.portal-card-link:hover,
.portal-card-link:focus,
.portal-card-link:active {
    text-decoration: none;
    color: inherit;
}

.portal-card-link .portal-card {
    cursor: pointer;
}

.portal-enter {
    display: inline-block;
}
</style>

</head>

<body>

<!-- ================= NAVBAR ================= -->

<header>

<nav>

<div class="logo">
Campus<span>Connect</span>
</div>

<ul>

<li><a href="#">Home</a></li>
<li><a href="#services">Services</a></li>
<li><a href="#about">About</a></li>

</ul>

</nav>

</header>

<!-- ================= HERO ================= -->

<section class="hero">

<div class="overlay">

<div class="portal-container">

<a href="student_login.php" class="portal-card-link">
    <div class="portal-card student">
        <span class="icon">🎓</span>

        <h2>Student Portal</h2>

        <p>
            Access notices, applications,
            library and canteen services.
        </p>

        <span class="portal-enter">
            Enter Portal →
        </span>
    </div>
</a>

<a href="faculty_login.php" class="portal-card-link">
    <div class="portal-card faculty">
        <span class="icon">👨‍🏫</span>

        <h2>Faculty Portal</h2>

        <p>
            Manage notices and student
            applications.
        </p>

        <span class="portal-enter">
            Enter Portal →
        </span>
    </div>
</a>

</div>

</section>

<!-- ================= CAMPUS SERVICES ================= -->

<section class="services" id="services">

    <h2>Campus Services</h2>

    <div class="service-container">

        <div class="service-box">
            <i class="fas fa-bullhorn"></i>
            <h3>Notices</h3>
            <p>
                Stay informed with the latest college announcements and updates.
            </p>
        </div>

        <div class="service-box">
            <i class="fas fa-file-alt"></i>
            <h3>Applications</h3>
            <p>
                Submit requests and applications directly to faculty members.
            </p>
        </div>

        <div class="service-box">
            <i class="fas fa-book"></i>
            <h3>Library</h3>
            <p>
                Browse books and manage your library activities easily.
            </p>
        </div>

        <div class="service-box">
            <i class="fas fa-hamburger"></i>
            <h3>Canteen</h3>
            <p>
                Order food online and manage your canteen orders quickly.
            </p>
        </div>

        <div class="service-box">
            <i class="fas fa-credit-card"></i>
            <h3>Payments</h3>
            <p>
                Track fee payments and transaction history securely.
            </p>
        </div>

    </div>

</section>
<!-- ================= ABOUT ================= -->

<section class="about" id="about">

    <div class="about-container">

        <div class="about-text">

            <h2>About CampusConnect</h2>

            <p>
                CampusConnect is a modern college management system designed to
                simplify communication between students and faculty.
                It provides one platform to manage notices, online applications,
                library services, canteen facilities and fee payments.
            </p>

            <p>
                The system improves transparency, saves time and provides
                students with instant access to important college services
                anytime and anywhere.
            </p>

        </div>

        <div class="about-image">

            <div class="image-card">

                <i class="fa-solid fa-building-columns"></i>

                <h3>Smart Campus</h3>

                <p>
                    Digital Education Platform
                </p>

            </div>

        </div>

    </div>

</section>

<!-- ================= FOOTER ================= -->

<footer>

    <div class="footer-content">

        <div class="footer-logo">

            <h2>Campus<span>Connect</span></h2>

            <p>
                Smart College Management System
            </p>

        </div>

        <div class="footer-links">

            <h3>Quick Links</h3>

            <a href="#">Home</a>
            <a href="#">Services</a>
            <a href="#">About</a>

        </div>

        <div class="footer-links">

            <h3>Portals</h3>

            <a href="student_login.php">Student Login</a>
            <a href="faculty_login.php">Faculty Login</a>
            <a href="#">Contact</a>

        </div>

       
    </div>

    <div class="copyright">

        © 2026 CampusConnect. All Rights Reserved.

    </div>

</footer>

</body>
</html>