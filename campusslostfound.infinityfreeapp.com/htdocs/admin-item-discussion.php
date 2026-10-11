<?php

session_start();

require_once "db.php";



/* =========================================================

   LOGIN CHECK

   ========================================================= */



if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");

    exit();

}



$admin_id = intval($_SESSION["user_id"]);





/* =========================================================

   ADMIN CHECK

   ========================================================= */



$admin_sql = "SELECT user_id, name, role

              FROM users

              WHERE user_id = ?";



$admin_stmt = $conn->prepare($admin_sql);



if (!$admin_stmt) {

    die("SQL Error: " . $conn->error);

}



$admin_stmt->bind_param("i", $admin_id);

$admin_stmt->execute();



$admin_result = $admin_stmt->get_result();



if ($admin_result->num_rows === 0) {

    die("User not found.");

}



$admin = $admin_result->fetch_assoc();



$admin_stmt->close();



if (strtolower(trim($admin["role"])) !== "admin") {

    header("Location: dashboard.php");

    exit();

}



$admin_name = $admin["name"];





/* =========================================================

   ITEM ID CHECK

   ========================================================= */



if (!isset($_GET["id"])) {

    header("Location: admin-items.php");

    exit();

}



$item_id = intval($_GET["id"]);



if ($item_id <= 0) {

    header("Location: admin-items.php");

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

   CREATE COMMENTS TABLE IF NEEDED

   ========================================================= */



$conn->query("

    CREATE TABLE IF NOT EXISTS comments (

        comment_id INT(11) AUTO_INCREMENT PRIMARY KEY,

        item_id INT(11) NOT NULL,

        user_id INT(11) NOT NULL,

        comment_text TEXT NOT NULL,

        comment_date DATETIME DEFAULT CURRENT_TIMESTAMP

    )

");





/* =========================================================

   DELETE COMMENT

   ========================================================= */



if (

    $_SERVER["REQUEST_METHOD"] === "POST" &&

    isset($_POST["delete_comment_id"])

) {



    $delete_id =

        intval($_POST["delete_comment_id"]);



    if ($delete_id > 0) {



        /*

         * Restrict deletion to this item as well.

         * Admin can still delete any comment shown on this page.

         */



        $delete_stmt = $conn->prepare(

            "DELETE FROM comments

             WHERE comment_id = ?

             AND item_id = ?"

        );



        if ($delete_stmt) {



            $delete_stmt->bind_param(

                "ii",

                $delete_id,

                $item_id

            );



            $delete_stmt->execute();



            $delete_stmt->close();

        }

    }



    header(

        "Location: admin-item-discussion.php?id=" .

        $item_id .

        "&deleted=1"

    );



    exit();

}





/* =========================================================

   GET COMMENTS

   ========================================================= */




/*
   PROFILE PICTURE SUPPORT
   Detect the profile image column safely so a different column name
   will not cause an HTTP 500 error.
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

$comments_sql = "

    SELECT

        c.comment_id,

        c.comment_text,

        c.comment_date,

        u.name,

        u.user_id
        " . $profile_select . "

    FROM comments c

    LEFT JOIN users u

        ON c.user_id = u.user_id

    WHERE c.item_id = ?

    ORDER BY

        c.comment_date ASC,

        c.comment_id ASC

";



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



$total_comments =

    $comments_result->num_rows;





/* =========================================================

   DISPLAY HELPERS

   ========================================================= */



$item_type =

    strtolower(

        trim($item["item_type"])

    );



$item_status =

    strtolower(

        trim($item["status"])

    );



$type_class =

    ($item_type === "found")

        ? "found"

        : "lost";



$status_class = "default-status";



if ($item_status === "pending") {



    $status_class =

        "pending-status";



} elseif ($item_status === "rejected") {



    $status_class =

        "rejected-status";



} elseif ($item_status === "returned") {



    $status_class =

        "returned-status";



} elseif ($item_status === "found") {



    $status_class =

        "found-status";



} elseif ($item_status === "lost") {



    $status_class =

        "lost-status";

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

    Discussion -

    <?php

    echo htmlspecialchars(

        $item["item_name"]

    );

    ?>

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

        *background* .3s ease,

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

    display: flex;



    align-items: center;



    gap: 9px;



    flex-wrap: wrap;



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

   ITEM SUMMARY

   ========================================================= */



.item-card {

    display: grid;



    grid-template-columns:

        180px 1fr;



    overflow: hidden;



    margin-bottom: 20px;



    border:

        1px solid #e8edf5;



    border-radius: 16px;



    background: white;



    box-shadow:

        0 6px 22px

        rgba(15,39,71,.05);

}



.item-image {

    min-height: 180px;



    display: flex;



    align-items: center;

    justify-content: center;



    overflow: hidden;



    background: #eef4ff;

}



.item-image img {

    width: 100%;

    height: 100%;



    object-fit: cover;

}



.no-image {

    display: flex;



    flex-direction: column;



    align-items: center;



    gap: 8px;



    color: #94a3b8;



    font-size: 10px;



    font-weight: 700;

}



.no-image i {

    color: #bfdbfe;



    font-size: 34px;

}



.item-content {

    padding: 21px 23px;

}



.item-top {

    display: flex;



    align-items: flex-start;

    justify-content: space-between;



    gap: 15px;



    margin-bottom: 16px;

}



.item-id {

    display: flex;



    align-items: center;



    gap: 5px;



    margin-bottom: 6px;



    color: #94a3b8;



    font-size: 9px;



    font-weight: 700;

}



.item-name {

    color: #102342;



    font-size: 20px;



    line-height: 1.3;



    word-break: break-word;

}



.type-badge {

    flex-shrink: 0;



    display: inline-flex;



    align-items: center;



    gap: 5px;



    padding: 6px 10px;



    border-radius: 20px;



    font-size: 9px;



    font-weight: 800;



    text-transform: uppercase;

}



.type-badge.found {

    border:

        1px solid #bbf7d0;



    background: #ecfdf5;



    color: #15803d;

}



.type-badge.lost {

    border:

        1px solid #fecdd3;



    background: #fff1f2;



    color: #be123c;

}





/* =========================================================

   ITEM META

   ========================================================= */



.item-meta {

    display: grid;



    grid-template-columns:

        repeat(2, 1fr);



    gap: 9px;

}



.meta-box {

    padding: 10px 12px;



    border:

        1px solid #edf1f5;



    border-radius: 9px;



    background: #f8fafc;

}



.meta-label {

    display: flex;



    align-items: center;



    gap: 5px;



    margin-bottom: 5px;



    color: #8491a5;



    font-size: 9px;



    font-weight: 700;



    text-transform: uppercase;

}



.meta-label i {

    color: #2563eb;

}



.meta-value {

    color: #334155;



    font-size: 11px;



    font-weight: 700;



    word-break: break-word;

}





/* =========================================================

   STATUS

   ========================================================= */



.status-badge {

    display: inline-flex;



    align-items: center;



    gap: 5px;



    padding: 4px 8px;



    border-radius: 20px;



    font-size: 8px;



    font-weight: 800;



    text-transform: uppercase;

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

   DISCUSSION CARD

   ========================================================= */



.discussion-card {

    overflow: hidden;



    border:

        1px solid #e8edf5;



    border-radius: 16px;



    background: white;



    box-shadow:

        0 6px 22px

        rgba(15,39,71,.05);

}



.discussion-header {

    display: flex;



    align-items: center;

    justify-content: space-between;



    gap: 15px;



    padding: 20px 22px;



    border-bottom:

        1px solid #edf1f5;

}



.discussion-heading {

    display: flex;



    align-items: center;



    gap: 11px;

}



.discussion-icon {

    width: 38px;

    height: 38px;



    flex-shrink: 0;



    display: flex;



    align-items: center;

    justify-content: center;



    border-radius: 10px;



    background: #eff6ff;



    color: #2563eb;



    font-size: 14px;

}



.discussion-heading h2 {

    margin-bottom: 4px;



    color: #102342;



    font-size: 16px;

}



.discussion-heading p {

    color: #8491a5;



    font-size: 10px;

}



.comment-count {

    display: inline-flex;



    align-items: center;



    gap: 5px;



    padding: 6px 10px;



    border-radius: 20px;



    background: #eff6ff;



    color: #2563eb;



    font-size: 9px;



    font-weight: 800;

}





/* =========================================================

   COMMENTS

   ========================================================= */



.comment-list {

    display: flex;



    flex-direction: column;



    gap: 13px;



    padding: 20px 22px;

}



.comment {

    position: relative;



    padding: 15px;



    border:

        1px solid #e8edf5;



    border-radius: 12px;



    background: #f8fafc;

}



.comment-top {

    display: flex;



    align-items: flex-start;

    justify-content: space-between;



    gap: 15px;



    margin-bottom: 10px;

}



.comment-user {

    display: flex;



    align-items: center;



    gap: 9px;

}



.comment-avatar {

    width: 32px;

    height: 32px;



    flex-shrink: 0;



    display: flex;



    align-items: center;

    justify-content: center;



    border-radius: 9px;



    background: #e0ecff;



    color: #2563eb;



    font-size: 11px;

}

.comment-avatar {
    overflow: hidden;
    border-radius: 50%;
}

.comment-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    border-radius: 50%;
}



.comment-name {

    display: block;



    margin-bottom: 3px;



    color: #172033;



    font-size: 11px;



    font-weight: 800;

}



.comment-user-id {

    color: #94a3b8;



    font-size: 8px;



    font-weight: 700;

}



.comment-date {

    display: flex;



    align-items: center;



    gap: 5px;



    color: #94a3b8;



    font-size: 9px;



    white-space: nowrap;

}



.comment-text {

    padding-left: 41px;



    color: #475569;



    font-size: 11px;



    line-height: 1.7;



    white-space: pre-wrap;



    word-break: break-word;

}





/* =========================================================

   COMMENT ACTION

   ========================================================= */



.comment-actions {

    display: flex;



    justify-content: flex-end;



    margin-top: 11px;

}



.delete-comment {

    display: inline-flex;



    align-items: center;



    gap: 5px;



    padding: 7px 9px;



    border:

        1px solid #fecdd3;



    border-radius: 7px;



    background: #fff1f2;



    color: #be123c;



    font-size: 9px;



    font-weight: 800;



    cursor: pointer;



    transition: .2s;

}



.delete-comment:hover {

    border-color: #e11d48;



    background: #e11d48;



    color: white;

}





/* =========================================================

   EMPTY

   ========================================================= */



.no-comments {

    min-height: 230px;



    display: flex;



    flex-direction: column;



    align-items: center;

    justify-content: center;



    padding: 35px 20px;



    text-align: center;

}



.empty-icon {

    width: 55px;

    height: 55px;



    display: flex;



    align-items: center;

    justify-content: center;



    margin-bottom: 13px;



    border-radius: 50%;



    background: #eff6ff;



    color: #2563eb;



    font-size: 19px;

}



.no-comments h3 {

    margin-bottom: 6px;



    color: #102342;



    font-size: 14px;

}



.no-comments p {

    max-width: 350px;



    color: #8491a5;



    font-size: 10px;



    line-height: 1.6;

}





/* =========================================================

   DELETE SUCCESS

   ========================================================= */



.success-message {

    display: flex;



    align-items: center;



    gap: 7px;



    padding: 11px 13px;



    margin-bottom: 15px;



    border:

        1px solid #bbf7d0;



    border-radius: 9px;



    background: #f0fdf4;



    color: #15803d;



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



html.dark-mode .page-header h1,

html.dark-mode .item-name,

html.dark-mode .discussion-heading h2,

html.dark-mode .no-comments h3 {

    color: #f8fafc;

}



html.dark-mode .page-header p {

    color: #94a3b8;

}



html.dark-mode .admin-profile,

html.dark-mode .back-btn,

html.dark-mode .item-card,

html.dark-mode .discussion-card {

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

}



html.dark-mode .meta-box,

html.dark-mode .comment {

    border-color: #334155;



    background: #172033;

}



html.dark-mode .meta-value {

    color: #e2e8f0;

}



html.dark-mode .discussion-header {

    border-color: #334155;

}



html.dark-mode .discussion-icon,

html.dark-mode .comment-count {

    background: #172554;



    color: #93c5fd;

}



html.dark-mode .comment-avatar {

    background: #172554;



    color: #93c5fd;

}



html.dark-mode .comment-name {

    color: #f1f5f9;

}



html.dark-mode .comment-text {

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



html.dark-mode .delete-comment {

    border-color: #63202e;



    background: #4c1723;



    color: #fda4af;

}



html.dark-mode .delete-comment:hover {

    border-color: #e11d48;



    background: #e11d48;



    color: white;

}



html.dark-mode .success-message {

    border-color: #166534;



    background: #14532d;



    color: #bbf7d0;

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



    .item-card {

        grid-template-columns: 1fr;

    }



    .item-image {

        height: 220px;

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



    .item-meta {

        grid-template-columns: 1fr;

    }



    .item-top {

        flex-direction: column;

    }



    .discussion-header {

        align-items: flex-start;



        flex-direction: column;

    }



    .comment-top {

        flex-direction: column;

    }



    .comment-date {

        padding-left: 41px;

    }



    .comment-text {

        padding-left: 0;

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





        <a href="admin-dashboard.php">



            <i class="fa-solid fa-house"></i>



            Dashboard



        </a>





        <a

            href="admin-items.php"

            class="active"

        >



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



                    <i class="fa-regular fa-comments"></i>



                    Item Discussion



                </span>





                <h1>



                    Discussion Monitor



                </h1>





                <p>



                    Monitor user communication related to this item.



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

                            $admin_name

                        );

                        ?>



                    </strong>





                    <span>



                        <i class="fa-solid fa-shield-halved"></i>



                        Administrator



                    </span>





                </div>





            </div>





        </div>







        <!-- BACK BUTTONS -->



        <div class="back-row">





            <a

                href="admin-item-details.php?id=<?php

                echo intval($item["item_id"]);

                ?>"

                class="back-btn"

            >



                <i class="fa-solid fa-arrow-left"></i>



                Back to Item Details



            </a>





            <a

                href="admin-items.php"

                class="back-btn"

            >



                <i class="fa-solid fa-boxes-stacked"></i>



                Manage Items



            </a>





        </div>







        <?php if (

            isset($_GET["deleted"])

        ): ?>





            <div class="success-message">



                <i class="fa-solid fa-circle-check"></i>



                Comment deleted successfully.



            </div>





        <?php endif; ?>







        <!-- =================================================

             ITEM SUMMARY

             ================================================= -->



        <section class="item-card">





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



                            No image



                        </span>





                    </div>





                <?php endif; ?>





            </div>







            <div class="item-content">





                <div class="item-top">





                    <div>





                        <div class="item-id">



                            <i class="fa-solid fa-hashtag"></i>



                            Item ID:



                            <?php

                            echo intval(

                                $item["item_id"]

                            );

                            ?>



                        </div>





                        <h2 class="item-name">



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







                <div class="item-meta">





                    <div class="meta-box">





                        <div class="meta-label">



                            <i class="fa-solid fa-folder"></i>



                            Category



                        </div>





                        <div class="meta-value">



                            <?php

                            echo htmlspecialchars(

                                $item["category"]

                            );

                            ?>



                        </div>





                    </div>







                    <div class="meta-box">





                        <div class="meta-label">



                            <i class="fa-solid fa-location-dot"></i>



                            Location



                        </div>





                        <div class="meta-value">



                            <?php

                            echo htmlspecialchars(

                                $item["location"]

                            );

                            ?>



                        </div>





                    </div>







                    <div class="meta-box">





                        <div class="meta-label">



                            <i class="fa-regular fa-calendar"></i>



                            Date Reported



                        </div>





                        <div class="meta-value">



                            <?php

                            echo htmlspecialchars(

                                $item["date_reported"]

                            );

                            ?>



                        </div>





                    </div>







                    <div class="meta-box">





                        <div class="meta-label">



                            <i class="fa-solid fa-circle-info"></i>



                            Status



                        </div>





                        <div class="meta-value">





                            <span

                                class="status-badge <?php

                                echo $status_class;

                                ?>"

                            >





                                <?php

                                echo htmlspecialchars(

                                    $item["status"]

                                );

                                ?>





                            </span>





                        </div>





                    </div>





                </div>





            </div>





        </section>







        <!-- =================================================

             DISCUSSION

             ================================================= -->



        <section class="discussion-card">





            <div class="discussion-header">





                <div class="discussion-heading">





                    <div class="discussion-icon">



                        <i class="fa-regular fa-comments"></i>



                    </div>





                    <div>





                        <h2>



                            User Discussion



                        </h2>





                        <p>



                            Communication between users about this item.



                        </p>





                    </div>





                </div>







                <div class="comment-count">



                    <i class="fa-regular fa-comment-dots"></i>



                    <?php

                    echo intval(

                        $total_comments

                    );

                    ?>



                    <?php

                    echo

                        ($total_comments === 1)

                            ? "Comment"

                            : "Comments";

                    ?>



                </div>





            </div>







            <?php if (

                $comments_result->num_rows > 0

            ): ?>





                <div class="comment-list">





                    <?php while (

                        $comment =

                        $comments_result->fetch_assoc()

                    ): ?>





                        <article class="comment">





                            <div class="comment-top">





                                <div class="comment-user">





                                    <?php
                                        $profile_picture = trim(
                                            (string) (
                                                $comment["profile_picture"]
                                                ?? ""
                                            )
                                        );

                                        $profile_src = "";

                                        if ($profile_picture !== "") {

                                            if (
                                                preg_match(
                                                    '/^https?:\/\//i',
                                                    $profile_picture
                                                )
                                            ) {
                                                $profile_src =
                                                    $profile_picture;
                                            } else {

                                                $clean_profile = ltrim(
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

                                    <div class="comment-avatar">

                                        <?php if ($profile_src !== ""): ?>

                                            <img
                                                src="<?php
                                                    echo htmlspecialchars(
                                                        $profile_src
                                                    );
                                                ?>"
                                                alt="<?php
                                                    echo htmlspecialchars(
                                                        $comment["name"]
                                                        ?? "User"
                                                    );
                                                ?>"
                                            >

                                        <?php else: ?>

                                            <i class="fa-solid fa-user"></i>

                                        <?php endif; ?>

                                    </div>





                                    <div>





                                        <span class="comment-name">



                                            <?php

                                            echo htmlspecialchars(

                                                $comment["name"]

                                                ??

                                                "Unknown User"

                                            );

                                            ?>



                                        </span>





                                        <div class="comment-user-id">



                                            User ID:



                                            <?php

                                            echo intval(

                                                $comment["user_id"]

                                            );

                                            ?>



                                        </div>





                                    </div>





                                </div>







                                <span class="comment-date">



                                    <i class="fa-regular fa-clock"></i>



                                    <?php

                                    echo htmlspecialchars(

                                        $comment["comment_date"]

                                    );

                                    ?>



                                </span>





                            </div>







                            <div class="comment-text"><?php

                                echo htmlspecialchars(

                                    $comment["comment_text"]

                                );

                            ?></div>







                            <div class="comment-actions">





                                <form

                                    method="POST"

                                    onsubmit="return confirm('Delete this comment? This action cannot be undone.');"

                                >





                                    <input

                                        type="hidden"

                                        name="delete_comment_id"

                                        value="<?php

                                        echo intval(

                                            $comment["comment_id"]

                                        );

                                        ?>"

                                    >





                                    <button

                                        type="submit"

                                        class="delete-comment"

                                    >



                                        <i class="fa-regular fa-trash-can"></i>



                                        Delete Comment



                                    </button>





                                </form>





                            </div>





                        </article>





                    <?php endwhile; ?>





                </div>





            <?php else: ?>





                <div class="no-comments">





                    <div class="empty-icon">



                        <i class="fa-regular fa-comments"></i>



                    </div>





                    <h3>



                        No Discussion Yet



                    </h3>





                    <p>



                        There are currently no user comments

                        for this item.



                    </p>





                </div>





            <?php endif; ?>





        </section>





    </div>





</main>





</body>



</html>



<?php



if (

    isset($comments_stmt) &&

    $comments_stmt

) {

    $comments_stmt->close();

}



?>