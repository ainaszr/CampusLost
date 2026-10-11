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
   GET USER'S CLAIMS
========================================================= */

$sql = "SELECT
            c.claim_id,
            c.claim_description,
            c.claim_date,
            c.claim_status,
            i.item_id,
            i.item_name,
            i.category,
            i.location,
            i.date_reported,
            i.image,
            i.item_type
        FROM claims c
        INNER JOIN items i
            ON c.item_id = i.item_id
        WHERE c.user_id = ?
        ORDER BY c.claim_id DESC";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die("SQL Error: " . $conn->error);

}


$stmt->bind_param("i", $user_id);

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


<!-- LOAD THEME EARLY -->

<script src="theme.js?v=2"></script>


<title>
    My Claims - CampusLost
</title>


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

    font-family: Arial, sans-serif;

    background: #f5f7fb;

    color: #172033;

    min-height: 100vh;

    transition: .3s ease;

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

    background: #071a38;

    color: white;

    padding: 28px 14px 20px;

    display: flex;

    flex-direction: column;

    z-index: 1000;

    box-shadow:
        5px 0 25px rgba(0,0,0,.08);

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

    background:
        rgba(37,99,235,.18);

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

    background:
        rgba(37,99,235,.14);

    color: white;

    transform:
        translateX(2px);

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
        0 7px 18px
        rgba(37,99,235,.25);

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

    background:
        rgba(239,68,68,.14);

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


/* =========================================================
   MAIN
========================================================= */

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


/* =========================================================
   HEADER
========================================================= */

.header {

    display: flex;

    align-items: flex-end;

    justify-content: space-between;

    gap: 20px;

    margin-bottom: 25px;

}


.page-label {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    color: #2563eb;

    font-size: 10px;

    font-weight: 800;

    letter-spacing: 1px;

    text-transform: uppercase;

    margin-bottom: 7px;

}


.header h1 {

    color: #102342;

    font-size: 29px;

    margin-bottom: 7px;

}


.header p {

    color: #748199;

    font-size: 13px;

}


.browse-btn {

    min-height: 40px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    padding: 0 14px;

    background: #2563eb;

    color: white;

    text-decoration: none;

    border-radius: 9px;

    font-size: 10px;

    font-weight: 700;

    box-shadow:
        0 5px 14px
        rgba(37,99,235,.17);

    transition: .2s;

}


.browse-btn:hover {

    background: #1d4ed8;

    transform:
        translateY(-1px);

}


/* =========================================================
   SUMMARY
========================================================= */

.summary-bar {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    padding: 13px 15px;

    margin-bottom: 20px;

    background: white;

    border:
        1px solid #e8edf5;

    border-radius: 12px;

    color: #64748b;

    font-size: 10px;

}


.summary-left {

    display: flex;

    align-items: center;

    gap: 8px;

}


.summary-left i {

    color: #2563eb;

}


.summary-count {

    color: #102342;

    font-weight: 800;

}


/* =========================================================
   CLAIM GRID
========================================================= */

.claims-grid {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0,1fr));

    gap: 18px;

}


/* =========================================================
   CLAIM CARD
========================================================= */

.claim-card {

    background: white;

    border:
        1px solid #e8edf5;

    border-radius: 16px;

    overflow: hidden;

    box-shadow:
        0 5px 20px
        rgba(15,39,71,.045);

    transition: .2s ease;

}


.claim-card:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 9px 25px
        rgba(15,39,71,.08);

}


/* =========================================================
   IMAGE
========================================================= */

.item-image {

    position: relative;

    width: 100%;

    height: 210px;

    background: #edf1f6;

    overflow: hidden;

}


.item-image img {

    width: 100%;

    height: 100%;

    object-fit: cover;

    display: block;

    transition:
        transform .35s ease;

}


.claim-card:hover
.item-image img {

    transform:
        scale(1.02);

}


.no-image {

    width: 100%;

    height: 100%;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #94a3b8;

    font-size: 40px;

}


/* =========================================================
   IMAGE BADGES
========================================================= */

.item-type {

    position: absolute;

    top: 13px;

    left: 13px;

    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 6px 9px;

    border-radius: 20px;

    background:
        rgba(5,78,38,.88);

    color: #dcfce7;

    font-size: 8px;

    font-weight: 800;

    text-transform: uppercase;

    backdrop-filter: blur(6px);

}


.claim-id {

    position: absolute;

    top: 13px;

    right: 13px;

    padding: 6px 9px;

    border-radius: 20px;

    background:
        rgba(15,23,42,.75);

    color: white;

    font-size: 8px;

    font-weight: 700;

    backdrop-filter: blur(6px);

}


/* =========================================================
   CONTENT
========================================================= */

.content {

    padding: 20px;

}


.title-row {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 12px;

    margin-bottom: 14px;

}


.title-row h2 {

    color: #102342;

    font-size: 19px;

    line-height: 1.3;

    text-transform: capitalize;

    word-break: break-word;

}


/* =========================================================
   STATUS
========================================================= */

.status {

    flex-shrink: 0;

    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 6px 9px;

    border-radius: 20px;

    font-size: 8px;

    font-weight: 800;

    letter-spacing: .2px;

    text-transform: uppercase;

}


.status i {

    font-size: 6px;

}


.status.pending {

    background: #fef3c7;

    color: #b45309;

}


.status.approved {

    background: #dcfce7;

    color: #15803d;

}


.status.rejected {

    background: #fee2e2;

    color: #dc2626;

}


.status.other {

    background: #e2e8f0;

    color: #475569;

}


/* =========================================================
   ITEM INFO
========================================================= */

.claim-info {

    display: grid;

    grid-template-columns:
        repeat(3, minmax(0,1fr));

    gap: 8px;

    margin-bottom: 14px;

}


.info-box {

    padding: 10px;

    background: #f8fafc;

    border:
        1px solid #e2e8f0;

    border-radius: 9px;

}


.info-label {

    display: flex;

    align-items: center;

    gap: 5px;

    color: #8a97aa;

    font-size: 8px;

    margin-bottom: 5px;

}


.info-label i {

    color: #2563eb;

}


.info-value {

    color: #334155;

    font-size: 10px;

    font-weight: 700;

    line-height: 1.4;

    word-break: break-word;

}


/* =========================================================
   CLAIM DESCRIPTION
========================================================= */

.claim-description {

    padding: 13px;

    background: #f8fafc;

    border:
        1px solid #e2e8f0;

    border-radius: 10px;

    margin-bottom: 13px;

}


.claim-description strong {

    display: flex;

    align-items: center;

    gap: 6px;

    color: #334155;

    font-size: 10px;

    margin-bottom: 6px;

}


.claim-description strong i {

    color: #2563eb;

}


.claim-description p {

    color: #64748b;

    font-size: 10px;

    line-height: 1.6;

    word-break: break-word;

}


/* =========================================================
   DATE
========================================================= */

.date {

    display: flex;

    align-items: center;

    gap: 6px;

    color: #94a3b8;

    font-size: 9px;

    margin-bottom: 14px;

}


/* =========================================================
   STATUS MESSAGE
========================================================= */

.status-message {

    display: flex;

    align-items: flex-start;

    gap: 8px;

    padding: 10px 11px;

    margin-bottom: 14px;

    border-radius: 9px;

    font-size: 9px;

    line-height: 1.5;

}


.status-message.pending {

    background: #fffbeb;

    color: #92400e;

    border:
        1px solid #fde68a;

}


.status-message.approved {

    background: #f0fdf4;

    color: #166534;

    border:
        1px solid #bbf7d0;

}


.status-message.rejected {

    background: #fef2f2;

    color: #991b1b;

    border:
        1px solid #fecaca;

}


/* =========================================================
   VIEW BUTTON
========================================================= */

.view-btn {

    width: 100%;

    min-height: 38px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 8px;

    padding: 0 12px;

    background: #eef4ff;

    color: #2563eb;

    text-decoration: none;

    border:
        1px solid #dbeafe;

    border-radius: 9px;

    font-size: 10px;

    font-weight: 700;

    transition: .2s;

}


.view-btn:hover {

    background: #dbeafe;

}


/* =========================================================
   EMPTY STATE
========================================================= */

.empty {

    background: white;

    border:
        1px solid #e8edf5;

    padding: 65px 30px;

    text-align: center;

    border-radius: 16px;

    box-shadow:
        0 5px 20px
        rgba(15,39,71,.045);

}


.empty-icon {

    width: 62px;

    height: 62px;

    display: flex;

    align-items: center;

    justify-content: center;

    margin: 0 auto 14px;

    background: #eef4ff;

    color: #2563eb;

    border-radius: 17px;

    font-size: 23px;

}


.empty h2 {

    color: #102342;

    font-size: 18px;

    margin-bottom: 7px;

}


.empty p {

    color: #748199;

    font-size: 11px;

    margin-bottom: 18px;

}


.search-btn {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    background: #2563eb;

    color: white;

    padding: 11px 15px;

    border-radius: 9px;

    text-decoration: none;

    font-size: 10px;

    font-weight: 700;

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


html.dark-mode .header h1 {

    color: #f8fafc;

}


html.dark-mode .header p {

    color: #94a3b8;

}


html.dark-mode .summary-bar {

    background: #1e293b;

    border-color: #27364c;

    color: #94a3b8;

}


html.dark-mode .summary-count {

    color: #f8fafc;

}


html.dark-mode .claim-card {

    background: #1e293b;

    border-color: #27364c;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.18);

}


html.dark-mode .claim-card:hover {

    box-shadow:
        0 9px 25px
        rgba(0,0,0,.28);

}


html.dark-mode .item-image {

    background: #263449;

}


html.dark-mode .title-row h2 {

    color: #f8fafc;

}


html.dark-mode .status.pending {

    background: #451a03;

    color: #fcd34d;

}


html.dark-mode .status.approved {

    background: #052e16;

    color: #86efac;

}


html.dark-mode .status.rejected {

    background: #450a0a;

    color: #fca5a5;

}


html.dark-mode .status.other {

    background: #334155;

    color: #cbd5e1;

}


html.dark-mode .info-box,
html.dark-mode .claim-description {

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

    color: #e2e8f0;

}


html.dark-mode .claim-description strong {

    color: #e2e8f0;

}


html.dark-mode .claim-description p {

    color: #94a3b8;

}


html.dark-mode .date {

    color: #94a3b8;

}


html.dark-mode .status-message.pending {

    background: #422006;

    color: #fde68a;

    border-color: #78350f;

}


html.dark-mode .status-message.approved {

    background: #052e16;

    color: #bbf7d0;

    border-color: #166534;

}


html.dark-mode .status-message.rejected {

    background: #450a0a;

    color: #fecaca;

    border-color: #7f1d1d;

}


html.dark-mode .view-btn {

    background: #172b4d;

    color: #93c5fd;

    border-color: #29466f;

}


html.dark-mode .view-btn:hover {

    background: #1e3a5f;

}


html.dark-mode .empty {

    background: #1e293b;

    color: #e2e8f0;

    border-color: #27364c;

}


html.dark-mode .empty-icon {

    background: #172b4d;

    color: #60a5fa;

}


html.dark-mode .empty h2 {

    color: #f8fafc;

}


html.dark-mode .empty p {

    color: #94a3b8;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1000px) {

    .claims-grid {

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


    .claim-info {

        grid-template-columns: 1fr;

    }


    .title-row {

        flex-direction: column;

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


        <a
            href="my-claims.php"
            class="active"
        >

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

                <i class="fa-solid fa-file-circle-check"></i>

                Claim Tracking

            </span>


            <h1>

                My Claims

            </h1>


            <p>

                View and track the status of items
                you have claimed.

            </p>


        </div>



        <a
            href="found-items.php"
            class="browse-btn"
        >

            <i class="fa-solid fa-magnifying-glass"></i>

            Browse Found Items

        </a>


    </div>



    <?php if ($result->num_rows > 0): ?>


        <!-- =================================================
             SUMMARY
        ================================================== -->

        <div class="summary-bar">


            <div class="summary-left">

                <i class="fa-solid fa-layer-group"></i>

                <span>

                    Showing

                    <span class="summary-count">

                        <?php
                            echo $result->num_rows;
                        ?>

                    </span>

                    claim<?php
                        echo
                            $result->num_rows == 1
                            ? ""
                            : "s";
                    ?>

                </span>

            </div>


            <span>

                Latest claims shown first

            </span>


        </div>



        <!-- =================================================
             CLAIM GRID
        ================================================== -->

        <div class="claims-grid">


            <?php while (
                $claim =
                    $result->fetch_assoc()
            ): ?>


                <?php

                    $status =
                        strtolower(
                            trim(
                                $claim[
                                    "claim_status"
                                ]
                            )
                        );


                    if (
                        !in_array(
                            $status,
                            [
                                "pending",
                                "approved",
                                "rejected"
                            ]
                        )
                    ) {

                        $status_class = "other";

                    } else {

                        $status_class = $status;

                    }

                ?>


                <article class="claim-card">


                    <!-- =====================================
                         IMAGE
                    ====================================== -->

                    <div class="item-image">


                        <?php if (
                            !empty($claim["image"]) &&
                            file_exists(
                                "uploads/"
                                . $claim["image"]
                            )
                        ): ?>


                            <img
                                src="uploads/<?php
                                    echo htmlspecialchars(
                                        $claim[
                                            "image"
                                        ]
                                    );
                                ?>"

                                alt="<?php
                                    echo htmlspecialchars(
                                        $claim[
                                            "item_name"
                                        ]
                                    );
                                ?>"
                            >


                        <?php else: ?>


                            <div class="no-image">

                                <i class="fa-regular fa-image"></i>

                            </div>


                        <?php endif; ?>



                        <span class="item-type">

                            <i class="fa-solid fa-circle"></i>

                            <?php
                                echo htmlspecialchars(
                                    $claim[
                                        "item_type"
                                    ]
                                );
                            ?>

                        </span>



                        <span class="claim-id">

                            Claim #<?php
                                echo intval(
                                    $claim[
                                        "claim_id"
                                    ]
                                );
                            ?>

                        </span>


                    </div>



                    <!-- =====================================
                         CONTENT
                    ====================================== -->

                    <div class="content">


                        <!-- TITLE + STATUS -->

                        <div class="title-row">


                            <h2>

                                <?php
                                    echo htmlspecialchars(
                                        $claim[
                                            "item_name"
                                        ]
                                    );
                                ?>

                            </h2>



                            <span
                                class="status <?php
                                    echo $status_class;
                                ?>"
                            >


                                <?php if (
                                    $status === "pending"
                                ): ?>


                                    <i class="fa-solid fa-circle"></i>

                                    Pending


                                <?php elseif (
                                    $status === "approved"
                                ): ?>


                                    <i class="fa-solid fa-circle-check"></i>

                                    Approved


                                <?php elseif (
                                    $status === "rejected"
                                ): ?>


                                    <i class="fa-solid fa-circle-xmark"></i>

                                    Rejected


                                <?php else: ?>


                                    <?php
                                        echo htmlspecialchars(
                                            $claim[
                                                "claim_status"
                                            ]
                                        );
                                    ?>


                                <?php endif; ?>


                            </span>


                        </div>



                        <!-- =================================
                             ITEM INFORMATION
                        ================================== -->

                        <div class="claim-info">


                            <div class="info-box">


                                <div class="info-label">

                                    <i class="fa-solid fa-folder"></i>

                                    Category

                                </div>


                                <div class="info-value">

                                    <?php
                                        echo htmlspecialchars(
                                            $claim[
                                                "category"
                                            ]
                                        );
                                    ?>

                                </div>


                            </div>



                            <div class="info-box">


                                <div class="info-label">

                                    <i class="fa-solid fa-location-dot"></i>

                                    Location

                                </div>


                                <div class="info-value">

                                    <?php
                                        echo htmlspecialchars(
                                            $claim[
                                                "location"
                                            ]
                                        );
                                    ?>

                                </div>


                            </div>



                            <div class="info-box">


                                <div class="info-label">

                                    <i class="fa-regular fa-calendar"></i>

                                    Found Date

                                </div>


                                <div class="info-value">

                                    <?php
                                        echo htmlspecialchars(
                                            $claim[
                                                "date_reported"
                                            ]
                                        );
                                    ?>

                                </div>


                            </div>


                        </div>



                        <!-- =================================
                             CLAIM DESCRIPTION
                        ================================== -->

                        <div class="claim-description">


                            <strong>

                                <i class="fa-solid fa-align-left"></i>

                                Your Claim

                            </strong>


                            <p>

                                <?php

                                    echo nl2br(
                                        htmlspecialchars(
                                            $claim[
                                                "claim_description"
                                            ]
                                        )
                                    );

                                ?>

                            </p>


                        </div>



                        <!-- =================================
                             SUBMISSION DATE
                        ================================== -->

                        <div class="date">

                            <i class="fa-regular fa-clock"></i>

                            Submitted

                            <?php
                                echo htmlspecialchars(
                                    $claim[
                                        "claim_date"
                                    ]
                                );
                            ?>

                        </div>



                        <!-- =================================
                             STATUS MESSAGE
                        ================================== -->

                        <?php if (
                            $status === "pending"
                        ): ?>


                            <div class="status-message pending">

                                <i class="fa-regular fa-clock"></i>

                                <span>

                                    Your claim is waiting
                                    for review.

                                </span>

                            </div>


                        <?php elseif (
                            $status === "approved"
                        ): ?>


                            <div class="status-message approved">

                                <i class="fa-solid fa-circle-check"></i>

                                <span>

                                    Your claim has been approved.

                                </span>

                            </div>


                        <?php elseif (
                            $status === "rejected"
                        ): ?>


                            <div class="status-message rejected">

                                <i class="fa-solid fa-circle-xmark"></i>

                                <span>

                                    Your claim was not approved.

                                </span>

                            </div>


                        <?php endif; ?>



                        <!-- =================================
                             VIEW ITEM
                        ================================== -->

                        <a
                            href="item-details.php?id=<?php
                                echo intval(
                                    $claim[
                                        "item_id"
                                    ]
                                );
                            ?>"
                            class="view-btn"
                        >

                            <span>

                                <i class="fa-regular fa-eye"></i>

                                View Item Details

                            </span>


                            <i class="fa-solid fa-arrow-right"></i>

                        </a>


                    </div>


                </article>


            <?php endwhile; ?>


        </div>


    <?php else: ?>


        <!-- =================================================
             EMPTY STATE
        ================================================== -->

        <div class="empty">


            <div class="empty-icon">

                <i class="fa-solid fa-magnifying-glass"></i>

            </div>


            <h2>

                No Claims Yet

            </h2>


            <p>

                You haven't submitted any item claims yet.

            </p>


            <a
                href="found-items.php"
                class="search-btn"
            >

                <i class="fa-solid fa-magnifying-glass"></i>

                Browse Found Items

            </a>


        </div>


    <?php endif; ?>


</div>


</main>


</body>

</html>