<?php

session_start();

require_once "db.php";


/* =========================================================
   CHECK LOGIN
========================================================= */

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit();

}


$user_id = intval($_SESSION["user_id"]);


/* =========================================================
   GET USER INFORMATION
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


if ($user_result->num_rows === 0) {

    die("User not found.");

}


$user = $user_result->fetch_assoc();

$name = $user["name"];

$role = strtolower(
    trim($user["role"])
);

$stmt_user->close();


/* =========================================================
   GET NOTIFICATIONS
========================================================= */

$sql = "SELECT
            notification_id,
            message,
            notification_date,
            is_read
        FROM notifications
        WHERE user_id = ?
        ORDER BY notification_id DESC";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die("SQL Error: " . $conn->error);

}


$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();


/* =========================================================
   COUNT UNREAD
========================================================= */

$unread_count = 0;

$notifications = [];


while (
    $notification =
        $result->fetch_assoc()
) {

    if (
        intval(
            $notification["is_read"]
        ) === 0
    ) {

        $unread_count++;

    }

    $notifications[] =
        $notification;

}


$stmt->close();


/* =========================================================
   MARK NOTIFICATIONS AS READ

   They are still shown as NEW during this page load.
   After refresh / next visit they become read.
========================================================= */

if ($unread_count > 0) {

    $read_sql =
        "UPDATE notifications
         SET is_read = 1
         WHERE user_id = ?
         AND is_read = 0";


    $read_stmt =
        $conn->prepare($read_sql);


    if ($read_stmt) {

        $read_stmt->bind_param(
            "i",
            $user_id
        );

        $read_stmt->execute();

        $read_stmt->close();

    }

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


<script src="theme.js?v=4"></script>


<title>
    Notifications - CampusLost
</title>


<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
>


<style>

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
    transition:
        background .3s ease,
        color .3s ease;
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
    overflow-x: hidden;
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

.header {
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

.header h1 {
    margin-bottom: 7px;
    color: #102342;
    font-size: 28px;
}

.header p {
    color: #748199;
    font-size: 13px;
}

.profile-card {
    min-width: 190px;
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 13px 17px;
    border: 1px solid #e8edf5;
    border-radius: 14px;
    background: white;
    box-shadow: 0 5px 18px rgba(15,39,71,.05);
}

.profile-avatar {
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
    font-weight: 800;
}

.profile-info strong {
    display: block;
    max-width: 160px;
    margin-bottom: 4px;
    color: #102342;
    font-size: 13px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.profile-info span {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #2563eb;
    font-size: 11px;
}

.summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 15px 18px;
    margin-bottom: 20px;
    border: 1px solid #e8edf5;
    border-radius: 14px;
    background: white;
    color: #64748b;
    font-size: 13px;
    box-shadow: 0 5px 18px rgba(15,39,71,.04);
}

.summary-left {
    display: flex;
    align-items: center;
    gap: 9px;
}

.summary-left i {
    color: #2563eb;
    font-size: 14px;
}

.summary strong {
    color: #102342;
}

.unread-summary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 11px;
    background: #eff6ff;
    color: #2563eb;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.notification-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.notification {
    position: relative;
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 18px 20px;
    background: white;
    border: 1px solid #e8edf5;
    border-radius: 14px;
    box-shadow: 0 5px 18px rgba(15,39,71,.05);
    transition: .2s ease;
}

.notification:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(15,39,71,.08);
}

.notification.unread {
    background: #f7faff;
    border-color: #bfdbfe;
}

.notification.unread::before {
    content: "";
    position: absolute;
    left: -1px;
    top: 13px;
    bottom: 13px;
    width: 4px;
    background: #2563eb;
    border-radius: 0 5px 5px 0;
}

.notification-icon {
    width: 46px;
    height: 46px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: #eff6ff;
    color: #2563eb;
    border-radius: 12px;
    font-size: 17px;
}

.notification.unread .notification-icon {
    background: #dbeafe;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-message {
    margin-bottom: 7px;
    color: #334155;
    font-size: 14px;
    font-weight: 500;
    line-height: 1.55;
    word-break: break-word;
}

.notification-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #94a3b8;
    font-size: 11px;
}

.new-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    flex-shrink: 0;
    padding: 6px 9px;
    background: #2563eb;
    color: white;
    border-radius: 20px;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .3px;
}

.new-dot {
    width: 5px;
    height: 5px;
    background: white;
    border-radius: 50%;
}

.empty {
    padding: 45px 20px;
    text-align: center;
    color: #94a3b8;
    border: 1px solid #e8edf5;
    border-radius: 16px;
    background: white;
    box-shadow: 0 6px 20px rgba(15,39,71,.05);
}

.empty-icon {
    width: 52px;
    height: 52px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    border-radius: 14px;
    background: #eff6ff;
    color: #2563eb;
    font-size: 20px;
}

.empty h2 {
    margin-bottom: 5px;
    color: #64748b;
    font-size: 13px;
}

.empty p {
    color: #94a3b8;
    font-size: 11px;
    line-height: 1.6;
}

/* DARK MODE — exact CampusLost palette */
html.dark-mode body,
html.dark-mode .main {
    background: #0f172a;
    color: #e2e8f0;
}

html.dark-mode .sidebar {
    background: #020617;
}

html.dark-mode .header h1 {
    color: #f8fafc;
}

html.dark-mode .header p {
    color: #94a3b8;
}

html.dark-mode .profile-card,
html.dark-mode .summary,
html.dark-mode .notification,
html.dark-mode .empty {
    border-color: #2d3c52;
    background: #1e293b;
}

html.dark-mode .profile-info strong,
html.dark-mode .summary strong {
    color: #f8fafc;
}

html.dark-mode .profile-avatar {
    background: #172554;
    color: #93c5fd;
}

html.dark-mode .profile-info span {
    color: #60a5fa;
}

html.dark-mode .notification-message {
    color: #e2e8f0;
}

html.dark-mode .notification-meta,
html.dark-mode .summary,
html.dark-mode .empty p {
    color: #94a3b8;
}

html.dark-mode .notification.unread {
    background: #172554;
    border-color: #1e40af;
}

html.dark-mode .empty h2 {
    color: #cbd5e1;
}

html.dark-mode .empty-icon {
    background: #172554;
    color: #93c5fd;
}

@media (max-width: 900px) {
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

    .header {
        flex-direction: column;
        align-items: flex-start;
    }

    .profile-card {
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

    .summary {
        align-items: flex-start;
        flex-direction: column;
    }

    .notification {
        align-items: flex-start;
    }

    .new-badge {
        position: absolute;
        top: 10px;
        right: 10px;
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


    <?php if ($role === "admin"): ?>


        <!-- =============================================
             ADMIN MENU
        ============================================== -->

        <div class="menu-label">
            Administration
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


        <a href="admin-users.php">
            <i class="fa-solid fa-users"></i>
            Manage Users
        </a>


        <div class="menu-label">
            Account
        </div>


        <a
            href="notifications.php"
            class="active"
        >
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



    <?php elseif ($role === "staff"): ?>


        <!-- =============================================
             STAFF MENU
        ============================================== -->

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


        <a href="staff-manage-items.php">
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


        <a
            href="notifications.php"
            class="active"
        >
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



    <?php else: ?>


        <!-- =============================================
             STUDENT MENU
        ============================================== -->

        <div class="menu-label">
            Main
        </div>


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


        <a
            href="notifications.php"
            class="active"
        >
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


    <?php endif; ?>


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


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="header">


        <div>


            <span class="page-label">

                <i class="fa-solid fa-bell"></i>

                Activity Updates

            </span>


            <h1>

                Notifications

            </h1>


            <p>

                Stay updated with your
                CampusLost activities.

            </p>


        </div>



        <div class="profile-card">


            <div class="profile-avatar">

                <?php

                    echo strtoupper(
                        substr(
                            trim($name),
                            0,
                            1
                        )
                    );

                ?>

            </div>


            <div class="profile-info">


                <strong>

                    <?php
                        echo htmlspecialchars(
                            $name
                        );
                    ?>

                </strong>


                <span>

                    <?php

                        echo
                            ($role === "admin")
                            ? "Administrator"
                            : ucfirst($role);

                    ?>

                </span>


            </div>


        </div>


    </div>



    <?php if (
        count($notifications) > 0
    ): ?>


        <!-- =================================================
             SUMMARY
        ================================================== -->

        <div class="summary">


            <div class="summary-left">

                <i class="fa-solid fa-inbox"></i>

                <span>

                    You have

                    <strong>

                        <?php
                            echo count(
                                $notifications
                            );
                        ?>

                    </strong>

                    notification<?php
                        echo
                            count($notifications) == 1
                            ? ""
                            : "s";
                    ?>.

                </span>

            </div>



            <?php if (
                $unread_count > 0
            ): ?>


                <span class="unread-summary">

                    <i class="fa-solid fa-circle"></i>

                    <?php
                        echo $unread_count;
                    ?>

                    new

                </span>


            <?php else: ?>


                <span class="unread-summary">

                    <i class="fa-solid fa-check"></i>

                    All caught up

                </span>


            <?php endif; ?>


        </div>



        <!-- =================================================
             NOTIFICATION LIST
        ================================================== -->

        <div class="notification-list">


            <?php foreach (
                $notifications
                as $notification
            ): ?>


                <?php

                    $is_unread =
                        intval(
                            $notification[
                                "is_read"
                            ]
                        ) === 0;

                ?>


                <article
                    class="notification <?php
                        echo
                            $is_unread
                            ? "unread"
                            : "";
                    ?>"
                >


                    <div class="notification-icon">

                        <i class="fa-solid fa-bell"></i>

                    </div>



                    <div class="notification-content">


                        <p class="notification-message">

                            <?php

                                echo htmlspecialchars(
                                    $notification[
                                        "message"
                                    ]
                                );

                            ?>

                        </p>



                        <div class="notification-meta">

                            <i class="fa-regular fa-clock"></i>

                            <span>

                                <?php

                                    echo htmlspecialchars(
                                        $notification[
                                            "notification_date"
                                        ]
                                    );

                                ?>

                            </span>

                        </div>


                    </div>



                    <?php if ($is_unread): ?>


                        <span class="new-badge">

                            <span class="new-dot"></span>

                            NEW

                        </span>


                    <?php endif; ?>


                </article>


            <?php endforeach; ?>


        </div>


    <?php else: ?>


        <!-- =================================================
             EMPTY STATE
        ================================================== -->

        <div class="empty">


            <div class="empty-icon">

                <i class="fa-regular fa-bell"></i>

            </div>


            <h2>

                No Notifications

            </h2>


            <p>

                You're all caught up!<br>

                New updates will appear here.

            </p>


        </div>


    <?php endif; ?>


</div>


</main>


    </body>

</html>