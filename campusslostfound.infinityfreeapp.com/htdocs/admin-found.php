<?php
session_start();
require_once "db.php";

/* =========================
   ADMIN ACCESS CHECK
========================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

if (strtolower($_SESSION["role"]) !== "admin") {
    header("Location: dashboard.php");
    exit();
}


/* =========================
   APPROVE / REJECT FOUND
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $item_id = intval($_POST["item_id"]);
    $action = $_POST["action"];

    if ($action === "approve") {

        $stmt = $conn->prepare(
            "UPDATE items
             SET status = 'Found'
             WHERE item_id = ?
             AND item_type = 'Found'
             AND status = 'Pending'"
        );

        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $stmt->close();

    } elseif ($action === "reject") {

        $stmt = $conn->prepare(
            "UPDATE items
             SET status = 'Rejected'
             WHERE item_id = ?
             AND item_type = 'Found'
             AND status = 'Pending'"
        );

        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admin-found.php");
    exit();
}


/* =========================
   FILTER
========================= */

$status_filter = isset($_GET["status"])
    ? trim($_GET["status"])
    : "All";


/* =========================
   GET FOUND ITEMS
========================= */

$sql = "SELECT
            i.item_id,
            i.item_name,
            i.category,
            i.description,
            i.location,
            i.date_reported,
            i.image,
            i.status,
            u.name AS reporter_name,
            u.email AS reporter_email,
            u.role AS reporter_role
        FROM items i
        INNER JOIN users u
            ON i.user_id = u.user_id
        WHERE i.item_type = 'Found'";


if ($status_filter !== "All") {

    $sql .= " AND i.status = ?";
}

$sql .= " ORDER BY i.item_id DESC";


if ($status_filter !== "All") {

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("s", $status_filter);

    $stmt->execute();

    $result = $stmt->get_result();

} else {

    $result = $conn->query($sql);
}


/* =========================
   COUNT FOUND REPORTS
========================= */

$count_sql = "SELECT
                COUNT(*) AS total,
                SUM(status = 'Pending') AS pending,
                SUM(status = 'Found') AS approved,
                SUM(status = 'Rejected') AS rejected
              FROM items
              WHERE item_type = 'Found'";

$count_result = $conn->query($count_sql);

$count_data = $count_result->fetch_assoc();

$total_found = $count_data["total"] ?? 0;
$pending_found = $count_data["pending"] ?? 0;
$approved_found = $count_data["approved"] ?? 0;
$rejected_found = $count_data["rejected"] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <script src="theme.js?v=4"></script>

    <title>Manage Found - Campus Lost & Found</title>


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {
            font-family: Arial, sans-serif;

            background: #f8fafc;

            color: #1e293b;
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

            padding: 25px 18px;
        }


        .sidebar h2 {
            text-align: center;

            margin-bottom: 30px;

            font-size: 22px;
        }


        .sidebar-menu a {
            display: block;

            padding: 13px 15px;

            margin-bottom: 8px;

            color: #dbeafe;

            text-decoration: none;

            border-radius: 8px;

            font-size: 15px;

            transition: 0.2s;
        }


        .sidebar-menu a:hover {
            background: #1e3a5f;

            color: white;
        }


        .sidebar-menu a.active {
            background: #2563eb;

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
        }


        .logout a {
            display: block;

            padding: 12px;

            text-align: center;

            background: #ef4444;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-weight: bold;
        }


        .logout a:hover {
            background: #dc2626;
        }


        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: 250px;

            padding: 35px;
        }


        .top-section {
            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;
        }


        .top-section h1 {
            color: #0f2747;

            font-size: 30px;

            margin-bottom: 6px;
        }


        .top-section p {
            color: #64748b;
        }


        /* =========================
           SUMMARY CARDS
        ========================= */

        .summary {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

            margin-bottom: 25px;
        }


        .summary-card {
            background: white;

            padding: 20px;

            border-radius: 14px;

            box-shadow:
                0 4px 15px rgba(0,0,0,0.06);

            cursor: pointer;

            transition: 0.2s;
        }


        .summary-card:hover {
            transform: translateY(-3px);

            box-shadow:
                0 7px 20px rgba(0,0,0,0.10);
        }


        .summary-card .icon {
            font-size: 25px;

            margin-bottom: 10px;
        }


        .summary-card h3 {
            font-size: 27px;

            color: #0f2747;

            margin-bottom: 4px;
        }


        .summary-card p {
            color: #64748b;

            font-size: 13px;
        }


        .summary-card.total {
            border-left: 5px solid #2563eb;
        }


        .summary-card.pending {
            border-left: 5px solid #f59e0b;
        }


        .summary-card.approved {
            border-left: 5px solid #22c55e;
        }


        .summary-card.rejected {
            border-left: 5px solid #ef4444;
        }


        /* =========================
           FILTER BAR
        ========================= */

        .filter-bar {
            background: white;

            padding: 18px;

            border-radius: 14px;

            box-shadow:
                0 4px 12px rgba(0,0,0,0.06);

            margin-bottom: 25px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;
        }


        .filter-buttons {
            display: flex;

            gap: 8px;

            flex-wrap: wrap;
        }


        .filter-buttons a {
            text-decoration: none;

            padding: 9px 15px;

            border-radius: 8px;

            background: #f1f5f9;

            color: #475569;

            font-size: 13px;

            font-weight: bold;
        }


        .filter-buttons a:hover {
            background: #dbeafe;

            color: #1d4ed8;
        }


        .filter-buttons a.active {
            background: #2563eb;

            color: white;
        }


        .search-box {
            width: 280px;
        }


        .search-box input {
            width: 100%;

            padding: 11px 14px;

            border: 1px solid #cbd5e1;

            border-radius: 8px;

            outline: none;

            font-size: 14px;
        }


        .search-box input:focus {
            border-color: #2563eb;

            box-shadow:
                0 0 0 3px rgba(37,99,235,0.1);
        }


        /* =========================
           ITEMS GRID
        ========================= */

        .items-grid {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 22px;
        }


        .item-card {
            background: white;

            border-radius: 16px;

            overflow: hidden;

            box-shadow:
                0 4px 15px rgba(0,0,0,0.07);

            transition: 0.2s;
        }


        .item-card:hover {
            transform: translateY(-4px);

            box-shadow:
                0 8px 25px rgba(0,0,0,0.10);
        }


        /* =========================
           IMAGE
        ========================= */

        .item-image {
            width: 100%;

            height: 250px;

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

            display: block;
        }


        .no-image {
            color: #64748b;

            font-size: 14px;
        }


        /* =========================
           CARD CONTENT
        ========================= */

        .item-content {
            padding: 20px;
        }


        .item-header {
            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 10px;

            margin-bottom: 12px;
        }


        .item-header h2 {
            color: #0f2747;

            font-size: 19px;

            line-height: 1.3;
        }


        /* =========================
           STATUS BADGES
        ========================= */

        .badge {
            display: inline-block;

            padding: 6px 10px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: bold;

            white-space: nowrap;
        }


        .badge.pending {
            background: #fef3c7;

            color: #b45309;
        }


        .badge.found {
            background: #dcfce7;

            color: #15803d;
        }


        .badge.rejected {
            background: #fee2e2;

            color: #b91c1c;
        }


        .badge.category {
            background: #dbeafe;

            color: #1d4ed8;

            margin-bottom: 12px;
        }


        /* =========================
           ITEM INFO
        ========================= */

        .item-info {
            margin-bottom: 14px;
        }


        .item-info p {
            color: #64748b;

            font-size: 13px;

            margin-bottom: 7px;

            line-height: 1.5;
        }


        .item-info strong {
            color: #334155;
        }


        .description {
            color: #475569;

            font-size: 13px;

            line-height: 1.6;

            margin-bottom: 15px;
        }


        /* =========================
           REPORTER
        ========================= */

        .reporter {
            background: #f8fafc;

            border-radius: 10px;

            padding: 12px;

            margin-bottom: 15px;
        }


        .reporter-title {
            font-size: 11px;

            color: #64748b;

            margin-bottom: 5px;

            text-transform: uppercase;

            font-weight: bold;
        }


        .reporter-name {
            font-size: 14px;

            font-weight: bold;

            color: #334155;

            margin-bottom: 3px;
        }


        .reporter-email {
            font-size: 12px;

            color: #64748b;
        }


        /* =========================
           BUTTONS
        ========================= */

        .actions {
            display: flex;

            gap: 8px;

            flex-wrap: wrap;
        }


        .btn {
            border: none;

            text-decoration: none;

            padding: 10px 13px;

            border-radius: 8px;

            font-size: 12px;

            font-weight: bold;

            cursor: pointer;

            text-align: center;
        }


        .view-btn {
            background: #dbeafe;

            color: #1d4ed8;

            flex: 1;
        }


        .view-btn:hover {
            background: #bfdbfe;
        }


        .approve-btn {
            background: #22c55e;

            color: white;

            flex: 1;
        }


        .approve-btn:hover {
            background: #16a34a;
        }


        .reject-btn {
            background: #ef4444;

            color: white;

            flex: 1;
        }


        .reject-btn:hover {
            background: #dc2626;
        }


        /* =========================
           EMPTY
        ========================= */

        .empty {
            background: white;

            border-radius: 16px;

            padding: 70px 20px;

            text-align: center;

            box-shadow:
                0 4px 15px rgba(0,0,0,0.06);
        }


        .empty-icon {
            font-size: 50px;

            margin-bottom: 15px;
        }


        .empty h3 {
            color: #334155;

            margin-bottom: 8px;
        }


        .empty p {
            color: #64748b;

            font-size: 14px;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1100px) {

            .items-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .summary {
                grid-template-columns:
                    repeat(2, 1fr);
            }

        }


        @media (max-width: 800px) {

            .sidebar {
                width: 210px;
            }

            .main {
                margin-left: 210px;

                padding: 25px;
            }

            .filter-bar {
                flex-direction: column;

                align-items: stretch;
            }

            .search-box {
                width: 100%;
            }

        }


        @media (max-width: 600px) {

            .items-grid {
                grid-template-columns: 1fr;
            }

            .summary {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<div class="sidebar">

    <h2>🎓 Campus L&F</h2>


    <div class="sidebar-menu">

        <a href="admin-dashboard.php">
            🏠 Dashboard
        </a>

        <a href="admin-items.php">
            📦 Manage Items
        </a>

        <a href="admin-found.php" class="active">
            🟢 Manage Found
        </a>

        <a href="admin-claims.php">
            🔎 Manage Claims
        </a>

        <a href="admin-users.php">
            👥 Manage Users
        </a>

        <a href="notifications.php">
            🔔 Notifications
        </a>

        <a href="profile.php">
            👤 Profile
        </a>

    </div>


    <div class="logout">

        <a href="logout.php">
            🚪 Logout
        </a>

    </div>

</div>



<!-- =========================
     MAIN CONTENT
========================= -->

<div class="main">


    <div class="top-section">

        <div>

            <h1>Manage Found</h1>

            <p>
                Review and manage found item reports.
            </p>

        </div>

    </div>



    <!-- =========================
         SUMMARY
    ========================= -->

    <div class="summary">


        <div
            class="summary-card total"
            onclick="window.location='admin-found.php'"
        >

            <div class="icon">
                📦
            </div>

            <h3>
                <?php echo $total_found; ?>
            </h3>

            <p>
                Total Found Reports
            </p>

        </div>


        <div
            class="summary-card pending"
            onclick="window.location='admin-found.php?status=Pending'"
        >

            <div class="icon">
                ⏳
            </div>

            <h3>
                <?php echo $pending_found; ?>
            </h3>

            <p>
                Pending Approval
            </p>

        </div>


        <div
            class="summary-card approved"
            onclick="window.location='admin-found.php?status=Found'"
        >

            <div class="icon">
                ✅
            </div>

            <h3>
                <?php echo $approved_found; ?>
            </h3>

            <p>
                Approved
            </p>

        </div>


        <div
            class="summary-card rejected"
            onclick="window.location='admin-found.php?status=Rejected'"
        >

            <div class="icon">
                ❌
            </div>

            <h3>
                <?php echo $rejected_found; ?>
            </h3>

            <p>
                Rejected
            </p>

        </div>


    </div>



    <!-- =========================
         FILTER + SEARCH
    ========================= -->

    <div class="filter-bar">


        <div class="filter-buttons">

            <a
                href="admin-found.php"
                class="<?php echo $status_filter === 'All' ? 'active' : ''; ?>"
            >
                All
            </a>


            <a
                href="admin-found.php?status=Pending"
                class="<?php echo $status_filter === 'Pending' ? 'active' : ''; ?>"
            >
                Pending
            </a>


            <a
                href="admin-found.php?status=Found"
                class="<?php echo $status_filter === 'Found' ? 'active' : ''; ?>"
            >
                Approved
            </a>


            <a
                href="admin-found.php?status=Rejected"
                class="<?php echo $status_filter === 'Rejected' ? 'active' : ''; ?>"
            >
                Rejected
            </a>

        </div>


        <div class="search-box">

            <input
                type="text"
                id="searchInput"
                placeholder="🔍 Search found items..."
                onkeyup="searchItems()"
            >

        </div>


    </div>



    <!-- =========================
         FOUND ITEMS
    ========================= -->

    <?php if ($result && $result->num_rows > 0): ?>


        <div class="items-grid" id="itemsGrid">


            <?php while ($item = $result->fetch_assoc()): ?>


                <div
                    class="item-card"
                    data-search="<?php
                        echo htmlspecialchars(
                            strtolower(
                                $item["item_name"]
                                . " "
                                . $item["category"]
                                . " "
                                . $item["location"]
                                . " "
                                . $item["reporter_name"]
                            )
                        );
                    ?>"
                >


                    <!-- IMAGE -->

                    <div class="item-image">

                        <?php if (!empty($item["image"])): ?>

                            <img
                                src="uploads/<?php
                                    echo htmlspecialchars($item["image"]);
                                ?>"
                                alt="Found Item"
                            >

                        <?php else: ?>

                            <div class="no-image">
                                📦 No Image Available
                            </div>

                        <?php endif; ?>

                    </div>



                    <!-- CONTENT -->

                    <div class="item-content">


                        <div class="item-header">

                            <h2>
                                <?php
                                echo htmlspecialchars(
                                    $item["item_name"]
                                );
                                ?>
                            </h2>


                            <?php

                            $status_class = strtolower(
                                $item["status"]
                            );

                            ?>

                            <span
                                class="badge <?php
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



                        <span class="badge category">

                            <?php
                            echo htmlspecialchars(
                                $item["category"]
                            );
                            ?>

                        </span>



                        <div class="item-info">

                            <p>
                                📍
                                <strong>Location:</strong>
                                <?php
                                echo htmlspecialchars(
                                    $item["location"]
                                );
                                ?>
                            </p>


                            <p>
                                📅
                                <strong>Date Reported:</strong>
                                <?php
                                echo htmlspecialchars(
                                    $item["date_reported"]
                                );
                                ?>
                            </p>

                        </div>



                        <div class="description">

                            <?php

                            $description =
                                $item["description"];

                            if (strlen($description) > 120) {

                                echo htmlspecialchars(
                                    substr(
                                        $description,
                                        0,
                                        120
                                    )
                                ) . "...";

                            } else {

                                echo htmlspecialchars(
                                    $description
                                );

                            }

                            ?>

                        </div>



                        <!-- REPORTER -->

                        <div class="reporter">

                            <div class="reporter-title">
                                Reported By
                            </div>


                            <div class="reporter-name">

                                <?php
                                echo htmlspecialchars(
                                    $item["reporter_name"]
                                );
                                ?>

                            </div>


                            <div class="reporter-email">

                                <?php
                                echo htmlspecialchars(
                                    $item["reporter_email"]
                                );
                                ?>

                            </div>

                        </div>



                        <!-- ACTIONS -->

                        <div class="actions">


                            <a
                                href="item-details.php?id=<?php
                                    echo $item["item_id"];
                                ?>"
                                class="btn view-btn"
                            >
                                👁 View
                            </a>


                            <?php if ($item["status"] === "Pending"): ?>


                                <form
                                    method="POST"
                                    style="flex:1;"
                                    onsubmit="
                                        return confirm(
                                            'Approve this found item?'
                                        );
                                    "
                                >

                                    <input
                                        type="hidden"
                                        name="item_id"
                                        value="<?php
                                            echo $item["item_id"];
                                        ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="approve"
                                    >

                                    <button
                                        type="submit"
                                        class="btn approve-btn"
                                        style="width:100%;"
                                    >
                                        ✅ Approve
                                    </button>

                                </form>



                                <form
                                    method="POST"
                                    style="flex:1;"
                                    onsubmit="
                                        return confirm(
                                            'Reject this found item?'
                                        );
                                    "
                                >

                                    <input
                                        type="hidden"
                                        name="item_id"
                                        value="<?php
                                            echo $item["item_id"];
                                        ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="reject"
                                    >

                                    <button
                                        type="submit"
                                        class="btn reject-btn"
                                        style="width:100%;"
                                    >
                                        ❌ Reject
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
                🟢
            </div>

            <h3>
                No Found Reports
            </h3>

            <p>
                There are no found item reports
                matching this filter.
            </p>

        </div>


    <?php endif; ?>


</div>



<!-- =========================
     SEARCH SCRIPT
========================= -->

<script>

function searchItems() {

    const input =
        document.getElementById("searchInput");

    const filter =
        input.value.toLowerCase();

    const cards =
        document.querySelectorAll(".item-card");


    cards.forEach(function(card) {

        const text =
            card.getAttribute("data-search");

        if (text.includes(filter)) {

            card.style.display = "";

        } else {

            card.style.display = "none";

        }

    });

}

</script>


</body>

</html>