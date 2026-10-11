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
   CHECK ADMIN
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

$staff = $user_result->fetch_assoc();

$staff_name = $staff["name"];
$role = strtolower(trim($staff["role"]));

$stmt_user->close();

if ($role !== "staff") {

    if ($role === "admin") {
        header("Location: admin-dashboard.php");
    } else {
        header("Location: dashboard.php");
    }

    exit();
}


/* =========================================================
   CHECK ITEM ID
   ========================================================= */

if (!isset($_GET["id"])) {
    header("Location: staff-manage-items.php");
    exit();
}

$item_id = intval($_GET["id"]);

if ($item_id <= 0) {
    header("Location: staff-manage-items.php");
    exit();
}


/* =========================================================
   GET ITEM
   ========================================================= */

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
   APPROVE / REJECT PENDING REPORT
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["report_action"])) {

    $report_action = trim($_POST["report_action"]);

    if (strtolower(trim($item["status"])) !== "pending") {
        header("Location: staff-item-details.php?id=" . $item_id);
        exit();
    }

    if ($report_action === "approve") {

        $new_status = $item["item_type"];

        $update_stmt = $conn->prepare(
            "UPDATE items
             SET status = ?
             WHERE item_id = ?
             AND status = 'Pending'"
        );

        if ($update_stmt) {

            $update_stmt->bind_param("si", $new_status, $item_id);
            $update_stmt->execute();

            if ($update_stmt->affected_rows > 0) {

                /* Get reporter */
                $reporter_stmt = $conn->prepare(
                    "SELECT user_id
                     FROM items
                     WHERE item_id = ?"
                );

                if ($reporter_stmt) {
                    $reporter_stmt->bind_param("i", $item_id);
                    $reporter_stmt->execute();
                    $reporter_result = $reporter_stmt->get_result();

                    if ($reporter_result->num_rows === 1) {
                        $reporter = $reporter_result->fetch_assoc();
                        $reporter_id = intval($reporter["user_id"]);

                        campuslost_email_user_by_id(
                            $conn,
                            $reporter_id,
                            "CampusLost - Item Report Approved",
                            'Your report for "' . $item["item_name"] . '" has been approved.'
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
                            $notification_stmt->bind_param(
                                "is",
                                $reporter_id,
                                $notification_message
                            );
                            $notification_stmt->execute();
                            $notification_stmt->close();
                        }
                    }

                    $reporter_stmt->close();
                }
            }

            $update_stmt->close();
        }

        header(
            "Location: staff-item-details.php?id=" .
            $item_id .
            "&approved=1"
        );
        exit();
    }

    elseif ($report_action === "reject") {

        $update_stmt = $conn->prepare(
            "UPDATE items
             SET status = 'Rejected'
             WHERE item_id = ?
             AND status = 'Pending'"
        );

        if ($update_stmt) {

            $update_stmt->bind_param("i", $item_id);
            $update_stmt->execute();

            if ($update_stmt->affected_rows > 0) {

                /* Get reporter */
                $reporter_stmt = $conn->prepare(
                    "SELECT user_id
                     FROM items
                     WHERE item_id = ?"
                );

                if ($reporter_stmt) {
                    $reporter_stmt->bind_param("i", $item_id);
                    $reporter_stmt->execute();
                    $reporter_result = $reporter_stmt->get_result();

                    if ($reporter_result->num_rows === 1) {
                        $reporter = $reporter_result->fetch_assoc();
                        $reporter_id = intval($reporter["user_id"]);

                        campuslost_email_user_by_id(
                            $conn,
                            $reporter_id,
                            "CampusLost - Item Report Rejected",
                            'Your report for "' . $item["item_name"] . '" has been rejected.'
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
                            $notification_stmt->bind_param(
                                "is",
                                $reporter_id,
                                $notification_message
                            );
                            $notification_stmt->execute();
                            $notification_stmt->close();
                        }
                    }

                    $reporter_stmt->close();
                }
            }

            $update_stmt->close();
        }

        header(
            "Location: staff-item-details.php?id=" .
            $item_id .
            "&rejected=1"
        );
        exit();
    }
}


/* =========================================================
   UPDATE ITEM STATUS
   ========================================================= */

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["update_status"])) {

    $new_status = trim($_POST["new_status"] ?? "");

    $current_type = strtolower(trim($item["item_type"]));
    $current_status = strtolower(trim($item["status"]));

    $allowed = false;

    /* LOST FLOW
       Lost -> Matched -> Resolved
    */

    if ($current_type === "lost") {

        if ($current_status === "lost"
            && $new_status === "Matched") {

            $allowed = true;
        }

        elseif ($current_status === "matched"
            && $new_status === "Resolved") {

            $allowed = true;
        }
    }

    /* FOUND FLOW
       Found -> In Custody -> Returned
    */

    elseif ($current_type === "found") {

        if ($current_status === "found"
            && $new_status === "In Custody") {

            $allowed = true;
        }

        elseif ($current_status === "in custody"
            && $new_status === "Returned") {

            $allowed = true;
        }
    }

    if ($allowed) {

        $update_sql =
            "UPDATE items
             SET status = ?
             WHERE item_id = ?";

        $update_stmt = $conn->prepare($update_sql);

        if ($update_stmt) {

            $update_stmt->bind_param(
                "si",
                $new_status,
                $item_id
            );

            if ($update_stmt->execute()) {

                $update_stmt->close();

                header(
                    "Location: staff-item-details.php?id="
                    . $item_id
                    . "&updated=1"
                );

                exit();

            } else {

                $message = "Unable to update item status.";
                $message_type = "error";

            }

            $update_stmt->close();

        } else {

            $message = "Unable to prepare status update.";
            $message_type = "error";
        }

    } else {

        $message = "Invalid status change.";
        $message_type = "error";
    }
}


/* SUCCESS MESSAGE */

if (isset($_GET["approved"]) && $_GET["approved"] === "1") {
    $message = "Item report approved successfully.";
    $message_type = "success";
}
elseif (isset($_GET["rejected"]) && $_GET["rejected"] === "1") {
    $message = "Item report rejected successfully.";
    $message_type = "success";
}
elseif (isset($_GET["updated"])
    && $_GET["updated"] === "1") {

    $message = "Item status updated successfully.";
    $message_type = "success";
}


/* =========================================================
   DISPLAY HELPERS
   ========================================================= */

$item_type = strtolower(trim($item["item_type"]));
$item_status = strtolower(trim($item["status"]));

$type_class = ($item_type === "found")
    ? "found"
    : "lost";

$status_class = "default-status";

if ($item_status === "pending") {
    $status_class = "pending-status";
}
elseif ($item_status === "rejected") {
    $status_class = "rejected-status";
}
elseif ($item_status === "returned") {
    $status_class = "returned-status";
}
elseif ($item_status === "found") {
    $status_class = "found-status";
}
elseif ($item_status === "lost") {
    $status_class = "lost-status";
}

/* =========================================================
   NEXT STATUS
   ========================================================= */

$next_status = null;

if ($item_type === "lost") {

    if ($item_status === "lost") {
        $next_status = "Matched";
    }

    elseif ($item_status === "matched") {
        $next_status = "Resolved";
    }

}
elseif ($item_type === "found") {

    if ($item_status === "found") {
        $next_status = "In Custody";
    }

    elseif ($item_status === "in custody") {
        $next_status = "Returned";
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
    <?php echo htmlspecialchars($item["item_name"]); ?>
    - CampusLost
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
    min-height: 100vh;

    font-family: Arial, sans-serif;

    background: #f5f7fb;
    color: #172033;

    transition:
        background .3s ease,
        color .3s ease;
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

    font-weight: 800;

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

    border-radius: 10px;

    background:
        rgba(239,68,68,.14);

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

.container {
    width: 100%;

    max-width: 1050px;

    margin: 0 auto;
}


/* =========================================================
   PAGE HEADER
   ========================================================= */

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


/* =========================================================
   ADMIN PROFILE
   ========================================================= */

.admin-profile {
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
   BACK
   ========================================================= */

.back-row {
    margin-bottom: 17px;
}

.back-btn {
    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 9px 13px;

    border:
        1px solid #dce3ec;

    border-radius: 9px;

    background: white;

    color: #475569;

    text-decoration: none;

    font-size: 11px;

    font-weight: 700;

    transition: .2s;
}

.back-btn:hover {
    border-color: #93c5fd;

    background: #eff6ff;

    color: #2563eb;
}


/* =========================================================
   DETAILS CARD
   ========================================================= */

.details-card {
    overflow: hidden;

    border:
        1px solid #e8edf5;

    border-radius: 18px;

    background: white;

    box-shadow:
        0 7px 25px
        rgba(15,39,71,.06);
}


/* =========================================================
   IMAGE
   ========================================================= */

.item-image {
    position: relative;

    width: 100%;

    height: 350px;

    display: flex;

    align-items: center;
    justify-content: center;

    overflow: hidden;

    background: #eef4ff;

    border-bottom:
        1px solid #e8edf5;
}

.item-image img {
    width: 100%;
    height: 100%;

    object-fit: contain;

    object-position: center;

    background: #f8fafc;
}

.no-image {
    display: flex;

    flex-direction: column;

    align-items: center;
    justify-content: center;

    gap: 10px;

    color: #94a3b8;
}

.no-image i {
    font-size: 48px;

    color: #bfdbfe;
}

.no-image span {
    font-size: 11px;

    font-weight: 700;
}


/* =========================================================
   CONTENT
   ========================================================= */

.content {
    padding: 27px 30px 30px;
}


/* =========================================================
   TITLE
   ========================================================= */

.title-row {
    display: flex;

    align-items: flex-start;
    justify-content: space-between;

    gap: 20px;

    padding-bottom: 20px;

    margin-bottom: 20px;

    border-bottom:
        1px solid #edf1f5;
}

.item-title-area {
    min-width: 0;
}

.item-id {
    display: flex;

    align-items: center;

    gap: 5px;

    margin-bottom: 7px;

    color: #94a3b8;

    font-size: 10px;

    font-weight: 700;
}

.item-title {
    color: #102342;

    font-size: 25px;

    line-height: 1.3;

    word-break: break-word;
}


/* =========================================================
   ITEM TYPE
   ========================================================= */

.type-badge {
    flex-shrink: 0;

    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 7px 12px;

    border-radius: 20px;

    font-size: 10px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .4px;
}

.type-badge.found {
    background: #ecfdf5;

    color: #15803d;

    border:
        1px solid #bbf7d0;
}

.type-badge.lost {
    background: #fff1f2;

    color: #be123c;

    border:
        1px solid #fecdd3;
}


/* =========================================================
   INFO GRID
   ========================================================= */

.info-grid {
    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 13px;

    margin-bottom: 17px;
}

.info-box {
    padding: 15px;

    border:
        1px solid #e8edf5;

    border-radius: 12px;

    background: #f8fafc;
}

.info-label {
    display: flex;

    align-items: center;

    gap: 7px;

    margin-bottom: 8px;

    color: #7c899d;

    font-size: 10px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: .4px;
}

.info-label i {
    width: 14px;

    color: #2563eb;

    text-align: center;
}

.info-value {
    color: #24344d;

    font-size: 12px;

    font-weight: 700;

    line-height: 1.5;

    word-break: break-word;
}


/* =========================================================
   STATUS BADGE
   ========================================================= */

.status-badge {
    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 5px 9px;

    border-radius: 20px;

    font-size: 9px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .3px;
}

.pending-status {
    background: #fff7ed;

    color: #c2410c;
}

.rejected-status {
    background: #fff1f2;

    color: #be123c;
}

.returned-status {
    background: #ecfdf5;

    color: #15803d;
}

.found-status {
    background: #eff6ff;

    color: #2563eb;
}

.lost-status {
    background: #fff7ed;

    color: #c2410c;
}

.default-status {
    background: #f1f5f9;

    color: #475569;
}


/* =========================================================
   DESCRIPTION
   ========================================================= */

.description {
    padding: 17px;

    border:
        1px solid #e8edf5;

    border-radius: 12px;

    background: #f8fafc;
}

.description h3 {
    display: flex;

    align-items: center;

    gap: 7px;

    margin-bottom: 9px;

    color: #102342;

    font-size: 12px;
}

.description h3 i {
    color: #2563eb;
}

.description p {
    color: #64748b;

    font-size: 12px;

    line-height: 1.7;

    word-break: break-word;
}


/* =========================================================
   ACTIONS
   ========================================================= */

.actions {
    display: flex;

    align-items: center;

    gap: 10px;

    margin-top: 20px;
}

.btn {
    display: inline-flex;

    align-items: center;
    justify-content: center;

    gap: 7px;

    padding: 10px 14px;

    border-radius: 9px;

    text-decoration: none;

    font-size: 11px;

    font-weight: 800;

    transition: .2s;
}

.discussion {
    border:
        1px solid #bfdbfe;

    background: #eff6ff;

    color: #2563eb;
}

.discussion:hover {
    border-color: #2563eb;

    background: #2563eb;

    color: white;

    transform:
        translateY(-1px);
}
    
    .update-status {
    border: none;
    background: #2563eb;
    color: white;
    cursor: pointer;
}

.update-status:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

.approve-report,
.reject-report {
    border: none;
    cursor: pointer;
}

.approve-report {
    background: #ecfdf5;
    color: #15803d;
    border: 1px solid #bbf7d0;
}

.approve-report:hover {
    background: #15803d;
    color: white;
    transform: translateY(-1px);
}

.reject-report {
    background: #fff1f2;
    color: #be123c;
    border: 1px solid #fecdd3;
}

.reject-report:hover {
    background: #be123c;
    color: white;
    transform: translateY(-1px);
}

.status-form {
    display: inline-flex;
}

.alert {
    margin-bottom: 17px;
    padding: 12px 15px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
}

.alert.success {
    background: #ecfdf5;
    color: #15803d;
    border: 1px solid #bbf7d0;
}

.alert.error {
    background: #fff1f2;
    color: #be123c;
    border: 1px solid #fecdd3;
}

html.dark-mode .alert.success {
    background: #052e16;
    color: #86efac;
    border-color: #166534;
}

html.dark-mode .alert.error {
    background: #4c0519;
    color: #fda4af;
    border-color: #881337;
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
html.dark-mode .item-title {
    color: #f8fafc;
}

html.dark-mode .page-header p {
    color: #94a3b8;
}

html.dark-mode .admin-profile,
html.dark-mode .details-card,
html.dark-mode .back-btn {
    border-color: #2d3c52;

    background: #1e293b;
}

html.dark-mode .admin-profile strong {
    color: #f8fafc;
}

html.dark-mode .admin-avatar {
    background: #172554;

    color: #93c5fd;
}

html.dark-mode .back-btn {
    color: #cbd5e1;
}

html.dark-mode .back-btn:hover {
    border-color: #3b82f6;

    background: #172554;

    color: #93c5fd;
}

html.dark-mode .item-image {
    background: #172033;

    border-color: #334155;
}

html.dark-mode .item-image img {
    background: #172033;
}

html.dark-mode .title-row {
    border-color: #334155;
}

html.dark-mode .item-id {
    color: #94a3b8;
}

html.dark-mode .info-box,
html.dark-mode .description {
    border-color: #334155;

    background: #172033;
}

html.dark-mode .info-label {
    color: #94a3b8;
}

html.dark-mode .info-value {
    color: #e2e8f0;
}

html.dark-mode .description h3 {
    color: #f8fafc;
}

html.dark-mode .description p {
    color: #cbd5e1;
}

html.dark-mode .type-badge.found {
    border-color: #166534;

    background: #052e16;

    color: #86efac;
}

html.dark-mode .type-badge.lost {
    border-color: #881337;

    background: #4c0519;

    color: #fda4af;
}

html.dark-mode .pending-status {
    background: #431407;

    color: #fdba74;
}

html.dark-mode .rejected-status {
    background: #4c0519;

    color: #fda4af;
}

html.dark-mode .returned-status {
    background: #052e16;

    color: #86efac;
}

html.dark-mode .found-status {
    background: #172554;

    color: #93c5fd;
}

html.dark-mode .lost-status {
    background: #431407;

    color: #fdba74;
}

html.dark-mode .default-status {
    background: #334155;

    color: #cbd5e1;
}

html.dark-mode .discussion {
    border-color: #1e40af;

    background: #172554;

    color: #93c5fd;
}

html.dark-mode .discussion:hover {
    background: #2563eb;

    color: white;
}


html.dark-mode .approve-report {
    background: #052e16;
    color: #86efac;
    border-color: #166534;
}

html.dark-mode .approve-report:hover {
    background: #15803d;
    color: white;
}

html.dark-mode .reject-report {
    background: #4c0519;
    color: #fda4af;
    border-color: #881337;
}

html.dark-mode .reject-report:hover {
    background: #be123c;
    color: white;
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

    .page-header {
        align-items: flex-start;

        flex-direction: column;
    }

    .admin-profile {
        width: 100%;
    }

    .item-image {
        height: 300px;
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

    .info-grid {
        grid-template-columns: 1fr;
    }

    .title-row {
        flex-direction: column;
    }

    .content {
        padding: 21px;
    }

    .item-image {
        height: 240px;
    }

    .actions {
        flex-direction: column;

        align-items: stretch;
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

    <a href="staff-manage-items.php" class="active">
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

                    <i class="fa-solid fa-box-open"></i>

                    Item Details

                </span>


                <h1>

                    Item Information

                </h1>


                <p>

                    Review the complete information for this report.

                </p>


            </div>



            <div class="admin-profile">


                <div class="admin-avatar">

                    <i class="fa-solid fa-user-gear"></i>

                </div>


                <div>


                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $staff_name
                        );
                        ?>

                    </strong>


                    <span>

                        <i class="fa-solid fa-user-gear"></i>
                        Staff

                    </span>


                </div>


            </div>


        </div>



        <!-- BACK -->

        <div class="back-row">


            <a
                href="staff-manage-items.php"
                class="back-btn"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Back to Manage Items

            </a>


        </div>



        <!-- =================================================
             DETAILS CARD
             ================================================= -->

        <div class="details-card">


            <!-- IMAGE -->

            <div class="item-image">


                <?php if (
                    !empty($item["image"]) &&
                    file_exists(
                        "uploads/" .
                        $item["image"]
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


            </div>



            <!-- CONTENT -->

            <div class="content">


                <!-- TITLE -->

                <div class="title-row">


                    <div class="item-title-area">


                        <div class="item-id">

                            <i class="fa-solid fa-hashtag"></i>

                            Item ID:

                            <?php
                            echo intval(
                                $item["item_id"]
                            );
                            ?>

                        </div>


                        <h2 class="item-title">

                            <?php
                            echo htmlspecialchars(
                                $item["item_name"]
                            );
                            ?>

                        </h2>


                    </div>



                    <span
                        class="type-badge <?php
                        echo $type_class;
                        ?>"
                    >


                        <?php if (
                            $item_type === "found"
                        ): ?>

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


                </div>



                <!-- INFO -->

                <div class="info-grid">


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



                    <div class="info-box">


                        <div class="info-label">

                            <i class="fa-solid fa-circle-info"></i>

                            Status

                        </div>


                        <div class="info-value">


                            <span
                                class="status-badge <?php
                                echo $status_class;
                                ?>"
                            >


                                <?php if (
                                    $item_status === "pending"
                                ): ?>

                                    <i class="fa-regular fa-clock"></i>


                                <?php elseif (
                                    $item_status === "rejected"
                                ): ?>

                                    <i class="fa-solid fa-xmark"></i>


                                <?php elseif (
                                    $item_status === "returned"
                                ): ?>

                                    <i class="fa-solid fa-check"></i>


                                <?php elseif (
                                    $item_status === "found"
                                ): ?>

                                    <i class="fa-solid fa-box-open"></i>


                                <?php elseif (
                                    $item_status === "lost"
                                ): ?>

                                    <i class="fa-solid fa-magnifying-glass"></i>


                                <?php else: ?>

                                    <i class="fa-solid fa-circle"></i>


                                <?php endif; ?>


                                <?php
                                echo htmlspecialchars(
                                    $item["status"]
                                );
                                ?>


                            </span>


                        </div>


                    </div>


                </div>



                <!-- DESCRIPTION -->

                <div class="description">


                    <h3>

                        <i class="fa-regular fa-file-lines"></i>

                        Description

                    </h3>


                    <p>

                        <?php

                        if (
                            !empty(
                                trim(
                                    $item["description"]
                                )
                            )
                        ) {

                            echo nl2br(
                                htmlspecialchars(
                                    $item["description"]
                                )
                            );

                        } else {

                            echo "No description provided.";

                        }

                        ?>

                    </p>


                </div>



                <!-- ACTION -->

                <?php if (!empty($message)): ?>
                <div class="alert <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                
                <?php endif; ?>
                
               <div class="actions">
                   <a
                      href="staff-item-discussion.php?id=<?php
                            echo intval($item["item_id"]);
                            ?>"
                      class="btn discussion"
                      >
                       <i class="fa-regular fa-comments"></i>
                       View Discussion
                   </a>
                   <?php if ($item_status === "pending"): ?>

                   <form
                       method="POST"
                       class="status-form"
                       onsubmit="return confirm('Approve this item report?');"
                   >
                       <input
                           type="hidden"
                           name="report_action"
                           value="approve"
                       >

                       <button
                           type="submit"
                           class="btn approve-report"
                       >
                           <i class="fa-solid fa-check"></i>
                           Approve
                       </button>
                   </form>

                   <form
                       method="POST"
                       class="status-form"
                       onsubmit="return confirm('Reject this item report?');"
                   >
                       <input
                           type="hidden"
                           name="report_action"
                           value="reject"
                       >

                       <button
                           type="submit"
                           class="btn reject-report"
                       >
                           <i class="fa-solid fa-xmark"></i>
                           Reject
                       </button>
                   </form>

                   <?php endif; ?>

                   <?php if ($next_status !== null): ?>
                   <form
                         method="POST"
                         class="status-form"
                         onsubmit="return confirm(
                                   'Change status to <?php
                                   echo htmlspecialchars($next_status);
                                   ?>?'
                                   );"
                         >
                       <input
                              type="hidden"
                              name="new_status"
                              value="<?php
                                     echo htmlspecialchars($next_status);
                                     ?>"
                              >
                       <button
                               type="submit"
                               name="update_status"
                               class="btn update-status"
                               >
                           <i class="fa-solid fa-arrow-right"></i>
                           Mark as
                           <?php
                           echo htmlspecialchars($next_status);
                           ?>
                       </button>
                   </form>
                   <?php endif; ?>
                </div>


            </div>


        </div>


    </div>


</main>


</body>
</html>