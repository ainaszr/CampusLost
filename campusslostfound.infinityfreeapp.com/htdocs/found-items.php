<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];


/* =========================
   GET CURRENT USER ROLE
========================= */

$user_stmt = $conn->prepare(
    "SELECT role
     FROM users
     WHERE user_id = ?"
);

if (!$user_stmt) {
    die("SQL Error: " . $conn->error);
}

$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();

$user_result = $user_stmt->get_result();

if ($user_result->num_rows !== 1) {
    header("Location: logout.php");
    exit();
}

$current_user = $user_result->fetch_assoc();

$role = strtolower(
    trim($current_user["role"])
);

$user_stmt->close();


/* =========================
   GET FOUND ITEMS
========================= */

$sql = "SELECT item_id,
               item_name,
               category,
               description,
               location,
               date_reported,
               image,
               status
        FROM items
        WHERE item_type = 'Found'
        ORDER BY item_id DESC";

$result = $conn->query($sql);

if (!$result) {
    die("SQL Error: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="theme.js?v=4"></script>

    <title>Found Items - CampusLost</title>

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

            background: rgba(37,99,235,0.18);
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

        /* MENU */

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
        }

        .menu a:hover {
            background: rgba(37,99,235,0.14);
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
                0 7px 18px rgba(37,99,235,0.25);
        }

        /* LOGOUT */

        .logout {
            padding-top: 14px;
            border-top: 1px solid rgba(255,255,255,0.09);
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
           HEADER
        ========================= */

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;

            gap: 20px;

            margin-bottom: 25px;
        }

        .page-label {
            display: inline-block;

            color: #22c55e;

            font-size: 11px;
            font-weight: 800;

            letter-spacing: 1px;
            text-transform: uppercase;

            margin-bottom: 7px;
        }

        .header h1 {
            font-size: 28px;
            color: #102342;

            margin-bottom: 7px;
        }

        .header p {
            color: #748199;
            font-size: 14px;
        }

        .item-count {
            padding: 9px 14px;

            border: 1px solid #e3e9f2;
            border-radius: 10px;

            background: white;

            color: #64748b;

            font-size: 12px;
        }

        /* =========================
           SEARCH
        ========================= */

        .search-box {
            position: relative;

            background: white;

            padding: 18px;

            border: 1px solid #e8edf5;
            border-radius: 16px;

            box-shadow:
                0 5px 20px rgba(15,39,71,0.04);

            margin-bottom: 28px;
        }

        .search-icon {
            position: absolute;

            left: 37px;
            top: 50%;

            transform: translateY(-50%);

            color: #8290a6;

            font-size: 15px;

            pointer-events: none;
        }

        .search-box input {
            width: 100%;
            height: 53px;

            padding: 0 50px;

            border: 1px solid #d8e0eb;
            border-radius: 11px;

            font-size: 14px;

            outline: none;

            color: #334155;
            background: #f9fbfd;

            transition: 0.2s ease;
        }

        .search-box input:focus {
            background: white;
            border-color: #2563eb;

            box-shadow:
                0 0 0 4px rgba(37,99,235,0.08);
        }

        .search-box input::placeholder {
            color: #94a3b8;
        }

        /* =========================
           GRID
        ========================= */

        .item-grid {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0,1fr));

            gap: 22px;
        }

        /* =========================
           CARD
        ========================= */

        .item-card {
            display: flex;
            flex-direction: column;

            background: white;

            border: 1px solid #e8edf5;
            border-radius: 17px;

            overflow: hidden;

            box-shadow:
                0 5px 20px rgba(15,39,71,0.045);

            transition: 0.25s ease;
        }

        .item-card:hover {
            transform: translateY(-5px);

            border-color: #d6e3fa;

            box-shadow:
                0 15px 30px rgba(15,39,71,0.10);
        }

        /* IMAGE */

        .image-wrapper {
            position: relative;
            overflow: hidden;

            background: #edf1f6;
        }

        .item-image {
            display: block;

            width: 100%;
            height: 225px;

            object-fit: cover;

            transition: transform 0.35s ease;
        }

        .item-card:hover .item-image {
            transform: scale(1.025);
        }

        .no-image {
            width: 100%;
            height: 225px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #edf1f6;
            color: #94a3b8;

            font-size: 35px;
        }

        .image-type {
            position: absolute;

            top: 14px;
            left: 14px;

            display: flex;
            align-items: center;

            gap: 6px;

            padding: 6px 10px;

            background: rgba(5,46,22,0.82);
            color: #dcfce7;

            border-radius: 30px;

            font-size: 10px;
            font-weight: 700;

            backdrop-filter: blur(6px);
        }

        /* CONTENT */

        .item-content {
            display: flex;
            flex-direction: column;

            flex: 1;

            padding: 20px;
        }

        .item-content h3 {
            color: #102342;

            font-size: 18px;
            line-height: 1.3;

            margin-bottom: 9px;

            text-transform: capitalize;
        }

        .category {
            display: inline-flex;
            align-self: flex-start;

            padding: 5px 10px;

            border-radius: 20px;

            background: #e8f0ff;
            color: #2563eb;

            font-size: 10px;
            font-weight: 800;

            margin-bottom: 13px;
        }

        .item-description {
            min-height: 42px;

            color: #748199;

            font-size: 13px;
            line-height: 1.55;

            margin-bottom: 16px;

            display: -webkit-box;

            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;

            overflow: hidden;
        }

        /* ITEM INFORMATION */

        .item-info {
            display: flex;
            flex-direction: column;

            gap: 9px;

            padding: 13px 0;

            border-top: 1px solid #edf1f5;
            border-bottom: 1px solid #edf1f5;

            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            align-items: center;

            gap: 9px;

            color: #718096;

            font-size: 12px;
        }

        .info-icon {
            width: 25px;
            height: 25px;

            flex-shrink: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 7px;

            background: #ecfdf3;
            color: #16a34a;

            font-size: 10px;
        }

        .info-row strong {
            color: #334155;
            margin-right: 3px;
        }

        /* BOTTOM */

        .card-bottom {
            display: flex;

            justify-content: space-between;
            align-items: center;

            gap: 10px;

            margin-top: auto;
        }

        /* STATUS */

        .status {
            display: inline-flex;
            align-items: center;

            gap: 5px;

            padding: 6px 10px;

            border-radius: 20px;

            font-size: 10px;
            font-weight: 800;

            text-transform: uppercase;
        }

        .status.pending {
            background: #fff5d8;
            color: #b45309;
        }

        .status.lost {
            background: #feecee;
            color: #dc2626;
        }

        .status.found {
            background: #e5f8ed;
            color: #15803d;
        }

        .status.claimed {
            background: #f0eaff;
            color: #6d28d9;
        }

        .status.returned {
            background: #e5f8ed;
            color: #15803d;
        }

        .status.rejected {
            background: #f1f5f9;
            color: #64748b;
        }

        /* BUTTON */

        .view-btn {
            display: inline-flex;

            justify-content: center;
            align-items: center;

            gap: 7px;

            padding: 9px 14px;

            background: #2563eb;
            color: white;

            text-decoration: none;

            border-radius: 9px;

            font-weight: 700;
            font-size: 11px;

            transition: 0.2s ease;
        }

        .view-btn:hover {
            background: #1d4ed8;

            transform: translateX(2px);
        }

        /* =========================
           EMPTY
        ========================= */

        .empty {
            background: white;

            padding: 70px 30px;

            text-align: center;

            border: 1px solid #e8edf5;
            border-radius: 17px;

            box-shadow:
                0 5px 20px rgba(15,39,71,0.04);
        }

        .empty-icon {
            width: 60px;
            height: 60px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin: 0 auto 15px;

            border-radius: 17px;

            background: #ecfdf3;
            color: #16a34a;

            font-size: 23px;
        }

        .empty h2 {
            color: #102342;

            font-size: 19px;

            margin-bottom: 7px;
        }

        .empty p {
            color: #748199;

            font-size: 13px;
        }

        #noSearchResult {
            display: none;

            grid-column: 1 / -1;

            padding: 50px;

            text-align: center;

            color: #94a3b8;
        }

        #noSearchResult i {
            display: block;

            font-size: 28px;

            margin-bottom: 10px;
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

        html.dark-mode .header h1 {
            color: #f8fafc;
        }

        html.dark-mode .header p {
            color: #94a3b8;
        }

        html.dark-mode .item-count {
            background: #1e293b;

            border-color: #27364c;

            color: #94a3b8;
        }

        /* SEARCH */

        html.dark-mode .search-box {
            background: #1e293b;

            border-color: #27364c;

            box-shadow:
                0 5px 18px rgba(0,0,0,0.18);
        }

        html.dark-mode .search-box input {
            background: #111b2f;

            border-color: #3a4a62;

            color: #e2e8f0;
        }

        html.dark-mode .search-box input:focus {
            background: #111b2f;
            border-color: #3b82f6;
        }

        html.dark-mode .search-box input::placeholder {
            color: #71809a;
        }

        /* CARDS */

        html.dark-mode .item-card {
            background: #1e293b;

            border-color: #27364c;

            box-shadow:
                0 5px 18px rgba(0,0,0,0.18);
        }

        html.dark-mode .item-card:hover {
            border-color: #3b5680;

            box-shadow:
                0 15px 30px rgba(0,0,0,0.30);
        }

        html.dark-mode .item-content h3 {
            color: #f8fafc;
        }

        html.dark-mode .category {
            background: #172b4d;
            color: #60a5fa;
        }

        html.dark-mode .item-description {
            color: #94a3b8;
        }

        html.dark-mode .item-info {
            border-color: #334155;
        }

        html.dark-mode .info-row {
            color: #94a3b8;
        }

        html.dark-mode .info-row strong {
            color: #e2e8f0;
        }

        html.dark-mode .info-icon {
            background: #052e16;
            color: #86efac;
        }

        html.dark-mode .no-image {
            background: #263449;
            color: #94a3b8;
        }

        html.dark-mode .status.lost {
            background: #450a0a;
            color: #fca5a5;
        }

        html.dark-mode .status.pending {
            background: #451a03;
            color: #fcd34d;
        }

        html.dark-mode .status.found,
        html.dark-mode .status.returned {
            background: #052e16;
            color: #86efac;
        }

        html.dark-mode .status.claimed {
            background: #2e1065;
            color: #c4b5fd;
        }

        html.dark-mode .status.rejected {
            background: #334155;
            color: #cbd5e1;
        }

        html.dark-mode .empty {
            background: #1e293b;

            border-color: #27364c;

            box-shadow:
                0 5px 18px rgba(0,0,0,0.18);
        }

        html.dark-mode .empty h2 {
            color: #f8fafc;
        }

        html.dark-mode .empty p,
        html.dark-mode #noSearchResult {
            color: #94a3b8;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1100px) {

            .item-grid {
                grid-template-columns:
                    repeat(2, minmax(0,1fr));
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

            .header {
                align-items: flex-start;
                flex-direction: column;
            }

            .item-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

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

    <?php if ($role === "staff"): ?>

        <!-- STAFF -->

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

        <a href="found-items.php" class="active">
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


    <?php else: ?>

        <!-- STUDENT -->

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

        <a href="found-items.php" class="active">
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

    <?php endif; ?>

</nav>


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


    <!-- HEADER -->

    <div class="header">


        <div>

            <span class="page-label">

                Lost & Found

            </span>


            <h1>

                Found Items

            </h1>


            <p>

                Browse items that have been reported found around campus.

            </p>

        </div>


        <div class="item-count">

            <i class="fa-solid fa-box-open"></i>

            &nbsp;

            <span id="visibleCount">

                <?php echo $result->num_rows; ?>

            </span>

            Found Item<?php echo $result->num_rows == 1 ? "" : "s"; ?>

        </div>


    </div>


    <!-- =========================
         SEARCH
    ========================== -->

    <div class="search-box">

        <i class="fa-solid fa-magnifying-glass search-icon"></i>

        <input
            type="text"
            id="searchInput"
            placeholder="Search by item name, description, location or category..."
            onkeyup="searchItems()"
        >

    </div>


    <!-- =========================
         ITEMS
    ========================== -->

    <?php if ($result->num_rows > 0): ?>


        <div class="item-grid"
             id="itemGrid">


            <?php while ($item = $result->fetch_assoc()): ?>


                <div
                    class="item-card"

                    data-search="<?php

                        echo strtolower(
                            htmlspecialchars(
                                $item["item_name"]
                                . " "
                                . $item["description"]
                                . " "
                                . $item["location"]
                                . " "
                                . $item["category"]
                            )
                        );

                    ?>"
                >


                    <!-- IMAGE -->

                    <div class="image-wrapper">


                        <?php if (
                            !empty($item["image"]) &&
                            file_exists(
                                "uploads/" . $item["image"]
                            )
                        ): ?>


                            <img
                                src="uploads/<?php

                                    echo htmlspecialchars(
                                        $item["image"]
                                    );

                                ?>"

                                class="item-image"

                                alt="<?php

                                    echo htmlspecialchars(
                                        $item["item_name"]
                                    );

                                ?>"
                            >


                        <?php else: ?>


                            <div class="no-image">

                                <i class="fa-regular fa-image"></i>

                            </div>


                        <?php endif; ?>


                        <span class="image-type">

                            <i class="fa-solid fa-box-open"></i>

                            Found Item

                        </span>


                    </div>


                    <!-- CONTENT -->

                    <div class="item-content">


                        <h3>

                            <?php

                                echo htmlspecialchars(
                                    $item["item_name"]
                                );

                            ?>

                        </h3>


                        <!-- CATEGORY -->

                        <span class="category">

                            <?php

                                echo htmlspecialchars(
                                    $item["category"]
                                );

                            ?>

                        </span>


                        <!-- DESCRIPTION -->

                        <p class="item-description">

                            <?php

                                echo htmlspecialchars(
                                    $item["description"]
                                );

                            ?>

                        </p>


                        <!-- INFORMATION -->

                        <div class="item-info">


                            <div class="info-row">


                                <span class="info-icon">

                                    <i class="fa-solid fa-location-dot"></i>

                                </span>


                                <div>

                                    <strong>
                                        Location:
                                    </strong>

                                    <?php

                                        echo htmlspecialchars(
                                            $item["location"]
                                        );

                                    ?>

                                </div>


                            </div>


                            <div class="info-row">


                                <span class="info-icon">

                                    <i class="fa-regular fa-calendar"></i>

                                </span>


                                <div>

                                    <strong>
                                        Reported:
                                    </strong>

                                    <?php

                                        echo htmlspecialchars(
                                            $item["date_reported"]
                                        );

                                    ?>

                                </div>


                            </div>


                        </div>


                        <!-- STATUS -->

                        <?php

                            $status_class =
                                strtolower(
                                    str_replace(
                                        " ",
                                        "-",
                                        $item["status"]
                                    )
                                );

                        ?>


                        <div class="card-bottom">


                            <span
                                class="status <?php
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


                            <!-- VIEW DETAILS -->

                            <a
                                href="<?php
                                    if ($role === "staff") {
                                        echo "staff-item-details.php?id=" . intval($item["item_id"]);
                                    } elseif ($role === "admin") {
                                        echo "admin-item-details.php?id=" . intval($item["item_id"]);
                                    } else {
                                        echo "item-details.php?id=" . intval($item["item_id"]);
                                    }
                                ?>"

                                class="view-btn"
                            >

                                View Details

                                <i class="fa-solid fa-arrow-right"></i>

                            </a>


                        </div>


                    </div>


                </div>


            <?php endwhile; ?>


            <!-- SEARCH EMPTY -->

            <div id="noSearchResult">

                <i class="fa-solid fa-magnifying-glass"></i>

                No items match your search.

            </div>


        </div>


    <?php else: ?>


        <div class="empty">


            <div class="empty-icon">

                <i class="fa-solid fa-box-open"></i>

            </div>


            <h2>

                No Found Items

            </h2>


            <p>

                There are currently no found items reported.

            </p>


        </div>


    <?php endif; ?>


</main>


<!-- =========================
     SEARCH
========================= -->

<script>

function searchItems() {

    const input =
        document
            .getElementById("searchInput")
            .value
            .toLowerCase()
            .trim();


    const cards =
        document.querySelectorAll(".item-card");


    const noResult =
        document.getElementById("noSearchResult");


    const visibleCount =
        document.getElementById("visibleCount");


    let visible = 0;


    cards.forEach(function(card) {


        const text =
            card
                .getAttribute("data-search")
                .toLowerCase();


        if (text.includes(input)) {

            card.style.display = "";

            visible++;

        } else {

            card.style.display = "none";

        }


    });


    if (visibleCount) {

        visibleCount.textContent = visible;

    }


    if (noResult) {

        if (visible === 0) {

            noResult.style.display = "block";

        } else {

            noResult.style.display = "none";

        }

    }

}

</script>


</body>
</html>