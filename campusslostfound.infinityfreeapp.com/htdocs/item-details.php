<?php

session_start();

require_once "db.php";


/* =========================================
   CHECK LOGIN
========================================= */

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit();

}


/* =========================================
   CHECK ITEM ID
========================================= */

if (!isset($_GET["id"])) {

    header("Location: dashboard.php");
    exit();

}


$item_id = intval($_GET["id"]);


/* =========================================
   GET ITEM
========================================= */

$sql = "SELECT item_id, user_id, item_name, category, description,
               location, date_reported, image, item_type, status
        FROM items
        WHERE item_id = ?";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die("SQL Error: " . $conn->error);

}


$stmt->bind_param("i", $item_id);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows == 0) {

    echo "Item not found.";
    exit();

}


$item = $result->fetch_assoc();

$stmt->close();


/* =========================================
   CHECK IF CURRENT USER IS REPORT OWNER
========================================= */

$is_owner = (
    intval($item["user_id"]) === intval($_SESSION["user_id"])
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <script src="theme.js?v=4"></script>

    <title>
        <?php echo htmlspecialchars($item["item_name"]); ?>
        - CampusLost
    </title>

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


        /* BRAND */

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

            font-size: 11px;

            color: #94a3b8;

            letter-spacing: .4px;
        }


        /* =========================================
           MENU
        ========================================= */

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


        /* =========================================
           LOGOUT
        ========================================= */

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


        /* =========================================
           MAIN
        ========================================= */

        .main {

            margin-left: 260px;

            min-height: 100vh;

            padding: 35px 42px 55px;
        }


        .container {

            width: 100%;

            max-width: 1180px;

            margin: 0 auto;
        }


        /* =========================================
           BACK
        ========================================= */

        .back-link {

            display: inline-flex;

            align-items: center;

            gap: 7px;

            color: #64748b;

            text-decoration: none;

            font-size: 12px;

            font-weight: 600;

            margin-bottom: 20px;

            transition: .2s;
        }


        .back-link:hover {

            color: #2563eb;

            transform: translateX(-2px);
        }


        /* =========================================
           ITEM CARD
        ========================================= */

        .details-card {

            display: grid;

            grid-template-columns:
                minmax(330px, 42%) 1fr;

            background: white;

            border:
                1px solid #e8edf5;

            border-radius: 19px;

            overflow: hidden;

            box-shadow:
                0 8px 28px rgba(15,39,71,.06);

            min-height: 520px;
        }


        /* =========================================
           IMAGE SIDE
        ========================================= */

        .image-side {

            position: relative;

            min-height: 520px;

            background: #eef2f7;

            overflow: hidden;
        }


        .image-side img {

            width: 100%;

            height: 100%;

            object-fit: cover;

            display: block;

            transition:
                transform .4s ease;
        }


        .details-card:hover
        .image-side img {

            transform: scale(1.015);
        }


        .no-image {

            width: 100%;
            height: 100%;

            min-height: 520px;

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            gap: 10px;

            color: #94a3b8;
        }


        .no-image i {

            font-size: 48px;
        }


        .no-image span {

            font-size: 12px;
        }


        /* TYPE BADGE */

        .type-badge {

            position: absolute;

            top: 18px;
            left: 18px;

            display: inline-flex;

            align-items: center;

            gap: 7px;

            padding: 8px 12px;

            border-radius: 30px;

            font-size: 10px;

            font-weight: 800;

            letter-spacing: .4px;

            text-transform: uppercase;

            backdrop-filter: blur(8px);

            box-shadow:
                0 4px 12px rgba(0,0,0,.10);
        }


        .type-badge.found {

            background: rgba(5,78,38,.88);

            color: #dcfce7;
        }


        .type-badge.lost {

            background: rgba(127,29,29,.88);

            color: #fee2e2;
        }


        /* =========================================
           CONTENT SIDE
        ========================================= */

        .content {

            padding: 34px 34px 30px;

            display: flex;

            flex-direction: column;
        }


        /* TOP */

        .item-top {

            display: flex;

            align-items: flex-start;

            justify-content: space-between;

            gap: 20px;

            padding-bottom: 20px;

            border-bottom:
                1px solid #edf1f5;

            margin-bottom: 22px;
        }


        .small-label {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            color: #2563eb;

            font-size: 10px;

            font-weight: 800;

            letter-spacing: 1px;

            text-transform: uppercase;

            margin-bottom: 8px;
        }


        .item-title {

            color: #102342;

            font-size: 28px;

            line-height: 1.2;

            word-break: break-word;

            text-transform: capitalize;
        }


        /* DATABASE STATUS */

        .database-status {

            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 7px 11px;

            border-radius: 20px;

            background: #f1f5f9;

            color: #64748b;

            font-size: 10px;

            font-weight: 800;

            text-transform: uppercase;

            white-space: nowrap;
        }


        .database-status i {

            font-size: 6px;

            color: #2563eb;
        }


        /* =========================================
           INFO GRID
        ========================================= */

        .info-grid {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0,1fr));

            gap: 13px;

            margin-bottom: 22px;
        }


        .info-box {

            padding: 14px 15px;

            background: #f8fafc;

            border:
                1px solid #e2e8f0;

            border-radius: 11px;
        }


        .info-label {

            display: flex;

            align-items: center;

            gap: 7px;

            color: #8a97aa;

            font-size: 10px;

            margin-bottom: 7px;
        }


        .info-label i {

            color: #2563eb;

            width: 13px;

            text-align: center;
        }


        .info-value {

            color: #334155;

            font-size: 13px;

            font-weight: 700;

            line-height: 1.4;

            word-break: break-word;
        }


        /* =========================================
           DESCRIPTION
        ========================================= */

        .description {

            padding: 18px;

            background: #f8fafc;

            border:
                1px solid #e2e8f0;

            border-radius: 11px;

            margin-bottom: 22px;
        }


        .description-title {

            display: flex;

            align-items: center;

            gap: 8px;

            color: #334155;

            font-size: 12px;

            font-weight: 800;

            margin-bottom: 9px;
        }


        .description-title i {

            color: #2563eb;
        }


        .description p {

            color: #64748b;

            font-size: 12px;

            line-height: 1.65;

            word-break: break-word;
        }


        /* =========================================
           OWNER NOTICE
        ========================================= */

        .owner-notice {

            display: flex;

            align-items: flex-start;

            gap: 10px;

            padding: 13px 14px;

            margin-bottom: 20px;

            background: #eff6ff;

            border:
                1px solid #dbeafe;

            border-radius: 10px;

            color: #1e40af;

            font-size: 11px;

            line-height: 1.5;
        }


        .owner-notice i {

            margin-top: 1px;
        }


        /* =========================================
           ACTIONS
        ========================================= */

        .actions {

            display: flex;

            align-items: center;

            gap: 10px;

            margin-top: auto;

            padding-top: 20px;

            border-top:
                1px solid #edf1f5;
        }


        .btn {

            min-height: 42px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding: 0 16px;

            border-radius: 9px;

            text-decoration: none;

            font-size: 11px;

            font-weight: 700;

            transition: .2s ease;
        }


        .discussion {

            background: #eef4ff;

            color: #1d4ed8;

            border:
                1px solid #dbeafe;
        }


        .discussion:hover {

            background: #dbeafe;

            transform: translateY(-1px);
        }


        .claim {

            background: #2563eb;

            color: white;

            border:
                1px solid #2563eb;

            box-shadow:
                0 5px 14px rgba(37,99,235,.18);
        }


        .claim:hover {

            background: #1d4ed8;

            border-color: #1d4ed8;

            transform: translateY(-1px);
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


        html.dark-mode .back-link {

            color: #94a3b8;
        }


        html.dark-mode .back-link:hover {

            color: #60a5fa;
        }


        html.dark-mode .details-card {

            background: #1e293b;

            border-color: #27364c;

            box-shadow:
                0 8px 28px rgba(0,0,0,.25);
        }


        html.dark-mode .image-side {

            background: #263449;
        }


        html.dark-mode .no-image {

            color: #94a3b8;
        }


        html.dark-mode .item-top {

            border-color: #334155;
        }


        html.dark-mode .item-title {

            color: #f8fafc;
        }


        html.dark-mode .database-status {

            background: #334155;

            color: #cbd5e1;
        }


        html.dark-mode .database-status i {

            color: #60a5fa;
        }


        html.dark-mode .info-box {

            background: #111b2f;

            border-color: #334155;
        }


        html.dark-mode .info-label {

            color: #94a3b8;
        }


        html.dark-mode .info-label i {

            color: #60a5fa;
        }


        html.dark-mode .info-value {

            color: #f1f5f9;
        }


        html.dark-mode .description {

            background: #111b2f;

            border-color: #334155;
        }


        html.dark-mode .description-title {

            color: #e2e8f0;
        }


        html.dark-mode .description-title i {

            color: #60a5fa;
        }


        html.dark-mode .description p {

            color: #94a3b8;
        }


        html.dark-mode .owner-notice {

            background: #172554;

            border-color: #1e3a8a;

            color: #bfdbfe;
        }


        html.dark-mode .actions {

            border-color: #334155;
        }


        html.dark-mode .discussion {

            background: #172b4d;

            color: #93c5fd;

            border-color: #29466f;
        }


        html.dark-mode .discussion:hover {

            background: #1e3a5f;
        }


        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 1000px) {

            .details-card {

                grid-template-columns:
                    minmax(280px, 38%) 1fr;
            }

            .content {

                padding: 28px;
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


            .details-card {

                grid-template-columns: 1fr;
            }


            .image-side {

                height: 330px;

                min-height: 330px;
            }


            .no-image {

                min-height: 330px;
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


            .item-top {

                flex-direction: column;
            }


            .info-grid {

                grid-template-columns: 1fr;
            }


            .content {

                padding: 22px;
            }


            .item-title {

                font-size: 24px;
            }


            .actions {

                flex-direction: column;
            }


            .btn {

                width: 100%;
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



    <div class="logout">

        <a href="logout.php">

            <i class="fa-solid fa-right-from-bracket"></i>

            Logout

        </a>

    </div>


</aside>



<!-- =========================================
     MAIN CONTENT
========================================= -->

<main class="main">


    <div class="container">


        <!-- BACK -->

        <a href="javascript:history.back()"
           class="back-link">

            <i class="fa-solid fa-arrow-left"></i>

            Back

        </a>



        <!-- DETAILS CARD -->

        <div class="details-card">


            <!-- =================================
                 IMAGE
            ================================= -->

            <div class="image-side">


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

                        <span>
                            No image available
                        </span>

                    </div>


                <?php endif; ?>



                <!-- LOST / FOUND BADGE -->

                <?php if (
                    $item["item_type"] === "Found"
                ): ?>


                    <span class="type-badge found">

                        <i class="fa-solid fa-circle"></i>

                        Found Item

                    </span>


                <?php else: ?>


                    <span class="type-badge lost">

                        <i class="fa-solid fa-circle"></i>

                        Lost Item

                    </span>


                <?php endif; ?>


            </div>



            <!-- =================================
                 CONTENT
            ================================= -->

            <div class="content">


                <!-- TOP -->

                <div class="item-top">


                    <div>


                        <span class="small-label">

                            <i class="fa-solid fa-box"></i>

                            Item Details

                        </span>


                        <h1 class="item-title">

                            <?php

                                echo htmlspecialchars(
                                    $item["item_name"]
                                );

                            ?>

                        </h1>


                    </div>



                    <span class="database-status">

                        <i class="fa-solid fa-circle"></i>

                        <?php

                            echo htmlspecialchars(
                                $item["status"]
                            );

                        ?>

                    </span>


                </div>



                <!-- =================================
                     INFORMATION
                ================================= -->

                <div class="info-grid">


                    <!-- CATEGORY -->

                    <div class="info-box">


                        <div class="info-label">

                            <i class="fa-solid fa-folder"></i>

                            Category

                        </div>


                        <div class="info-value">

                            <?php

                                echo htmlspecialchars(
                                    $item["category"]
                                );

                            ?>

                        </div>


                    </div>



                    <!-- LOCATION -->

                    <div class="info-box">


                        <div class="info-label">

                            <i class="fa-solid fa-location-dot"></i>

                            Location

                        </div>


                        <div class="info-value">

                            <?php

                                echo htmlspecialchars(
                                    $item["location"]
                                );

                            ?>

                        </div>


                    </div>



                    <!-- DATE -->

                    <div class="info-box">


                        <div class="info-label">

                            <i class="fa-regular fa-calendar"></i>

                            Date Reported

                        </div>


                        <div class="info-value">

                            <?php

                                echo htmlspecialchars(
                                    $item["date_reported"]
                                );

                            ?>

                        </div>


                    </div>



                    <!-- TYPE -->

                    <div class="info-box">


                        <div class="info-label">

                            <i class="fa-solid fa-tag"></i>

                            Report Type

                        </div>


                        <div class="info-value">

                            <?php

                                echo htmlspecialchars(
                                    $item["item_type"]
                                );

                            ?>

                            Item

                        </div>


                    </div>


                </div>



                <!-- =================================
                     DESCRIPTION
                ================================= -->

                <div class="description">


                    <div class="description-title">

                        <i class="fa-solid fa-align-left"></i>

                        Description

                    </div>


                    <p>

                        <?php

                            echo nl2br(
                                htmlspecialchars(
                                    $item["description"]
                                )
                            );

                        ?>

                    </p>


                </div>



                <!-- =================================
                     OWNER MESSAGE
                ================================= -->

                <?php if ($is_owner): ?>


                    <div class="owner-notice">

                        <i class="fa-solid fa-circle-info"></i>

                        <div>

                            <strong>
                                You reported this item.
                            </strong>

                            You can use the discussion section
                            to respond to questions or provide
                            additional information.

                        </div>

                    </div>


                <?php endif; ?>



                <!-- =================================
                     ACTIONS
                ================================= -->

                <div class="actions">


                    <!-- DISCUSSION ALWAYS AVAILABLE -->

                    <a
                        href="item-discussion.php?id=<?php
                            echo $item["item_id"];
                        ?>"

                        class="btn discussion"
                    >

                        <i class="fa-solid fa-comments"></i>

                        Discussion

                    </a>



                    <!--
                        CLAIM BUTTON:

                        Only show when:
                        1. Current user is NOT reporter
                        2. Item type is FOUND
                    -->

                    <?php if (
                        !$is_owner &&
                        $item["item_type"] === "Found"
                    ): ?>


                        <a
                            href="claim.php?id=<?php
                                echo $item["item_id"];
                            ?>"

                            class="btn claim"
                        >

                            <i class="fa-solid fa-hand"></i>

                            Claim This Item

                        </a>


                    <?php endif; ?>


                </div>


            </div>


        </div>


    </div>


</main>


</body>

</html>