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

$sql_user = "SELECT name, role
             FROM users
             WHERE user_id = ?";

$stmt_user = $conn->prepare($sql_user);

if (!$stmt_user) {
    die("SQL Error: " . $conn->error);
}

$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();

$user_result = $stmt_user->get_result();

if ($user_result->num_rows === 0) {
    die("User not found.");
}

$user = $user_result->fetch_assoc();

$name = $user["name"];
$role = strtolower(trim($user["role"]));

$stmt_user->close();


/* =========================
   ADMIN CHECK
========================= */

if ($role !== "admin") {
    header("Location: dashboard.php");
    exit();
}


/* =========================
   ADMIN STATISTICS
========================= */

/* LOST REPORTS */

$sql = "SELECT COUNT(*) AS total
        FROM items
        WHERE item_type = 'Lost'";

$result = $conn->query($sql);

if (!$result) {
    die("SQL Error: " . $conn->error);
}

$lost_count = $result->fetch_assoc()["total"];


/* FOUND REPORTS */

$sql = "SELECT COUNT(*) AS total
        FROM items
        WHERE item_type = 'Found'";

$result = $conn->query($sql);

if (!$result) {
    die("SQL Error: " . $conn->error);
}

$found_count = $result->fetch_assoc()["total"];


/* PENDING APPROVALS */

$sql = "SELECT COUNT(*) AS total
        FROM items
        WHERE status = 'Pending'";

$result = $conn->query($sql);

if (!$result) {
    die("SQL Error: " . $conn->error);
}

$pending_count = $result->fetch_assoc()["total"];


/* RETURNED ITEMS */

$sql = "SELECT COUNT(*) AS total
        FROM items
        WHERE status = 'Returned'";

$result = $conn->query($sql);

if (!$result) {
    die("SQL Error: " . $conn->error);
}

$returned_count = $result->fetch_assoc()["total"];


/* CAMPUS USERS */

$sql = "SELECT COUNT(*) AS total
        FROM users
        WHERE LOWER(role) != 'admin'";

$result = $conn->query($sql);

if (!$result) {
    die("SQL Error: " . $conn->error);
}

$user_count = $result->fetch_assoc()["total"];


/* PENDING CLAIMS */

$sql = "SELECT COUNT(*) AS total
        FROM claims
        WHERE claim_status = 'Pending'";

$result = $conn->query($sql);

if (!$result) {
    die("SQL Error: " . $conn->error);
}

$claim_count = $result->fetch_assoc()["total"];


/* =========================
   RECENT PENDING REPORTS
========================= */

$sql = "SELECT item_id,
               item_name,
               item_type,
               category,
               location,
               date_reported,
               status
        FROM items
        WHERE status = 'Pending'
        ORDER BY item_id DESC
        LIMIT 5";

$recent_result = $conn->query($sql);

if (!$recent_result) {
    die("SQL Error: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<!-- LOAD GLOBAL THEME -->
<script src="theme.js?v=4"></script>

<title>Admin Dashboard - CampusLost</title>

<!-- FONT AWESOME -->
<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
>

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
    transition: background .3s ease, color .3s ease;
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

    box-shadow: 5px 0 25px rgba(0,0,0,.08);
}


/* =========================
   BRAND
========================= */

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
    background: linear-gradient(
        90deg,
        #2563eb,
        #3478f6
    );

    color: white;

    box-shadow:
        0 7px 18px rgba(37,99,235,.25);
}


/* =========================
   LOGOUT
========================= */

.logout {
    padding-top: 14px;

    border-top:
        1px solid rgba(255,255,255,.09);
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


/* =========================
   MAIN
========================= */

.main {
    margin-left: 260px;

    min-height: 100vh;

    padding: 35px 42px 55px;
}


/* =========================
   HEADER
========================= */

.header {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 25px;

    margin-bottom: 30px;
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

.header h1 {
    color: #102342;

    font-size: 28px;

    margin-bottom: 7px;
}

.header p {
    color: #748199;

    font-size: 14px;
}


/* =========================
   ADMIN PROFILE
========================= */

.admin-profile {
    min-width: 190px;

    background: white;

    border: 1px solid #e8edf5;

    border-radius: 14px;

    padding: 13px 17px;

    display: flex;

    align-items: center;

    gap: 11px;

    box-shadow:
        0 5px 18px rgba(15,39,71,.05);
}

.admin-avatar {
    width: 39px;
    height: 39px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 11px;

    background: #eff6ff;

    color: #2563eb;

    font-size: 15px;
}

.admin-profile strong {
    display: block;

    color: #102342;

    font-size: 13px;

    margin-bottom: 4px;
}

.admin-profile span {
    display: flex;

    align-items: center;

    gap: 5px;

    color: #2563eb;

    font-size: 11px;
}


/* =========================
   STATISTICS
========================= */

.stats {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 18px;

    margin-bottom: 35px;
}

.stat-card {
    position: relative;

    display: block;

    background: white;

    border: 1px solid #e8edf5;

    border-radius: 16px;

    padding: 22px;

    min-height: 175px;

    text-decoration: none;

    color: #172033;

    overflow: hidden;

    box-shadow:
        0 6px 22px rgba(15,39,71,.05);

    transition: .2s ease;
}

.stat-card:hover {
    transform: translateY(-3px);

    border-color: #cbd9eb;

    box-shadow:
        0 10px 28px rgba(15,39,71,.09);
}

.stat-top {
    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 19px;
}

.stat-icon {
    width: 44px;
    height: 44px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 12px;

    font-size: 17px;
}


/* ICON COLOURS */

.stat-icon.red {
    background: #fff1f2;
    color: #e11d48;
}

.stat-icon.green {
    background: #ecfdf5;
    color: #059669;
}

.stat-icon.orange {
    background: #fff7ed;
    color: #ea580c;
}

.stat-icon.blue {
    background: #eff6ff;
    color: #2563eb;
}

.stat-icon.purple {
    background: #faf5ff;
    color: #9333ea;
}

.stat-icon.cyan {
    background: #ecfeff;
    color: #0891b2;
}

.stat-number {
    color: #102342;

    font-size: 30px;

    font-weight: 800;

    line-height: 1;
}

.stat-title {
    color: #475569;

    font-size: 13px;

    font-weight: 700;

    margin-bottom: 14px;
}

.stat-link {
    display: flex;

    align-items: center;

    gap: 6px;

    color: #2563eb;

    font-size: 11px;

    font-weight: 700;
}

.stat-link i {
    font-size: 9px;

    transition: .2s;
}

.stat-card:hover .stat-link i {
    transform: translateX(3px);
}


/* =========================
   SECTION HEADER
========================= */

.section-heading {
    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 16px;
}

.section-heading h2 {
    color: #102342;

    font-size: 18px;
}

.section-heading p {
    color: #94a3b8;

    font-size: 11px;

    margin-top: 4px;
}


/* =========================
   QUICK ACTIONS
========================= */

.actions {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 18px;

    margin-bottom: 38px;
}

.action-card {
    display: flex;

    align-items: flex-start;

    gap: 15px;

    background: white;

    border: 1px solid #e8edf5;

    border-radius: 15px;

    padding: 20px;

    text-decoration: none;

    color: #172033;

    box-shadow:
        0 5px 20px rgba(15,39,71,.04);

    transition: .2s ease;
}

.action-card:hover {
    transform: translateY(-2px);

    border-color: #bfdbfe;

    box-shadow:
        0 8px 25px rgba(15,39,71,.08);
}

.action-icon {
    width: 43px;
    height: 43px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 11px;

    background: #eff6ff;

    color: #2563eb;

    font-size: 16px;
}

.action-card h3 {
    color: #102342;

    font-size: 14px;

    margin-bottom: 6px;
}

.action-card p {
    color: #8290a5;

    font-size: 11px;

    line-height: 1.5;
}


/* =========================
   RECENT REPORTS
========================= */

.recent {
    background: white;

    border: 1px solid #e8edf5;

    border-radius: 16px;

    box-shadow:
        0 6px 22px rgba(15,39,71,.05);

    overflow: hidden;
}

.report {
    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;

    padding: 18px 22px;

    border-bottom: 1px solid #edf1f5;

    transition: .2s;
}

.report:last-child {
    border-bottom: none;
}

.report:hover {
    background: #f9fbfd;
}

.report-left {
    display: flex;

    align-items: center;

    gap: 14px;
}

.report-icon {
    width: 41px;
    height: 41px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 11px;

    font-size: 15px;
}

.report-icon.lost {
    background: #fff1f2;
    color: #e11d48;
}

.report-icon.found {
    background: #ecfdf5;
    color: #059669;
}

.report-info h3 {
    color: #102342;

    font-size: 13px;

    margin-bottom: 6px;
}

.report-info p {
    display: flex;

    flex-wrap: wrap;

    gap: 13px;

    color: #8290a5;

    font-size: 11px;
}

.report-info p span {
    display: inline-flex;

    align-items: center;

    gap: 5px;
}


/* =========================
   BADGES
========================= */

.badge {
    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 9px;

    font-weight: 800;

    letter-spacing: .4px;
}

.badge.lost {
    background: #fff1f2;
    color: #be123c;
}

.badge.found {
    background: #ecfdf5;
    color: #047857;
}


/* =========================
   EMPTY
========================= */

.empty {
    text-align: center;

    padding: 45px 25px;

    color: #94a3b8;

    font-size: 12px;
}

.empty-icon {
    width: 50px;
    height: 50px;

    margin: 0 auto 13px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background: #ecfdf5;

    color: #059669;

    font-size: 19px;
}

.empty strong {
    display: block;

    color: #475569;

    margin-bottom: 5px;

    font-size: 13px;
}


/* =========================
   DARK MODE
========================= */

html.dark-mode body,
html.dark-mode .main {
    background: #0f172a;
    color: #e2e8f0;
}

html.dark-mode .sidebar {
    background: #020617;
}

html.dark-mode .header h1,
html.dark-mode .section-heading h2 {
    color: #f8fafc;
}

html.dark-mode .header p {
    color: #94a3b8;
}

html.dark-mode .admin-profile,
html.dark-mode .stat-card,
html.dark-mode .action-card,
html.dark-mode .recent {
    background: #1e293b;

    border-color: #2d3c52;

    box-shadow:
        0 6px 22px rgba(0,0,0,.15);
}

html.dark-mode .admin-avatar,
html.dark-mode .action-icon {
    background: #172554;

    color: #93c5fd;
}

html.dark-mode .admin-profile strong,
html.dark-mode .stat-number,
html.dark-mode .action-card h3,
html.dark-mode .report-info h3 {
    color: #f8fafc;
}

html.dark-mode .admin-profile span {
    color: #60a5fa;
}

html.dark-mode .stat-title {
    color: #cbd5e1;
}

html.dark-mode .stat-card:hover,
html.dark-mode .action-card:hover {
    background: #243247;

    border-color: #3b4c65;
}

html.dark-mode .stat-icon.red {
    background: #4c1723;
    color: #fda4af;
}

html.dark-mode .stat-icon.green {
    background: #073b2c;
    color: #6ee7b7;
}

html.dark-mode .stat-icon.orange {
    background: #4a250c;
    color: #fdba74;
}

html.dark-mode .stat-icon.blue {
    background: #172554;
    color: #93c5fd;
}

html.dark-mode .stat-icon.purple {
    background: #3b1b52;
    color: #d8b4fe;
}

html.dark-mode .stat-icon.cyan {
    background: #083344;
    color: #67e8f9;
}

html.dark-mode .action-card p,
html.dark-mode .report-info p {
    color: #94a3b8;
}

html.dark-mode .report {
    border-color: #334155;
}

html.dark-mode .report:hover {
    background: #243247;
}

html.dark-mode .report-icon.lost {
    background: #4c1723;
    color: #fda4af;
}

html.dark-mode .report-icon.found {
    background: #073b2c;
    color: #6ee7b7;
}

html.dark-mode .badge.lost {
    background: #4c1723;
    color: #fda4af;
}

html.dark-mode .badge.found {
    background: #073b2c;
    color: #6ee7b7;
}

html.dark-mode .empty {
    color: #94a3b8;
}

html.dark-mode .empty-icon {
    background: #073b2c;
    color: #6ee7b7;
}

html.dark-mode .empty strong {
    color: #e2e8f0;
}


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1150px) {

    .stats {
        grid-template-columns:
            repeat(2, 1fr);
    }

}


@media (max-width: 900px) {

    .actions {
        grid-template-columns: 1fr;
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

        padding: 28px 22px 40px;
    }

    .header {
        align-items: flex-start;

        flex-direction: column;
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

    .stats {
        grid-template-columns: 1fr;
    }

    .report {
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


        <a
            href="admin-dashboard.php"
            class="active"
        >

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
     MAIN
========================= -->

<main class="main">


    <!-- =========================
         HEADER
    ========================= -->

    <div class="header">


        <div>

            <span class="page-label">

                <i class="fa-solid fa-shield-halved"></i>

                Admin Dashboard

            </span>


            <h1>

                Welcome Back,
                <?php echo htmlspecialchars($name); ?>!

            </h1>


            <p>

                Manage campus lost and found activities.

            </p>

        </div>


        <!-- ADMIN PROFILE -->

        <div class="admin-profile">


            <div class="admin-avatar">

                <i class="fa-solid fa-user-shield"></i>

            </div>


            <div>

                <strong>

                    <?php
                    echo htmlspecialchars($name);
                    ?>

                </strong>


                <span>

                    <i class="fa-solid fa-shield-halved"></i>

                    Administrator

                </span>

            </div>


        </div>


    </div>



    <!-- =========================
         STATISTICS
    ========================= -->

    <div class="stats">


        <!-- LOST REPORTS -->

        <a
            href="admin-items.php?type=Lost"
            class="stat-card"
        >


            <div class="stat-top">

                <div class="stat-icon red">

                    <i class="fa-solid fa-magnifying-glass"></i>

                </div>

            </div>


            <div class="stat-number">

                <?php echo $lost_count; ?>

            </div>


            <div class="stat-title">

                Lost Reports

            </div>


            <span class="stat-link">

                Manage Lost Reports

                <i class="fa-solid fa-arrow-right"></i>

            </span>


        </a>



        <!-- FOUND REPORTS -->

        <a
            href="admin-items.php?type=Found"
            class="stat-card"
        >


            <div class="stat-top">

                <div class="stat-icon green">

                    <i class="fa-solid fa-box-open"></i>

                </div>

            </div>


            <div class="stat-number">

                <?php echo $found_count; ?>

            </div>


            <div class="stat-title">

                Found Reports

            </div>


            <span class="stat-link">

                Manage Found Reports

                <i class="fa-solid fa-arrow-right"></i>

            </span>


        </a>



        <!-- PENDING APPROVALS -->

        <a
            href="admin-items.php?status=Pending"
            class="stat-card"
        >


            <div class="stat-top">

                <div class="stat-icon orange">

                    <i class="fa-solid fa-clock"></i>

                </div>

            </div>


            <div class="stat-number">

                <?php echo $pending_count; ?>

            </div>


            <div class="stat-title">

                Pending Approvals

            </div>


            <span class="stat-link">

                Review Reports

                <i class="fa-solid fa-arrow-right"></i>

            </span>


        </a>



        <!-- RETURNED ITEMS -->

        <a
            href="admin-items.php?status=Returned"
            class="stat-card"
        >


            <div class="stat-top">

                <div class="stat-icon blue">

                    <i class="fa-solid fa-circle-check"></i>

                </div>

            </div>


            <div class="stat-number">

                <?php echo $returned_count; ?>

            </div>


            <div class="stat-title">

                Returned Items

            </div>


            <span class="stat-link">

                View Returned Items

                <i class="fa-solid fa-arrow-right"></i>

            </span>


        </a>



        <!-- CAMPUS USERS -->

        <a
            href="admin-users.php"
            class="stat-card"
        >


            <div class="stat-top">

                <div class="stat-icon purple">

                    <i class="fa-solid fa-users"></i>

                </div>

            </div>


            <div class="stat-number">

                <?php echo $user_count; ?>

            </div>


            <div class="stat-title">

                Campus Users

            </div>


            <span class="stat-link">

                Manage Users

                <i class="fa-solid fa-arrow-right"></i>

            </span>


        </a>



        <!-- PENDING CLAIMS -->

        <a
            href="admin-claims.php?status=Pending"
            class="stat-card"
        >


            <div class="stat-top">

                <div class="stat-icon cyan">

                    <i class="fa-solid fa-file-circle-check"></i>

                </div>

            </div>


            <div class="stat-number">

                <?php echo $claim_count; ?>

            </div>


            <div class="stat-title">

                Pending Claims

            </div>


            <span class="stat-link">

                Review Claims

                <i class="fa-solid fa-arrow-right"></i>

            </span>


        </a>


    </div>



    <!-- =========================
         QUICK ACTIONS
    ========================= -->

    <div class="section-heading">

        <div>

            <h2>
                Admin Quick Actions
            </h2>

            <p>
                Quickly access common administrative tasks.
            </p>

        </div>

    </div>


    <div class="actions">


        <!-- REVIEW REPORTS -->

        <a
            href="admin-items.php?status=Pending"
            class="action-card"
        >


            <div class="action-icon">

                <i class="fa-solid fa-clipboard-check"></i>

            </div>


            <div>

                <h3>
                    Review Reports
                </h3>

                <p>
                    Approve or reject lost and found reports.
                </p>

            </div>


        </a>



        <!-- REVIEW CLAIMS -->

        <a
            href="admin-claims.php?status=Pending"
            class="action-card"
        >


            <div class="action-icon">

                <i class="fa-solid fa-file-circle-check"></i>

            </div>


            <div>

                <h3>
                    Review Claims
                </h3>

                <p>
                    Check and manage claims submitted by users.
                </p>

            </div>


        </a>



        <!-- MANAGE USERS -->

        <a
            href="admin-users.php"
            class="action-card"
        >


            <div class="action-icon">

                <i class="fa-solid fa-users-gear"></i>

            </div>


            <div>

                <h3>
                    Manage Users
                </h3>

                <p>
                    View registered students and staff.
                </p>

            </div>


        </a>


    </div>



    <!-- =========================
         PENDING REPORTS
    ========================= -->

    <div class="section-heading">

        <div>

            <h2>
                Reports Waiting for Approval
            </h2>

            <p>
                Latest reports that require administrator review.
            </p>

        </div>

    </div>


    <div class="recent">


        <?php if ($recent_result->num_rows > 0): ?>


            <?php while ($item = $recent_result->fetch_assoc()): ?>


                <div class="report">


                    <div class="report-left">


                        <div
                            class="report-icon
                            <?php
                            echo
                                $item["item_type"] === "Lost"
                                ? "lost"
                                : "found";
                            ?>"
                        >

                            <?php if ($item["item_type"] === "Lost"): ?>

                                <i class="fa-solid fa-magnifying-glass"></i>

                            <?php else: ?>

                                <i class="fa-solid fa-box-open"></i>

                            <?php endif; ?>

                        </div>


                        <div class="report-info">


                            <h3>

                                <?php
                                echo htmlspecialchars(
                                    $item["item_name"]
                                );
                                ?>

                            </h3>


                            <p>


                                <span>

                                    <i class="fa-solid fa-location-dot"></i>

                                    <?php
                                    echo htmlspecialchars(
                                        $item["location"]
                                    );
                                    ?>

                                </span>


                                <span>

                                    <i class="fa-solid fa-calendar"></i>

                                    <?php
                                    echo htmlspecialchars(
                                        $item["date_reported"]
                                    );
                                    ?>

                                </span>


                                <span>

                                    <i class="fa-solid fa-tag"></i>

                                    <?php
                                    echo htmlspecialchars(
                                        $item["category"]
                                    );
                                    ?>

                                </span>


                            </p>


                        </div>


                    </div>



                    <?php if (
                        $item["item_type"] === "Lost"
                    ): ?>


                        <span class="badge lost">

                            <i class="fa-solid fa-circle"></i>

                            LOST

                        </span>


                    <?php else: ?>


                        <span class="badge found">

                            <i class="fa-solid fa-circle"></i>

                            FOUND

                        </span>


                    <?php endif; ?>


                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <div class="empty">


                <div class="empty-icon">

                    <i class="fa-solid fa-check"></i>

                </div>


                <strong>
                    All caught up!
                </strong>


                No reports are waiting for approval.


            </div>


        <?php endif; ?>


    </div>


</main>


</body>
</html>