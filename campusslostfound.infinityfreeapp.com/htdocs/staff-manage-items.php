<?php

session_start();
require_once "db.php";
require_once "mail_helper.php";

/* =========================================================
   CHECK LOGIN
   ========================================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION["user_id"]);


/* =========================================================
   CHECK STAFF
   ========================================================= */

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

if ($user_result->num_rows !== 1) {
    die("User not found.");
}

$staff = $user_result->fetch_assoc();

$staff_name = $staff["name"];
$role = strtolower(trim($staff["role"]));

$stmt_user->close();

/* =========================================================
   APPROVE / REJECT ITEM REPORT
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $item_id = intval($_POST["item_id"] ?? 0);
    $action = $_POST["action"] ?? "";

    /* =========================
       APPROVE
       ========================= */

    if ($action === "approve") {

        $sql = "SELECT item_type, item_name, user_id
                FROM items
                WHERE item_id = ?";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("SQL Error: " . $conn->error);
        }

        $stmt->bind_param("i", $item_id);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $item = $result->fetch_assoc();

            $new_status = $item["item_type"];

            $update_sql = "UPDATE items
                           SET status = ?
                           WHERE item_id = ?
                           AND status = 'Pending'";

            $update_stmt = $conn->prepare($update_sql);

            if (!$update_stmt) {
                die("SQL Error: " . $conn->error);
            }

            $update_stmt->bind_param(
                "si",
                $new_status,
                $item_id
            );

            if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {

                campuslost_email_user_by_id(
                    $conn,
                    intval($item["user_id"]),
                    "CampusLost - Item Report Approved",
                    "Your report for \"" . $item["item_name"] . "\" has been approved."
                );

                $notification_message =
                    'Your report for "' .
                    $item["item_name"] .
                    '" has been approved.';

                $notification_stmt = $conn->prepare(
                    "INSERT INTO notifications (user_id, message)
                     VALUES (?, ?)"
                );

                if ($notification_stmt) {

                    $reporter_id = intval($item["user_id"]);

                    $notification_stmt->bind_param(
                        "is",
                        $reporter_id,
                        $notification_message
                    );

                    $notification_stmt->execute();
                    $notification_stmt->close();
                }
            }

            $update_stmt->close();
        }

        $stmt->close();
    }


    /* =========================
       REJECT
       ========================= */

    elseif ($action === "reject") {

        $sql = "SELECT item_name, user_id
                FROM items
                WHERE item_id = ?";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("SQL Error: " . $conn->error);
        }

        $stmt->bind_param("i", $item_id);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $item = $result->fetch_assoc();

            $update_sql = "UPDATE items
                           SET status = 'Rejected'
                           WHERE item_id = ?
                           AND status = 'Pending'";

            $update_stmt = $conn->prepare($update_sql);

            if (!$update_stmt) {
                die("SQL Error: " . $conn->error);
            }

            $update_stmt->bind_param("i", $item_id);

            if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {

                campuslost_email_user_by_id(
                    $conn,
                    intval($item["user_id"]),
                    "CampusLost - Item Report Rejected",
                    "Your report for \"" . $item["item_name"] . "\" has been rejected."
                );
                
                $notification_message =
    'Your report for "' .
    $item["item_name"] .
    '" has been rejected.';

$notification_stmt = $conn->prepare(
    "INSERT INTO notifications (user_id, message)
     VALUES (?, ?)"
);

if ($notification_stmt) {

    $reporter_id = intval($item["user_id"]);

    $notification_stmt->bind_param(
        "is",
        $reporter_id,
        $notification_message
    );

    $notification_stmt->execute();
    $notification_stmt->close();
}
            }

            $update_stmt->close();
        }

        $stmt->close();
    }


    /* Refresh page */

    header("Location: staff-manage-items.php?status=Pending");
    exit();
}


/* =========================================================
   FILTERS
   ========================================================= */

$type_filter = trim($_GET["type"] ?? "");
$status_filter = trim($_GET["status"] ?? "");
$search = trim($_GET["search"] ?? "");


/* =========================================================
   GET ITEMS
   ========================================================= */

$sql = "SELECT
            item_id,
            item_name,
            category,
            location,
            date_reported,
            item_type,
            status
        FROM items
        WHERE 1=1";
$params = [];
$types = "";


/* TYPE FILTER */

if ($type_filter === "Lost" || $type_filter === "Found") {

    $sql .= " AND item_type = ?";

    $params[] = $type_filter;
    $types .= "s";
}


/* STATUS FILTER */

$allowed_statuses = [
    "Pending",
    "Lost",
    "Found",
    "Matched",
    "Resolved",
    "In Custody",
    "Returned",
    "Rejected"
];

if (in_array($status_filter, $allowed_statuses, true)) {

    $sql .= " AND status = ?";

    $params[] = $status_filter;
    $types .= "s";
}


/* SEARCH */

if ($search !== "") {

    $sql .= " AND (
                item_name LIKE ?
                OR category LIKE ?
                OR location LIKE ?
              )";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $types .= "sss";
}


$sql .= " ORDER BY item_id DESC";


$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}


/* BIND FILTER VALUES */

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<script src="theme.js?v=4"></script>

<title>Manage Items - CampusLost</title>

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
            transition: 0.3s ease;
        }
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
.main {
    min-height: 100vh;

    margin-left: 260px;

    padding: 35px 42px 55px;
}

.container {
    width: 100%;
    max-width: 1200px;

    margin: 0 auto;
}

.page-header {
    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 25px;

    margin-bottom: 25px;
}

.page-label {
    display: inline-flex;
    align-items: center;

    gap: 7px;

    margin-bottom: 8px;

    color: #2563eb;

    font-size: 11px;
    font-weight: 800;

    letter-spacing: 1px;
    text-transform: uppercase;
}

.page-header h1 {
    margin-bottom: 7px;

    color: #102342;

    font-size: 28px;
}

.page-header p {
    color: #748199;

    font-size: 13px;
}

.staff-profile {
    min-width: 190px;

    display: flex;

    align-items: center;

    gap: 11px;

    padding: 13px 17px;

    border:
        1px solid #e8edf5;

    border-radius: 14px;

    background: white;

    box-shadow:
        0 5px 18px
        rgba(15,39,71,.05);
}

.staff-avatar {
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

.staff-profile strong {
    display: block;

    max-width: 160px;

    margin-bottom: 4px;

    color: #102342;

    font-size: 13px;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}

.staff-profile span {
    display: flex;

    align-items: center;

    gap: 5px;

    color: #2563eb;

    font-size: 11px;
}


.filter-card {
    padding: 20px;

    margin-bottom: 20px;

    border:
        1px solid #e8edf5;

    border-radius: 16px;

    background: white;

    box-shadow:
        0 5px 18px
        rgba(15,39,71,.04);
}

.filter-form {
    display: grid;

    grid-template-columns:
        2fr 1fr 1fr auto;

    gap: 12px;

    align-items: end;
}

.form-group label {
    display: block;

    margin-bottom: 7px;

    color: #64748b;

    font-size: 10px;
    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .5px;
}

.form-group input,
.form-group select {
    width: 100%;

    height: 41px;

    padding: 0 12px;

    border:
        1px solid #dce3ec;

    border-radius: 9px;

    outline: none;

    background: #fff;

    color: #24344d;

    font-size: 12px;
}

.form-group input:focus,
.form-group select:focus {
    border-color: #2563eb;

    box-shadow:
        0 0 0 3px
        rgba(37,99,235,.08);
}

.filter-btn {
    height: 41px;

    padding: 0 17px;

    border: none;

    border-radius: 9px;

    background: #2563eb;

    color: white;

    font-weight: 700;

    cursor: pointer;
}

.filter-btn:hover {
    background: #1d4ed8;
}

.table-card {
    overflow: hidden;

    border:
        1px solid #e8edf5;

    border-radius: 16px;

    background: white;

    box-shadow:
        0 6px 20px
        rgba(15,39,71,.05);
}

.table-header {
    padding: 18px 20px;

    border-bottom:
        1px solid #edf1f5;

    display: flex;

    align-items: center;
    justify-content: space-between;
}

.table-header h2 {
    color: #102342;

    font-size: 15px;
}

.item-count {
    padding: 5px 9px;

    border-radius: 20px;

    background: #eff6ff;

    color: #2563eb;

    font-size: 10px;
    font-weight: 800;
}

.table-wrapper {
    width: 100%;

    overflow-x: auto;
}

table {
    width: 100%;

    border-collapse: collapse;
}

th {
    padding: 13px 15px;

    background: #f8fafc;

    color: #64748b;

    font-size: 10px;
    font-weight: 800;

    text-align: left;

    text-transform: uppercase;

    letter-spacing: .4px;
}

td {
    padding: 15px;

    border-top:
        1px solid #edf1f5;

    color: #334155;

    font-size: 12px;

    vertical-align: middle;
}

.item-name {
    color: #102342;

    font-weight: 800;
}

.item-id {
    margin-top: 4px;

    color: #94a3b8;

    font-size: 9px;
}


/* =========================================================
   BADGES
   ========================================================= */

.badge {
    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 5px 9px;

    border-radius: 20px;

    font-size: 9px;
    font-weight: 800;

    white-space: nowrap;
}

.type-lost {
    background: #fff1f2;
    color: #be123c;
}

.type-found {
    background: #ecfdf5;
    color: #15803d;
}

.status-lost {
    background: #fff7ed;
    color: #c2410c;
}

.status-found {
    background: #eff6ff;
    color: #2563eb;
}

.status-matched {
    background: #f5f3ff;
    color: #7c3aed;
}

.status-resolved {
    background: #ecfdf5;
    color: #15803d;
}

.status-in-custody {
    background: #fefce8;
    color: #a16207;
}

.status-returned {
    background: #ecfdf5;
    color: #15803d;
}


/* =========================================================
   VIEW BUTTON
   ========================================================= */

.view-btn {
    display: inline-flex;

    align-items: center;
    justify-content: center;

    gap: 6px;

    padding: 8px 11px;

    border:
        1px solid #bfdbfe;

    border-radius: 8px;

    background: #eff6ff;

    color: #2563eb;

    text-decoration: none;

    font-size: 10px;
    font-weight: 800;

    transition: .2s;
}

.action-buttons {
    display: flex;
    align-items: center;
    gap: 7px;
    flex-wrap: wrap;
}

.action-buttons form {
    margin: 0;
}

.approve-btn,
.reject-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;

    padding: 8px 11px;

    border: none;
    border-radius: 8px;

    font-size: 10px;
    font-weight: 800;

    cursor: pointer;
    transition: .2s;
}

.approve-btn {
    background: #ecfdf5;
    color: #15803d;
}

.approve-btn:hover {
    background: #15803d;
    color: white;
}

.reject-btn {
    background: #fff1f2;
    color: #be123c;
}

.reject-btn:hover {
    background: #be123c;
    color: white;
}


/* =========================================================
   EMPTY
   ========================================================= */

.empty {
    padding: 45px 20px;

    text-align: center;

    color: #94a3b8;
}

.empty i {
    display: block;

    margin-bottom: 12px;

    color: #bfdbfe;

    font-size: 38px;
}

.empty strong {
    display: block;

    margin-bottom: 5px;

    color: #64748b;

    font-size: 13px;
}

.empty span {
    font-size: 11px;
}


/* =========================================================
   DARK MODE
   ========================================================= */

html.dark-mode body,
html.dark-mode .main {
    background: #0f172a;
    color: #e2e8f0;
}

html.dark-mode .sidebar {
    background: #020617;
}

html.dark-mode .page-header h1,
html.dark-mode .table-header h2,
html.dark-mode .item-name {
    color: #f8fafc;
}

html.dark-mode .page-header p {
    color: #94a3b8;
}

html.dark-mode .staff-profile,
html.dark-mode .filter-card,
html.dark-mode .table-card {
    border-color: #2d3c52;

    background: #1e293b;
}

html.dark-mode .staff-profile strong {
    color: #f8fafc;
}

html.dark-mode .staff-avatar {
    background: #172554;
    color: #93c5fd;
}

html.dark-mode .form-group label {
    color: #94a3b8;
}

html.dark-mode .form-group input,
html.dark-mode .form-group select {
    border-color: #334155;

    background: #172033;

    color: #e2e8f0;
}

html.dark-mode .table-header {
    border-color: #334155;
}

html.dark-mode th {
    background: #172033;

    color: #94a3b8;
}

html.dark-mode td {
    border-color: #334155;

    color: #cbd5e1;
}

html.dark-mode .empty strong {
    color: #cbd5e1;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 900px) {

    .filter-form {
        grid-template-columns: 1fr 1fr;
    }

}

@media (max-width: 800px) {

    .sidebar {
        width: 260px;
    }

    .brand {
        padding: 4px 14px 25px;
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
        font-size: 19px;
    }

    .brand-name {
        font-size: 22px;
        font-weight: 800;
        letter-spacing: -.5px;
    }

    .brand-subtitle {
        display: block;
        margin-top: 6px;
        margin-left: 48px;
        font-size: 11px;
        color: #94a3b8;
        letter-spacing: .4px;
    }

    .menu-label {
        font-size: 10px;
        padding: 0 14px;
        margin: 10px 0 8px;
    }

    .menu a {
        gap: 13px;
        padding: 12px 14px;
        margin-bottom: 5px;
        font-size: 14px;
    }

    .main {
        margin-left: 260px;
        padding: 28px 22px 40px;
    }

    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .staff-profile {
        width: 100%;
    }

}

@media (max-width: 650px) {

    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    .brand-name {
        font-size: 22px;
    }

    .brand-subtitle {
        display: block;
        margin-top: 6px;
        margin-left: 48px;
        font-size: 11px;
    }

    .menu {
        overflow: visible;
    }

    .main {
        margin-left: 0;
        padding: 25px 16px;
    }

    .filter-form {
        grid-template-columns: 1fr;
    }

}

</style>

</head>

<body>


<!-- =========================================================
     SIDEBAR
     ========================================================= -->

<aside class="sidebar">
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
        LOST &amp; FOUND SYSTEM
    </div>

</div>
        </div>
    </div>
    
    <nav class="menu">

        <div class="menu-label">
            Main
        </div>

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


        <div class="menu-label">
            Staff Management
        </div>

        <a
            href="staff-manage-items.php"
            class="active"
        >
            <i class="fa-solid fa-boxes-stacked"></i>
            Manage Items
        </a>

        <a href="staff-claims.php">
            <i class="fa-solid fa-file-circle-check"></i>
            Claims
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


    <div class="logout">

        <a href="logout.php">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>

    </div>

</aside>


<!-- =========================================================
     MAIN
     ========================================================= -->

<main class="main">

    <div class="container">


        <!-- PAGE HEADER -->

        <div class="page-header">

            <div>

                <span class="page-label">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    Staff Management
                </span>

                <h1>
                    Manage Items
                </h1>

                <p>
                    View and manage approved lost and found items.
                </p>

            </div>


            <div class="staff-profile">

                <div class="staff-avatar">
                    <i class="fa-solid fa-user-gear"></i>
                </div>

                <div>

                    <strong>
                        <?php
                        echo htmlspecialchars($staff_name);
                        ?>
                    </strong>

                    <span>
                        <i class="fa-solid fa-user-gear"></i>
                        Staff
                    </span>

                </div>

            </div>

        </div>


        <!-- =================================================
             FILTER
             ================================================= -->

        <div class="filter-card">

            <form
                method="GET"
                action="staff-manage-items.php"
                class="filter-form"
            >

                <div class="form-group">

                    <label>
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        placeholder="Search item, category or location..."
                        value="<?php
                        echo htmlspecialchars($search);
                        ?>"
                    >

                </div>


                <div class="form-group">

                    <label>
                        Item Type
                    </label>

                    <select name="type">

                        <option value="">
                            All Types
                        </option>

                        <option
                            value="Lost"
                            <?php
                            echo $type_filter === "Lost"
                                ? "selected"
                                : "";
                            ?>
                        >
                            Lost
                        </option>

                        <option
                            value="Found"
                            <?php
                            echo $type_filter === "Found"
                                ? "selected"
                                : "";
                            ?>
                        >
                            Found
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Status
                    </label>

                    <select name="status">

                        <option value="">
                            All Statuses
                        </option>

                        <?php

                        foreach ($allowed_statuses as $status):

                        ?>

                            <option
                                value="<?php
                                echo htmlspecialchars($status);
                                ?>"
                                <?php
                                echo $status_filter === $status
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                <?php
                                echo htmlspecialchars($status);
                                ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <button
                    type="submit"
                    class="filter-btn"
                >
                    <i class="fa-solid fa-filter"></i>
                    Filter
                </button>

            </form>

        </div>


        <!-- =================================================
             ITEMS TABLE
             ================================================= -->

        <div class="table-card">

            <div class="table-header">

                <h2>
                    Items
                </h2>

                <span class="item-count">

                    <?php
                    echo intval($result->num_rows);
                    ?>

                    item<?php
                    echo $result->num_rows === 1
                        ? ""
                        : "s";
                    ?>

                </span>

            </div>


            <?php if ($result->num_rows > 0): ?>

                <div class="table-wrapper">

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Item
                                </th>

                                <th>
                                    Type
                                </th>

                                <th>
                                    Category
                                </th>

                                <th>
                                    Location
                                </th>

                                <th>
                                    Date
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php while ($item = $result->fetch_assoc()): ?>

                            <?php

                            $item_type =
                                strtolower(
                                    trim($item["item_type"])
                                );

                            $item_status =
                                strtolower(
                                    trim($item["status"])
                                );

                            $status_class =
                                "status-" .
                                str_replace(
                                    " ",
                                    "-",
                                    $item_status
                                );

                            ?>


                            <tr>

                                <td>

                                    <div class="item-name">

                                        <?php
                                        echo htmlspecialchars(
                                            $item["item_name"]
                                        );
                                        ?>

                                    </div>

                                    <div class="item-id">

                                        #<?php
                                        echo intval(
                                            $item["item_id"]
                                        );
                                        ?>

                                    </div>

                                </td>


                                <td>

                                    <span
                                        class="badge <?php
                                        echo $item_type === "found"
                                            ? "type-found"
                                            : "type-lost";
                                        ?>"
                                    >

                                        <?php if ($item_type === "found"): ?>

                                            <i class="fa-solid fa-box-open"></i>

                                        <?php else: ?>

                                            <i class="fa-solid fa-magnifying-glass"></i>

                                        <?php endif; ?>


                                        <?php
                                        echo htmlspecialchars(
                                            $item["item_type"]
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $item["category"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $item["location"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $item["date_reported"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <span
                                        class="badge <?php
                                        echo htmlspecialchars(
                                            $status_class
                                        );
                                        ?>"
                                    >

                                        <i class="fa-solid fa-circle"></i>

                                        <?php
                                        echo htmlspecialchars(
                                            $item["status"]
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

    <div class="action-buttons">

        <!-- VIEW DETAILS -->
        <a
            href="staff-item-details.php?id=<?php
            echo intval($item["item_id"]);
            ?>"
            class="view-btn"
        >
            <i class="fa-regular fa-eye"></i>
            View Details
        </a>


        <!-- APPROVE / REJECT ONLY FOR PENDING REPORT -->
        <?php if ($item["status"] === "Pending"): ?>

            <form method="POST">

                <input
                    type="hidden"
                    name="item_id"
                    value="<?php echo intval($item["item_id"]); ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="approve"
                >

                <button
                    type="submit"
                    class="approve-btn"
                >
                    <i class="fa-solid fa-check"></i>
                    Approve
                </button>

            </form>


            <form method="POST">

                <input
                    type="hidden"
                    name="item_id"
                    value="<?php echo intval($item["item_id"]); ?>"
                >

                <input
                    type="hidden"
                    name="action"
                    value="reject"
                >

                <button
                    type="submit"
                    class="reject-btn"
                >
                    <i class="fa-solid fa-xmark"></i>
                    Reject
                </button>

            </form>

        <?php endif; ?>

    </div>

</td>

                            </tr>

                        <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <div class="empty">

                    <i class="fa-regular fa-folder-open"></i>

                    <strong>
                        No items found
                    </strong>

                    <span>
                        Try changing the search or filter.
                    </span>

                </div>


            <?php endif; ?>

        </div>


    </div>

</main>

</body>

</html>

<?php

$stmt->close();

?>