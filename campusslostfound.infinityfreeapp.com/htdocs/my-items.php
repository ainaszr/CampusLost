<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

/* Get items reported by current user */
$sql = "SELECT
            item_id,
            item_name,
            category,
            description,
            location,
            date_reported,
            image,
            item_type,
            status
        FROM items
        WHERE user_id = ?
        ORDER BY item_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="theme.js?v=2"></script>

    <title>My Items - CampusLost</title>

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
            transition: .3s ease;
        }


        /* =========================================
           SIDEBAR
        ========================================= */

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


        /* LOGOUT */

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


        /* =========================================
           MAIN
        ========================================= */

        .main {
            margin-left: 260px;

            min-height: 100vh;

            padding: 35px 42px 55px;
        }


        /* =========================================
           HEADER
        ========================================= */

        .header {
            display: flex;

            justify-content: space-between;
            align-items: flex-end;

            gap: 20px;

            margin-bottom: 27px;
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


        /* REPORT BUTTON */

        .report-btn {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            gap: 8px;

            padding: 11px 16px;

            background: #2563eb;
            color: white;

            border-radius: 9px;

            text-decoration: none;

            font-size: 12px;
            font-weight: 700;

            box-shadow:
                0 5px 14px rgba(37,99,235,.18);

            transition: .2s ease;
        }

        .report-btn:hover {
            background: #1d4ed8;

            transform: translateY(-1px);
        }


        /* =========================================
           SUMMARY
        ========================================= */

        .summary-bar {
            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 20px;

            padding: 15px 18px;

            margin-bottom: 25px;

            background: white;

            border: 1px solid #e8edf5;
            border-radius: 13px;

            box-shadow:
                0 4px 15px rgba(15,39,71,.035);
        }

        .summary-left {
            display: flex;

            align-items: center;

            gap: 11px;
        }

        .summary-icon {
            width: 35px;
            height: 35px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 9px;

            background: #e8f0ff;
            color: #2563eb;

            font-size: 14px;
        }

        .summary-text strong {
            display: block;

            color: #334155;

            font-size: 13px;

            margin-bottom: 2px;
        }

        .summary-text span {
            color: #94a3b8;

            font-size: 11px;
        }

        .item-count {
            padding: 7px 11px;

            border-radius: 20px;

            background: #f1f5f9;
            color: #64748b;

            font-size: 11px;
            font-weight: 700;
        }


        /* =========================================
           GRID
        ========================================= */

        .items-grid {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0,1fr));

            gap: 22px;
        }


        /* =========================================
           ITEM CARD
        ========================================= */

        .item-card {
            display: flex;
            flex-direction: column;

            background: white;

            border: 1px solid #e8edf5;
            border-radius: 17px;

            overflow: hidden;

            box-shadow:
                0 5px 20px rgba(15,39,71,.045);

            transition: .25s ease;
        }

        .item-card:hover {
            transform: translateY(-5px);

            border-color: #d6e3fa;

            box-shadow:
                0 15px 30px rgba(15,39,71,.10);
        }


        /* IMAGE */

        .item-image {
            position: relative;

            width: 100%;
            height: 225px;

            background: #edf1f6;

            overflow: hidden;
        }

        .item-image img {
            width: 100%;
            height: 100%;

            object-fit: cover;

            display: block;

            transition: transform .35s ease;
        }

        .item-card:hover .item-image img {
            transform: scale(1.025);
        }

        .no-image {
            width: 100%;
            height: 100%;

            display: flex;

            align-items: center;
            justify-content: center;

            color: #94a3b8;

            font-size: 35px;
        }


        /* ITEM TYPE ON IMAGE */

        .type-badge {
            position: absolute;

            top: 14px;
            left: 14px;

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 6px 10px;

            border-radius: 30px;

            font-size: 10px;
            font-weight: 800;

            backdrop-filter: blur(6px);
        }

        .type-badge.lost {
            background: rgba(127,29,29,.88);
            color: #fee2e2;
        }

        .type-badge.found {
            background: rgba(5,78,38,.88);
            color: #dcfce7;
        }


        /* CONTENT */

        .content {
            display: flex;
            flex-direction: column;

            flex: 1;

            padding: 20px;
        }

        .content h2 {
            color: #102342;

            font-size: 18px;
            line-height: 1.3;

            text-transform: capitalize;

            margin-bottom: 13px;
        }


        /* INFORMATION */

        .item-info {
            display: flex;
            flex-direction: column;

            gap: 9px;

            padding-bottom: 14px;

            border-bottom: 1px solid #edf1f5;

            margin-bottom: 14px;
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

            background: #eef4ff;
            color: #2563eb;

            font-size: 10px;
        }

        .info-row strong {
            color: #334155;

            margin-right: 3px;
        }


        /* DESCRIPTION */

        .description {
            color: #748199;

            font-size: 12px;
            line-height: 1.55;

            min-height: 40px;

            margin-bottom: 16px;

            display: -webkit-box;

            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;

            overflow: hidden;
        }


        /* STATUS + BUTTON */

        .card-bottom {
            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 10px;

            margin-top: auto;
        }

        .status-badge {
            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 6px 9px;

            border-radius: 20px;

            font-size: 9px;
            font-weight: 800;

            text-transform: uppercase;
        }

        .status-badge.pending {
            background: #fff5d8;
            color: #b45309;
        }

        .status-badge.claimed {
            background: #f0eaff;
            color: #6d28d9;
        }

        .status-badge.returned {
            background: #e5f8ed;
            color: #15803d;
        }

        .status-badge.lost {
            background: #feecee;
            color: #dc2626;
        }

        .status-badge.found {
            background: #e5f8ed;
            color: #15803d;
        }


        /* VIEW BUTTON */

        .view-btn {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            gap: 7px;

            padding: 9px 13px;

            background: #2563eb;
            color: white;

            text-decoration: none;

            border-radius: 9px;

            font-size: 11px;
            font-weight: 700;

            transition: .2s ease;
        }

        .view-btn:hover {
            background: #1d4ed8;

            transform: translateX(2px);
        }


        /* =========================================
           EMPTY
        ========================================= */

        .empty {
            padding: 70px 30px;

            background: white;

            border: 1px solid #e8edf5;
            border-radius: 17px;

            text-align: center;

            box-shadow:
                0 5px 20px rgba(15,39,71,.04);
        }

        .empty-icon {
            width: 60px;
            height: 60px;

            margin: 0 auto 15px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 17px;

            background: #e8f0ff;
            color: #2563eb;

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

            margin-bottom: 20px;
        }


        /* =========================================
           DARK MODE
        ========================================= */

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


        /* SUMMARY */

        html.dark-mode .summary-bar {
            background: #1e293b;

            border-color: #27364c;

            box-shadow:
                0 4px 15px rgba(0,0,0,.15);
        }

        html.dark-mode .summary-icon {
            background: #172b4d;
            color: #60a5fa;
        }

        html.dark-mode .summary-text strong {
            color: #e2e8f0;
        }

        html.dark-mode .summary-text span {
            color: #94a3b8;
        }

        html.dark-mode .item-count {
            background: #334155;
            color: #cbd5e1;
        }


        /* CARD */

        html.dark-mode .item-card {
            background: #1e293b;

            border-color: #27364c;

            box-shadow:
                0 5px 18px rgba(0,0,0,.18);
        }

        html.dark-mode .item-card:hover {
            border-color: #3b5680;

            box-shadow:
                0 15px 30px rgba(0,0,0,.30);
        }

        html.dark-mode .item-image {
            background: #263449;
        }

        html.dark-mode .no-image {
            color: #94a3b8;
        }

        html.dark-mode .content h2 {
            color: #f8fafc;
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
            background: #172b4d;
            color: #60a5fa;
        }

        html.dark-mode .description {
            color: #94a3b8;
        }

        html.dark-mode .status-badge.pending {
            background: #451a03;
            color: #fcd34d;
        }

        html.dark-mode .status-badge.claimed {
            background: #2e1065;
            color: #c4b5fd;
        }

        html.dark-mode .status-badge.returned {
            background: #052e16;
            color: #86efac;
        }

        html.dark-mode .status-badge.lost {
            background: #450a0a;
            color: #fca5a5;
        }

        html.dark-mode .status-badge.found {
            background: #052e16;
            color: #86efac;
        }


        /* EMPTY */

        html.dark-mode .empty {
            background: #1e293b;

            border-color: #27364c;

            box-shadow:
                0 5px 18px rgba(0,0,0,.18);
        }

        html.dark-mode .empty h2 {
            color: #f8fafc;
        }

        html.dark-mode .empty p {
            color: #94a3b8;
        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 1100px) {

            .items-grid {
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
                flex-direction: column;

                align-items: flex-start;
            }

            .summary-bar {
                align-items: flex-start;

                flex-direction: column;
            }

            .items-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<!-- =========================================
     SIDEBAR
========================================= -->

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


        <a href="my-items.php"
           class="active">

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


    <div class="logout">

        <a href="logout.php">

            <i class="fa-solid fa-right-from-bracket"></i>

            Logout

        </a>

    </div>


</aside>



<!-- =========================================
     MAIN
========================================= -->

<main class="main">


    <!-- HEADER -->

    <div class="header">


        <div>

            <span class="page-label">

                <i class="fa-solid fa-clipboard-list"></i>

                My Reports

            </span>


            <h1>
                My Items
            </h1>


            <p>
                View and manage the lost and found items you have reported.
            </p>

        </div>


        <a href="report-lost.php"
           class="report-btn">

            <i class="fa-solid fa-plus"></i>

            Report Item

        </a>


    </div>



    <!-- SUMMARY -->

    <div class="summary-bar">


        <div class="summary-left">

            <div class="summary-icon">

                <i class="fa-solid fa-box-archive"></i>

            </div>


            <div class="summary-text">

                <strong>
                    Your Reported Items
                </strong>

                <span>
                    All lost and found reports submitted by you.
                </span>

            </div>

        </div>


        <span class="item-count">

            <?php echo $result->num_rows; ?>

            Item<?php echo $result->num_rows == 1 ? "" : "s"; ?>

        </span>


    </div>



    <!-- =========================================
         ITEMS
    ========================================= -->

    <?php if ($result->num_rows > 0): ?>


        <div class="items-grid">


            <?php while ($item = $result->fetch_assoc()): ?>


                <div class="item-card">


                    <!-- IMAGE -->

                    <div class="item-image">


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



                        <!-- TYPE -->

                        <?php if ($item["item_type"] == "Lost"): ?>


                            <span class="type-badge lost">

                                <i class="fa-solid fa-circle"></i>

                                Lost

                            </span>


                        <?php else: ?>


                            <span class="type-badge found">

                                <i class="fa-solid fa-circle"></i>

                                Found

                            </span>


                        <?php endif; ?>


                    </div>



                    <!-- CONTENT -->

                    <div class="content">


                        <h2>

                            <?php
                                echo htmlspecialchars(
                                    $item["item_name"]
                                );
                            ?>

                        </h2>



                        <!-- INFORMATION -->

                        <div class="item-info">


                            <div class="info-row">

                                <span class="info-icon">

                                    <i class="fa-solid fa-folder"></i>

                                </span>


                                <div>

                                    <strong>
                                        Category:
                                    </strong>

                                    <?php
                                        echo htmlspecialchars(
                                            $item["category"]
                                        );
                                    ?>

                                </div>

                            </div>



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



                        <!-- DESCRIPTION -->

                        <div class="description">

                            <?php

                                $description =
                                    $item["description"];


                                if (
                                    strlen($description) > 100
                                ) {

                                    $description =
                                        substr(
                                            $description,
                                            0,
                                            100
                                        )
                                        . "...";

                                }


                                echo htmlspecialchars(
                                    $description
                                );

                            ?>

                        </div>



                        <!-- STATUS + BUTTON -->

                        <div class="card-bottom">


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


                            <span
                                class="status-badge <?php
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



                            <a
                                href="item-details.php?id=<?php
                                    echo $item["item_id"];
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


        </div>


    <?php else: ?>


        <!-- EMPTY -->

        <div class="empty">


            <div class="empty-icon">

                <i class="fa-solid fa-clipboard-list"></i>

            </div>


            <h2>
                No Items Reported
            </h2>


            <p>
                You haven't reported any lost or found items yet.
            </p>


            <a
                href="report-lost.php"
                class="report-btn"
            >

                <i class="fa-solid fa-plus"></i>

                Report Your First Item

            </a>


        </div>


    <?php endif; ?>


</main>


</body>
</html>