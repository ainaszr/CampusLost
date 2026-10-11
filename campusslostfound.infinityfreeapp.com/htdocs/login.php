<?php

session_start();
require_once "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id_number = strtoupper(trim($_POST["id_number"]));
    $password = $_POST["password"];

    if (empty($id_number) || empty($password)) {

        $message = "Please enter your ID Number and password.";

    } else {

        /* DETECT ROLE */

        if (substr($id_number, 0, 3) === "STU") {

            $detected_role = "student";

        } elseif (substr($id_number, 0, 2) === "ST") {

            $detected_role = "staff";

        } elseif (substr($id_number, 0, 2) === "AD") {

            $detected_role = "admin";

        } else {

            $detected_role = "";

        }


        if (empty($detected_role)) {

            $message = "Invalid ID Number format.";

        } else {

            /* CHECK USER */

            $sql = "SELECT user_id, id_number, name, email, password, role
                    FROM users
                    WHERE id_number = ?";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                die("SQL Error: " . $conn->error);
            }

            $stmt->bind_param("s", $id_number);

            $stmt->execute();

            $result = $stmt->get_result();


            if ($result->num_rows == 1) {

                $user = $result->fetch_assoc();


                /* CHECK PASSWORD */

                if (password_verify($password, $user["password"])) {

                    $database_role = strtolower($user["role"]);


                    if ($database_role !== $detected_role) {

                        $message = "ID Number and account role do not match.";

                    } else {

                        /* SAVE SESSION */

                        $_SESSION["user_id"] = $user["user_id"];
                        $_SESSION["id_number"] = $user["id_number"];
                        $_SESSION["name"] = $user["name"];
                        $_SESSION["email"] = $user["email"];
                        $_SESSION["role"] = $database_role;


                        /* REDIRECT */

                        if ($detected_role === "student") {

                            header("Location: dashboard.php");
                            exit();

                        } elseif ($detected_role === "staff") {

                            header("Location: staff-dashboard.php");
                            exit();

                        } elseif ($detected_role === "admin") {

                            header("Location: admin-dashboard.php");
                            exit();

                        }

                    }

                } else {

                    $message = "Invalid ID Number or password.";

                }

            } else {

                $message = "Invalid ID Number or password.";

            }

            $stmt->close();

        }

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - CampusLost</title>

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;

            min-height: 100vh;

            background:
                linear-gradient(
                    rgba(7, 35, 82, 0.76),
                    rgba(8, 34, 78, 0.76)
                ),
                url("images/campus-bg.jpg");

            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;

            display: flex;
            align-items: center;
            justify-content: center;

            color: white;

            padding: 40px;
        }


        /* =========================
           MAIN CONTAINER
        ========================= */

        .page-container {
            width: 1180px;
            max-width: 100%;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 90px;
        }


        /* =========================
           LEFT SIDE
        ========================= */

        .left-section {
            width: 46%;
            padding-left: 20px;
        }


        /* BRAND */

        .brand {
            margin-bottom: 28px;
        }

        .brand-icon {
            font-size: 48px;
            margin-bottom: 8px;
        }

        .brand h1 {
            font-size: 48px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -1px;
        }

        .brand h1 span {
            color: #3b82f6;
        }

        .brand-subtitle {
            margin-top: 10px;
            margin-left: 45px;

            color: #93c5fd;

            font-size: 20px;
            font-weight: 500;
        }


        /* TAGLINE */

        .tagline {
            font-size: 19px;
            line-height: 1.6;

            margin: 32px 0 30px 45px;

            color: #ffffff;
        }


        /* FEATURES */

        .features {
            display: flex;
            flex-direction: column;
            gap: 13px;
        }

        .feature {
            display: flex;
            align-items: center;

            gap: 17px;

            width: 100%;

            padding: 12px 16px;

            border-radius: 14px;

            background: rgba(4, 28, 66, 0.38);

            transition: 0.25s ease;
        }

        .feature:hover {
            transform: translateX(5px);
            background: rgba(4, 28, 66, 0.58);
        }

        .feature-icon {
            min-width: 60px;
            height: 60px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: rgba(37, 99, 235, 0.30);

            font-size: 25px;
        }

        .feature-text h3 {
            font-size: 17px;
            margin-bottom: 4px;
        }

        .feature-text p {
            color: #dbeafe;
            font-size: 14px;
            line-height: 1.4;
        }


        /* =========================
           LOGIN CARD
        ========================= */

        .login-card {
            width: 520px;
            max-width: 100%;

            background: rgba(255, 255, 255, 0.98);

            color: #0f2747;

            padding: 55px 52px 45px;

            border-radius: 20px;

            box-shadow:
                0 25px 60px rgba(0, 0, 0, 0.28);

            animation: fadeIn 0.6s ease;
        }

        @keyframes fadeIn {

            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }


        /* LOGIN TITLE */

        .login-card h2 {
            font-size: 36px;
            font-weight: 800;

            margin-bottom: 7px;

            color: #082c5c;
        }

        .subtitle {
            color: #718096;

            font-size: 16px;

            margin-bottom: 35px;
        }


        /* =========================
           ERROR MESSAGE
        ========================= */

        .message {
            background: #fee2e2;

            color: #b91c1c;

            border: 1px solid #fecaca;

            padding: 12px 15px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-size: 14px;

            text-align: center;
        }


        /* =========================
           INPUTS
        ========================= */

        .input-group {
            position: relative;

            margin-bottom: 18px;
        }

        .input-icon {
            position: absolute;

            left: 17px;
            top: 50%;

            transform: translateY(-50%);

            color: #123b73;

            font-size: 18px;

            pointer-events: none;
        }

        .input-group input {
            width: 100%;

            height: 58px;

            padding:
                0 52px
                0 50px;

            border:
                1.5px solid
                #d6dfeb;

            border-radius: 11px;

            font-size: 15px;

            color: #1e293b;

            background: white;

            outline: none;

            transition: 0.2s ease;
        }

        .input-group input::placeholder {
            color: #7c8ba1;
        }

        .input-group input:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 4px
                rgba(37, 99, 235, 0.10);
        }


        /* PASSWORD EYE */

        .show-password {
            position: absolute;

            right: 17px;
            top: 50%;

            transform: translateY(-50%);

            border: none;
            background: transparent;

            color: #123b73;

            cursor: pointer;

            font-size: 18px;

            padding: 5px;
        }

        .show-password:hover {
            color: #2563eb;
        }


        /* =========================
           OPTIONS
        ========================= */

        .options {
            display: flex;

            justify-content: space-between;

            align-items: center;

            margin: 5px 0 28px;

            font-size: 14px;
        }

        .remember {
            display: flex;

            align-items: center;

            gap: 8px;

            color: #183153;

            cursor: pointer;
        }

        .remember input {
            width: 17px;
            height: 17px;

            accent-color: #2563eb;
        }

        .forgot {
            color: #1264f3;

            text-decoration: none;

            font-weight: 500;
        }

        .forgot:hover {
            text-decoration: underline;
        }


        /* =========================
           LOGIN BUTTON
        ========================= */

        .login-button {
            width: 100%;

            height: 57px;

            border: none;

            border-radius: 13px;

            background:
                linear-gradient(
                    90deg,
                    #2583f8,
                    #2f6df6
                );

            color: white;

            font-size: 17px;

            font-weight: 700;

            cursor: pointer;

            box-shadow:
                0 8px 20px
                rgba(37, 99, 235, 0.25);

            transition: 0.25s ease;
        }

        .login-button:hover {
            transform: translateY(-2px);

            box-shadow:
                0 12px 25px
                rgba(37, 99, 235, 0.35);
        }

        .login-button:active {
            transform: translateY(0);
        }


        /* =========================
           DIVIDER
        ========================= */

        .divider {
            height: 1px;

            background: #e2e8f0;

            margin: 30px 0 22px;
        }


        /* =========================
           REGISTER
        ========================= */

        .register-link {
            text-align: center;

            color: #718096;

            font-size: 14px;
        }

        .register-link a {
            color: #1264f3;

            text-decoration: none;

            font-weight: 700;
        }

        .register-link a:hover {
            text-decoration: underline;
        }


        /* =========================
           BACK HOME
        ========================= */

        .back-home {
            text-align: center;

            margin-top: 28px;
        }

        .back-home a {
            color: #183153;

            text-decoration: none;

            font-size: 14px;

            transition: 0.2s;
        }

        .back-home a:hover {
            color: #2563eb;
        }

        .back-home i {
            margin-right: 6px;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 950px) {

            body {
                padding: 30px 20px;
            }

            .page-container {
                gap: 40px;
            }

            .left-section {
                width: 45%;
            }

            .login-card {
                width: 480px;

                padding:
                    45px 38px
                    40px;
            }

            .brand h1 {
                font-size: 40px;
            }

        }


        @media (max-width: 780px) {

            body {
                align-items: flex-start;
            }

            .page-container {
                flex-direction: column;

                gap: 35px;

                padding: 20px 0;
            }

            .left-section {
                width: 100%;

                max-width: 520px;

                padding: 0;
            }

            .brand {
                text-align: center;
            }

            .brand-subtitle {
                margin-left: 0;
            }

            .tagline {
                margin:
                    25px 0;

                text-align: center;
            }

            .login-card {
                width: 100%;
            }

        }


        @media (max-width: 500px) {

            body {
                padding: 15px;
            }

            .features {
                display: none;
            }

            .brand-icon {
                font-size: 40px;
            }

            .brand h1 {
                font-size: 36px;
            }

            .brand-subtitle {
                font-size: 17px;
            }

            .tagline {
                font-size: 16px;
            }

            .login-card {
                padding:
                    38px 25px
                    32px;

                border-radius: 17px;
            }

            .login-card h2 {
                font-size: 30px;
            }

            .options {
                gap: 10px;
            }

        }

    </style>
</head>


<body>


<div class="page-container">


    <!-- =================================
         LEFT SIDE
    ================================== -->

    <div class="left-section">


        <!-- BRAND -->

        <div class="brand">

            <div class="brand-icon">
                🎓
            </div>

            <h1>
                Campus<span>Lost</span>
            </h1>

            <div class="brand-subtitle">
                Lost & Found System
            </div>

        </div>


        <!-- TAGLINE -->

        <div class="tagline">

            Find what you've lost.<br>

            Return what you've found.

        </div>


        <!-- FEATURES -->

        <div class="features">


            <!-- FEATURE 1 -->

            <div class="feature">

                <div class="feature-icon">

                    <i class="fa-solid fa-magnifying-glass"></i>

                </div>

                <div class="feature-text">

                    <h3>
                        Report Lost Items
                    </h3>

                    <p>
                        Easily report your lost belongings.
                    </p>

                </div>

            </div>


            <!-- FEATURE 2 -->

            <div class="feature">

                <div class="feature-icon">

                    <i class="fa-solid fa-box"></i>

                </div>

                <div class="feature-text">

                    <h3>
                        View Found Items
                    </h3>

                    <p>
                        Check items found around campus.
                    </p>

                </div>

            </div>


            <!-- FEATURE 3 -->

            <div class="feature">

                <div class="feature-icon">

                    <i class="fa-solid fa-users"></i>

                </div>

                <div class="feature-text">

                    <h3>
                        Build a Safer Campus
                    </h3>

                    <p>
                        Help keep our campus community connected.
                    </p>

                </div>

            </div>


        </div>

    </div>



    <!-- =================================
         RIGHT SIDE LOGIN
    ================================== -->

    <div class="login-card">


        <h2>
            Welcome Back!
        </h2>

        <p class="subtitle">
            Login to your campus account
        </p>


        <!-- PHP ERROR MESSAGE -->

        <?php if (!empty($message)): ?>

            <div class="message">

                <?php
                    echo htmlspecialchars($message);
                ?>

            </div>

        <?php endif; ?>


        <!-- LOGIN FORM -->

        <form method="POST">


            <!-- ID NUMBER -->

            <div class="input-group">

                <i class="fa-regular fa-user input-icon"></i>

                <input
                    type="text"
                    id="id_number"
                    name="id_number"
                    placeholder="Enter your ID number"
                    autocomplete="username"
                    required
                >

            </div>



            <!-- PASSWORD -->

            <div class="input-group">

                <i class="fa-solid fa-lock input-icon"></i>

                <input
                    type="password"
                    name="password"
                    id="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >

                <button
                    type="button"
                    class="show-password"
                    onclick="togglePassword()"
                    aria-label="Show or hide password"
                >

                    <i
                        id="eyeIcon"
                        class="fa-regular fa-eye-slash">
                    </i>

                </button>

            </div>



            <!-- OPTIONS -->

            <div class="options">


                <label class="remember">

                    <input
                        type="checkbox"
                        name="remember"
                    >

                    <span>
                        Remember me
                    </span>

                </label>


                <a
                    href="#"
                    class="forgot"
                >
                    Forgot Password?
                </a>


            </div>



            <!-- LOGIN BUTTON -->

            <button
                type="submit"
                class="login-button"
            >

                Login

            </button>


        </form>



        <!-- DIVIDER -->

        <div class="divider"></div>



        <!-- REGISTER -->

        <div class="register-link">

            Don't have an account?

            <a href="register.php">
                Create Account
            </a>

        </div>



        <!-- BACK HOME -->

        <div class="back-home">

            <a href="index.php">

                <i class="fa-solid fa-house"></i>

                ← Back to Home

            </a>

        </div>


    </div>


</div>

s

<script>

    function togglePassword() {

        const password =
            document.getElementById("password");

        const eyeIcon =
            document.getElementById("eyeIcon");


        if (password.type === "password") {

            password.type = "text";

            eyeIcon.classList.remove(
                "fa-eye-slash"
            );

            eyeIcon.classList.add(
                "fa-eye"
            );

        } else {

            password.type = "password";

            eyeIcon.classList.remove(
                "fa-eye"
            );

            eyeIcon.classList.add(
                "fa-eye-slash"
            );

        }

    }

</script>


</body>

</html>