<?php
session_start();
require_once "db.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"]);
    $id_number = trim($_POST["id_number"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];


    /* =========================
       VALIDATION
    ========================= */

    if (
        $name === "" ||
        $id_number === "" ||
        $email === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {

        $error = "Please fill in all fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid student email.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } elseif (strlen($password) < 6) {
        
        $error = "Password must be at least 6 characters.";
    
    } elseif (!preg_match('/^(STU|ST)[0-9]+$/i', $id_number)) {
        
        $error = "Invalid ID. Only Student (STU) and Staff (ST) IDs can register.";
    
    } else {


        /* =========================
           CHECK ID NUMBER
        ========================= */

        $check_id = $conn->prepare(
            "SELECT user_id FROM users WHERE id_number = ?"
        );

        $check_id->bind_param("s", $id_number);

        $check_id->execute();

        $id_result = $check_id->get_result();


        if ($id_result->num_rows > 0) {

            $error = "This ID Number is already registered.";

        } else {


            /* =========================
               CHECK EMAIL
            ========================= */

            $check_email = $conn->prepare(
                "SELECT user_id FROM users WHERE email = ?"
            );

            $check_email->bind_param("s", $email);

            $check_email->execute();

            $email_result = $check_email->get_result();


            if ($email_result->num_rows > 0) {

                $error = "This email is already registered.";

            } else {


                /* =========================
                   HASH PASSWORD
                ========================= */

                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


                /* =========================
                   INSERT USER
                ========================= */

                $id_number = strtoupper($id_number);
                
                if (preg_match('/^STU[0-9]+$/', $id_number)) {
                    $role = "student";
                } elseif (preg_match('/^ST[0-9]+$/', $id_number)) {
                    $role = "staff";
                }

                $sql = "INSERT INTO users
                        (id_number, name, email, password, role)
                        VALUES (?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);

                if (!$stmt) {

                    $error = "Database error: " . $conn->error;

                } else {

                    $stmt->bind_param(
                        "sssss",
                        $id_number,
                        $name,
                        $email,
                        $hashed_password,
                        $role
                    );


                    if ($stmt->execute()) {

                        $success = "Account created successfully! You can now login.";

                    } else {

                        $error = "Registration failed. Please try again.";

                    }

                    $stmt->close();
                }
            }

            $check_email->close();
        }

        $check_id->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account - CampusLost</title>

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
            justify-content: center;
            align-items: center;

            padding: 35px;

            color: white;
        }


        /* =========================
           MAIN CONTAINER
        ========================= */

        .page-container {
            width: 1180px;
            max-width: 100%;

            display: flex;
            justify-content: space-between;
            align-items: center;

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

            color: white;
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
            justify-content: center;
            align-items: center;

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
           REGISTER CARD
        ========================= */

        .register-card {
            width: 520px;
            max-width: 100%;

            background: rgba(255, 255, 255, 0.98);

            color: #0f2747;

            padding: 42px 50px 35px;

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


        /* =========================
           TITLE
        ========================= */

        .register-card h2 {
            font-size: 34px;

            font-weight: 800;

            color: #082c5c;

            margin-bottom: 7px;
        }

        .subtitle {
            color: #718096;

            font-size: 15px;

            margin-bottom: 25px;
        }


        /* =========================
           MESSAGES
        ========================= */

        .error,
        .success {
            padding: 11px 14px;

            border-radius: 10px;

            margin-bottom: 18px;

            font-size: 13px;
        }

        .error {
            background: #fee2e2;

            color: #b91c1c;

            border: 1px solid #fecaca;
        }

        .success {
            background: #dcfce7;

            color: #166534;

            border: 1px solid #bbf7d0;
        }


        /* =========================
           INPUTS
        ========================= */

        .input-group {
            position: relative;

            margin-bottom: 14px;
        }

        .input-icon {
            position: absolute;

            left: 16px;
            top: 50%;

            transform: translateY(-50%);

            color: #123b73;

            font-size: 16px;

            pointer-events: none;
        }

        .input-group input {
            width: 100%;

            height: 53px;

            padding:
                0 48px
                0 48px;

            border:
                1.5px solid
                #d6dfeb;

            border-radius: 11px;

            font-size: 14px;

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


        /* =========================
           PASSWORD EYE
        ========================= */

        .show-password {
            position: absolute;

            right: 15px;
            top: 50%;

            transform: translateY(-50%);

            border: none;

            background: transparent;

            color: #123b73;

            cursor: pointer;

            font-size: 17px;

            padding: 5px;
        }

        .show-password:hover {
            color: #2563eb;
        }


        /* PASSWORD INFO */

        .password-info {
            font-size: 12px;

            color: #94a3b8;

            margin:
                -5px 0
                14px 3px;
        }


        /* =========================
           REGISTER BUTTON
        ========================= */

        .register-btn {
            width: 100%;

            height: 55px;

            margin-top: 5px;

            border: none;

            border-radius: 13px;

            background:
                linear-gradient(
                    90deg,
                    #2583f8,
                    #2f6df6
                );

            color: white;

            font-size: 16px;

            font-weight: 700;

            cursor: pointer;

            box-shadow:
                0 8px 20px
                rgba(37, 99, 235, 0.25);

            transition: 0.25s ease;
        }

        .register-btn:hover {
            transform: translateY(-2px);

            box-shadow:
                0 12px 25px
                rgba(37, 99, 235, 0.35);
        }

        .register-btn:active {
            transform: translateY(0);
        }


        /* =========================
           DIVIDER
        ========================= */

        .divider {
            height: 1px;

            background: #e2e8f0;

            margin: 25px 0 20px;
        }


        /* =========================
           LOGIN LINK
        ========================= */

        .login-link {
            text-align: center;

            color: #718096;

            font-size: 14px;
        }

        .login-link a {
            color: #1264f3;

            text-decoration: none;

            font-weight: 700;
        }

        .login-link a:hover {
            text-decoration: underline;
        }


        /* =========================
           BACK HOME
        ========================= */

        .back-home {
            display: block;

            text-align: center;

            margin-top: 22px;

            color: #183153;

            text-decoration: none;

            font-size: 14px;

            transition: 0.2s;
        }

        .back-home:hover {
            color: #2563eb;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 950px) {

            .page-container {
                gap: 40px;
            }

            .left-section {
                width: 45%;
            }

            .register-card {
                width: 480px;

                padding:
                    38px 35px
                    32px;
            }

            .brand h1 {
                font-size: 40px;
            }

        }


        @media (max-width: 780px) {

            body {
                align-items: flex-start;

                padding: 25px 20px;
            }

            .page-container {
                flex-direction: column;

                gap: 35px;

                padding: 15px 0;
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
                margin: 25px 0;

                text-align: center;
            }

            .register-card {
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

            .register-card {
                padding:
                    32px 22px
                    28px;

                border-radius: 17px;
            }

            .register-card h2 {
                font-size: 29px;
            }

        }

    </style>
</head>


<body>


<div class="page-container">


    <!-- =========================
         LEFT SIDE
    ========================== -->

    <div class="left-section">


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


        <div class="tagline">

            Join your campus community.<br>

            Help lost items find their way home.

        </div>


        <div class="features">


            <div class="feature">

                <div class="feature-icon">

                    <i class="fa-solid fa-magnifying-glass"></i>

                </div>

                <div class="feature-text">

                    <h3>
                        Report Lost Items
                    </h3>

                    <p>
                        Quickly report belongings you've lost.
                    </p>

                </div>

            </div>



            <div class="feature">

                <div class="feature-icon">

                    <i class="fa-solid fa-box"></i>

                </div>

                <div class="feature-text">

                    <h3>
                        View Found Items
                    </h3>

                    <p>
                        Browse items found around campus.
                    </p>

                </div>

            </div>



            <div class="feature">

                <div class="feature-icon">

                    <i class="fa-solid fa-users"></i>

                </div>

                <div class="feature-text">

                    <h3>
                        Build a Safer Campus
                    </h3>

                    <p>
                        Help reconnect belongings with their owners.
                    </p>

                </div>

            </div>


        </div>

    </div>



    <!-- =========================
         REGISTER CARD
    ========================== -->

    <div class="register-card">


        <h2>
            Create Account
        </h2>

        <p class="subtitle">
            Register your campus account to get started
        </p>



        <!-- ERROR -->

        <?php if ($error !== ""): ?>

            <div class="error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>



        <!-- SUCCESS -->

        <?php if ($success !== ""): ?>

            <div class="success">

                <i class="fa-solid fa-circle-check"></i>

                <?php echo htmlspecialchars($success); ?>

            </div>

        <?php endif; ?>



        <!-- =========================
             REGISTER FORM
        ========================== -->

        <form method="POST">


            <!-- FULL NAME -->

            <div class="input-group">

                <i class="fa-regular fa-user input-icon"></i>

                <input
                    type="text"
                    name="name"
                    placeholder="Full Name"
                    value="<?php
                        echo isset($_POST["name"])
                            ? htmlspecialchars($_POST["name"])
                            : "";
                    ?>"
                    required
                >

            </div>



            <!-- ID NUMBER -->

            <div class="input-group">

                <i class="fa-regular fa-id-card input-icon"></i>

                <input
                    type="text"
                    name="id_number"
                    placeholder="Campus ID (e.g. STU001, ST001)"
                    value="<?php
                        echo isset($_POST["id_number"])
                            ? htmlspecialchars($_POST["id_number"])
                            : "";
                    ?>"
                    required
                >

            </div>



            <!-- EMAIL -->

            <div class="input-group">

                <i class="fa-regular fa-envelope input-icon"></i>

                <input
                    type="email"
                    name="email"
                    placeholder="Campus Email"
                    value="<?php
                        echo isset($_POST["email"])
                            ? htmlspecialchars($_POST["email"])
                            : "";
                    ?>"
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
                    placeholder="Create Password"
                    required
                >

                <button
                    type="button"
                    class="show-password"
                    onclick="togglePassword('password', 'eyePassword')"
                    aria-label="Show or hide password"
                >

                    <i
                        id="eyePassword"
                        class="fa-regular fa-eye-slash">
                    </i>

                </button>

            </div>


            <div class="password-info">
                Password must be at least 6 characters.
            </div>



            <!-- CONFIRM PASSWORD -->

            <div class="input-group">

                <i class="fa-solid fa-lock input-icon"></i>

                <input
                    type="password"
                    name="confirm_password"
                    id="confirm_password"
                    placeholder="Confirm Password"
                    required
                >

                <button
                    type="button"
                    class="show-password"
                    onclick="togglePassword('confirm_password', 'eyeConfirm')"
                    aria-label="Show or hide password"
                >

                    <i
                        id="eyeConfirm"
                        class="fa-regular fa-eye-slash">
                    </i>

                </button>

            </div>



            <!-- CREATE ACCOUNT -->

            <button
                type="submit"
                class="register-btn"
            >

                Create Account

            </button>


        </form>



        <div class="divider"></div>



        <!-- LOGIN -->

        <div class="login-link">

            Already have an account?

            <a href="login.php">
                Login
            </a>

        </div>



        <!-- HOME -->

        <a
            href="index.php"
            class="back-home"
        >

            <i class="fa-solid fa-house"></i>

            &nbsp;← Back to Home

        </a>


    </div>


</div>



<script>

    function togglePassword(inputId, iconId) {

        const input =
            document.getElementById(inputId);

        const icon =
            document.getElementById(iconId);


        if (input.type === "password") {

            input.type = "text";

            icon.classList.remove(
                "fa-eye-slash"
            );

            icon.classList.add(
                "fa-eye"
            );

        } else {

            input.type = "password";

            icon.classList.remove(
                "fa-eye"
            );

            icon.classList.add(
                "fa-eye-slash"
            );

        }

    }

</script>


</body>
</html>