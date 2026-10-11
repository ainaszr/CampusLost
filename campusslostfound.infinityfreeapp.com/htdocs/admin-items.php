<?php

session_start();

require_once "db.php";

require_once "mail_helper.php";





/* =========================

   CHECK LOGIN

   ========================= */



if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");

    exit();

}



$user_id = $_SESSION["user_id"];





/* =========================

   CHECK ADMIN

   ========================= */



$sql = "SELECT name, role

        FROM users

        WHERE user_id = ?";



$stmt = $conn->prepare($sql);



if (!$stmt) {

    die("SQL Error: " . $conn->error);

}



$stmt->bind_param("i", $user_id);

$stmt->execute();



$result = $stmt->get_result();



if ($result->num_rows === 0) {

    die("User not found.");

}



$admin = $result->fetch_assoc();



if (strtolower($admin["role"]) !== "admin") {

    header("Location: dashboard.php");

    exit();

}





/* =========================

   APPROVE / REJECT

   ========================= */



if ($_SERVER["REQUEST_METHOD"] === "POST") {



    $item_id = intval($_POST["item_id"]);

    $action = $_POST["action"];





    /* APPROVE */



    if ($action === "approve") {



        $sql = "SELECT item_type, item_name, user_id

                FROM items

                WHERE item_id = ?";



        $stmt = $conn->prepare($sql);

        $stmt->bind_param("i", $item_id);

        $stmt->execute();



        $result = $stmt->get_result();



        if ($result->num_rows > 0) {



            $item = $result->fetch_assoc();



            $new_status = $item["item_type"];





            $update_sql = "UPDATE items

                           SET status = ?

                           WHERE item_id = ?";



            $update_stmt = $conn->prepare($update_sql);



            if (!$update_stmt) {

                die("SQL Error: " . $conn->error);

            }



            $update_stmt->bind_param(

                "si",

                $new_status,

                $item_id

            );



            if ($update_stmt->execute()) {

                campuslost_email_user_by_id(

                    $conn,

                    intval($item["user_id"]),

                    "CampusLost - Item Report Approved",

                    "Your report for \"" . $item["item_name"] . "\" has been approved."

                );

            }

        }

    }





    /* REJECT */



    elseif ($action === "reject") {



        $item_info = null;

        $info_stmt = $conn->prepare("SELECT item_name, user_id FROM items WHERE item_id = ?");

        if ($info_stmt) {

            $info_stmt->bind_param("i", $item_id);

            $info_stmt->execute();

            $info_result = $info_stmt->get_result();

            $item_info = $info_result->fetch_assoc();

            $info_stmt->close();

        }



        $new_status = "Rejected";





        $update_sql = "UPDATE items

                       SET status = ?

                       WHERE item_id = ?";



        $update_stmt = $conn->prepare($update_sql);



        if (!$update_stmt) {

            die("SQL Error: " . $conn->error);

        }



        $update_stmt->bind_param(

            "si",

            $new_status,

            $item_id

        );



        if ($update_stmt->execute() && $item_info) {

            campuslost_email_user_by_id(

                $conn,

                intval($item_info["user_id"]),

                "CampusLost - Item Report Rejected",

                "Your report for \"" . $item_info["item_name"] . "\" has been rejected."

            );

        }

    }





    /* Refresh page */



    header("Location: admin-items.php");

    exit();

}





/* =========================

   FILTER

   ========================= */



$type = isset($_GET["type"])

    ? $_GET["type"]

    : "";



$status = isset($_GET["status"])

    ? $_GET["status"]

    : "";





/* =========================

   GET ITEMS

   ========================= */



$sql = "SELECT

            i.item_id,

            i.item_name,

            i.category,

            i.description,

            i.location,

            i.date_reported,

            i.image,

            i.item_type,

            i.status,

            COALESCE(u.name, 'Unknown User') AS reporter_name,

            COALESCE(u.role, 'User') AS reporter_role

        FROM items i

        LEFT JOIN users u

            ON i.user_id = u.user_id

        WHERE 1=1";





$params = [];

$types = "";





/* Filter by type */



if ($type === "Lost" || $type === "Found") {



    $sql .= " AND LOWER(TRIM(i.item_type)) = LOWER(?)";



    $params[] = $type;



    $types .= "s";

}





/* Filter by status */



if ($status !== "") {



    $sql .= " AND i.status = ?";



    $params[] = $status;



    $types .= "s";

}





$sql .= " ORDER BY i.item_id DESC";





$stmt = $conn->prepare($sql);



if (!$stmt) {

    die("SQL Error: " . $conn->error);

}





/* Bind dynamic parameters */



if (!empty($params)) {



    $stmt->bind_param(

        $types,

        ...$params

    );

}





$stmt->execute();



$result = $stmt->get_result();



?>





<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="theme.js?v=4"></script>
<title>Manage Items - CampusLost</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}body{font-family:Arial,sans-serif;background:#f5f7fb;color:#172033;min-height:100vh}.sidebar{position:fixed;inset:0 auto 0 0;width:260px;height:100vh;background:#071a38;color:#fff;padding:28px 14px 20px;display:flex;flex-direction:column;z-index:1000;box-shadow:5px 0 25px rgba(0,0,0,.08)}.brand{padding:4px 14px 25px;border-bottom:1px solid rgba(255,255,255,.09);margin-bottom:20px}.brand-top{display:flex;align-items:center;gap:10px}.brand-icon{width:38px;height:38px;display:flex;align-items:center;justify-content:center;border-radius:11px;background:rgba(37,99,235,.18);color:#60a5fa;font-size:19px}.brand-name{font-size:22px;font-weight:800;letter-spacing:-.5px}.brand-name span{color:#60a5fa}.brand-subtitle{margin:6px 0 0 48px;font-size:11px;color:#94a3b8;letter-spacing:.4px}.menu{flex:1;overflow-y:auto}.menu-label{font-size:10px;font-weight:bold;color:#64748b;text-transform:uppercase;letter-spacing:1px;padding:0 14px;margin:10px 0 8px}.menu a{display:flex;align-items:center;gap:13px;padding:12px 14px;margin-bottom:5px;color:#cbd5e1;text-decoration:none;border-radius:10px;font-size:14px;transition:.2s}.menu a i{width:20px;text-align:center}.menu a:hover{background:rgba(37,99,235,.14);color:#fff;transform:translateX(2px)}.menu a.active{background:linear-gradient(90deg,#2563eb,#3478f6);color:#fff;box-shadow:0 7px 18px rgba(37,99,235,.25)}.logout{padding-top:14px;border-top:1px solid rgba(255,255,255,.09)}.logout a{display:flex;justify-content:center;align-items:center;gap:9px;padding:12px;background:rgba(239,68,68,.14);color:#fca5a5;border-radius:10px;text-decoration:none;font-size:14px;font-weight:600}.logout a:hover{background:#dc2626;color:#fff}.main{margin-left:260px;min-height:100vh;padding:35px 42px 55px}.header{display:flex;justify-content:space-between;align-items:center;gap:25px;margin-bottom:26px}.page-label{display:inline-flex;align-items:center;gap:7px;color:#2563eb;font-size:11px;font-weight:800;letter-spacing:1px;text-transform:uppercase;margin-bottom:8px}.header h1{color:#102342;font-size:28px;margin-bottom:7px}.header p{color:#748199;font-size:14px}.admin-profile{background:#fff;border:1px solid #e8edf5;border-radius:14px;padding:13px 17px;display:flex;align-items:center;gap:11px;box-shadow:0 5px 18px rgba(15,39,71,.05)}.admin-avatar{width:39px;height:39px;display:flex;align-items:center;justify-content:center;border-radius:11px;background:#eff6ff;color:#2563eb}.admin-profile strong{display:block;color:#102342;font-size:13px;margin-bottom:4px}.admin-profile span{color:#2563eb;font-size:11px}.filters{background:#fff;border:1px solid #e8edf5;padding:16px 18px;border-radius:14px;box-shadow:0 5px 20px rgba(15,39,71,.04);margin-bottom:25px;display:flex;gap:8px;flex-wrap:wrap}.filters a{display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border-radius:20px;background:#f1f5f9;color:#475569;text-decoration:none;font-size:12px;font-weight:700}.filters a:hover,.filters a.active{background:#2563eb;color:#fff}.items-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}.item-card{background:#fff;border:1px solid #e8edf5;border-radius:16px;overflow:hidden;box-shadow:0 6px 22px rgba(15,39,71,.05);transition:.2s}.item-card:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(15,39,71,.09)}.item-image{width:100%;height:220px;background:#eef4fb;display:flex;align-items:center;justify-content:center;overflow:hidden}.item-image img{width:100%;height:100%;object-fit:cover}.no-image{font-size:40px;color:#94a3b8}.item-content{padding:20px}.item-title{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:12px}.item-title h3{color:#102342;font-size:17px}.badge{display:inline-flex;align-items:center;gap:5px;padding:6px 10px;border-radius:20px;font-size:9px;font-weight:800;white-space:nowrap}.badge.lost{background:#fff1f2;color:#be123c}.badge.found{background:#ecfdf5;color:#047857}.badge.pending{background:#fff7ed;color:#c2410c}.badge.approved{background:#eff6ff;color:#1d4ed8}.badge.rejected{background:#f1f5f9;color:#64748b}.info{margin-bottom:12px}.info p{display:flex;align-items:center;gap:7px;color:#64748b;font-size:12px;margin-bottom:7px}.info i{width:14px;color:#94a3b8}.description{color:#64748b;font-size:12px;line-height:1.55;margin-bottom:15px;min-height:38px}.status-row{margin-bottom:14px;font-size:12px;color:#64748b}.actions{display:flex;gap:8px}.actions form{flex:1}.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;flex:1;border:none;padding:10px;border-radius:8px;text-align:center;text-decoration:none;font-size:11px;font-weight:800;cursor:pointer}.view{background:#eff6ff;color:#1d4ed8}.approve{background:#ecfdf5;color:#047857;width:100%}.reject{background:#fff1f2;color:#be123c;width:100%}.view:hover{background:#dbeafe}.approve:hover{background:#d1fae5}.reject:hover{background:#ffe4e6}.empty{background:#fff;border:1px solid #e8edf5;border-radius:16px;padding:55px;text-align:center;color:#64748b}.empty i{font-size:34px;color:#94a3b8;margin-bottom:15px}.empty h3{color:#334155;margin-bottom:8px}
html.dark-mode body,html.dark-mode .main{background:#0f172a;color:#e2e8f0}html.dark-mode .sidebar{background:#020617}html.dark-mode .header h1{color:#f8fafc}html.dark-mode .header p{color:#94a3b8}html.dark-mode .admin-profile,html.dark-mode .filters,html.dark-mode .item-card,html.dark-mode .empty{background:#1e293b;border-color:#2d3c52}html.dark-mode .admin-profile strong,html.dark-mode .item-title h3,html.dark-mode .empty h3{color:#f8fafc}html.dark-mode .admin-profile span{color:#60a5fa}html.dark-mode .admin-avatar{background:#172554;color:#93c5fd}html.dark-mode .filters a{background:#334155;color:#e2e8f0}html.dark-mode .filters a:hover,html.dark-mode .filters a.active{background:#2563eb;color:#fff}html.dark-mode .item-image{background:#111827}html.dark-mode .info p,html.dark-mode .description,html.dark-mode .status-row,html.dark-mode .empty{color:#94a3b8}
@media(max-width:1150px){.items-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:800px){.sidebar{width:210px}.brand-name{font-size:18px}.brand-subtitle{display:none}.main{margin-left:210px;padding:28px 22px}.items-grid{grid-template-columns:1fr}.header{align-items:flex-start;flex-direction:column}}@media(max-width:650px){.sidebar{position:relative;width:100%;height:auto}.main{margin-left:0;padding:25px 16px}.menu{overflow:visible}.logout{margin-top:15px}}
</style>
</head>
<body>
<aside class="sidebar">
<div class="brand"><div class="brand-top"><div class="brand-icon"><i class="fa-solid fa-graduation-cap"></i></div><div class="brand-name">Campus<span>Lost</span></div></div><div class="brand-subtitle">LOST &amp; FOUND SYSTEM</div></div>
<nav class="menu">
<div class="menu-label">Main</div>
<a href="admin-dashboard.php"><i class="fa-solid fa-house"></i>Dashboard</a>
<a href="admin-items.php" class="active"><i class="fa-solid fa-boxes-stacked"></i>Manage Items</a>
<a href="admin-claims.php"><i class="fa-solid fa-file-circle-check"></i>Manage Claims</a>
<a href="admin-users.php"><i class="fa-solid fa-users"></i>Manage Users</a>
<div class="menu-label">Account</div>
<a href="notifications.php"><i class="fa-solid fa-bell"></i>Notifications</a>
<a href="profile.php"><i class="fa-solid fa-user"></i>Profile</a>
<a href="settings.php"><i class="fa-solid fa-gear"></i>Settings</a>
</nav>
<div class="logout"><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i>Logout</a></div>
</aside>
<main class="main">
<div class="header"><div><span class="page-label"><i class="fa-solid fa-boxes-stacked"></i>Item Management</span><h1>Manage Items</h1><p>Review and manage lost and found reports.</p></div><div class="admin-profile"><div class="admin-avatar"><i class="fa-solid fa-user-shield"></i></div><div><strong><?php echo htmlspecialchars($admin["name"]); ?></strong><span><i class="fa-solid fa-shield-halved"></i> Administrator</span></div></div></div>
<div class="filters">
<a href="admin-items.php" class="<?php echo ($type === '' && $status === '') ? 'active' : ''; ?>"><i class="fa-solid fa-layer-group"></i>All Items</a>
<a href="admin-items.php?type=Lost" class="<?php echo ($type === 'Lost') ? 'active' : ''; ?>"><i class="fa-solid fa-magnifying-glass"></i>Lost</a>
<a href="admin-items.php?type=Found" class="<?php echo ($type === 'Found') ? 'active' : ''; ?>"><i class="fa-solid fa-box-open"></i>Found</a>
<a href="admin-items.php?status=Pending" class="<?php echo ($status === 'Pending') ? 'active' : ''; ?>"><i class="fa-solid fa-clock"></i>Pending</a>
<a href="admin-items.php?status=Rejected" class="<?php echo ($status === 'Rejected') ? 'active' : ''; ?>"><i class="fa-solid fa-circle-xmark"></i>Rejected</a>
<a href="admin-items.php?status=Returned" class="<?php echo ($status === 'Returned') ? 'active' : ''; ?>"><i class="fa-solid fa-circle-check"></i>Returned</a>
</div>
<?php if ($result->num_rows > 0): ?><div class="items-grid"><?php while ($item = $result->fetch_assoc()): ?>
<div class="item-card"><div class="item-image">
<?php if (!empty($item["image"]) && file_exists("uploads/".$item["image"])): ?><img src="uploads/<?php echo htmlspecialchars($item["image"]); ?>" alt="Item image"><?php else: ?><div class="no-image"><i class="fa-solid fa-image"></i></div><?php endif; ?>
</div><div class="item-content"><div class="item-title"><h3><?php echo htmlspecialchars($item["item_name"]); ?></h3><span class="badge <?php echo strtolower($item["item_type"]); ?>"><?php echo htmlspecialchars(strtoupper($item["item_type"])); ?></span></div>
<div class="info"><p><i class="fa-solid fa-folder"></i><?php echo htmlspecialchars($item["category"]); ?></p><p><i class="fa-solid fa-location-dot"></i><?php echo htmlspecialchars($item["location"]); ?></p><p><i class="fa-solid fa-calendar"></i><?php echo htmlspecialchars($item["date_reported"]); ?></p><p><i class="fa-solid fa-user"></i>Reported by: <?php echo htmlspecialchars($item["reporter_name"]); ?> (<?php echo htmlspecialchars($item["reporter_role"]); ?>)</p></div>
<div class="description"><?php $description=$item["description"]; if(strlen($description)>100){$description=substr($description,0,100)."...";} echo htmlspecialchars($description); ?></div>
<?php if ($item["status"] === "Pending"){$status_class="pending";}elseif($item["status"] === "Rejected"){$status_class="rejected";}else{$status_class="approved";} ?>
<div class="status-row">Status: <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars(strtoupper($item["status"])); ?></span></div>
<div class="actions"><a href="admin-item-details.php?id=<?php echo $item["item_id"]; ?>" class="btn view"><i class="fa-solid fa-eye"></i>View</a>
<?php if ($item["status"] === "Pending"): ?><form method="POST"><input type="hidden" name="item_id" value="<?php echo $item["item_id"]; ?>"><input type="hidden" name="action" value="approve"><button type="submit" class="btn approve"><i class="fa-solid fa-check"></i>Approve</button></form><form method="POST"><input type="hidden" name="item_id" value="<?php echo $item["item_id"]; ?>"><input type="hidden" name="action" value="reject"><button type="submit" class="btn reject"><i class="fa-solid fa-xmark"></i>Reject</button></form><?php endif; ?>
</div></div></div>
<?php endwhile; ?></div><?php else: ?><div class="empty"><i class="fa-solid fa-inbox"></i><h3>No items found</h3><p>There are no reports matching this filter.</p></div><?php endif; ?>
</main>
</body></html>
