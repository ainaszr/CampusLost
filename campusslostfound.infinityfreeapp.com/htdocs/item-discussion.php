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
   GET USER
========================================================= */

$user_sql = "SELECT user_id, name, role
             FROM users
             WHERE user_id = ?";

$user_stmt = $conn->prepare($user_sql);

if (!$user_stmt) {
    die("SQL Error: " . $conn->error);
}

$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();

$user_result = $user_stmt->get_result();

if ($user_result->num_rows !== 1) {
    die("User account not found.");
}

$current_user = $user_result->fetch_assoc();

$user_stmt->close();


/* =========================================================
   GET ITEM ID
========================================================= */

$item_id = isset($_GET["id"])
    ? intval($_GET["id"])
    : 0;

if ($item_id <= 0) {
    header("Location: found-items.php");
    exit();
}


/* =========================================================
   GET ITEM
========================================================= */

$item_sql = "SELECT
                item_id,
                item_name,
                item_type,
                category,
                location,
                date_reported,
                description,
                image,
                status,
                user_id
             FROM items
             WHERE item_id = ?";

$item_stmt = $conn->prepare($item_sql);

if (!$item_stmt) {
    die("SQL Error: " . $conn->error);
}

$item_stmt->bind_param("i", $item_id);
$item_stmt->execute();

$item_result = $item_stmt->get_result();

if ($item_result->num_rows !== 1) {
    die("Item not found.");
}

$item = $item_result->fetch_assoc();

$item_stmt->close();


/* =========================================================
   CHECK ITEM OWNER
========================================================= */

$is_owner =
    intval($item["user_id"]) === $user_id;


/* =========================================================
   CREATE COMMENTS TABLE
========================================================= */

$create_comments = "CREATE TABLE IF NOT EXISTS comments (

    comment_id INT(11) NOT NULL AUTO_INCREMENT,

    item_id INT(11) NOT NULL,

    user_id INT(11) NOT NULL,

    comment_text TEXT NOT NULL,

    comment_date DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (comment_id),

    INDEX (item_id),

    INDEX (user_id)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$conn->query($create_comments);


/* =========================================================
   ADD COMMENT
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["add_comment"])
) {

    $comment_text =
        isset($_POST["comment_text"])
        ? trim($_POST["comment_text"])
        : "";

    if ($comment_text !== "") {

        if (strlen($comment_text) > 1000) {

            $comment_text =
                substr($comment_text, 0, 1000);

        }

        $insert_sql =
            "INSERT INTO comments
            (
                item_id,
                user_id,
                comment_text,
                comment_date
            )
            VALUES (?, ?, ?, NOW())";

        $insert_stmt =
    $conn->prepare($insert_sql);

if ($insert_stmt) {

    $insert_stmt->bind_param(
        "iis",
        $item_id,
        $user_id,
        $comment_text
    );

    $insert_stmt->execute();


    /* =============================================
       NOTIFY ITEM OWNER
    ============================================= */

    if ($insert_stmt->affected_rows > 0) {

        // Get owner of the item
        $owner_id = intval($item["user_id"]);

        // Don't notify if owner comments on own item
        if (
            $owner_id > 0 &&
            $owner_id !== $user_id
        ) {

            $commenter_name =
                !empty($current_user["name"])
                ? $current_user["name"]
                : "Someone";

            $item_name =
                $item["item_name"];

            $message =
                $commenter_name .
                ' commented on your item "' .
                $item_name .
                '".';


            /* INSERT NOTIFICATION */

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
                $conn->prepare(
                    $notification_sql
                );

            if ($notification_stmt) {

                $notification_stmt->bind_param(
                    "is",
                    $owner_id,
                    $message
                );

                $notification_stmt->execute();

                $notification_stmt->close();
            }
        }
    }

    $insert_stmt->close();

}

}

    header(
        "Location: item-discussion.php?id="
        . $item_id
        . "#comments"
    );

    exit();
}


/* =========================================================
   DELETE OWN COMMENT
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["delete_comment"])
) {

    $comment_id =
        isset($_POST["comment_id"])
        ? intval($_POST["comment_id"])
        : 0;

    if ($comment_id > 0) {

        $delete_sql =
            "DELETE FROM comments
             WHERE comment_id = ?
             AND user_id = ?
             AND item_id = ?";

        $delete_stmt =
            $conn->prepare($delete_sql);

        if ($delete_stmt) {

            $delete_stmt->bind_param(
                "iii",
                $comment_id,
                $user_id,
                $item_id
            );

            $delete_stmt->execute();

            $delete_stmt->close();

        }

    }

    header(
        "Location: item-discussion.php?id="
        . $item_id
        . "#comments"
    );

    exit();
}


/* =========================================================
   GET COMMENTS
========================================================= */

/*
   Detect the profile-picture column safely.
   This prevents HTTP 500 if the column name is different.
*/
$profile_column = null;
$profile_candidates = [
    "profile_picture",
    "profile_image",
    "profile_photo",
    "avatar",
    "profile_pic"
];

$columns_result = $conn->query("SHOW COLUMNS FROM users");

if ($columns_result) {
    $user_columns = [];

    while ($column = $columns_result->fetch_assoc()) {
        $user_columns[] = $column["Field"];
    }

    foreach ($profile_candidates as $candidate) {
        if (in_array($candidate, $user_columns, true)) {
            $profile_column = $candidate;
            break;
        }
    }
}

$profile_select = $profile_column
    ? ", u.`" . $profile_column . "` AS profile_picture"
    : ", NULL AS profile_picture";

$comments_sql =
    "SELECT
        c.comment_id,
        c.comment_text,
        c.comment_date,
        c.user_id,
        u.name,
        u.role
        " . $profile_select . "
     FROM comments c
     LEFT JOIN users u
     ON c.user_id = u.user_id
     WHERE c.item_id = ?
     ORDER BY
        c.comment_date ASC,
        c.comment_id ASC";

$comments_stmt =
    $conn->prepare($comments_sql);

if (!$comments_stmt) {
    die("SQL Error: " . $conn->error);
}

$comments_stmt->bind_param(
    "i",
    $item_id
);

$comments_stmt->execute();

$comments_result =
    $comments_stmt->get_result();

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
    Discussion -
    <?php echo htmlspecialchars($item["item_name"]); ?>
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

    max-width: 1100px;

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

.header {

    margin-bottom: 24px;

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

    font-size: 28px;

    margin-bottom: 7px;

}


.header p {

    color: #748199;

    font-size: 13px;

}


/* =========================================================
   ITEM PREVIEW
========================================================= */

.item-card {

    display: grid;

    grid-template-columns:
        230px 1fr;

    background: white;

    border:
        1px solid #e8edf5;

    border-radius: 16px;

    overflow: hidden;

    margin-bottom: 22px;

    box-shadow:
        0 5px 20px
        rgba(15,39,71,.045);

}


.item-image {

    position: relative;

    min-height: 210px;

    background: #edf1f6;

    overflow: hidden;

}


.item-image img {

    width: 100%;

    height: 100%;

    object-fit: cover;

    display: block;

}


.no-image {

    width: 100%;

    height: 100%;

    min-height: 210px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #94a3b8;

    font-size: 38px;

}


/* TYPE */

.type-badge {

    position: absolute;

    top: 13px;

    left: 13px;

    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 6px 10px;

    border-radius: 30px;

    font-size: 9px;

    font-weight: 800;

    text-transform: uppercase;

    backdrop-filter: blur(6px);

}


.type-badge.found {

    background:
        rgba(5,78,38,.88);

    color: #dcfce7;

}


.type-badge.lost {

    background:
        rgba(127,29,29,.88);

    color: #fee2e2;

}


/* ITEM INFORMATION */

.item-info {

    padding: 24px;

}


.item-heading {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 17px;

}


.item-heading h2 {

    color: #102342;

    font-size: 21px;

    text-transform: capitalize;

}


.item-status {

    padding: 6px 10px;

    background: #f1f5f9;

    color: #64748b;

    border-radius: 20px;

    font-size: 9px;

    font-weight: 800;

    text-transform: uppercase;

    white-space: nowrap;

}


.item-details {

    display: grid;

    grid-template-columns:
        repeat(3, minmax(0,1fr));

    gap: 10px;

    margin-bottom: 14px;

}


.detail-box {

    padding: 11px 12px;

    background: #f8fafc;

    border:
        1px solid #e2e8f0;

    border-radius: 9px;

}


.detail-label {

    display: flex;

    align-items: center;

    gap: 6px;

    color: #8a97aa;

    font-size: 9px;

    margin-bottom: 5px;

}


.detail-label i {

    color: #2563eb;

}


.detail-value {

    color: #334155;

    font-size: 12px;

    font-weight: 700;

    line-height: 1.4;

    word-break: break-word;

}


.item-description {

    color: #748199;

    font-size: 12px;

    line-height: 1.6;

}


/* =========================================================
   DISCUSSION CARD
========================================================= */

.discussion-card {

    background: white;

    border:
        1px solid #e8edf5;

    border-radius: 16px;

    padding: 25px;

    box-shadow:
        0 5px 20px
        rgba(15,39,71,.045);

}


.discussion-title {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    padding-bottom: 18px;

    margin-bottom: 20px;

    border-bottom:
        1px solid #edf1f5;

}


.discussion-heading {

    display: flex;

    align-items: center;

    gap: 10px;

}


.discussion-icon {

    width: 36px;

    height: 36px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #e8f0ff;

    color: #2563eb;

    border-radius: 10px;

    font-size: 14px;

}


.discussion-title h2 {

    color: #102342;

    font-size: 18px;

    margin-bottom: 2px;

}


.discussion-title p {

    color: #94a3b8;

    font-size: 10px;

}


.comment-count {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    background: #eef4ff;

    color: #2563eb;

    padding: 7px 10px;

    border-radius: 20px;

    font-size: 10px;

    font-weight: 800;

}


/* =========================================================
   COMMENT FORM
========================================================= */

.comment-form {

    background: #f8fafc;

    border:
        1px solid #e2e8f0;

    padding: 17px;

    border-radius: 12px;

    margin-bottom: 24px;

}


.comment-form label {

    display: flex;

    align-items: center;

    gap: 7px;

    color: #334155;

    font-weight: 700;

    font-size: 11px;

    margin-bottom: 9px;

}


.comment-form label i {

    color: #2563eb;

}


.comment-form textarea {

    width: 100%;

    min-height: 95px;

    resize: vertical;

    padding: 12px 13px;

    border:
        1px solid #d8e0eb;

    border-radius: 9px;

    outline: none;

    font-family: Arial, sans-serif;

    font-size: 12px;

    line-height: 1.5;

    color: #334155;

    background: white;

    transition: .2s;

}


.comment-form textarea::placeholder {

    color: #a7b0be;

}


.comment-form textarea:focus {

    border-color: #2563eb;

    box-shadow:
        0 0 0 3px
        rgba(37,99,235,.08);

}


.form-bottom {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

    margin-top: 10px;

}


.character-note {

    color: #94a3b8;

    font-size: 9px;

}


.comment-submit {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 10px 14px;

    border: none;

    border-radius: 8px;

    background: #2563eb;

    color: white;

    font-size: 10px;

    font-weight: 700;

    cursor: pointer;

    transition: .2s;

}


.comment-submit:hover {

    background: #1d4ed8;

    transform:
        translateY(-1px);

}


/* =========================================================
   COMMENTS
========================================================= */

.comment-list {

    display: flex;

    flex-direction: column;

    gap: 12px;

}


.comment {

    position: relative;

    padding: 16px;

    border:
        1px solid #e2e8f0;

    border-radius: 12px;

    background: white;

    transition: .2s;

}


.comment:hover {

    border-color: #cdd9ea;

    box-shadow:
        0 5px 14px
        rgba(15,39,71,.035);

}


.comment.own-comment {

    background: #f8fbff;

    border-color: #dbeafe;

}


.comment-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

    margin-bottom: 10px;

}


.comment-user {

    display: flex;

    align-items: center;

    gap: 10px;

}


.avatar {

    width: 36px;

    height: 36px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background: #e8f0ff;

    color: #2563eb;

    font-size: 12px;

    font-weight: 800;

}
    
.avatar{
    overflow:hidden;
}

.avatar img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
    border-radius:50%;
}


.user-info {

    display: flex;

    flex-direction: column;

    gap: 3px;

}


.name-row {

    display: flex;

    align-items: center;

    gap: 7px;

}


.comment-user strong {

    color: #102342;

    font-size: 12px;

}


.you-badge {

    padding: 3px 6px;

    border-radius: 10px;

    background: #dbeafe;

    color: #2563eb;

    font-size: 7px;

    font-weight: 800;

    text-transform: uppercase;

}


.owner-badge {

    padding: 3px 6px;

    border-radius: 10px;

    background: #fef3c7;

    color: #b45309;

    font-size: 7px;

    font-weight: 800;

    text-transform: uppercase;

}


.comment-user small {

    color: #94a3b8;

    font-size: 9px;

}


.comment-date {

    color: #94a3b8;

    font-size: 9px;

}


.comment-text {

    margin-left: 46px;

    color: #475569;

    font-size: 12px;

    line-height: 1.65;

    word-break: break-word;

}


.delete-form {

    margin-left: 46px;

    margin-top: 8px;

}


.delete-btn {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 0;

    border: none;

    background: none;

    color: #ef4444;

    font-size: 9px;

    cursor: pointer;

}


.delete-btn:hover {

    color: #dc2626;

    text-decoration: underline;

}


/* =========================================================
   EMPTY COMMENTS
========================================================= */

.empty-comments {

    text-align: center;

    padding: 45px 15px;

}


.empty-icon {

    width: 52px;

    height: 52px;

    display: flex;

    align-items: center;

    justify-content: center;

    margin: 0 auto 12px;

    border-radius: 15px;

    background: #eef4ff;

    color: #2563eb;

    font-size: 19px;

}


.empty-comments h3 {

    color: #334155;

    font-size: 14px;

    margin-bottom: 5px;

}


.empty-comments p {

    color: #94a3b8;

    font-size: 11px;

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


html.dark-mode .header h1 {

    color: #f8fafc;

}


html.dark-mode .header p {

    color: #94a3b8;

}


html.dark-mode .item-card,
html.dark-mode .discussion-card {

    background: #1e293b;

    border-color: #27364c;

    box-shadow:
        0 5px 20px
        rgba(0,0,0,.18);

}


html.dark-mode .item-image {

    background: #263449;

}


html.dark-mode .item-heading h2 {

    color: #f8fafc;

}


html.dark-mode .item-status {

    background: #334155;

    color: #cbd5e1;

}


html.dark-mode .detail-box {

    background: #111b2f;

    border-color: #334155;

}


html.dark-mode .detail-label {

    color: #94a3b8;

}


html.dark-mode .detail-label i {

    color: #60a5fa;

}


html.dark-mode .detail-value {

    color: #e2e8f0;

}


html.dark-mode .item-description {

    color: #94a3b8;

}


html.dark-mode .discussion-title {

    border-color: #334155;

}


html.dark-mode .discussion-icon {

    background: #172b4d;

    color: #60a5fa;

}


html.dark-mode .discussion-title h2 {

    color: #f8fafc;

}


html.dark-mode .comment-count {

    background: #172b4d;

    color: #93c5fd;

}


html.dark-mode .comment-form {

    background: #111b2f;

    border-color: #334155;

}


html.dark-mode .comment-form label {

    color: #e2e8f0;

}


html.dark-mode .comment-form textarea {

    background: #1e293b;

    color: #e2e8f0;

    border-color: #475569;

}


html.dark-mode .comment {

    background: #111b2f;

    border-color: #334155;

}


html.dark-mode .comment.own-comment {

    background: #172554;

    border-color: #1e3a8a;

}


html.dark-mode .comment-user strong {

    color: #f8fafc;

}


html.dark-mode .comment-text {

    color: #cbd5e1;

}


html.dark-mode .avatar {

    background: #172b4d;

    color: #93c5fd;

}


html.dark-mode .you-badge {

    background: #1e3a8a;

    color: #bfdbfe;

}


html.dark-mode .owner-badge {

    background: #451a03;

    color: #fcd34d;

}


html.dark-mode .empty-icon {

    background: #172b4d;

    color: #60a5fa;

}


html.dark-mode .empty-comments h3 {

    color: #e2e8f0;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 900px) {

    .item-card {

        grid-template-columns: 1fr;

    }


    .item-image {

        height: 260px;

    }


    .item-details {

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


    .item-details {

        grid-template-columns: 1fr;

    }


    .discussion-title {

        align-items: flex-start;

        flex-direction: column;

    }


    .form-bottom {

        align-items: flex-start;

        flex-direction: column;

    }


    .comment-header {

        align-items: flex-start;

        flex-direction: column;

    }


    .comment-text,
    .delete-form {

        margin-left: 0;

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

        Back to Item Details

    </a>



    <!-- HEADER -->

    <div class="header">


        <span class="page-label">

            <i class="fa-solid fa-comments"></i>

            Community Discussion

        </span>


        <h1>
            Item Discussion
        </h1>


        <p>
            Ask questions or share useful information
            about this lost or found item.
        </p>


    </div>



    <!-- =====================================================
         ITEM PREVIEW
    ====================================================== -->

    <section class="item-card">


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



            <?php if (
                $item["item_type"] === "Found"
            ): ?>


                <span class="type-badge found">

                    <i class="fa-solid fa-circle"></i>

                    Found

                </span>


            <?php else: ?>


                <span class="type-badge lost">

                    <i class="fa-solid fa-circle"></i>

                    Lost

                </span>


            <?php endif; ?>


        </div>



        <!-- INFORMATION -->

        <div class="item-info">


            <div class="item-heading">


                <h2>

                    <?php
                        echo htmlspecialchars(
                            $item["item_name"]
                        );
                    ?>

                </h2>


                <span class="item-status">

                    <?php
                        echo htmlspecialchars(
                            $item["status"]
                        );
                    ?>

                </span>


            </div>



            <div class="item-details">


                <div class="detail-box">


                    <div class="detail-label">

                        <i class="fa-solid fa-folder"></i>

                        Category

                    </div>


                    <div class="detail-value">

                        <?php
                            echo htmlspecialchars(
                                $item["category"]
                            );
                        ?>

                    </div>


                </div>



                <div class="detail-box">


                    <div class="detail-label">

                        <i class="fa-solid fa-location-dot"></i>

                        Location

                    </div>


                    <div class="detail-value">

                        <?php
                            echo htmlspecialchars(
                                $item["location"]
                            );
                        ?>

                    </div>


                </div>



                <div class="detail-box">


                    <div class="detail-label">

                        <i class="fa-regular fa-calendar"></i>

                        Date

                    </div>


                    <div class="detail-value">

                        <?php
                            echo htmlspecialchars(
                                $item["date_reported"]
                            );
                        ?>

                    </div>


                </div>


            </div>



            <?php if (
                !empty($item["description"])
            ): ?>


                <div class="item-description">

                    <?php

                        echo nl2br(
                            htmlspecialchars(
                                $item["description"]
                            )
                        );

                    ?>

                </div>


            <?php endif; ?>


        </div>


    </section>



    <!-- =====================================================
         DISCUSSION
    ====================================================== -->

    <section
        class="discussion-card"
        id="comments"
    >


        <div class="discussion-title">


            <div class="discussion-heading">


                <div class="discussion-icon">

                    <i class="fa-solid fa-comments"></i>

                </div>


                <div>

                    <h2>
                        Discussion
                    </h2>

                    <p>
                        Conversation about this item
                    </p>

                </div>


            </div>



            <span class="comment-count">

                <i class="fa-regular fa-comment"></i>

                <?php
                    echo $comments_result->num_rows;
                ?>

                Comment<?php
                    echo
                        $comments_result->num_rows == 1
                        ? ""
                        : "s";
                ?>

            </span>


        </div>



        <!-- =================================================
             COMMENT FORM
        ================================================== -->

        <form
            method="POST"
            class="comment-form"
        >


            <label for="comment_text">

                <i class="fa-solid fa-user"></i>

                Comment as

                <?php
                    echo htmlspecialchars(
                        $current_user["name"]
                    );
                ?>

            </label>



            <textarea
                id="comment_text"
                name="comment_text"
                maxlength="1000"
                placeholder="Ask a question or share information about this item..."
                required
            ></textarea>



            <div class="form-bottom">


                <span class="character-note">

                    Maximum 1000 characters

                </span>


                <button
                    type="submit"
                    name="add_comment"
                    class="comment-submit"
                >

                    <i class="fa-solid fa-paper-plane"></i>

                    Post Comment

                </button>


            </div>


        </form>



        <!-- =================================================
             COMMENTS
        ================================================== -->

        <div class="comment-list">


            <?php if (
                $comments_result->num_rows > 0
            ): ?>


                <?php while (
                    $comment =
                        $comments_result->fetch_assoc()
                ): ?>


                    <?php

                        $display_name =
                            !empty($comment["name"])
                            ? $comment["name"]
                            : "User";


                        $initial =
                            strtoupper(
                                substr(
                                    trim($display_name),
                                    0,
                                    1
                                )
                            );


                        $is_own_comment =
                            intval(
                                $comment["user_id"]
                            ) === $user_id;


                        $is_report_owner =
                            intval(
                                $comment["user_id"]
                            ) === intval(
                                $item["user_id"]
                            );

                    ?>


                    <article
                        class="comment <?php
                            echo
                                $is_own_comment
                                ? "own-comment"
                                : "";
                        ?>"
                    >


                        <div class="comment-header">


                            <div class="comment-user">


                                <?php
                                    $profile_picture =
                                        trim(
                                            (string) (
                                                $comment["profile_picture"]
                                                ?? ""
                                            )
                                        );

                                    $profile_src = "";

                                    if ($profile_picture !== "") {

                                        /*
                                           Support values saved as:
                                           - filename.jpg
                                           - uploads/filename.jpg
                                           - uploads/profile/filename.jpg
                                           - full http/https URL
                                        */

                                        if (
                                            preg_match(
                                                '/^https?:\/\//i',
                                                $profile_picture
                                            )
                                        ) {
                                            $profile_src =
                                                $profile_picture;
                                        } else {

                                            $clean_profile =
                                                ltrim(
                                                    str_replace(
                                                        "\\",
                                                        "/",
                                                        $profile_picture
                                                    ),
                                                    "/"
                                                );

                                            $possible_paths = [];

                                            if (
                                                strpos(
                                                    $clean_profile,
                                                    "uploads/"
                                                ) === 0
                                            ) {
                                                $possible_paths[] =
                                                    $clean_profile;
                                            } else {
                                                $possible_paths[] =
                                                    "uploads/profile/" .
                                                    $clean_profile;

                                                $possible_paths[] =
                                                    "uploads/" .
                                                    $clean_profile;
                                            }

                                            foreach (
                                                $possible_paths
                                                as $possible_path
                                            ) {
                                                if (
                                                    file_exists(
                                                        __DIR__ .
                                                        "/" .
                                                        $possible_path
                                                    )
                                                ) {
                                                    $profile_src =
                                                        $possible_path;
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                ?>

                                <div class="avatar">

                                    <?php if (
                                        $profile_src !== ""
                                    ): ?>

                                        <img
                                            src="<?php
                                                echo htmlspecialchars(
                                                    $profile_src
                                                );
                                            ?>"
                                            alt="<?php
                                                echo htmlspecialchars(
                                                    $display_name
                                                );
                                            ?>"
                                        >

                                    <?php else: ?>

                                        <?php
                                            echo htmlspecialchars(
                                                $initial
                                            );
                                        ?>

                                    <?php endif; ?>

                                </div>

                                <div class="user-info">


                                    <div class="name-row">


                                        <strong>

                                            <?php
                                                echo htmlspecialchars(
                                                    $display_name
                                                );
                                            ?>

                                        </strong>



                                        <?php if (
                                            $is_own_comment
                                        ): ?>


                                            <span class="you-badge">

                                                You

                                            </span>


                                        <?php endif; ?>



                                        <?php if (
                                            $is_report_owner
                                        ): ?>


                                            <span class="owner-badge">

                                                Reporter

                                            </span>


                                        <?php endif; ?>


                                    </div>



                                    <small>

                                        <?php

                                            echo htmlspecialchars(
                                                ucfirst(
                                                    $comment["role"]
                                                    ?? "user"
                                                )
                                            );

                                        ?>

                                    </small>


                                </div>


                            </div>



                            <span class="comment-date">

                                <i class="fa-regular fa-clock"></i>

                                <?php

                                    echo htmlspecialchars(
                                        $comment[
                                            "comment_date"
                                        ]
                                    );

                                ?>

                            </span>


                        </div>



                        <div class="comment-text">

                            <?php

                                echo nl2br(
                                    htmlspecialchars(
                                        $comment[
                                            "comment_text"
                                        ]
                                    )
                                );

                            ?>

                        </div>



                        <?php if (
                            $is_own_comment
                        ): ?>


                            <form
                                method="POST"
                                class="delete-form"
                                onsubmit="return confirm('Delete this comment?');"
                            >


                                <input
                                    type="hidden"
                                    name="comment_id"
                                    value="<?php
                                        echo intval(
                                            $comment[
                                                "comment_id"
                                            ]
                                        );
                                    ?>"
                                >


                                <button
                                    type="submit"
                                    name="delete_comment"
                                    class="delete-btn"
                                >

                                    <i class="fa-regular fa-trash-can"></i>

                                    Delete my comment

                                </button>


                            </form>


                        <?php endif; ?>


                    </article>


                <?php endwhile; ?>


            <?php else: ?>


                <div class="empty-comments">


                    <div class="empty-icon">

                        <i class="fa-regular fa-comments"></i>

                    </div>


                    <h3>
                        No comments yet
                    </h3>


                    <p>
                        Be the first person to start
                        the discussion about this item.
                    </p>


                </div>


            <?php endif; ?>


        </div>


    </section>


</div>


</main>


</body>

</html>