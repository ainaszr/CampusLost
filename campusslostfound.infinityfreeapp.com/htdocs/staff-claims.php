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

$staff_id = $_SESSION["user_id"];


/* =========================================================
   GET STAFF INFORMATION
   ========================================================= */

$sql = "SELECT user_id, name, role
        FROM users
        WHERE user_id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("i", $staff_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("Staff account not found.");
}

$staff = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   STAFF ACCESS CHECK
   ========================================================= */

if (strtolower(trim($staff["role"])) !== "staff") {

    if (strtolower(trim($staff["role"])) === "admin") {
        header("Location: admin-dashboard.php");
    } else {
        header("Location: dashboard.php");
    }

    exit();
}


/* =========================================================
   APPROVE / REJECT CLAIM
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $claim_id = isset($_POST["claim_id"])
        ? intval($_POST["claim_id"])
        : 0;

    $action = isset($_POST["action"])
        ? $_POST["action"]
        : "";


    if (
        $claim_id > 0 &&
        ($action === "approve" || $action === "reject")
    ) {

        /* GET CLAIM */

        $sql = "SELECT
                    claim_id,
                    item_id,
                    user_id,
                    claim_status
                FROM claims
                WHERE claim_id = ?";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("SQL Error: " . $conn->error);
        }

        $stmt->bind_param("i", $claim_id);
        $stmt->execute();

        $claim_result = $stmt->get_result();


        if ($claim_result->num_rows === 1) {

            $claim = $claim_result->fetch_assoc();

            $item_id = intval($claim["item_id"]);
            $claimant_id = intval($claim["user_id"]);


            /* =================================================
               APPROVE CLAIM
               ================================================= */

            if ($action === "approve") {

                $item_name = "your claimed item";

                $item_sql = "SELECT item_name
                             FROM items
                             WHERE item_id = ?";

                $item_stmt = $conn->prepare($item_sql);

                if ($item_stmt) {

                    $item_stmt->bind_param("i", $item_id);
                    $item_stmt->execute();

                    $item_result = $item_stmt->get_result();

                    if ($item_result->num_rows === 1) {

                        $item_data = $item_result->fetch_assoc();

                        $item_name = $item_data["item_name"];
                    }

                    $item_stmt->close();
                }


                /* UPDATE CLAIM STATUS */

                $update_sql =
                    "UPDATE claims
                     SET claim_status = 'Approved'
                     WHERE claim_id = ?
                     AND claim_status = 'Pending'";

                $update_stmt = $conn->prepare($update_sql);

                if (!$update_stmt) {
                    die("SQL Error updating claim: " . $conn->error);
                }

                $update_stmt->bind_param("i", $claim_id);

                if (!$update_stmt->execute()) {
                    die("Failed to approve claim: " . $update_stmt->error);
                }

                $affected = $update_stmt->affected_rows;

                $update_stmt->close();

                if ($affected !== 1) {

                    die(
                        "Claim was not updated. Claim ID: " .
                        $claim_id .
                        " | Current status: " .
                        htmlspecialchars($claim["claim_status"])
                    );
                }


                /* MARK ITEM AS RETURNED */

                $item_update_sql =
                    "UPDATE items
                     SET status = 'Returned'
                     WHERE item_id = ?";

                $item_update_stmt =
                    $conn->prepare($item_update_sql);

                if ($item_update_stmt) {

                    $item_update_stmt->bind_param(
                        "i",
                        $item_id
                    );

                    $item_update_stmt->execute();

                    $item_update_stmt->close();
                }


                /* EMAIL + NOTIFICATION */

                if ($claimant_id > 0) {

                    $message =
                        "Your claim for \"" .
                        $item_name .
                        "\" has been approved.";

                    campuslost_email_user_by_id(
                        $conn,
                        $claimant_id,
                        "CampusLost - Claim Approved",
                        $message
                    );


                    $notification_sql =
                        "INSERT INTO notifications
                        (
                            user_id,
                            message,
                            notification_date,
                            is_read
                        )
                        VALUES (?, ?, NOW(), 0)";

                    $notification_stmt =
                        $conn->prepare($notification_sql);

                    if ($notification_stmt) {

                        $notification_stmt->bind_param(
                            "is",
                            $claimant_id,
                            $message
                        );

                        $notification_stmt->execute();

                        $notification_stmt->close();
                    }
                }
            }


            /* =================================================
               REJECT CLAIM
               ================================================= */

            elseif ($action === "reject") {

                $item_name = "your claimed item";

                $item_sql = "SELECT item_name
                             FROM items
                             WHERE item_id = ?";

                $item_stmt =
                    $conn->prepare($item_sql);

                if ($item_stmt) {

                    $item_stmt->bind_param(
                        "i",
                        $item_id
                    );

                    $item_stmt->execute();

                    $item_result =
                        $item_stmt->get_result();

                    if ($item_result->num_rows === 1) {

                        $item_data =
                            $item_result->fetch_assoc();

                        $item_name =
                            $item_data["item_name"];
                    }

                    $item_stmt->close();
                }


                /* UPDATE CLAIM */

                $update_sql =
                    "UPDATE claims
                     SET claim_status = 'Rejected'
                     WHERE claim_id = ?";

                $update_stmt =
                    $conn->prepare($update_sql);

                if (!$update_stmt) {

                    die(
                        "SQL Error: " .
                        $conn->error
                    );
                }

                $update_stmt->bind_param(
                    "i",
                    $claim_id
                );

                $update_stmt->execute();

                $update_stmt->close();


                /* EMAIL + NOTIFICATION */

                if ($claimant_id > 0) {

                    $message =
                        "Your claim for \"" .
                        $item_name .
                        "\" has been rejected.";

                    campuslost_email_user_by_id(
                        $conn,
                        $claimant_id,
                        "CampusLost - Claim Rejected",
                        $message
                    );


                    $notification_sql =
                        "INSERT INTO notifications
                        (
                            user_id,
                            message,
                            notification_date,
                            is_read
                        )
                        VALUES (?, ?, NOW(), 0)";

                    $notification_stmt =
                        $conn->prepare($notification_sql);

                    if ($notification_stmt) {

                        $notification_stmt->bind_param(
                            "is",
                            $claimant_id,
                            $message
                        );

                        $notification_stmt->execute();

                        $notification_stmt->close();
                    }
                }
            }
        }

        $stmt->close();
    }


    header("Location: staff-claims.php");
    exit();
}


/* =========================================================
   FILTER
   ========================================================= */

$status = isset($_GET["status"])
    ? trim($_GET["status"])
    : "";


/* =========================================================
   GET CLAIMS
   ========================================================= */

if (
    $status === "Pending" ||
    $status === "Approved" ||
    $status === "Rejected"
) {

    $sql = "SELECT
                claim_id,
                item_id,
                user_id,
                claim_description,
                claim_date,
                claim_status,
                admin_note
            FROM claims
            WHERE claim_status = ?
            ORDER BY claim_id DESC";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }

    $stmt->bind_param(
        "s",
        $status
    );

} else {

    $sql = "SELECT
                claim_id,
                item_id,
                user_id,
                claim_description,
                claim_date,
                claim_status,
                admin_note
            FROM claims
            ORDER BY claim_id DESC";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }
}


$stmt->execute();

$claims_result = $stmt->get_result();

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

<title>Manage Claims - CampusLost</title>

<!-- FONT AWESOME -->
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

    background: #071a38;
    color: white;

    padding: 28px 14px 20px;

    display: flex;
    flex-direction: column;

    z-index: 1000;

    box-shadow: 5px 0 25px rgba(0,0,0,.08);
}


/* =========================================================
   BRAND
   ========================================================= */

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


/* =========================================================
   LOGOUT
   ========================================================= */

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


/* =========================================================
   MAIN
   ========================================================= */

.main {
    margin-left: 260px;
    min-height: 100vh;
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


/* =========================================================
   ADMIN PROFILE
   ========================================================= */

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

    max-width: 160px;

    color: #102342;

    font-size: 13px;

    margin-bottom: 4px;

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
   FILTER PANEL
   ========================================================= */

.filter-panel {
    background: white;

    border: 1px solid #e8edf5;

    border-radius: 16px;

    padding: 18px 20px;

    margin-bottom: 25px;

    box-shadow:
        0 6px 22px rgba(15,39,71,.04);
}

.filter-title {
    display: flex;

    align-items: center;

    gap: 8px;

    color: #102342;

    font-size: 12px;
    font-weight: 800;

    margin-bottom: 13px;
}

.filter-title i {
    color: #2563eb;
}

.filters {
    display: flex;

    flex-wrap: wrap;

    gap: 8px;
}

.filters a {
    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 9px 14px;

    border: 1px solid #e2e8f0;

    border-radius: 20px;

    background: #f8fafc;

    color: #475569;

    text-decoration: none;

    font-size: 12px;
    font-weight: 700;

    transition: .2s;
}

.filters a:hover {
    background: #eff6ff;

    border-color: #bfdbfe;

    color: #2563eb;
}

.filters a.active {
    background: #2563eb;

    border-color: #2563eb;

    color: white;

    box-shadow:
        0 5px 13px rgba(37,99,235,.18);
}


/* =========================================================
   RESULT INFO
   ========================================================= */

.result-heading {
    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 15px;

    margin-bottom: 16px;
}

.result-heading h2 {
    color: #102342;

    font-size: 18px;
}

.result-heading p {
    color: #94a3b8;

    font-size: 11px;

    margin-top: 4px;
}

.result-count {
    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 7px 11px;

    border-radius: 20px;

    background: #eff6ff;

    color: #2563eb;

    font-size: 10px;
    font-weight: 800;
}


/* =========================================================
   CLAIM GRID
   ========================================================= */

.claims-grid {
    display: grid;

    grid-template-columns:
        repeat(3, minmax(0, 1fr));

    gap: 20px;
}


/* =========================================================
   CLAIM CARD
   ========================================================= */

.claim-card {
    background: white;

    border: 1px solid #e8edf5;

    border-radius: 16px;

    overflow: hidden;

    box-shadow:
        0 6px 22px rgba(15,39,71,.05);

    transition: .2s ease;
}

.claim-card:hover {
    transform: translateY(-3px);

    border-color: #cbd9eb;

    box-shadow:
        0 10px 28px rgba(15,39,71,.09);
}


/* =========================================================
   IMAGE
   ========================================================= */

.claim-image {
    position: relative;

    width: 100%;
    height: 195px;

    background: #eef4fb;

    display: flex;

    align-items: center;
    justify-content: center;

    overflow: hidden;
}

.claim-image img {
    width: 100%;
    height: 100%;

    object-fit: cover;

    transition: transform .3s ease;
}

.claim-card:hover .claim-image img {
    transform: scale(1.025);
}

.no-image {
    width: 58px;
    height: 58px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 16px;

    background: white;

    color: #94a3b8;

    font-size: 22px;

    box-shadow:
        0 5px 15px rgba(15,39,71,.06);
}

.item-type {
    position: absolute;

    top: 13px;
    left: 13px;

    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 6px 9px;

    border-radius: 20px;

    font-size: 9px;
    font-weight: 800;

    letter-spacing: .4px;

    box-shadow:
        0 3px 10px rgba(0,0,0,.08);
}

.item-type.lost {
    background: #fff1f2;
    color: #be123c;
}

.item-type.found {
    background: #ecfdf5;
    color: #047857;
}


/* =========================================================
   CONTENT
   ========================================================= */

.claim-content {
    padding: 19px;
}

.claim-title {
    display: flex;

    justify-content: space-between;
    align-items: flex-start;

    gap: 10px;

    margin-bottom: 15px;
}

.claim-title h3 {
    color: #102342;

    font-size: 16px;

    line-height: 1.35;
}


/* =========================================================
   BADGES
   ========================================================= */

.badge {
    display: inline-flex;

    align-items: center;

    gap: 5px;

    flex-shrink: 0;

    padding: 6px 9px;

    border-radius: 20px;

    font-size: 9px;
    font-weight: 800;

    letter-spacing: .3px;

    white-space: nowrap;
}

.badge.pending {
    background: #fff7ed;
    color: #c2410c;
}

.badge.approved {
    background: #ecfdf5;
    color: #047857;
}

.badge.rejected {
    background: #fff1f2;
    color: #be123c;
}


/* =========================================================
   CLAIMANT
   ========================================================= */

.claimant-box {
    display: flex;

    align-items: center;

    gap: 11px;

    padding: 12px;

    background: #f8fafc;

    border: 1px solid #edf1f5;

    border-radius: 11px;

    margin-bottom: 14px;
}

.claimant-avatar {
    width: 36px;
    height: 36px;

    flex-shrink: 0;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 10px;

    background: #eff6ff;

    color: #2563eb;

    font-size: 13px;
}

.claimant-box strong {
    display: block;

    color: #102342;

    font-size: 12px;

    margin-bottom: 3px;
}

.claimant-box span {
    display: block;

    color: #8290a5;

    font-size: 10px;
}


/* =========================================================
   INFO
   ========================================================= */

.info {
    margin-bottom: 14px;
}

.info p {
    display: flex;

    align-items: flex-start;

    gap: 8px;

    color: #64748b;

    font-size: 11px;

    line-height: 1.5;

    margin-bottom: 7px;
}

.info p:last-child {
    margin-bottom: 0;
}

.info p i {
    width: 14px;

    flex-shrink: 0;

    text-align: center;

    color: #94a3b8;

    margin-top: 2px;
}

.info strong {
    color: #475569;

    margin-right: 2px;
}


/* =========================================================
   DESCRIPTION
   ========================================================= */

.claim-description {
    background: #f8fafc;

    border: 1px solid #edf1f5;

    border-left: 3px solid #2563eb;

    padding: 12px 13px;

    border-radius: 8px;

    color: #64748b;

    font-size: 11px;

    line-height: 1.55;

    margin-bottom: 15px;
}

.claim-description strong {
    display: flex;

    align-items: center;

    gap: 6px;

    color: #475569;

    font-size: 10px;

    margin-bottom: 6px;

    text-transform: uppercase;

    letter-spacing: .4px;
}

.claim-description strong i {
    color: #2563eb;
}


/* =========================================================
   ACTIONS
   ========================================================= */

.actions {
    display: flex;

    align-items: stretch;

    gap: 7px;

    padding-top: 14px;

    border-top: 1px solid #edf1f5;
}

.actions form {
    flex: 1;

    margin: 0;
}

.btn {
    width: 100%;

    min-height: 36px;

    border: none;

    padding: 9px 8px;

    border-radius: 8px;

    display: flex;

    align-items: center;
    justify-content: center;

    gap: 6px;

    font-size: 10px;
    font-weight: 800;

    cursor: pointer;

    text-decoration: none;

    transition: .2s;
}

.btn.view {
    flex: 1;

    background: #eff6ff;

    color: #2563eb;
}

.btn.approve {
    background: #ecfdf5;

    color: #047857;
}

.btn.reject {
    background: #fff1f2;

    color: #be123c;
}

.btn.view:hover {
    background: #dbeafe;
}

.btn.approve:hover {
    background: #d1fae5;
}

.btn.reject:hover {
    background: #ffe4e6;
}


/* =========================================================
   EMPTY
   ========================================================= */

.empty {
    background: white;

    border: 1px solid #e8edf5;

    border-radius: 16px;

    min-height: 360px;

    display: flex;

    flex-direction: column;

    align-items: center;
    justify-content: center;

    padding: 40px;

    text-align: center;

    box-shadow:
        0 6px 22px rgba(15,39,71,.05);
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
    color: #102342;

    font-size: 17px;

    margin-bottom: 7px;
}

.empty p {
    color: #8290a5;

    font-size: 12px;
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
html.dark-mode .result-heading h2 {
    color: #f8fafc;
}

html.dark-mode .header p {
    color: #94a3b8;
}

html.dark-mode .admin-profile,
html.dark-mode .filter-panel,
html.dark-mode .claim-card,
html.dark-mode .empty {
    background: #1e293b;

    border-color: #2d3c52;

    box-shadow:
        0 6px 22px rgba(0,0,0,.15);
}

html.dark-mode .admin-avatar {
    background: #172554;
    color: #93c5fd;
}

html.dark-mode .admin-profile strong,
html.dark-mode .claim-title h3,
html.dark-mode .filter-title,
html.dark-mode .empty h3 {
    color: #f8fafc;
}

html.dark-mode .admin-profile span {
    color: #60a5fa;
}

html.dark-mode .filters a {
    background: #273449;

    border-color: #334155;

    color: #cbd5e1;
}

html.dark-mode .filters a:hover {
    background: #334155;

    border-color: #475569;

    color: #93c5fd;
}

html.dark-mode .filters a.active {
    background: #2563eb;

    border-color: #2563eb;

    color: white;
}

html.dark-mode .result-count {
    background: #172554;
    color: #93c5fd;
}

html.dark-mode .claim-image {
    background: #111c2e;
}

html.dark-mode .no-image {
    background: #243247;

    color: #94a3b8;

    box-shadow: none;
}

html.dark-mode .claimant-box,
html.dark-mode .claim-description {
    background: #172033;

    border-color: #334155;
}

html.dark-mode .claimant-avatar {
    background: #172554;
    color: #93c5fd;
}

html.dark-mode .claimant-box strong,
html.dark-mode .claim-description strong,
html.dark-mode .info strong {
    color: #e2e8f0;
}

html.dark-mode .claimant-box span,
html.dark-mode .info p,
html.dark-mode .claim-description,
html.dark-mode .empty p {
    color: #94a3b8;
}

html.dark-mode .actions {
    border-color: #334155;
}

html.dark-mode .btn.view {
    background: #172554;
    color: #93c5fd;
}

html.dark-mode .btn.approve {
    background: #073b2c;
    color: #6ee7b7;
}

html.dark-mode .btn.reject {
    background: #4c1723;
    color: #fda4af;
}

html.dark-mode .badge.pending {
    background: #4a250c;
    color: #fdba74;
}

html.dark-mode .badge.approved {
    background: #073b2c;
    color: #6ee7b7;
}

html.dark-mode .badge.rejected {
    background: #4c1723;
    color: #fda4af;
}

html.dark-mode .item-type.lost {
    background: #4c1723;
    color: #fda4af;
}

html.dark-mode .item-type.found {
    background: #073b2c;
    color: #6ee7b7;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 1200px) {

    .claims-grid {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
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
        flex-direction: column;

        align-items: flex-start;
    }

    .claims-grid {
        grid-template-columns: 1fr;
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

    .filter-panel {
        padding: 15px;
    }

    .result-heading {
        align-items: flex-start;

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


        <a href="staff-dashboard.php">

            <i class="fa-solid fa-house"></i>

            Dashboard

        </a>


        <a href="staff-manage-items.php">

            <i class="fa-solid fa-boxes-stacked"></i>

            Manage Items

        </a>


        <a
            href="staff-claims.php"
            class="active"
        >

            <i class="fa-solid fa-file-circle-check"></i>

            Manage Claims

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

                <i class="fa-solid fa-shield-halved"></i>

                Claim Management

            </span>


            <h1>

                Manage Claims

            </h1>


            <p>

                Review and manage item claims submitted by campus users.

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
                        $staff["name"]
                    );
                    ?>

                </strong>


                <span>

                    <i class="fa-solid fa-shield-halved"></i>

                    Staff

                </span>


            </div>


        </div>


    </div>



    <!-- =====================================================
         FILTERS
         ===================================================== -->

    <div class="filter-panel">


        <div class="filter-title">

            <i class="fa-solid fa-filter"></i>

            Filter Claims

        </div>


        <div class="filters">


            <a
                href="staff-claims.php"
                class="<?php
                    echo ($status === "")
                        ? "active"
                        : "";
                ?>"
            >

                <i class="fa-solid fa-layer-group"></i>

                All Claims

            </a>


            <a
                href="staff-claims.php?status=Pending"
                class="<?php
                    echo ($status === "Pending")
                        ? "active"
                        : "";
                ?>"
            >

                <i class="fa-regular fa-clock"></i>

                Pending

            </a>


            <a
                href="staff-claims.php?status=Approved"
                class="<?php
                    echo ($status === "Approved")
                        ? "active"
                        : "";
                ?>"
            >

                <i class="fa-solid fa-circle-check"></i>

                Approved

            </a>


            <a
                href="staff-claims.php?status=Rejected"
                class="<?php
                    echo ($status === "Rejected")
                        ? "active"
                        : "";
                ?>"
            >

                <i class="fa-solid fa-circle-xmark"></i>

                Rejected

            </a>


        </div>


    </div>



    <!-- =====================================================
         CLAIMS
         ===================================================== -->


    <div class="result-heading">


        <div>

            <h2>

                <?php

                if ($status === "Pending") {

                    echo "Pending Claims";

                } elseif ($status === "Approved") {

                    echo "Approved Claims";

                } elseif ($status === "Rejected") {

                    echo "Rejected Claims";

                } else {

                    echo "All Claims";
                }

                ?>

            </h2>


            <p>

                Review claimant information and claim details.

            </p>

        </div>


        <span class="result-count">

            <i class="fa-solid fa-file-circle-check"></i>

            <?php
            echo intval($claims_result->num_rows);
            ?>

            Claim<?php
            echo ($claims_result->num_rows == 1)
                ? ""
                : "s";
            ?>

        </span>


    </div>



    <?php if ($claims_result->num_rows > 0): ?>


        <div class="claims-grid">


            <?php while ($claim = $claims_result->fetch_assoc()): ?>


                <?php

                /* =================================================
                   DEFAULT VALUES
                   ================================================= */

                $item_name = "Unknown Item";

                $category = "Unknown";

                $location = "Unknown";

                $image = "";

                $item_type = "";

                $item_status = "";


                $claimant_name = "Unknown User";

                $claimant_id = "-";

                $claimant_email = "-";


                /* =================================================
                   GET ITEM
                   ================================================= */

                $item_sql = "SELECT
                                item_name,
                                category,
                                location,
                                image,
                                item_type,
                                status
                             FROM items
                             WHERE item_id = ?";

                $item_stmt =
                    $conn->prepare($item_sql);


                if ($item_stmt) {

                    $item_stmt->bind_param(
                        "i",
                        $claim["item_id"]
                    );

                    $item_stmt->execute();

                    $item_result =
                        $item_stmt->get_result();


                    if ($item_result->num_rows === 1) {

                        $item_data =
                            $item_result->fetch_assoc();


                        $item_name =
                            $item_data["item_name"];

                        $category =
                            $item_data["category"];

                        $location =
                            $item_data["location"];

                        $image =
                            $item_data["image"];

                        $item_type =
                            $item_data["item_type"];

                        $item_status =
                            $item_data["status"];
                    }


                    $item_stmt->close();
                }


                /* =================================================
                   GET CLAIMANT
                   ================================================= */

                $user_sql = "SELECT
                                name,
                                id_number,
                                email
                             FROM users
                             WHERE user_id = ?";

                $user_stmt =
                    $conn->prepare($user_sql);


                if ($user_stmt) {

                    $user_stmt->bind_param(
                        "i",
                        $claim["user_id"]
                    );

                    $user_stmt->execute();

                    $user_result =
                        $user_stmt->get_result();


                    if ($user_result->num_rows === 1) {

                        $user_data =
                            $user_result->fetch_assoc();


                        $claimant_name =
                            $user_data["name"];

                        $claimant_id =
                            $user_data["id_number"];

                        $claimant_email =
                            $user_data["email"];
                    }


                    $user_stmt->close();
                }


                /* =================================================
                   STATUS CLASS
                   ================================================= */

                if (
                    $claim["claim_status"]
                    === "Pending"
                ) {

                    $status_class = "pending";

                } elseif (
                    $claim["claim_status"]
                    === "Approved"
                ) {

                    $status_class = "approved";

                } else {

                    $status_class = "rejected";
                }

                ?>


                <!-- CLAIM CARD -->

                <div class="claim-card">


                    <!-- IMAGE -->

                    <div class="claim-image">


                        <?php

                        $image_path =
                            "uploads/" .
                            $image;

                        if (
                            !empty($image) &&
                            file_exists($image_path)
                        ):

                        ?>


                            <img
                                src="<?php
                                echo htmlspecialchars(
                                    $image_path
                                );
                                ?>"
                                alt="Item Image"
                            >


                        <?php else: ?>


                            <div class="no-image">

                                <i class="fa-regular fa-image"></i>

                            </div>


                        <?php endif; ?>



                        <?php if ($item_type === "Lost"): ?>


                            <span class="item-type lost">

                                <i class="fa-solid fa-magnifying-glass"></i>

                                LOST

                            </span>


                        <?php elseif ($item_type === "Found"): ?>


                            <span class="item-type found">

                                <i class="fa-solid fa-box-open"></i>

                                FOUND

                            </span>


                        <?php endif; ?>


                    </div>



                    <!-- CONTENT -->

                    <div class="claim-content">


                        <div class="claim-title">


                            <h3>

                                <?php
                                echo htmlspecialchars(
                                    $item_name
                                );
                                ?>

                            </h3>


                            <span
                                class="badge
                                <?php echo $status_class; ?>"
                            >

                                <?php if ($status_class === "pending"): ?>

                                    <i class="fa-regular fa-clock"></i>

                                <?php elseif ($status_class === "approved"): ?>

                                    <i class="fa-solid fa-check"></i>

                                <?php else: ?>

                                    <i class="fa-solid fa-xmark"></i>

                                <?php endif; ?>


                                <?php
                                echo htmlspecialchars(
                                    strtoupper(
                                        $claim["claim_status"]
                                    )
                                );
                                ?>

                            </span>


                        </div>



                        <!-- CLAIMANT -->

                        <div class="claimant-box">


                            <div class="claimant-avatar">

                                <i class="fa-solid fa-user"></i>

                            </div>


                            <div>


                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $claimant_name
                                    );
                                    ?>

                                </strong>


                                <span>

                                    ID:
                                    <?php
                                    echo htmlspecialchars(
                                        $claimant_id
                                    );
                                    ?>

                                </span>


                            </div>


                        </div>



                        <!-- INFORMATION -->

                        <div class="info">


                            <p>

                                <i class="fa-solid fa-envelope"></i>

                                <span>

                                    <strong>Email:</strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $claimant_email
                                    );
                                    ?>

                                </span>

                            </p>


                            <p>

                                <i class="fa-solid fa-folder"></i>

                                <span>

                                    <strong>Category:</strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $category
                                    );
                                    ?>

                                </span>

                            </p>


                            <p>

                                <i class="fa-solid fa-location-dot"></i>

                                <span>

                                    <strong>Location:</strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $location
                                    );
                                    ?>

                                </span>

                            </p>


                            <p>

                                <i class="fa-solid fa-calendar"></i>

                                <span>

                                    <strong>Claim Date:</strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $claim["claim_date"]
                                    );
                                    ?>

                                </span>

                            </p>


                        </div>



                        <!-- DESCRIPTION -->

                        <div class="claim-description">


                            <strong>

                                <i class="fa-solid fa-message"></i>

                                Claim Description

                            </strong>


                            <?php

                            if (
                                !empty(
                                    $claim[
                                        "claim_description"
                                    ]
                                )
                            ) {

                                echo nl2br(
                                    htmlspecialchars(
                                        $claim[
                                            "claim_description"
                                        ]
                                    )
                                );

                            } else {

                                echo "No description provided.";
                            }

                            ?>


                        </div>



                        <!-- ACTIONS -->

                        <div class="actions">


                            <a
                                href="staff-item-details.php?id=<?php
                                echo intval(
                                    $claim["item_id"]
                                );
                                ?>"
                                class="btn view"
                            >

                                <i class="fa-regular fa-eye"></i>

                                View Item

                            </a>



                            <?php if (
                                $claim["claim_status"]
                                === "Pending"
                            ): ?>


                                <!-- APPROVE -->

                                <form method="POST">


                                    <input
                                        type="hidden"
                                        name="claim_id"
                                        value="<?php
                                        echo intval(
                                            $claim["claim_id"]
                                        );
                                        ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="action"
                                        value="approve"
                                    >


                                    <button
                                        type="submit"
                                        class="btn approve"
                                        onclick="return confirm('Approve this claim?');"
                                    >

                                        <i class="fa-solid fa-check"></i>

                                        Approve

                                    </button>


                                </form>



                                <!-- REJECT -->

                                <form method="POST">


                                    <input
                                        type="hidden"
                                        name="claim_id"
                                        value="<?php
                                        echo intval(
                                            $claim["claim_id"]
                                        );
                                        ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="action"
                                        value="reject"
                                    >


                                    <button
                                        type="submit"
                                        class="btn reject"
                                        onclick="return confirm('Reject this claim?');"
                                    >

                                        <i class="fa-solid fa-xmark"></i>

                                        Reject

                                    </button>


                                </form>


                            <?php endif; ?>


                        </div>


                    </div>


                </div>


            <?php endwhile; ?>


        </div>


    <?php else: ?>


        <div class="empty">


            <div class="empty-icon">

                <i class="fa-regular fa-folder-open"></i>

            </div>


            <h3>

                No Claims Found

            </h3>


            <p>

                There are no claims matching this filter.

            </p>


        </div>


    <?php endif; ?>


</main>


</body>

</html>