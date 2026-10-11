<?php

session_start();
require_once "db.php";

/* =========================
   CHECK LOGIN
========================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

/* =========================
   GET USER INFORMATION
========================= */

$user_sql = "SELECT name, role
             FROM users
             WHERE user_id = ?";

$user_stmt = $conn->prepare($user_sql);

if (!$user_stmt) {
    die("SQL Error: " . $conn->error);
}

$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();

$user_result = $user_stmt->get_result();

if ($user_result->num_rows === 0) {
    die("User not found.");
}

$user = $user_result->fetch_assoc();

$name = $user["name"];
$role = $user["role"];


/* =========================
   USER DASHBOARD STATISTICS
========================= */


/* Lost Items */

$sql = "SELECT COUNT(*) AS total
        FROM items
        WHERE user_id = ?
        AND item_type = 'Lost'";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$lost_count = $result->fetch_assoc()["total"];


/* Found Items */

$sql = "SELECT COUNT(*) AS total
        FROM items
        WHERE user_id = ?
        AND item_type = 'Found'";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$found_count = $result->fetch_assoc()["total"];


/* Pending Claims */

$sql = "SELECT COUNT(*) AS total
        FROM claims
        WHERE user_id = ?
        AND claim_status = 'Pending'";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$pending_claims = $result->fetch_assoc()["total"];


/* Returned Items */

$sql = "SELECT COUNT(*) AS total
        FROM items
        WHERE user_id = ?
        AND status = 'Returned'";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$returned_count = $result->fetch_assoc()["total"];


/* =========================
   RECENT ITEMS
========================= */

$recent_sql = "SELECT item_id,
                      item_name,
                      location,
                      date_reported,
                      item_type,
                      status
               FROM items
               WHERE user_id = ?
               ORDER BY item_id DESC
               LIMIT 3";

$recent_stmt = $conn->prepare($recent_sql);

if (!$recent_stmt) {
    die("SQL Error: " . $conn->error);
}

$recent_stmt->bind_param("i", $user_id);
$recent_stmt->execute();

$recent_result = $recent_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- KEEP GLOBAL DARK/LIGHT THEME -->
    <script src="theme.js?v=4"></script>

    <title>Dashboard - CampusLost</title>

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>

        /* =========================
           RESET
        ========================= */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #172033;
            min-height: 100vh;
            transition: 0.3s ease;
        }


        /* =========================
           SIDEBAR
        ========================= */

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

            box-shadow: 5px 0 25px rgba(0,0,0,0.08);
        }


        /* BRAND */

        .brand {
            padding: 4px 14px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.09);
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

            background: rgba(37, 99, 235, 0.18);
            color: #60a5fa;

            font-size: 19px;
        }

        .brand-name {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .brand-name span {
            color: #60a5fa;
        }

        .brand-subtitle {
            margin-top: 6px;
            margin-left: 48px;

            font-size: 11px;
            color: #94a3b8;

            letter-spacing: 0.4px;
        }


        /* =========================
           MENU
        ========================= */

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

            transition: 0.2s ease;
        }

        .menu a i {
            width: 20px;
            text-align: center;
            font-size: 15px;
        }

        .menu a:hover {
            background: rgba(37, 99, 235, 0.14);
            color: white;

            transform: translateX(2px);
        }

        .menu a.active {
            background: linear-gradient(
                90deg,
                #2563eb,
                #3478f6
            );

            color: white;

            box-shadow:
                0 7px 18px rgba(37, 99, 235, 0.25);
        }


        /* =========================
           LOGOUT
        ========================= */

        .logout {
            padding-top: 14px;

            border-top:
                1px solid rgba(255,255,255,0.09);
        }

        .logout a {
            display: flex;

            justify-content: center;
            align-items: center;

            gap: 9px;

            padding: 12px;

            background: rgba(239,68,68,0.14);
            color: #fca5a5;

            border-radius: 10px;

            text-decoration: none;

            font-size: 14px;
            font-weight: 600;

            transition: 0.2s;
        }

        .logout a:hover {
            background: #dc2626;
            color: white;
        }


        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: 260px;

            min-height: 100vh;

            padding: 35px 42px 50px;
        }


        /* =========================
           TOP BAR
        ========================= */

        .topbar {
            display: flex;

            justify-content: space-between;
            align-items: center;

            gap: 25px;

            margin-bottom: 30px;
        }

        .welcome-small {
            display: inline-block;

            color: #2563eb;

            font-size: 12px;
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: 1px;

            margin-bottom: 7px;
        }

        .welcome h1 {
            color: #102342;

            font-size: 27px;
            line-height: 1.25;

            margin-bottom: 7px;
        }

        .welcome p {
            color: #748199;
            font-size: 14px;
        }


        /* PROFILE CARD */

        .profile {
            display: flex;
            align-items: center;

            gap: 12px;

            min-width: 210px;

            padding: 10px 14px;

            background: white;

            border: 1px solid #e8edf5;

            border-radius: 14px;

            box-shadow:
                0 5px 18px rgba(15,39,71,0.05);
        }

        .profile-avatar {
            width: 42px;
            height: 42px;

            border-radius: 50%;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #e8f0ff;
            color: #2563eb;

            font-size: 17px;
        }

        .profile-info {
            min-width: 0;
        }

        .profile-info strong {
            display: block;

            max-width: 180px;

            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;

            color: #102342;

            font-size: 13px;
        }

        .role {
            display: block;

            color: #8290a6;

            font-size: 11px;

            margin-top: 3px;
        }


        /* =========================
           STATS
        ========================= */

        .stats {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

            margin-bottom: 35px;
        }

        .stat-card {
            position: relative;

            display: block;

            min-height: 180px;

            background: white;

            padding: 22px;

            border: 1px solid #e8edf5;
            border-radius: 16px;

            text-decoration: none;

            box-shadow:
                0 5px 20px rgba(15,39,71,0.045);

            overflow: hidden;

            transition: 0.25s ease;
        }

        .stat-card::after {
            content: "";

            position: absolute;

            width: 90px;
            height: 90px;

            right: -35px;
            top: -35px;

            border-radius: 50%;

            background: rgba(37,99,235,0.045);
        }

        .stat-card:hover {
            transform: translateY(-5px);

            box-shadow:
                0 14px 28px rgba(15,39,71,0.10);

            border-color: #dbe7ff;
        }


        /* STAT ICON */

        .stat-icon {
            width: 44px;
            height: 44px;

            border-radius: 12px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin-bottom: 18px;

            font-size: 17px;
        }

        .lost-icon {
            background: #feecee;
            color: #ef4444;
        }

        .found-icon {
            background: #e6f9ef;
            color: #16a34a;
        }

        .claim-icon {
            background: #fff6dd;
            color: #d97706;
        }

        .returned-icon {
            background: #e8f0ff;
            color: #2563eb;
        }


        /* STAT CONTENT */

        .stat-top {
            display: flex;
            align-items: flex-end;
            gap: 9px;

            margin-bottom: 4px;
        }

        .stat-card h3 {
            font-size: 29px;

            color: #102342;

            line-height: 1;
        }

        .stat-card p {
            color: #738198;
            font-size: 13px;
        }

        .stat-link {
            display: block;

            margin-top: 14px;

            color: #2563eb;

            font-size: 11px;
            font-weight: 700;
        }


        /* =========================
           SECTION TITLE
        ========================= */

        .section-heading {
            display: flex;

            justify-content: space-between;
            align-items: center;

            margin-bottom: 16px;
        }

        .section-title {
            color: #102342;

            font-size: 19px;
            font-weight: 750;
        }

        .section-subtitle {
            color: #8a97aa;

            font-size: 12px;

            margin-top: 4px;
        }


        /* =========================
           QUICK ACTIONS
        ========================= */

        .actions {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

            margin-bottom: 37px;
        }

        .action-card {
            position: relative;

            min-height: 155px;

            background: white;

            padding: 21px;

            border:
                1px solid #e8edf5;

            border-radius: 16px;

            text-decoration: none;

            color: #334155;

            box-shadow:
                0 5px 20px
                rgba(15,39,71,0.04);

            transition: 0.25s ease;

            overflow: hidden;
        }

        .action-card:hover {
            transform: translateY(-4px);

            border-color: #bfd4ff;

            box-shadow:
                0 13px 25px
                rgba(15,39,71,0.09);
        }

        .action-icon {
            width: 43px;
            height: 43px;

            display: flex;
            justify-content: center;
            align-items: center;

            border-radius: 12px;

            background: #edf4ff;
            color: #2563eb;

            font-size: 17px;

            margin-bottom: 16px;

            transition: 0.25s;
        }

        .action-card:hover .action-icon {
            background: #2563eb;
            color: white;
        }

        .action-card h3 {
            color: #102342;

            font-size: 15px;

            margin-bottom: 6px;
        }

        .action-card p {
            color: #7d899d;

            font-size: 12px;

            line-height: 1.45;
        }

        .action-arrow {
            position: absolute;

            right: 20px;
            top: 22px;

            color: #b6c0cf;

            font-size: 12px;

            transition: 0.2s;
        }

        .action-card:hover .action-arrow {
            color: #2563eb;
            transform: translateX(3px);
        }


        /* =========================
           RECENT ITEMS
        ========================= */

        .recent {
            background: white;

            border:
                1px solid #e8edf5;

            border-radius: 16px;

            padding: 5px 24px;

            box-shadow:
                0 5px 20px
                rgba(15,39,71,0.04);
        }

        .item {
            display: flex;

            justify-content: space-between;
            align-items: center;

            gap: 20px;

            padding: 20px 5px;

            border-bottom:
                1px solid #edf0f5;
        }

        .item:last-child {
            border-bottom: none;
        }

        .item-left {
            display: flex;
            align-items: center;

            gap: 15px;
        }

        .item-icon {
            width: 42px;
            height: 42px;

            flex-shrink: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 12px;

            background: #edf4ff;
            color: #2563eb;

            font-size: 16px;
        }

        .item-info h3 {
            color: #102342;

            font-size: 14px;

            margin-bottom: 6px;
        }

        .item-info p {
            color: #8290a6;

            font-size: 11px;
        }

        .item-info p i {
            color: #64748b;
            margin-right: 3px;
        }


        /* =========================
           BADGES
        ========================= */

        .badge {
            padding: 6px 11px;

            border-radius: 30px;

            font-size: 10px;
            font-weight: 800;

            letter-spacing: 0.3px;
        }

        .lost {
            background: #feecee;
            color: #dc2626;
        }

        .found {
            background: #e4f9ed;
            color: #15803d;
        }

        .returned {
            background: #e8f0ff;
            color: #2563eb;
        }


        /* =========================
           EMPTY
        ========================= */

        .empty {
            text-align: center;

            padding: 40px;

            color: #8a97aa;

            font-size: 13px;
        }

        .empty i {
            display: block;

            font-size: 30px;

            color: #cbd5e1;

            margin-bottom: 12px;
        }


        /* =========================
           DARK MODE
        ========================= */

        html.dark-mode body {
            background: #0f172a;
            color: #e2e8f0;
        }

        html.dark-mode .main {
            background: #0f172a;
        }

        html.dark-mode .sidebar {
            background: #020617;
        }

        html.dark-mode .brand {
            border-bottom-color: #1e293b;
        }

        html.dark-mode .menu a {
            color: #cbd5e1;
        }

        html.dark-mode .menu a:hover {
            background: #172033;
        }

        html.dark-mode .menu a.active {
            background:
                linear-gradient(
                    90deg,
                    #2563eb,
                    #3478f6
                );
        }


        /* TOP */

        html.dark-mode .welcome h1,
        html.dark-mode .section-title {
            color: #f8fafc;
        }

        html.dark-mode .welcome p,
        html.dark-mode .section-subtitle {
            color: #94a3b8;
        }


        /* PROFILE */

        html.dark-mode .profile {
            background: #1e293b;

            border-color: #27364c;

            box-shadow:
                0 5px 18px
                rgba(0,0,0,0.20);
        }

        html.dark-mode .profile-avatar {
            background: #172b4d;
            color: #60a5fa;
        }

        html.dark-mode .profile-info strong {
            color: #f8fafc;
        }

        html.dark-mode .role {
            color: #94a3b8;
        }


        /* STAT CARDS */

        html.dark-mode .stat-card {
            background: #1e293b;

            border-color: #27364c;

            box-shadow:
                0 5px 18px
                rgba(0,0,0,0.18);
        }

        html.dark-mode .stat-card:hover {
            border-color: #334b70;

            box-shadow:
                0 14px 28px
                rgba(0,0,0,0.30);
        }

        html.dark-mode .stat-card h3 {
            color: #f8fafc;
        }

        html.dark-mode .stat-card p {
            color: #94a3b8;
        }

        html.dark-mode .stat-link {
            color: #60a5fa;
        }

        html.dark-mode .lost-icon {
            background: rgba(239,68,68,0.14);
        }

        html.dark-mode .found-icon {
            background: rgba(34,197,94,0.14);
        }

        html.dark-mode .claim-icon {
            background: rgba(245,158,11,0.14);
        }

        html.dark-mode .returned-icon {
            background: rgba(37,99,235,0.18);
            color: #60a5fa;
        }


        /* ACTION CARDS */

        html.dark-mode .action-card {
            background: #1e293b;

            border-color: #27364c;

            box-shadow:
                0 5px 18px
                rgba(0,0,0,0.18);
        }

        html.dark-mode .action-card:hover {
            border-color: #3b5680;

            box-shadow:
                0 13px 25px
                rgba(0,0,0,0.28);
        }

        html.dark-mode .action-icon {
            background: #172b4d;
            color: #60a5fa;
        }

        html.dark-mode .action-card:hover .action-icon {
            background: #2563eb;
            color: white;
        }

        html.dark-mode .action-card h3 {
            color: #f8fafc;
        }

        html.dark-mode .action-card p {
            color: #94a3b8;
        }


        /* RECENT */

        html.dark-mode .recent {
            background: #1e293b;

            border-color: #27364c;

            box-shadow:
                0 5px 18px
                rgba(0,0,0,0.18);
        }

        html.dark-mode .item {
            border-bottom-color: #334155;
        }

        html.dark-mode .item-icon {
            background: #172b4d;
            color: #60a5fa;
        }

        html.dark-mode .item-info h3 {
            color: #f8fafc;
        }

        html.dark-mode .item-info p {
            color: #94a3b8;
        }

        html.dark-mode .item-info p i {
            color: #64748b;
        }

        html.dark-mode .empty {
            color: #94a3b8;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1100px) {

            .stats,
            .actions {
                grid-template-columns:
                    repeat(2, 1fr);
            }

        }


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

                padding:
                    28px 22px
                    40px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .profile {
                width: 100%;
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
            }

            .stats,
            .actions {
                grid-template-columns: 1fr;
            }

            .welcome h1 {
                font-size: 23px;
            }

            .item {
                align-items: flex-start;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

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


        <div class="menu-label">
            Main
        </div>


        <a href="dashboard.php"
           class="active">

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



        <div class="menu-label">
            Reports
        </div>


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



        <div class="menu-label">
            Account
        </div>


        <a href="notifications.php">

            <i class="fa-solid fa-bell"></i>

            Notifications

        </a>


        <a href="profile.php">

            <i class="fa-solid fa-user"></i>

            Profile

        </a>


        <a href="settings.php">

            <i class="fa-solid fa-gear"></i>

            Settings

        </a>


    </nav>



    <!-- LOGOUT -->

    <div class="logout">

        <a href="logout.php">

            <i class="fa-solid fa-right-from-bracket"></i>

            Logout

        </a>

    </div>


</aside>



<!-- =========================
     MAIN CONTENT
========================= -->

<main class="main">


    <!-- =========================
         TOP BAR
    ========================== -->

    <div class="topbar">


        <div class="welcome">

            <span class="welcome-small">
                Student Dashboard
            </span>

            <h1>

                Welcome Back,
                <?php echo htmlspecialchars($name); ?>! 👋

            </h1>

            <p>
                Here's what's happening on campus today.
            </p>

        </div>



        <!-- PROFILE -->

        <div class="profile">

            <div class="profile-avatar">

                <i class="fa-solid fa-user"></i>

            </div>


            <div class="profile-info">

                <strong>
                    <?php echo htmlspecialchars($name); ?>
                </strong>

                <span class="role">

                    <?php
                        echo ucfirst(
                            htmlspecialchars($role)
                        );
                    ?>

                </span>

            </div>

        </div>


    </div>



    <!-- =========================
         STATISTICS
    ========================== -->

    <div class="stats">


        <!-- LOST -->

        <a href="my-items.php?type=Lost"
           class="stat-card">

            <div class="stat-icon lost-icon">

                <i class="fa-solid fa-magnifying-glass"></i>

            </div>

            <div class="stat-top">

                <h3>
                    <?php echo $lost_count; ?>
                </h3>

            </div>

            <p>
                Lost Items
            </p>

            <span class="stat-link">
                View My Lost Items &nbsp;→
            </span>

        </a>



        <!-- FOUND -->

        <a href="my-items.php?type=Found"
           class="stat-card">

            <div class="stat-icon found-icon">

                <i class="fa-solid fa-box-open"></i>

            </div>

            <div class="stat-top">

                <h3>
                    <?php echo $found_count; ?>
                </h3>

            </div>

            <p>
                Found Items
            </p>

            <span class="stat-link">
                View My Found Items &nbsp;→
            </span>

        </a>



        <!-- CLAIMS -->

        <a href="my-claims.php?status=Pending"
           class="stat-card">

            <div class="stat-icon claim-icon">

                <i class="fa-solid fa-clock"></i>

            </div>

            <div class="stat-top">

                <h3>
                    <?php echo $pending_claims; ?>
                </h3>

            </div>

            <p>
                Pending Claims
            </p>

            <span class="stat-link">
                View My Claims &nbsp;→
            </span>

        </a>



        <!-- RETURNED -->

        <a href="my-items.php?status=Returned"
           class="stat-card">

            <div class="stat-icon returned-icon">

                <i class="fa-solid fa-circle-check"></i>

            </div>

            <div class="stat-top">

                <h3>
                    <?php echo $returned_count; ?>
                </h3>

            </div>

            <p>
                Returned Items
            </p>

            <span class="stat-link">
                View Returned Items &nbsp;→
            </span>

        </a>


    </div>



    <!-- =========================
         QUICK ACTIONS TITLE
    ========================== -->

    <div class="section-heading">

        <div>

            <h2 class="section-title">
                Quick Actions
            </h2>

            <p class="section-subtitle">
                What would you like to do?
            </p>

        </div>

    </div>



    <!-- =========================
         QUICK ACTIONS
    ========================== -->

    <div class="actions">


        <a href="report-lost.php"
           class="action-card">

            <span class="action-arrow">
                <i class="fa-solid fa-arrow-right"></i>
            </span>

            <div class="action-icon">

                <i class="fa-solid fa-bullhorn"></i>

            </div>

            <h3>
                Report Lost Item
            </h3>

            <p>
                Report something you have lost.
            </p>

        </a>



        <a href="report-found.php"
           class="action-card">

            <span class="action-arrow">
                <i class="fa-solid fa-arrow-right"></i>
            </span>

            <div class="action-icon">

                <i class="fa-solid fa-box"></i>

            </div>

            <h3>
                Report Found Item
            </h3>

            <p>
                Help someone find their item.
            </p>

        </a>



        <a href="lost-items.php"
           class="action-card">

            <span class="action-arrow">
                <i class="fa-solid fa-arrow-right"></i>
            </span>

            <div class="action-icon">

                <i class="fa-solid fa-magnifying-glass"></i>

            </div>

            <h3>
                Search Items
            </h3>

            <p>
                Search through lost and found items.
            </p>

        </a>



        <a href="my-claims.php"
           class="action-card">

            <span class="action-arrow">
                <i class="fa-solid fa-arrow-right"></i>
            </span>

            <div class="action-icon">

                <i class="fa-solid fa-file-circle-check"></i>

            </div>

            <h3>
                My Claims
            </h3>

            <p>
                Check your submitted claims.
            </p>

        </a>


    </div>



    <!-- =========================
         RECENT ITEMS TITLE
    ========================== -->

    <div class="section-heading">

        <div>

            <h2 class="section-title">
                My Recently Reported Items
            </h2>

            <p class="section-subtitle">
                Your latest lost and found reports
            </p>

        </div>

    </div>



    <!-- =========================
         RECENT ITEMS
    ========================== -->

    <div class="recent">


        <?php if ($recent_result->num_rows > 0): ?>


            <?php while ($item = $recent_result->fetch_assoc()): ?>


                <div class="item">


                    <div class="item-left">


                        <div class="item-icon">

                            <?php if ($item["item_type"] === "Lost"): ?>

                                <i class="fa-solid fa-magnifying-glass"></i>

                            <?php else: ?>

                                <i class="fa-solid fa-box-open"></i>

                            <?php endif; ?>

                        </div>



                        <div class="item-info">


                            <h3>

                                <?php
                                    echo htmlspecialchars(
                                        $item["item_name"]
                                    );
                                ?>

                            </h3>


                            <p>

                                <i class="fa-solid fa-location-dot"></i>

                                <?php
                                    echo htmlspecialchars(
                                        $item["location"]
                                    );
                                ?>


                                &nbsp;&nbsp;•&nbsp;&nbsp;


                                <i class="fa-regular fa-calendar"></i>

                                <?php
                                    echo htmlspecialchars(
                                        $item["date_reported"]
                                    );
                                ?>

                            </p>


                        </div>


                    </div>



                    <!-- STATUS BADGE -->

                    <?php if (
                        $item["status"] === "Returned"
                    ): ?>


                        <span class="badge returned">

                            RETURNED

                        </span>


                    <?php elseif (
                        $item["item_type"] === "Lost"
                    ): ?>


                        <span class="badge lost">

                            LOST

                        </span>


                    <?php else: ?>


                        <span class="badge found">

                            FOUND

                        </span>


                    <?php endif; ?>


                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <div class="empty">

                <i class="fa-regular fa-folder-open"></i>

                You haven't reported any items yet.

            </div>


        <?php endif; ?>


    </div>


</main>


</body>
</html>