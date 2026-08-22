<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CampusConnect | Student Registration</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=10">
</head>

<body>

   
    <!-- Registration Section -->

    <section class="login-section">

        <div class="login-container">

            <!-- Left Side -->

            <div class="login-left">

                <h1>Student Registration</h1>

                <p>
                    Create your CampusConnect account and access notices,
                    applications, library services, canteen orders and
                    payment records from one secure platform.
                </p>

            </div>

            <!-- Right Side -->

            <div class="login-right">

                <h2>Create Account</h2>

                <p class="subtitle">
                    Register to continue
                </p>

                <form action="register_process.php" method="POST">

                    <label>Student ID</label>
                    <input type="text"
                           name="student_id"
                           placeholder="Enter Student ID"
                           required>

                    <label>Full Name</label>
                    <input type="text"
                           name="fullname"
                           placeholder="Enter Full Name"
                           required>

                    <label>Email Address</label>
                    <input type="email"
                           name="email"
                           placeholder="Enter Email Address"
                           required>

                    <label>Course</label>
                    <select name="course" required>
                        <option value="">Select Course</option>
                        <option>BCA</option>
                        <option>BBA</option>
                        <option>B.Com</option>
                        <option>B.Sc</option>
                        <option>MCA</option>
                        <option>M.Com</option>
                        <option>B.Ed</option>
                        <option>LLB</option>
                    </select>

                   <label>Semester</label>

                    <select name="semester" required>
                        <option value="">Select Semester</option>
                        <option>1</option>
                        <option>2</option>
                        <option>3</option>
                        <option>4</option>
                        <option>5</option>
                        <option>6</option>
                        <option>7</option>
                        <option>8</option>
                    </select>
                    
                    <label>Password</label>
                    <input type="password"
                           name="password"
                           placeholder="Create Password"
                           required>

                    <button type="submit">
                        Create Account
                    </button>

                </form>

                <div class="signup">

                    Already have an account?

                    <a href="student_login.php">
                        Login
                    </a>

                </div>

            </div>

        </div>

    </section>

</body>

</html>
