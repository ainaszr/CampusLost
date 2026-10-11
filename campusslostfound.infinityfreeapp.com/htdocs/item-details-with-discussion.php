<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET["id"])) {
    header("Location: dashboard.php");
    exit();
}

$item_id = intval($_GET["id"]);

$sql = "SELECT item_id, item_name, category, description,
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
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- DARK MODE -->
    <script src="theme.js?v=4"></script>

    <title>
        <?php echo htmlspecialchars($item["item_name"]); ?>
        - Campus Lost & Found
    </title>


    <style>

        /* =========================
           RESET
        ========================= */

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }


        /* =========================
           BODY
        ========================= */

        body {

            background: #f8fafc;

            color: #0f2747;

            min-height: 100vh;
        }


        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {

            position: fixed;

            left: 0;
            top: 0;

            width: 250px;

            height: 100vh;

            background: #0f2747;

            color: white;

            padding: 25px 10px;

            z-index: 1000;
        }


        /* =========================
           LOGO
        ========================= */

        .logo {

            font-size: 21px;

            font-weight: bold;

            margin-bottom: 35px;

            text-align: center;

            color: white;

            line-height: 1.2;
        }


        .logo span {

            color: #60a5fa;
        }


        /* =========================
           MENU
        ========================= */

        .menu a {

            display: block;

            padding: 13px 15px;

            margin-bottom: 7px;

            color: #dbeafe;

            text-decoration: none;

            border-radius: 8px;

            position: relative;

            z-index: 1001;

            cursor: pointer;

            transition: 0.2s;

            font-size: 16px;
        }


        .menu a:hover {

            background: #1e293b;

            color: white;
        }


        /* =========================
           LOGOUT
        ========================= */

        .logout {

            position: absolute;

            bottom: 25px;

            left: 18px;

            right: 18px;

            z-index: 1001;
        }


        .logout a {

            display: block;

            padding: 12px;

            text-align: center;

            background: #ef4444;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            cursor: pointer;

            transition: 0.2s;
        }


        .logout a:hover {

            background: #dc2626;
        }


        /* =========================
           MAIN
        ========================= */

        .main {

            margin-left: 250px;

            min-height: 100vh;

            padding: 45px 40px;

            display: flex;

            justify-content: center;

            align-items: flex-start;
        }


        /* =========================
           CONTAINER
        ========================= */

        .container {

            width: 100%;

            max-width: 680px;

            margin: 0 auto;
        }


        /* =========================
           DETAILS CARD
        ========================= */

        .details-card {

            background: white;

            border-radius: 16px;

            overflow: hidden;

            box-shadow:
                0 6px 25px rgba(0,0,0,0.08);
        }


        /* =========================
           IMAGE
        ========================= */

        .item-image {

            width: 100%;

            height: 180px;

            background: #eaf2ff;

            display: flex;

            align-items: center;

            justify-content: center;

            overflow: hidden;
        }


        .item-image img {

            width: 100%;

            height: 100%;

            object-fit: cover;

            object-position: center;
        }


        .no-image {

            font-size: 60px;
        }


        /* =========================
           CONTENT
        ========================= */

        .content {

            padding: 22px 25px 25px;
        }


        /* =========================
           TITLE
        ========================= */

        .title-row {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 20px;
        }


        h1 {

            font-size: 26px;

            color: #0f2747;

            line-height: 1.2;

            word-break: break-word;
        }


        /* =========================
           ITEM TYPE STATUS
        ========================= */

        .status {

            padding: 7px 14px;

            border-radius: 20px;

            font-weight: bold;

            font-size: 12px;

            white-space: nowrap;
        }


        .found {

            background: #dcfce7;

            color: #15803d;
        }


        .lost {

            background: #fee2e2;

            color: #dc2626;
        }


        /* =========================
           INFO GRID
        ========================= */

        .info-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 12px;

            margin-bottom: 15px;
        }


        /* =========================
           INFO BOX
        ========================= */

        .info-box {

            background: #f8fafc;

            padding: 13px 15px;

            border-radius: 9px;

            border: 1px solid #e2e8f0;
        }


        .info-label {

            color: #64748b;

            font-size: 12px;

            margin-bottom: 5px;
        }


        .info-value {

            color: #0f2747;

            font-size: 14px;

            font-weight: 600;

            word-break: break-word;

            line-height: 1.4;
        }


        /* =========================
           DESCRIPTION
        ========================= */

        .description {

            margin-top: 5px;

            padding: 15px;

            background: #f8fbff;

            border-radius: 9px;

            border: 1px solid #e2e8f0;
        }


        .description h3 {

            margin-bottom: 7px;

            color: #0f2747;

            font-size: 16px;
        }


        .description p {

            color: #64748b;

            line-height: 1.5;

            font-size: 13px;

            word-break: break-word;
        }


        /* =========================
           ACTIONS
        ========================= */

        .actions {

            display: flex;

            gap: 12px;

            margin-top: 18px;
        }


        /* =========================
           BUTTON
        ========================= */

        .btn {

            padding: 11px 18px;

            border-radius: 8px;

            text-decoration: none;

            font-weight: bold;

            border: none;

            cursor: pointer;

            font-size: 13px;

            display: inline-block;

            transition: 0.2s;
        }


        .claim {

            background: #2563eb;

            color: white;
        }


        .claim:hover {

            background: #1d4ed8;
        }

        .discussion {

            background: #dbeafe;

            color: #1d4ed8;

        }


        .discussion:hover {

            background: #bfdbfe;

        }



        /* =====================================================
           DARK MODE
        ===================================================== */

        html.dark-mode body {

            background: #0f172a;

            color: #e2e8f0;
        }


        /* =========================
           DARK SIDEBAR
        ========================= */

        html.dark-mode .sidebar {

            background: #020617;

            color: #e2e8f0;
        }


        html.dark-mode .logo {

            color: #f8fafc;
        }


        html.dark-mode .logo span {

            color: #60a5fa;
        }


        html.dark-mode .menu a {

            color: #cbd5e1;
        }


        html.dark-mode .menu a:hover {

            background: #1e293b;

            color: #ffffff;
        }


        html.dark-mode .logout a {

            background: #991b1b;
        }


        html.dark-mode .logout a:hover {

            background: #b91c1c;
        }


        /* =========================
           DARK MAIN
        ========================= */

        html.dark-mode .main {

            background: #0f172a;
        }


        /* =========================
           DARK CARD
        ========================= */

        html.dark-mode .details-card {

            background: #1e293b;

            box-shadow:
                0 6px 25px rgba(0,0,0,0.3);
        }


        /* =========================
           DARK IMAGE
        ========================= */

        html.dark-mode .item-image {

            background: #172554;
        }


        /* =========================
           DARK TITLE
        ========================= */

        html.dark-mode h1 {

            color: #f8fafc;
        }


        /* =========================
           DARK INFO BOX
        ========================= */

        html.dark-mode .info-box {

            background: #0f172a;

            border-color: #334155;
        }


        html.dark-mode .info-label {

            color: #94a3b8;
        }


        html.dark-mode .info-value {

            color: #f8fafc;
        }


        /* =========================
           DARK DESCRIPTION
        ========================= */

        html.dark-mode .description {

            background: #0f172a;

            border-color: #334155;
        }


        html.dark-mode .description h3 {

            color: #f8fafc;
        }


        html.dark-mode .description p {

            color: #94a3b8;
        }


        /* =========================
           DARK FOUND
        ========================= */

        html.dark-mode .found {

            background: #052e16;

            color: #86efac;
        }


        /* =========================
           DARK LOST
        ========================= */

        html.dark-mode .lost {

            background: #450a0a;

            color: #fca5a5;
        }


        /* =========================
           DARK CLAIM BUTTON
        ========================= */

        html.dark-mode .claim {

            background: #2563eb;

            color: white;
        }


        html.dark-mode .claim:hover {

            background: #1d4ed8;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 900px) {

            .main {

                padding: 30px 25px;
            }

            .container {

                max-width: 650px;
            }

        }


        @media (max-width: 700px) {

            .sidebar {

                position: relative;

                width: 100%;

                height: auto;
            }


            .logout {

                position: static;

                margin-top: 20px;
            }


            .main {

                margin-left: 0;

                padding: 25px 20px;
            }


            .container {

                max-width: 100%;
            }


            .item-image {

                height: 180px;
            }


            .content {

                padding: 20px;
            }


            .title-row {

                flex-direction: column;

                align-items: flex-start;
            }


            h1 {

                font-size: 24px;
            }


            .info-grid {

                grid-template-columns: 1fr;

                gap: 10px;
            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">


    <div class="logo">

        🎓 Campus Lost & Found

    </div>


    <nav class="menu">


        <a href="dashboard.php">

            🏠 Dashboard

        </a>


        <a href="lost-items.php">

            🔴 Lost Items

        </a>


        <a href="found-items.php">

            🟢 Found Items

        </a>


        <a href="report-lost.php">

            📢 Report Lost

        </a>


        <a href="report-found.php">

            📦 Report Found

        </a>


        <a href="my-items.php">

            📋 My Items

        </a>


        <a href="my-claims.php">

            🔎 My Claims

        </a>


        <a href="notifications.php">

            🔔 Notifications

        </a>


        <a href="profile.php">

            👤 Profile

        </a>


        <a href="settings.php">

            ⚙️ Settings

        </a>


    </nav>


    <div class="logout">

        <a href="logout.php">

            🚪 Logout

        </a>

    </div>


</aside>



<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<main class="main">


    <div class="container">


        <div class="details-card">


            <!-- =========================
                 ITEM IMAGE
            ========================= -->

            <div class="item-image">


                <?php if (!empty($item["image"]) && file_exists("uploads/" . $item["image"])): ?>


                    <img
                        src="uploads/<?php echo htmlspecialchars($item["image"]); ?>"
                        alt="<?php echo htmlspecialchars($item["item_name"]); ?>"
                    >


                <?php else: ?>


                    <div class="no-image">

                        📦

                    </div>


                <?php endif; ?>


            </div>



            <!-- =========================
                 CONTENT
            ========================= -->

            <div class="content">


                <!-- TITLE + FOUND / LOST -->

                <div class="title-row">


                    <h1>

                        <?php
                        echo htmlspecialchars($item["item_name"]);
                        ?>

                    </h1>


                    <span
                        class="status <?php echo strtolower($item["item_type"]); ?>"
                    >


                        <?php if ($item["item_type"] == "Found"): ?>


                            🟢 FOUND


                        <?php else: ?>


                            🔴 LOST


                        <?php endif; ?>


                    </span>


                </div>



                <!-- =========================
                     INFO GRID
                ========================= -->

                <div class="info-grid">


                    <!-- CATEGORY -->

                    <div class="info-box">


                        <div class="info-label">

                            📁 Category

                        </div>


                        <div class="info-value">

                            <?php
                            echo htmlspecialchars($item["category"]);
                            ?>

                        </div>


                    </div>



                    <!-- LOCATION -->

                    <div class="info-box">


                        <div class="info-label">

                            📍 Location

                        </div>


                        <div class="info-value">

                            <?php
                            echo htmlspecialchars($item["location"]);
                            ?>

                        </div>


                    </div>



                    <!-- DATE -->

                    <div class="info-box">


                        <div class="info-label">

                            📅 Date

                        </div>


                        <div class="info-value">

                            <?php
                            echo htmlspecialchars($item["date_reported"]);
                            ?>

                        </div>


                    </div>



                    <!-- STATUS -->

                    <div class="info-box">


                        <div class="info-label">

                            📌 Status

                        </div>


                        <div class="info-value">

                            <?php
                            echo htmlspecialchars($item["status"]);
                            ?>

                        </div>


                    </div>


                </div>



                <!-- =========================
                     DESCRIPTION
                ========================= -->

                <div class="description">


                    <h3>

                        📝 Description

                    </h3>


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



                <!-- =========================
                     CLAIM BUTTON
                ========================= -->

                <div class="actions">
                    <a href="item-discussion.php?id=<?php echo $item["item_id"]; ?>" class="btn discussion">
                        💬 Discussion
                    </a>
                    
                    <a href="claim.php?id=<?php echo $item["item_id"]; ?>" class="btn claim">
                        🔎 Claim This Item
                    </a>
                </div>


            </div>


        </div>


    </div>


</main>


</body>

</html>