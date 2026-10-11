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


/* =========================================================
   CHECK ITEM ID
========================================================= */

if (!isset($_GET["id"])) {

    header("Location: found-items.php");
    exit();

}


$item_id = intval($_GET["id"]);
$user_id = intval($_SESSION["user_id"]);


if ($item_id <= 0) {

    header("Location: found-items.php");
    exit();

}


/* =========================================================
   GET ITEM
========================================================= */

$sql = "SELECT
            item_id,
            user_id,
            item_name,
            category,
            description,
            location,
            date_reported,
            image,
            item_type,
            status
        FROM items
        WHERE item_id = ?";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die("SQL Error: " . $conn->error);

}


$stmt->bind_param("i", $item_id);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows === 0) {

    echo "Item not found.";
    exit();

}


$item = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   CHECK OWNER
========================================================= */

$is_owner =
    intval($item["user_id"]) === $user_id;


/* =========================================================
   ONLY FOUND ITEMS CAN BE CLAIMED
========================================================= */

if ($item["item_type"] !== "Found") {

    header(
        "Location: item-details.php?id="
        . $item_id
    );

    exit();

}


/* =========================================================
   OWNER CANNOT CLAIM OWN ITEM
========================================================= */

if ($is_owner) {

    header(
        "Location: item-details.php?id="
        . $item_id
    );

    exit();

}


/* =========================================================
   CHECK EXISTING CLAIM
========================================================= */

$already_claimed = false;


$existing_sql = "SELECT claim_id
                 FROM claims
                 WHERE item_id = ?
                 AND user_id = ?
                 LIMIT 1";


$existing_stmt =
    $conn->prepare($existing_sql);


if ($existing_stmt) {

    $existing_stmt->bind_param(
        "ii",
        $item_id,
        $user_id
    );

    $existing_stmt->execute();

    $existing_result =
        $existing_stmt->get_result();


    if ($existing_result->num_rows > 0) {

        $already_claimed = true;

    }


    $existing_stmt->close();

}


/* =========================================================
   SUBMIT CLAIM
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {


    /* Prevent duplicate submission */

    if ($already_claimed) {

        $error =
            "You have already submitted a claim for this item.";

    } else {


        $claim_description =
            isset($_POST["claim_description"])
            ? trim($_POST["claim_description"])
            : "";


        $claim_date = date("Y-m-d");


        if ($claim_description === "") {


            $error =
                "Please explain why you believe this item belongs to you.";


        } else {


            /* Limit description length */

            if (
                strlen($claim_description) > 1500
            ) {

                $claim_description =
                    substr(
                        $claim_description,
                        0,
                        1500
                    );

            }


            $claim_status = "Pending";


            $insert_sql =
                "INSERT INTO claims
                (
                    item_id,
                    user_id,
                    claim_description,
                    claim_date,
                    claim_status
                )
                VALUES (?, ?, ?, ?, ?)";


            $insert_stmt =
                $conn->prepare($insert_sql);


            if (!$insert_stmt) {

                $error =
                    "Unable to prepare claim submission.";

            } else {


                $insert_stmt->bind_param(
                    "iisss",
                    $item_id,
                    $user_id,
                    $claim_description,
                    $claim_date,
                    $claim_status
                );


                if ($insert_stmt->execute()) {


                    /* =========================================
                       EMAIL ADMIN
                    ========================================= */

                    $claimant_name =
                        isset($_SESSION["name"])
                        ? $_SESSION["name"]
                        : "A student";


                    campuslost_email_admins(
                        $conn,
                        "CampusLost - New Claim Submitted",
                        $claimant_name
                        . " submitted a claim for: "
                        . $item["item_name"]
                        . "."
                    );


                    header(
                        "Location: my-claims.php"
                    );

                    exit();


                } else {


                    $error =
                        "Failed to submit claim.";


                }


                $insert_stmt->close();

            }

        }

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


<!-- LOAD THEME EARLY -->

<script src="theme.js"></script>


<title>
    Claim Item - CampusLost
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

    justify-content: center;

    align-items: center;

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

    max-width: 900px;

    margin: 0 auto;

}


/* =========================================================
   BACK
========================================================= */

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

    transform:
        translateX(-2px);

}


/* =========================================================
   HEADER
========================================================= */

.page-header {

    margin-bottom: 22px;

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


.page-header h1 {

    color: #102342;

    font-size: 28px;

    margin-bottom: 7px;

}


.page-header p {

    color: #748199;

    font-size: 13px;

}


/* =========================================================
   CARD
========================================================= */

.claim-card {

    background: white;

    border:
        1px solid #e8edf5;

    border-radius: 18px;

    padding: 28px;

    box-shadow:
        0 7px 25px
        rgba(15,39,71,.055);

}


/* =========================================================
   ITEM PREVIEW
========================================================= */

.item-preview {

    display: grid;

    grid-template-columns:
        155px 1fr;

    gap: 20px;

    padding: 15px;

    background: #f8fafc;

    border:
        1px solid #e2e8f0;

    border-radius: 13px;

    margin-bottom: 20px;

}


.preview-image {

    width: 155px;

    height: 120px;

    position: relative;

    overflow: hidden;

    background: #edf1f6;

    border-radius: 10px;

}


.preview-image img {

    width: 100%;

    height: 100%;

    object-fit: cover;

    display: block;

}


.no-image {

    width: 100%;

    height: 100%;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #94a3b8;

    font-size: 30px;

}


.found-badge {

    position: absolute;

    left: 8px;

    top: 8px;

    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 5px 8px;

    background:
        rgba(5,78,38,.88);

    color: #dcfce7;

    border-radius: 20px;

    font-size: 8px;

    font-weight: 800;

    text-transform: uppercase;

    backdrop-filter: blur(5px);

}


.item-info {

    display: flex;

    flex-direction: column;

    justify-content: center;

}


.item-info h2 {

    color: #102342;

    font-size: 19px;

    margin-bottom: 11px;

    text-transform: capitalize;

}


.preview-details {

    display: flex;

    flex-wrap: wrap;

    gap: 8px 18px;

}


.preview-detail {

    display: flex;

    align-items: center;

    gap: 6px;

    color: #64748b;

    font-size: 11px;

}


.preview-detail i {

    width: 12px;

    text-align: center;

    color: #2563eb;

}


/* =========================================================
   WARNING
========================================================= */

.warning {

    display: flex;

    gap: 11px;

    padding: 14px 15px;

    margin-bottom: 22px;

    background: #fff8ed;

    border:
        1px solid #fed7aa;

    border-radius: 11px;

    color: #9a5415;

    font-size: 11px;

    line-height: 1.55;

}


.warning-icon {

    width: 28px;

    height: 28px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #ffedd5;

    border-radius: 8px;

    color: #ea580c;

}


/* =========================================================
   ERROR
========================================================= */

.error {

    display: flex;

    align-items: flex-start;

    gap: 9px;

    padding: 13px 14px;

    margin-bottom: 18px;

    background: #fef2f2;

    border:
        1px solid #fecaca;

    color: #b91c1c;

    border-radius: 10px;

    font-size: 11px;

    line-height: 1.5;

}


/* =========================================================
   ALREADY CLAIMED
========================================================= */

.already-claimed {

    text-align: center;

    padding: 35px 20px;

}


.claimed-icon {

    width: 58px;

    height: 58px;

    display: flex;

    align-items: center;

    justify-content: center;

    margin: 0 auto 14px;

    border-radius: 16px;

    background: #eff6ff;

    color: #2563eb;

    font-size: 22px;

}


.already-claimed h3 {

    color: #102342;

    font-size: 17px;

    margin-bottom: 7px;

}


.already-claimed p {

    color: #748199;

    font-size: 11px;

    line-height: 1.6;

    max-width: 430px;

    margin: 0 auto 18px;

}


/* =========================================================
   FORM
========================================================= */

.form-group {

    margin-top: 5px;

}


.form-group label {

    display: block;

    color: #334155;

    font-size: 12px;

    font-weight: 800;

    margin-bottom: 8px;

}


.required {

    color: #ef4444;

}


textarea {

    width: 100%;

    min-height: 155px;

    padding: 13px 14px;

    border:
        1px solid #d8e0eb;

    border-radius: 10px;

    resize: vertical;

    outline: none;

    font-family: Arial, sans-serif;

    font-size: 12px;

    line-height: 1.6;

    color: #334155;

    background: white;

    transition: .2s;

}


textarea::placeholder {

    color: #a3adbb;

}


textarea:focus {

    border-color: #2563eb;

    box-shadow:
        0 0 0 3px
        rgba(37,99,235,.08);

}


.form-help {

    display: flex;

    align-items: flex-start;

    gap: 6px;

    color: #8a97aa;

    font-size: 9px;

    line-height: 1.5;

    margin-top: 7px;

}


/* =========================================================
   BUTTONS
========================================================= */

.buttons {

    display: flex;

    align-items: center;

    justify-content: flex-end;

    gap: 9px;

    padding-top: 21px;

    margin-top: 21px;

    border-top:
        1px solid #edf1f5;

}


.btn {

    min-height: 40px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    padding: 0 15px;

    border-radius: 9px;

    text-decoration: none;

    border: none;

    cursor: pointer;

    font-size: 10px;

    font-weight: 700;

    font-family: Arial, sans-serif;

    transition: .2s;

}


.cancel {

    background: #f1f5f9;

    color: #475569;

    border:
        1px solid #e2e8f0;

}


.cancel:hover {

    background: #e2e8f0;

}


.submit {

    background: #2563eb;

    color: white;

    box-shadow:
        0 5px 14px
        rgba(37,99,235,.18);

}


.submit:hover {

    background: #1d4ed8;

    transform:
        translateY(-1px);

}


.my-claims {

    background: #2563eb;

    color: white;

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


html.dark-mode .back-link {

    color: #94a3b8;

}


html.dark-mode .back-link:hover {

    color: #60a5fa;

}


html.dark-mode .page-header h1 {

    color: #f8fafc;

}


html.dark-mode .page-header p {

    color: #94a3b8;

}


html.dark-mode .claim-card {

    background: #1e293b;

    border-color: #27364c;

    box-shadow:
        0 7px 25px
        rgba(0,0,0,.20);

}


html.dark-mode .item-preview {

    background: #111b2f;

    border-color: #334155;

}


html.dark-mode .preview-image {

    background: #263449;

}


html.dark-mode .item-info h2 {

    color: #f8fafc;

}


html.dark-mode .preview-detail {

    color: #94a3b8;

}


html.dark-mode .preview-detail i {

    color: #60a5fa;

}


html.dark-mode .warning {

    background: #431c08;

    border-color: #7c2d12;

    color: #fed7aa;

}


html.dark-mode .warning-icon {

    background: #7c2d12;

    color: #fdba74;

}


html.dark-mode .error {

    background: #450a0a;

    border-color: #7f1d1d;

    color: #fecaca;

}


html.dark-mode .claimed-icon {

    background: #172b4d;

    color: #60a5fa;

}


html.dark-mode .already-claimed h3 {

    color: #f8fafc;

}


html.dark-mode .already-claimed p {

    color: #94a3b8;

}


html.dark-mode .form-group label {

    color: #e2e8f0;

}


html.dark-mode textarea {

    background: #111b2f;

    color: #e2e8f0;

    border-color: #475569;

}


html.dark-mode textarea::placeholder {

    color: #64748b;

}


html.dark-mode .form-help {

    color: #94a3b8;

}


html.dark-mode .buttons {

    border-color: #334155;

}


html.dark-mode .cancel {

    background: #334155;

    color: #e2e8f0;

    border-color: #475569;

}


html.dark-mode .cancel:hover {

    background: #475569;

}


/* =========================================================
   RESPONSIVE
========================================================= */

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


    .claim-card {

        padding: 20px;

    }


    .item-preview {

        grid-template-columns: 1fr;

    }


    .preview-image {

        width: 100%;

        height: 190px;

    }


    .buttons {

        flex-direction: column-reverse;

    }


    .btn {

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


        <a href="dashboard.php">

            <i class="fa-solid fa-house"></i>

            Dashboard

        </a>


        <a href="lost-items.php">

            <i class="fa-solid fa-magnifying-glass"></i>

            Lost Items

        </a>


        <a
            href="found-items.php"
            class="active"
        >

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



<!-- =========================================================
     MAIN
========================================================= -->

<main class="main">


<div class="container">


    <!-- BACK -->

    <a
        href="item-details.php?id=<?php echo $item_id; ?>"
        class="back-link"
    >

        <i class="fa-solid fa-arrow-left"></i>

        Back to Item

    </a>



    <!-- HEADER -->

    <div class="page-header">


        <span class="page-label">

            <i class="fa-solid fa-hand"></i>

            Ownership Request

        </span>


        <h1>

            Claim This Item

        </h1>


        <p>

            Provide identifying details to help verify
            that this item belongs to you.

        </p>


    </div>



    <div class="claim-card">


        <!-- =================================================
             ERROR
        ================================================== -->

        <?php if (isset($error)): ?>


            <div class="error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <span>

                    <?php
                        echo htmlspecialchars(
                            $error
                        );
                    ?>

                </span>

            </div>


        <?php endif; ?>



        <!-- =================================================
             ITEM PREVIEW
        ================================================== -->

        <div class="item-preview">


            <div class="preview-image">


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



                <span class="found-badge">

                    <i class="fa-solid fa-circle"></i>

                    Found

                </span>


            </div>



            <div class="item-info">


                <h2>

                    <?php

                        echo htmlspecialchars(
                            $item["item_name"]
                        );

                    ?>

                </h2>



                <div class="preview-details">


                    <span class="preview-detail">

                        <i class="fa-solid fa-folder"></i>

                        <?php
                            echo htmlspecialchars(
                                $item["category"]
                            );
                        ?>

                    </span>



                    <span class="preview-detail">

                        <i class="fa-solid fa-location-dot"></i>

                        <?php
                            echo htmlspecialchars(
                                $item["location"]
                            );
                        ?>

                    </span>



                    <span class="preview-detail">

                        <i class="fa-regular fa-calendar"></i>

                        <?php
                            echo htmlspecialchars(
                                $item["date_reported"]
                            );
                        ?>

                    </span>


                </div>


            </div>


        </div>



        <!-- =================================================
             ALREADY CLAIMED
        ================================================== -->

        <?php if ($already_claimed): ?>


            <div class="already-claimed">


                <div class="claimed-icon">

                    <i class="fa-solid fa-file-circle-check"></i>

                </div>


                <h3>

                    Claim already submitted

                </h3>


                <p>

                    You have already submitted a claim
                    for this item. You can check the
                    current status from My Claims.

                </p>


                <a
                    href="my-claims.php"
                    class="btn my-claims"
                >

                    <i class="fa-solid fa-arrow-right"></i>

                    View My Claims

                </a>


            </div>


        <?php else: ?>


            <!-- =============================================
                 WARNING
            ============================================== -->

            <div class="warning">


                <div class="warning-icon">

                    <i class="fa-solid fa-triangle-exclamation"></i>

                </div>


                <div>

                    <strong>
                        Important
                    </strong>

                    <br>

                    Please provide accurate information.
                    Your claim will be reviewed before
                    the item is returned to you.

                </div>


            </div>



            <!-- =============================================
                 CLAIM FORM
            ============================================== -->

            <form method="POST">


                <div class="form-group">


                    <label for="claim_description">

                        Why do you believe this item is yours?

                        <span class="required">
                            *
                        </span>

                    </label>


                    <textarea
                        id="claim_description"
                        name="claim_description"
                        maxlength="1500"
                        placeholder="Example: The case is green with a butterfly decoration. I lost it near the computer lab..."
                        required
                    ><?php
                        if (
                            isset($_POST["claim_description"])
                        ) {
                            echo htmlspecialchars(
                                $_POST[
                                    "claim_description"
                                ]
                            );
                        }
                    ?></textarea>


                    <div class="form-help">

                        <i class="fa-solid fa-circle-info"></i>

                        Include identifying details that
                        only the real owner would know.
                        Avoid sharing passwords or other
                        sensitive information.

                    </div>


                </div>



                <div class="buttons">


                    <a
                        href="item-details.php?id=<?php
                            echo $item_id;
                        ?>"
                        class="btn cancel"
                    >

                        Cancel

                    </a>


                    <button
                        type="submit"
                        class="btn submit"
                    >

                        <i class="fa-solid fa-paper-plane"></i>

                        Submit Claim

                    </button>


                </div>


            </form>


        <?php endif; ?>


    </div>


</div>


</main>


</body>

</html>