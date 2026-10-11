<?php

session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];


/* =========================
   GET CURRENT USER
========================= */

$sql = "SELECT name, role
        FROM users
        WHERE user_id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("User not found.");
}

$user = $result->fetch_assoc();

$name = $user["name"];
$role = strtolower(trim($user["role"]));

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <!-- LOAD THEME EARLY -->
    <script src="theme.js?v=4"></script>

    <title>Settings - CampusLost</title>

    <!-- FONT AWESOME -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    >

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #172033;
            min-height: 100vh;
            transition: .3s ease;
        }


        /* ================= SIDEBAR ================= */

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            height: 100vh;
            background: #071a38;
            color: white;
            padding: 28px 14px 20px;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            box-shadow: 5px 0 25px rgba(0,0,0,.08);
        }

        .brand {
            padding: 4px 14px 25px;
            border-bottom: 1px solid rgba(255,255,255,.09);
            margin-bottom: 20px;
        }

        .brand-top {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-icon {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: rgba(37,99,235,.18);
            color: #60a5fa;
            font-size: 19px;
        }

        .brand-name {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -.5px;
        }

        .brand-name span {
            color: #60a5fa;
        }

        .brand-subtitle {
            margin-top: 6px;
            margin-left: 48px;
            font-size: 11px;
            color: #94a3b8;
            letter-spacing: .4px;
        }

        .menu {
            flex: 1;
            overflow-y: auto;
        }

        .menu::-webkit-scrollbar {
            width: 4px;
        }

        .menu::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 20px;
        }

        .menu-label {
            font-size: 10px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0 14px;
            margin: 10px 0 8px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 12px 14px;
            margin-bottom: 5px;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            transition: .2s ease;
        }

        .menu a i {
            width: 20px;
            text-align: center;
        }

        .menu a:hover {
            background: rgba(37,99,235,.14);
            color: white;
            transform: translateX(2px);
        }

        .menu a.active {
            background: linear-gradient(90deg,#2563eb,#3478f6);
            color: white;
            box-shadow: 0 7px 18px rgba(37,99,235,.25);
        }


        /* ================= LOGOUT ================= */

        .logout {
            padding-top: 14px;
            border-top: 1px solid rgba(255,255,255,.09);
        }

        .logout a {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 9px;
            padding: 12px;
            background: rgba(239,68,68,.14);
            color: #fca5a5;
            border-radius: 10px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: .2s;
        }

        .logout a:hover {
            background: #dc2626;
            color: white;
        }


        /* ================= MAIN ================= */

        .main {
            margin-left: 260px;
            min-height: 100vh;
            padding: 35px 42px 55px;
        }


        /* ================= HEADER ================= */

        .page-header {
            margin-bottom: 25px;
        }

        .page-label {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #2563eb;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .page-header h1 {
            color: #102342;
            font-size: 28px;
            margin-bottom: 7px;
        }

        .page-header p {
            color: #748199;
            font-size: 14px;
        }


        /* ================= SETTINGS CARD ================= */

        .settings-card {
            max-width: 1000px;
            background: white;
            border: 1px solid #e8edf5;
            border-radius: 18px;
            box-shadow: 0 7px 25px rgba(15,39,71,.05);
            overflow: hidden;
        }

        .settings-card-header {
            padding: 22px 28px;
            border-bottom: 1px solid #edf1f5;
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .settings-card-icon {
            width: 42px;
            height: 42px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 16px;
        }

        .settings-card-header h2 {
            color: #102342;
            font-size: 16px;
            margin-bottom: 3px;
        }

        .settings-card-header p {
            color: #8a97aa;
            font-size: 11px;
        }


        /* ================= SETTINGS CONTENT ================= */

        .settings-content {
            padding: 28px;
        }

        .section-title {
            color: #334155;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .section-description {
            color: #8a97aa;
            font-size: 12px;
            margin-bottom: 18px;
            line-height: 1.5;
        }


        /* ================= THEME OPTIONS ================= */

        .theme-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .theme-option {
            position: relative;
            border: 1px solid #d8e0eb;
            border-radius: 13px;
            padding: 21px;
            cursor: pointer;
            background: #f9fbfd;
            transition: .2s ease;
        }

        .theme-option:hover {
            border-color: #93c5fd;
            background: #f8fbff;
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(37,99,235,.06);
        }

        .theme-option.selected {
            border-color: #2563eb;
            background: #eff6ff;
            box-shadow: 0 0 0 3px rgba(37,99,235,.07);
        }

        .theme-option input {
            display: none;
        }


        /* THEME PREVIEW */

        .theme-preview {
            height: 95px;
            border-radius: 10px;
            margin-bottom: 17px;
            padding: 12px;
            display: flex;
            gap: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .light-preview {
            background: #f5f7fb;
        }

        .dark-preview {
            background: #0f172a;
            border-color: #334155;
        }

        .preview-sidebar {
            width: 25%;
            border-radius: 5px;
        }

        .light-preview .preview-sidebar {
            background: #071a38;
        }

        .dark-preview .preview-sidebar {
            background: #020617;
        }

        .preview-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .preview-line {
            height: 7px;
            border-radius: 10px;
        }

        .light-preview .preview-line {
            background: #dbe3ee;
        }

        .dark-preview .preview-line {
            background: #334155;
        }

        .preview-line.short {
            width: 55%;
        }

        .preview-card {
            flex: 1;
            border-radius: 6px;
        }

        .light-preview .preview-card {
            background: white;
            border: 1px solid #e2e8f0;
        }

        .dark-preview .preview-card {
            background: #1e293b;
            border: 1px solid #334155;
        }


        /* ================= OPTION INFORMATION ================= */

        .theme-option-bottom {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .theme-icon {
            width: 39px;
            height: 39px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: white;
            color: #f59e0b;
            font-size: 16px;
            border: 1px solid #e8edf5;
        }

        .theme-option.dark-option .theme-icon {
            background: #172554;
            color: #93c5fd;
            border-color: #1e3a8a;
        }

        .theme-text {
            flex: 1;
        }

        .theme-text h3 {
            color: #334155;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .theme-text p {
            color: #8a97aa;
            font-size: 11px;
            line-height: 1.4;
        }

        .check-icon {
            width: 23px;
            height: 23px;
            flex-shrink: 0;
            border-radius: 50%;
            border: 2px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: transparent;
            font-size: 10px;
            transition: .2s;
        }

        .theme-option.selected .check-icon {
            background: #2563eb;
            border-color: #2563eb;
            color: white;
        }


        /* ================= CURRENT THEME ================= */

        .current-theme {
            margin-top: 22px;
            padding: 14px 16px;
            background: #f8fafc;
            border: 1px solid #e8edf5;
            border-radius: 10px;
            color: #64748b;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .current-theme i {
            color: #2563eb;
        }

        .current-theme strong {
            color: #334155;
        }


        /* ================= DARK MODE ================= */

        html.dark-mode body,
        html.dark-mode .main {
            background: #0f172a;
            color: #e2e8f0;
        }

        html.dark-mode .sidebar {
            background: #020617;
        }

        html.dark-mode .page-header h1 {
            color: #f8fafc;
        }

        html.dark-mode .page-header p {
            color: #94a3b8;
        }

        html.dark-mode .settings-card {
            background: #1e293b;
            border-color: #27364c;
            box-shadow: 0 7px 25px rgba(0,0,0,.20);
        }

        html.dark-mode .settings-card-header {
            border-color: #334155;
        }

        html.dark-mode .settings-card-icon {
            background: #172554;
            color: #93c5fd;
        }

        html.dark-mode .settings-card-header h2 {
            color: #f8fafc;
        }

        html.dark-mode .settings-card-header p {
            color: #94a3b8;
        }

        html.dark-mode .section-title {
            color: #e2e8f0;
        }

        html.dark-mode .section-description {
            color: #94a3b8;
        }

        html.dark-mode .theme-option {
            background: #111b2f;
            border-color: #3a4a62;
        }

        html.dark-mode .theme-option:hover {
            background: #152138;
            border-color: #60a5fa;
        }

        html.dark-mode .theme-option.selected {
            background: #172554;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,.10);
        }

        html.dark-mode .theme-text h3 {
            color: #f8fafc;
        }

        html.dark-mode .theme-text p {
            color: #94a3b8;
        }

        html.dark-mode .theme-icon {
            background: #1e293b;
            border-color: #475569;
        }

        html.dark-mode .theme-option.dark-option .theme-icon {
            background: #0f172a;
            border-color: #334155;
        }

        html.dark-mode .check-icon {
            border-color: #64748b;
        }

        html.dark-mode .theme-option.selected .check-icon {
            border-color: #3b82f6;
            background: #3b82f6;
        }

        html.dark-mode .current-theme {
            background: #111b2f;
            border-color: #334155;
            color: #94a3b8;
        }

        html.dark-mode .current-theme strong {
            color: #e2e8f0;
        }


        /* ================= RESPONSIVE ================= */

        @media (max-width: 800px) {

            .sidebar {
                width: 210px;
            }

            .brand-name {
                font-size: 18px;
            }

            .brand-subtitle {
                display: none;
            }

            .main {
                margin-left: 210px;
                padding: 28px 22px 40px;
            }

            .theme-options {
                grid-template-columns: 1fr;
            }
        }


        @media (max-width: 650px) {

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .menu {
                overflow: visible;
            }

            .logout {
                margin-top: 15px;
            }

            .main {
                margin-left: 0;
                padding: 25px 16px;
            }

            .settings-card-header,
            .settings-content {
                padding: 20px;
            }
        }

    </style>

</head>


<body>


<!-- ================= SIDEBAR ================= -->

<aside class="sidebar">


    <!-- BRAND -->

    <div class="brand">

        <div class="brand-top">

            <div class="brand-icon">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>

            <div class="brand-name">
                Campus<span>Lost</span>
            </div>

        </div>

        <div class="brand-subtitle">
            LOST & FOUND SYSTEM
        </div>

    </div>


    <!-- MENU -->

   <nav class="menu">

    <?php if ($role === "admin"): ?>

        <!-- ADMIN -->

        <div class="menu-label">Main</div>

        <a href="admin-dashboard.php">
            <i class="fa-solid fa-house"></i>
            Dashboard
        </a>

        <a href="admin-items.php">
            <i class="fa-solid fa-boxes-stacked"></i>
            Manage Items
        </a>

        <a href="admin-claims.php">
            <i class="fa-solid fa-file-circle-check"></i>
            Manage Claims
        </a>

        <a href="admin-users.php">
            <i class="fa-solid fa-users"></i>
            Manage Users
        </a>

        <div class="menu-label">Account</div>

        <a href="notifications.php">
            <i class="fa-solid fa-bell"></i>
            Notifications
        </a>

        <a href="profile.php">
            <i class="fa-solid fa-user"></i>
            Profile
        </a>

        <a href="settings.php" class="active">
            <i class="fa-solid fa-gear"></i>
            Settings
        </a>


    <?php elseif ($role === "staff"): ?>

        <!-- STAFF -->

        <div class="menu-label">Main</div>

        <a href="staff-dashboard.php">
            <i class="fa-solid fa-house"></i>
            Dashboard
        </a>

        <a href="lost-items.php">
            <i class="fa-solid fa-magnifying-glass"></i>
            Lost Items
        </a>

        <a href="found-items.php">
            <i class="fa-solid fa-box-open"></i>
            Found Items
        </a>

        <div class="menu-label">Reports</div>

        <a href="report-lost.php">
            <i class="fa-solid fa-bullhorn"></i>
            Report Lost
        </a>

        <a href="report-found.php">
            <i class="fa-solid fa-box"></i>
            Report Found
        </a>

        <div class="menu-label">Staff Management</div>

        <a href="staff-manage-items.php">
            <i class="fa-solid fa-boxes-stacked"></i>
            Manage Items
        </a>

        <a href="staff-claims.php">
            <i class="fa-solid fa-file-circle-check"></i>
            Claims
        </a>

        <div class="menu-label">Account</div>

        <a href="notifications.php">
            <i class="fa-solid fa-bell"></i>
            Notifications
        </a>

        <a href="profile.php">
            <i class="fa-solid fa-user"></i>
            Profile
        </a>

        <a href="settings.php" class="active">
            <i class="fa-solid fa-gear"></i>
            Settings
        </a>


    <?php else: ?>

        <!-- STUDENT -->

        <div class="menu-label">Main</div>

        <a href="dashboard.php">
            <i class="fa-solid fa-house"></i>
            Dashboard
        </a>

        <a href="lost-items.php">
            <i class="fa-solid fa-magnifying-glass"></i>
            Lost Items
        </a>

        <a href="found-items.php">
            <i class="fa-solid fa-box-open"></i>
            Found Items
        </a>

        <div class="menu-label">Reports</div>

        <a href="report-lost.php">
            <i class="fa-solid fa-bullhorn"></i>
            Report Lost
        </a>

        <a href="report-found.php">
            <i class="fa-solid fa-box"></i>
            Report Found
        </a>

        <a href="my-items.php">
            <i class="fa-solid fa-clipboard-list"></i>
            My Items
        </a>

        <a href="my-claims.php">
            <i class="fa-solid fa-file-circle-check"></i>
            My Claims
        </a>

        <div class="menu-label">Account</div>

        <a href="notifications.php">
            <i class="fa-solid fa-bell"></i>
            Notifications
        </a>

        <a href="profile.php">
            <i class="fa-solid fa-user"></i>
            Profile
        </a>

        <a href="settings.php" class="active">
            <i class="fa-solid fa-gear"></i>
            Settings
        </a>

    <?php endif; ?>

</nav>

    <!-- LOGOUT -->

    <div class="logout">

        <a href="logout.php">

            <i class="fa-solid fa-right-from-bracket"></i>

            Logout

        </a>

    </div>


</aside>


<!-- ================= MAIN ================= -->

<main class="main">


    <!-- PAGE HEADER -->

    <div class="page-header">

        <span class="page-label">

            <i class="fa-solid fa-sliders"></i>

            Preferences

        </span>


        <h1>
            Settings
        </h1>


        <p>
            Customize your CampusLost experience.
        </p>

    </div>


    <!-- SETTINGS CARD -->

    <div class="settings-card">


        <!-- CARD HEADER -->

        <div class="settings-card-header">


            <div class="settings-card-icon">

                <i class="fa-solid fa-palette"></i>

            </div>


            <div>

                <h2>
                    Appearance
                </h2>

                <p>
                    Choose how CampusLost looks on your device.
                </p>

            </div>


        </div>


        <!-- CONTENT -->

        <div class="settings-content">


            <div class="section-title">
                Theme
            </div>


            <div class="section-description">
                Select your preferred appearance. Your choice will
                automatically be saved on this device.
            </div>


            <!-- THEME OPTIONS -->

            <div class="theme-options">


                <!-- LIGHT -->

                <label
                    class="theme-option"
                    id="lightOption"
                    onclick="setTheme('light')"
                >

                    <input
                        type="radio"
                        name="theme"
                        value="light"
                    >


                    <div class="theme-preview light-preview">

                        <div class="preview-sidebar"></div>

                        <div class="preview-content">

                            <div class="preview-line short"></div>

                            <div class="preview-line"></div>

                            <div class="preview-card"></div>

                        </div>

                    </div>


                    <div class="theme-option-bottom">


                        <div class="theme-icon">

                            <i class="fa-solid fa-sun"></i>

                        </div>


                        <div class="theme-text">

                            <h3>
                                Light Mode
                            </h3>

                            <p>
                                Bright and clean appearance.
                            </p>

                        </div>


                        <div class="check-icon">

                            <i class="fa-solid fa-check"></i>

                        </div>


                    </div>

                </label>


                <!-- DARK -->

                <label
                    class="theme-option dark-option"
                    id="darkOption"
                    onclick="setTheme('dark')"
                >

                    <input
                        type="radio"
                        name="theme"
                        value="dark"
                    >


                    <div class="theme-preview dark-preview">

                        <div class="preview-sidebar"></div>

                        <div class="preview-content">

                            <div class="preview-line short"></div>

                            <div class="preview-line"></div>

                            <div class="preview-card"></div>

                        </div>

                    </div>


                    <div class="theme-option-bottom">


                        <div class="theme-icon">

                            <i class="fa-solid fa-moon"></i>

                        </div>


                        <div class="theme-text">

                            <h3>
                                Dark Mode
                            </h3>

                            <p>
                                Comfortable appearance for low light.
                            </p>

                        </div>


                        <div class="check-icon">

                            <i class="fa-solid fa-check"></i>

                        </div>


                    </div>

                </label>


            </div>


            <!-- CURRENT THEME -->

            <div class="current-theme">

                <i class="fa-solid fa-circle-info"></i>

                <span>
                    Current theme:
                    <strong id="currentTheme">
                        Light Mode
                    </strong>
                </span>

            </div>


        </div>

    </div>


</main>


<script>

/* =========================
   THEME SETTINGS
========================= */

function applyTheme(theme) {

    if (theme === "dark") {

        document.documentElement.classList.add("dark-mode");

    } else {

        document.documentElement.classList.remove("dark-mode");

    }

    localStorage.setItem("campusTheme", theme);

    updateThemeUI(theme);
}


/* =========================
   SET THEME
========================= */

function setTheme(theme) {

    applyTheme(theme);

}


/* =========================
   UPDATE SETTINGS UI
========================= */

function updateThemeUI(theme) {

    const lightOption =
        document.getElementById("lightOption");

    const darkOption =
        document.getElementById("darkOption");

    const currentTheme =
        document.getElementById("currentTheme");


    if (!lightOption || !darkOption || !currentTheme) {
        return;
    }


    lightOption.classList.remove("selected");
    darkOption.classList.remove("selected");


    if (theme === "dark") {

        darkOption.classList.add("selected");

        currentTheme.textContent = "Dark Mode";

    } else {

        lightOption.classList.add("selected");

        currentTheme.textContent = "Light Mode";

    }


    const radio =
        document.querySelector(
            'input[value="' + theme + '"]'
        );

    if (radio) {
        radio.checked = true;
    }

}


/* =========================
   LOAD SAVED THEME
========================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const savedTheme =
            localStorage.getItem("campusTheme") || "light";

        applyTheme(savedTheme);

    }
);

</script>


</body>
</html>