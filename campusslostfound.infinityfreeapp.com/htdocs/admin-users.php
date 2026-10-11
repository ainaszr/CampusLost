<?php
session_start();
require_once "db.php";

/* =========================================================
   ADMIN ACCESS CHECK
   ========================================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

if (
    !isset($_SESSION["role"]) ||
    strtolower($_SESSION["role"]) !== "admin"
) {
    header("Location: dashboard.php");
    exit();
}


/* =========================================================
   DELETE USER
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (isset($_POST["delete_user"])) {

        $user_id = isset($_POST["user_id"])
            ? intval($_POST["user_id"])
            : 0;

        /* Prevent admin from deleting own account */
        if ($user_id == $_SESSION["user_id"]) {
            header("Location: admin-users.php?error=self");
            exit();
        }

        if ($user_id > 0) {

            /* Delete related claims */
            $stmt = $conn->prepare(
                "DELETE FROM claims WHERE user_id = ?"
            );

            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            }


            /* Delete related notifications */
            $stmt = $conn->prepare(
                "DELETE FROM notifications WHERE user_id = ?"
            );

            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            }


            /* Delete user - admin accounts excluded */
            $stmt = $conn->prepare(
                "DELETE FROM users
                 WHERE user_id = ?
                 AND LOWER(role) != 'admin'"
            );

            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        header("Location: admin-users.php?deleted=1");
        exit();
    }
}


/* =========================================================
   GET MESSAGE
   ========================================================= */

$message = "";
$message_type = "";

if (isset($_GET["deleted"])) {
    $message = "User deleted successfully.";
    $message_type = "success";
}

if (
    isset($_GET["error"]) &&
    $_GET["error"] === "self"
) {
    $message = "You cannot delete your own admin account.";
    $message_type = "error";
}


/* =========================================================
   GET ADMIN INFORMATION
   ========================================================= */

$admin_name = $_SESSION["name"] ?? "Administrator";

$admin_sql = "SELECT name
              FROM users
              WHERE user_id = ?";

$admin_stmt = $conn->prepare($admin_sql);

if ($admin_stmt) {

    $admin_stmt->bind_param(
        "i",
        $_SESSION["user_id"]
    );

    $admin_stmt->execute();

    $admin_result =
        $admin_stmt->get_result();

    if ($admin_result->num_rows === 1) {

        $admin_data =
            $admin_result->fetch_assoc();

        $admin_name =
            $admin_data["name"];
    }

    $admin_stmt->close();
}


/* =========================================================
   GET ALL USERS - EXCLUDE ADMIN
   ========================================================= */

$sql = "SELECT
            user_id,
            name,
            email,
            role
        FROM users
        WHERE LOWER(role) != 'admin'
        ORDER BY user_id DESC";

$result = $conn->query($sql);

$total_users =
    ($result)
        ? $result->num_rows
        : 0;

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

<title>Manage Users - CampusLost</title>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
>

<style>

/* =========================================================
   RESET
   ========================================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    min-height: 100vh;
    font-family: Arial, sans-serif;
    background: #f5f7fb;
    color: #172033;
    transition: background .3s ease, color .3s ease;
}


/* =========================================================
   SIDEBAR
   ========================================================= */

.sidebar {
    position: fixed;
    top: 0;
    left: 0;

    width: 260px;
    height: 100vh;

    padding: 28px 14px 20px;

    display: flex;
    flex-direction: column;

    background: #071a38;
    color: white;

    box-shadow:
        5px 0 25px rgba(0,0,0,.08);

    z-index: 1000;
}


/* =========================================================
   BRAND
   ========================================================= */

.brand {
    padding: 4px 14px 25px;

    border-bottom:
        1px solid rgba(255,255,255,.09);

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

    color: #94a3b8;

    font-size: 11px;
    letter-spacing: .4px;
}


/* =========================================================
   MENU
   ========================================================= */

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
    padding: 0 14px;

    margin: 10px 0 8px;

    color: #64748b;

    font-size: 10px;
    font-weight: bold;

    text-transform: uppercase;
    letter-spacing: 1px;
}

.menu a {
    display: flex;
    align-items: center;

    gap: 13px;

    padding: 12px 14px;
    margin-bottom: 5px;

    border-radius: 10px;

    color: #cbd5e1;

    text-decoration: none;

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
    background:
        linear-gradient(
            90deg,
            #2563eb,
            #3478f6
        );

    color: white;

    box-shadow:
        0 7px 18px rgba(37,99,235,.25);
}


/* =========================================================
   LOGOUT
   ========================================================= */

.logout {
    padding-top: 14px;

    border-top:
        1px solid rgba(255,255,255,.09);
}

.logout a {
    display: flex;

    align-items: center;
    justify-content: center;

    gap: 9px;

    padding: 12px;

    border-radius: 10px;

    background: rgba(239,68,68,.14);
    color: #fca5a5;

    text-decoration: none;

    font-size: 14px;
    font-weight: 600;

    transition: .2s;
}

.logout a:hover {
    background: #dc2626;
    color: white;
}


/* =========================================================
   MAIN
   ========================================================= */

.main {
    min-height: 100vh;

    margin-left: 260px;

    padding: 35px 42px 55px;
}


/* =========================================================
   HEADER
   ========================================================= */

.header {
    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 25px;

    margin-bottom: 28px;
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

.header h1 {
    margin-bottom: 7px;

    color: #102342;

    font-size: 28px;
}

.header p {
    color: #748199;
    font-size: 14px;
}


/* =========================================================
   ADMIN PROFILE
   ========================================================= */

.admin-profile {
    min-width: 190px;

    display: flex;
    align-items: center;

    gap: 11px;

    padding: 13px 17px;

    border: 1px solid #e8edf5;
    border-radius: 14px;

    background: white;

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

    max-width: 160px;

    margin-bottom: 4px;

    color: #102342;

    font-size: 13px;

    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.admin-profile span {
    display: flex;

    align-items: center;

    gap: 5px;

    color: #2563eb;

    font-size: 11px;
}


/* =========================================================
   MESSAGE
   ========================================================= */

.message {
    display: flex;
    align-items: center;

    gap: 9px;

    padding: 13px 16px;

    margin-bottom: 20px;

    border-radius: 11px;

    font-size: 12px;
    font-weight: 700;
}

.message.success {
    border: 1px solid #bbf7d0;

    background: #f0fdf4;
    color: #15803d;
}

.message.error {
    border: 1px solid #fecaca;

    background: #fef2f2;
    color: #b91c1c;
}


/* =========================================================
   TOOLBAR
   ========================================================= */

.toolbar {
    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 15px;

    padding: 18px;

    margin-bottom: 22px;

    border: 1px solid #e8edf5;
    border-radius: 16px;

    background: white;

    box-shadow:
        0 6px 22px rgba(15,39,71,.04);
}

.search-area {
    position: relative;

    flex: 1;

    max-width: 560px;
}

.search-area i {
    position: absolute;

    top: 50%;
    left: 15px;

    transform: translateY(-50%);

    color: #94a3b8;

    font-size: 13px;

    pointer-events: none;
}

.search-area input {
    width: 100%;

    padding: 11px 15px 11px 40px;

    border: 1px solid #dce3ec;
    border-radius: 10px;

    outline: none;

    background: #f8fafc;
    color: #334155;

    font-size: 12px;

    transition: .2s;
}

.search-area input::placeholder {
    color: #94a3b8;
}

.search-area input:focus {
    border-color: #60a5fa;

    background: white;

    box-shadow:
        0 0 0 3px rgba(37,99,235,.08);
}


/* =========================================================
   USER COUNT
   ========================================================= */

.user-count {
    display: flex;

    align-items: center;

    gap: 10px;

    flex-shrink: 0;

    padding: 9px 13px;

    border-radius: 10px;

    background: #eff6ff;
}

.user-count-icon {
    width: 31px;
    height: 31px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 9px;

    background: white;
    color: #2563eb;

    font-size: 12px;
}

.user-count strong {
    display: block;

    color: #1e40af;

    font-size: 13px;
}

.user-count span {
    display: block;

    margin-top: 2px;

    color: #64748b;

    font-size: 9px;

    text-transform: uppercase;
    letter-spacing: .4px;
}


/* =========================================================
   SECTION HEADER
   ========================================================= */

.section-heading {
    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 15px;

    margin-bottom: 14px;
}

.section-heading h2 {
    color: #102342;
    font-size: 18px;
}

.section-heading p {
    margin-top: 4px;

    color: #94a3b8;

    font-size: 11px;
}


/* =========================================================
   TABLE CONTAINER
   ========================================================= */

.table-container {
    overflow: hidden;

    border: 1px solid #e8edf5;
    border-radius: 16px;

    background: white;

    box-shadow:
        0 6px 22px rgba(15,39,71,.05);
}

.table-scroll {
    overflow-x: auto;
}

table {
    width: 100%;

    border-collapse: collapse;
}

thead {
    background: #f8fafc;
}

th {
    padding: 14px 18px;

    border-bottom: 1px solid #e8edf5;

    color: #64748b;

    text-align: left;

    font-size: 10px;
    font-weight: 800;

    text-transform: uppercase;
    letter-spacing: .5px;
}

td {
    padding: 15px 18px;

    border-bottom: 1px solid #edf1f5;

    color: #475569;

    font-size: 12px;
}

tbody tr {
    transition: background .2s;
}

tbody tr:hover {
    background: #f8fbff;
}

tbody tr:last-child td {
    border-bottom: none;
}


/* =========================================================
   USER ID
   ========================================================= */

.user-id {
    display: inline-flex;

    align-items: center;

    gap: 5px;

    color: #64748b;

    font-size: 11px;
    font-weight: 700;
}

.user-id i {
    color: #94a3b8;
}


/* =========================================================
   USER INFO
   ========================================================= */

.user-info {
    display: flex;

    align-items: center;

    gap: 10px;
}

.user-avatar {
    width: 35px;
    height: 35px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 10px;

    background: #eff6ff;
    color: #2563eb;

    font-size: 12px;
}

.user-name {
    color: #172033;

    font-size: 12px;
    font-weight: 700;
}

.email-cell {
    display: flex;

    align-items: center;

    gap: 7px;

    color: #64748b;
}

.email-cell i {
    color: #94a3b8;
}


/* =========================================================
   ROLE BADGE
   ========================================================= */

.role-badge {
    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 6px 10px;

    border-radius: 20px;

    background: #eff6ff;
    color: #2563eb;

    font-size: 9px;
    font-weight: 800;

    text-transform: uppercase;
    letter-spacing: .3px;
}


/* =========================================================
   DELETE BUTTON
   ========================================================= */

.delete-form {
    margin: 0;
}

.delete-btn {
    display: inline-flex;

    align-items: center;
    justify-content: center;

    gap: 6px;

    padding: 8px 11px;

    border: 1px solid #fecdd3;
    border-radius: 8px;

    background: #fff1f2;
    color: #be123c;

    font-size: 10px;
    font-weight: 800;

    cursor: pointer;

    transition: .2s;
}

.delete-btn:hover {
    border-color: #e11d48;

    background: #e11d48;
    color: white;
}


/* =========================================================
   EMPTY
   ========================================================= */

.empty {
    min-height: 330px;

    display: flex;

    flex-direction: column;

    align-items: center;
    justify-content: center;

    padding: 45px;

    text-align: center;
}

.empty-icon {
    width: 58px;
    height: 58px;

    display: flex;

    align-items: center;
    justify-content: center;

    margin-bottom: 15px;

    border-radius: 50%;

    background: #eff6ff;
    color: #2563eb;

    font-size: 20px;
}

.empty h3 {
    margin-bottom: 7px;

    color: #102342;

    font-size: 17px;
}

.empty p {
    color: #8290a5;

    font-size: 12px;
}


/* =========================================================
   NO SEARCH RESULTS
   ========================================================= */

.no-search-results {
    display: none;

    padding: 45px 20px;

    text-align: center;

    color: #94a3b8;

    font-size: 12px;
}

.no-search-results i {
    display: block;

    margin-bottom: 10px;

    color: #cbd5e1;

    font-size: 25px;
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

html.dark-mode .header h1,
html.dark-mode .section-heading h2 {
    color: #f8fafc;
}

html.dark-mode .header p,
html.dark-mode .section-heading p {
    color: #94a3b8;
}

html.dark-mode .admin-profile,
html.dark-mode .toolbar,
html.dark-mode .table-container {
    border-color: #2d3c52;

    background: #1e293b;

    box-shadow:
        0 6px 22px rgba(0,0,0,.15);
}

html.dark-mode .admin-avatar {
    background: #172554;
    color: #93c5fd;
}

html.dark-mode .admin-profile strong {
    color: #f8fafc;
}

html.dark-mode .admin-profile span {
    color: #60a5fa;
}

html.dark-mode .search-area input {
    border-color: #334155;

    background: #0f172a;
    color: #f8fafc;
}

html.dark-mode .search-area input:focus {
    border-color: #3b82f6;

    background: #111c2e;
}

html.dark-mode .user-count {
    background: #172554;
}

html.dark-mode .user-count-icon {
    background: #1e3a8a;
    color: #bfdbfe;
}

html.dark-mode .user-count strong {
    color: #bfdbfe;
}

html.dark-mode .user-count span {
    color: #94a3b8;
}

html.dark-mode thead {
    background: #172033;
}

html.dark-mode th {
    border-bottom-color: #334155;
    color: #94a3b8;
}

html.dark-mode td {
    border-bottom-color: #334155;
    color: #cbd5e1;
}

html.dark-mode tbody tr:hover {
    background: #243247;
}

html.dark-mode .user-avatar {
    background: #172554;
    color: #93c5fd;
}

html.dark-mode .user-name {
    color: #f1f5f9;
}

html.dark-mode .email-cell {
    color: #cbd5e1;
}

html.dark-mode .role-badge {
    background: #172554;
    color: #93c5fd;
}

html.dark-mode .delete-btn {
    border-color: #63202e;

    background: #4c1723;
    color: #fda4af;
}

html.dark-mode .delete-btn:hover {
    border-color: #e11d48;

    background: #e11d48;
    color: white;
}

html.dark-mode .message.success {
    border-color: #166534;

    background: #14532d;
    color: #bbf7d0;
}

html.dark-mode .message.error {
    border-color: #991b1b;

    background: #7f1d1d;
    color: #fecaca;
}

html.dark-mode .empty h3 {
    color: #f8fafc;
}

html.dark-mode .empty p,
html.dark-mode .no-search-results {
    color: #94a3b8;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 1000px) {

    .toolbar {
        align-items: stretch;
        flex-direction: column;
    }

    .search-area {
        max-width: none;
    }

    .user-count {
        align-self: flex-start;
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

    table {
        min-width: 760px;
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

    .admin-profile {
        width: 100%;
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

            LOST & FOUND SYSTEM

        </div>


    </div>



    <nav class="menu">


        <div class="menu-label">

            Main

        </div>


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


        <a
            href="admin-users.php"
            class="active"
        >

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


    <!-- HEADER -->

    <div class="header">


        <div>


            <span class="page-label">

                <i class="fa-solid fa-users-gear"></i>

                User Management

            </span>


            <h1>

                Manage Users

            </h1>


            <p>

                View and manage registered students and staff.

            </p>


        </div>



        <div class="admin-profile">


            <div class="admin-avatar">

                <i class="fa-solid fa-user-shield"></i>

            </div>


            <div>


                <strong>

                    <?php
                    echo htmlspecialchars(
                        $admin_name
                    );
                    ?>

                </strong>


                <span>

                    <i class="fa-solid fa-shield-halved"></i>

                    Administrator

                </span>


            </div>


        </div>


    </div>



    <!-- MESSAGE -->

    <?php if (!empty($message)): ?>


        <div class="message <?php echo $message_type; ?>">


            <?php if ($message_type === "success"): ?>

                <i class="fa-solid fa-circle-check"></i>

            <?php else: ?>

                <i class="fa-solid fa-circle-exclamation"></i>

            <?php endif; ?>


            <?php
            echo htmlspecialchars(
                $message
            );
            ?>


        </div>


    <?php endif; ?>



    <!-- =====================================================
         SEARCH / COUNT
         ===================================================== -->

    <div class="toolbar">


        <div class="search-area">


            <i class="fa-solid fa-magnifying-glass"></i>


            <input
                type="text"
                id="searchInput"
                placeholder="Search by name, email or role..."
                onkeyup="searchUsers()"
            >


        </div>



        <div class="user-count">


            <div class="user-count-icon">

                <i class="fa-solid fa-users"></i>

            </div>


            <div>


                <strong id="visibleCount">

                    <?php echo intval($total_users); ?>

                </strong>


                <span>

                    Registered Users

                </span>


            </div>


        </div>


    </div>



    <!-- =====================================================
         USER LIST
         ===================================================== -->

    <div class="section-heading">


        <div>


            <h2>

                Campus Users

            </h2>


            <p>

                Registered non-admin accounts in CampusLost.

            </p>


        </div>


    </div>



    <div class="table-container">


        <?php if (
            $result &&
            $result->num_rows > 0
        ): ?>


            <div class="table-scroll">


                <table id="usersTable">


                    <thead>


                        <tr>

                            <th>User ID</th>

                            <th>User</th>

                            <th>Email</th>

                            <th>Role</th>

                            <th>Action</th>

                        </tr>


                    </thead>



                    <tbody>


                    <?php while (
                        $user =
                        $result->fetch_assoc()
                    ): ?>


                        <tr class="user-row">


                            <td>


                                <span class="user-id">

                                    <i class="fa-solid fa-hashtag"></i>

                                    <?php
                                    echo intval(
                                        $user["user_id"]
                                    );
                                    ?>

                                </span>


                            </td>



                            <td>


                                <div class="user-info">


                                    <div class="user-avatar">

                                        <i class="fa-solid fa-user"></i>

                                    </div>


                                    <div class="user-name">

                                        <?php
                                        echo htmlspecialchars(
                                            $user["name"]
                                        );
                                        ?>

                                    </div>


                                </div>


                            </td>



                            <td>


                                <div class="email-cell">

                                    <i class="fa-regular fa-envelope"></i>

                                    <span>

                                        <?php
                                        echo htmlspecialchars(
                                            $user["email"]
                                        );
                                        ?>

                                    </span>

                                </div>


                            </td>



                            <td>


                                <span class="role-badge">

                                    <i class="fa-solid fa-id-badge"></i>

                                    <?php
                                    echo htmlspecialchars(
                                        $user["role"]
                                    );
                                    ?>

                                </span>


                            </td>



                            <td>


                                <form
                                    method="POST"
                                    class="delete-form"
                                    onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');"
                                >


                                    <input
                                        type="hidden"
                                        name="user_id"
                                        value="<?php
                                        echo intval(
                                            $user["user_id"]
                                        );
                                        ?>"
                                    >


                                    <button
                                        type="submit"
                                        name="delete_user"
                                        class="delete-btn"
                                    >

                                        <i class="fa-regular fa-trash-can"></i>

                                        Delete

                                    </button>


                                </form>


                            </td>


                        </tr>


                    <?php endwhile; ?>


                    </tbody>


                </table>


            </div>



            <div
                class="no-search-results"
                id="noSearchResults"
            >

                <i class="fa-solid fa-magnifying-glass"></i>

                No users match your search.

            </div>


        <?php else: ?>


            <div class="empty">


                <div class="empty-icon">

                    <i class="fa-solid fa-users"></i>

                </div>


                <h3>

                    No Users Found

                </h3>


                <p>

                    There are currently no registered students or staff.

                </p>


            </div>


        <?php endif; ?>


    </div>


</main>



<!-- =========================================================
     SEARCH SCRIPT
     ========================================================= -->

<script>

function searchUsers() {

    const input =
        document.getElementById("searchInput");

    const table =
        document.getElementById("usersTable");

    const count =
        document.getElementById("visibleCount");

    const noResults =
        document.getElementById("noSearchResults");


    if (!input || !table) {
        return;
    }


    const filter =
        input.value
            .toLowerCase()
            .trim();


    const rows =
        table.querySelectorAll(
            "tbody .user-row"
        );


    let visible = 0;


    rows.forEach(function(row) {

        const text =
            row.textContent
                .toLowerCase();


        if (text.includes(filter)) {

            row.style.display = "";

            visible++;

        } else {

            row.style.display = "none";
        }

    });


    if (count) {

        count.textContent =
            visible;
    }


    if (noResults) {

        noResults.style.display =
            visible === 0
                ? "block"
                : "none";
    }

}

</script>


</body>
</html>
