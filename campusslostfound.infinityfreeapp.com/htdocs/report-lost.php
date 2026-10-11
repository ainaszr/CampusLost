<?php
session_start();
require_once "db.php";
require_once "mail_helper.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$message = "";

/* =========================
   GET USER INFORMATION
========================= */

$user_sql = "SELECT name, role
             FROM users
             WHERE user_id = ?";

$user_stmt = $conn->prepare($user_sql);

if (!$user_stmt) {
    die("SQL Error: " . $conn->error);
}

$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();

$user_result = $user_stmt->get_result();

if ($user_result->num_rows === 0) {
    die("User not found.");
}

$user = $user_result->fetch_assoc();

$name = $user["name"];
$role = strtolower(trim($user["role"]));

$user_stmt->close();


/* =========================
   SUBMIT FORM
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $item_name = trim($_POST["item_name"]);
    $category = $_POST["category"];
    $description = trim($_POST["description"]);
    $location = trim($_POST["location"]);

    /* FIXED: use date_found from form */
    $date_reported = $_POST["date_lost"];

    $image_name = null;


    /* =========================
       IMAGE UPLOAD
    ========================= */

    if (
        isset($_FILES["image"]) &&
        $_FILES["image"]["error"] == 0
    ) {

        $upload_dir = "uploads/";

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $allowed_types = [
            "jpg",
            "jpeg",
            "png",
            "webp"
        ];

        $file_name = $_FILES["image"]["name"];
        $file_tmp = $_FILES["image"]["tmp_name"];
        $file_size = $_FILES["image"]["size"];

        $file_ext = strtolower(
            pathinfo(
                $file_name,
                PATHINFO_EXTENSION
            )
        );


        if (!in_array($file_ext, $allowed_types)) {

            $message = "Invalid image format.";

        }

        elseif ($file_size > 5 * 1024 * 1024) {

            $message = "Image must be less than 5MB.";

        }

        else {

            $image_name =
                uniqid("lost_", true)
                . "."
                . $file_ext;

            move_uploaded_file(
                $file_tmp,
                $upload_dir . $image_name
            );
        }
    }


    /* =========================
       INSERT DATABASE
    ========================= */

    if ($message == "") {

        $item_type = "Lost";
        $status = "Pending";

        $sql = "INSERT INTO items
                (
                    user_id,
                    item_name,
                    category,
                    description,
                    location,
                    date_reported,
                    image,
                    item_type,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("SQL Error: " . $conn->error);
        }

        $stmt->bind_param(
            "issssssss",
            $user_id,
            $item_name,
            $category,
            $description,
            $location,
            $date_reported,
            $image_name,
            $item_type,
            $status
        );


        if ($stmt->execute()) {

            campuslost_email_admins(
                $conn,
                "CampusLost - New Lost Item Report",
                $name . " submitted a new lost item report: " . $item_name . "."
            );

            header("Location: lost-items.php");
            exit();

        } else {

            $message = "Failed to report lost item.";
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="theme.js?v=4"></script>

    <title>Report Lost Item - CampusLost</title>

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

        /* ================= SIDEBAR ================= */

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
            background: linear-gradient(90deg,#2563eb,#3478f6);
            color: white;
            box-shadow: 0 7px 18px rgba(37,99,235,.25);
        }

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

        /* ================= MAIN ================= */

        .main {
            margin-left: 260px;
            min-height: 100vh;
            padding: 35px 42px 55px;
        }

        /* ================= HEADER ================= */

        .page-header {
            margin-bottom: 25px;
        }

        .page-label {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #ef4444;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .page-header h1 {
            color: #102342;
            font-size: 28px;
            margin-bottom: 7px;
        }

        .page-header p {
            color: #748199;
            font-size: 14px;
        }

        /* ================= MESSAGE ================= */

        .message {
            max-width: 1000px;
            padding: 13px 16px;
            margin-bottom: 18px;
            border-radius: 11px;
            font-size: 13px;
            font-weight: 600;
        }

        .error {
            background: #feecee;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }

        /* ================= FORM CARD ================= */

        .form-card {
            max-width: 1000px;
            background: white;
            border: 1px solid #e8edf5;
            border-radius: 18px;
            box-shadow: 0 7px 25px rgba(15,39,71,.05);
            overflow: hidden;
        }

        .form-card-header {
            padding: 22px 28px;
            border-bottom: 1px solid #edf1f5;
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .form-card-icon {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #feecee;
            color: #ef4444;
            font-size: 16px;
        }

        .form-card-header h2 {
            color: #102342;
            font-size: 16px;
            margin-bottom: 3px;
        }

        .form-card-header p {
            color: #8a97aa;
            font-size: 11px;
        }

        form {
            padding: 28px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px 24px;
        }

        .full {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #334155;
            font-size: 13px;
            font-weight: 700;
        }

        .required {
            color: #ef4444;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 13px;
            pointer-events: none;
        }

        input,
        select,
        textarea {
            width: 100%;
            border: 1px solid #d8e0eb;
            border-radius: 10px;
            background: #f9fbfd;
            color: #334155;
            font-family: Arial, sans-serif;
            font-size: 13px;
            outline: none;
            transition: .2s ease;
        }

        input,
        select {
            height: 49px;
            padding: 0 14px;
        }

        .has-icon input,
        .has-icon select {
            padding-left: 42px;
        }

        textarea {
            min-height: 130px;
            padding: 14px;
            resize: vertical;
            line-height: 1.5;
        }

        input::placeholder,
        textarea::placeholder {
            color: #94a3b8;
        }

        input:focus,
        select:focus,
        textarea:focus {
            background: white;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37,99,235,.08);
        }

        /* ================= UPLOAD ================= */

        .upload-box {
            position: relative;
            border: 2px dashed #bfdbfe;
            background: #f7faff;
            border-radius: 13px;
            padding: 27px 20px;
            text-align: center;
            transition: .2s ease;
        }

        .upload-box:hover {
            border-color: #60a5fa;
            background: #f1f6ff;
        }

        .upload-icon {
            width: 45px;
            height: 45px;
            margin: 0 auto 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: #e8f0ff;
            color: #2563eb;
            font-size: 18px;
        }

        .upload-title {
            color: #334155;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .upload-text {
            color: #94a3b8;
            font-size: 11px;
            margin-bottom: 14px;
        }

        .upload-box input[type="file"] {
            width: auto;
            max-width: 100%;
            height: auto;
            border: none;
            background: transparent;
            padding: 0;
            font-size: 12px;
        }

        .upload-box small {
            display: block;
            margin-top: 12px;
            color: #8a97aa;
            font-size: 10px;
        }

        /* ================= BUTTONS ================= */

        .buttons {
            display: flex;
            justify-content: flex-end;
            gap: 11px;
            margin-top: 27px;
            padding-top: 22px;
            border-top: 1px solid #edf1f5;
        }

        .btn {
            min-height: 43px;
            padding: 0 18px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: .2s ease;
        }

        .cancel {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .cancel:hover {
            background: #e2e8f0;
        }

        .submit {
            border: none;
            background: #2563eb;
            color: white;
            box-shadow: 0 5px 14px rgba(37,99,235,.20);
        }

        .submit:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        /* ================= DARK MODE ================= */

        html.dark-mode body,
        html.dark-mode .main {
            background: #0f172a;
            color: #e2e8f0;
        }

        html.dark-mode .sidebar {
            background: #020617;
        }

        html.dark-mode .page-header h1 {
            color: #f8fafc;
        }

        html.dark-mode .page-header p {
            color: #94a3b8;
        }

        html.dark-mode .form-card {
            background: #1e293b;
            border-color: #27364c;
            box-shadow: 0 7px 25px rgba(0,0,0,.20);
        }

        html.dark-mode .form-card-header {
            border-color: #334155;
        }

        html.dark-mode .form-card-header h2 {
            color: #f8fafc;
        }

        html.dark-mode .form-card-header p {
            color: #94a3b8;
        }

        html.dark-mode .form-card-icon {
            background: rgba(239,68,68,.13);
            color: #f87171;
        }

        html.dark-mode .form-group label {
            color: #e2e8f0;
        }

        html.dark-mode input,
        html.dark-mode select,
        html.dark-mode textarea {
            background: #111b2f;
            color: #e2e8f0;
            border-color: #3a4a62;
        }

        html.dark-mode input:focus,
        html.dark-mode select:focus,
        html.dark-mode textarea:focus {
            border-color: #3b82f6;
            background: #111b2f;
        }

        html.dark-mode input::placeholder,
        html.dark-mode textarea::placeholder {
            color: #71809a;
        }

        html.dark-mode .upload-box {
            background: #111b2f;
            border-color: #31558c;
        }

        html.dark-mode .upload-box:hover {
            background: #14213a;
            border-color: #3b82f6;
        }

        html.dark-mode .upload-icon {
            background: #172b4d;
            color: #60a5fa;
        }

        html.dark-mode .upload-title {
            color: #e2e8f0;
        }

        html.dark-mode .upload-text,
        html.dark-mode .upload-box small {
            color: #94a3b8;
        }

        html.dark-mode .buttons {
            border-color: #334155;
        }

        html.dark-mode .cancel {
            background: #334155;
            border-color: #475569;
            color: #e2e8f0;
        }

        html.dark-mode .cancel:hover {
            background: #475569;
        }

        html.dark-mode .error {
            background: #450a0a;
            border-color: #7f1d1d;
            color: #fca5a5;
        }

        /* ================= RESPONSIVE ================= */

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

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full {
                grid-column: auto;
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

            form {
                padding: 20px;
            }

            .form-card-header {
                padding: 20px;
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

<!-- SIDEBAR -->

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

<?php if ($role === "staff"): ?>

    <!-- STAFF -->

    <div class="menu-label">Main</div>

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


    <div class="menu-label">Reports</div>

    <a href="report-lost.php" class="active">
        <i class="fa-solid fa-bullhorn"></i>
        Report Lost
    </a>

    <a href="report-found.php">
        <i class="fa-solid fa-box"></i>
        Report Found
    </a>


    <div class="menu-label">Staff Management</div>

    <a href="staff-manage-items.php">
        <i class="fa-solid fa-boxes-stacked"></i>
        Manage Items
    </a>

    <a href="staff-claims.php">
        <i class="fa-solid fa-file-circle-check"></i>
        Claims
    </a>


    <div class="menu-label">Account</div>

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


<?php else: ?>

    <!-- STUDENT -->

    <div class="menu-label">Main</div>

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


    <div class="menu-label">Reports</div>

    <a href="report-lost.php" class="active">
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


    <div class="menu-label">Account</div>

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

<?php endif; ?>

</nav>


    <div class="logout">

        <a href="logout.php">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>

    </div>

</aside>


<!-- MAIN -->

<main class="main">

    <div class="page-header">

        <span class="page-label">
            <i class="fa-solid fa-circle-exclamation"></i>
            Lost Item Report
        </span>

        <h1>Report Lost Item</h1>

        <p>
            Tell us about the item you lost on campus.
        </p>

    </div>


    <?php if ($message != ""): ?>

        <div class="message error">

            <i class="fa-solid fa-circle-exclamation"></i>

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php endif; ?>


    <div class="form-card">

        <div class="form-card-header">

            <div class="form-card-icon">
                <i class="fa-solid fa-bullhorn"></i>
            </div>

            <div>
                <h2>Lost Item Information</h2>
                <p>Enter as much information as possible to help identify your item.</p>
            </div>

        </div>


        <form method="POST"
              enctype="multipart/form-data">

            <div class="form-grid">


                <!-- ITEM NAME -->

                <div class="form-group">

                    <label>
                        Item Name
                        <span class="required">*</span>
                    </label>

                    <div class="input-wrap has-icon">

                        <i class="fa-solid fa-tag input-icon"></i>

                        <input
                            type="text"
                            name="item_name"
                            placeholder="Example: Black Wallet"
                            required
                        >

                    </div>

                </div>


                <!-- CATEGORY -->

                <div class="form-group">

                    <label>
                        Category
                        <span class="required">*</span>
                    </label>

                    <div class="input-wrap has-icon">

                        <i class="fa-solid fa-layer-group input-icon"></i>

                        <select name="category" required>

                            <option value="">Select Category</option>
                            <option value="Electronics">Electronics</option>
                            <option value="Documents">Documents</option>
                            <option value="Bags">Bags</option>
                            <option value="Clothing">Clothing</option>
                            <option value="Accessories">Accessories</option>
                            <option value="Keys">Keys</option>
                            <option value="Others">Others</option>

                        </select>

                    </div>

                </div>


                <!-- DATE LOST -->

                <div class="form-group">

                    <label>
                        Date Lost
                        <span class="required">*</span>
                    </label>

                    <div class="input-wrap has-icon">

                        <i class="fa-regular fa-calendar input-icon"></i>

                        <input
                            type="date"
                            name="date_lost"
                            required
                        >

                    </div>

                </div>


                <!-- LOCATION -->

                <div class="form-group">

                    <label>
                        Location Lost
                        <span class="required">*</span>
                    </label>

                    <div class="input-wrap has-icon">

                        <i class="fa-solid fa-location-dot input-icon"></i>

                        <input
                            type="text"
                            name="location"
                            placeholder="Example: Library, Block A"
                            required
                        >

                    </div>

                </div>


                <!-- DESCRIPTION -->

                <div class="form-group full">

                    <label>
                        Description
                        <span class="required">*</span>
                    </label>

                    <textarea
                        name="description"
                        placeholder="Describe the item, colour, brand, where you lost it, etc."
                        required
                    ></textarea>

                </div>


                <!-- IMAGE -->

                <div class="form-group full">

                    <label>Item Image</label>

                    <div class="upload-box">

                        <div class="upload-icon">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>

                        <div class="upload-title">
                            Upload a photo of your item
                        </div>

                        <div class="upload-text">
                            A clear image can make the item easier to identify.
                        </div>

                        <input
                            type="file"
                            name="image"
                            accept=".jpg,.jpeg,.png,.webp"
                        >

                        <small>
                            JPG, JPEG, PNG or WEBP • Maximum 5MB
                        </small>

                    </div>

                </div>

            </div>


            <!-- BUTTONS -->

            <div class="buttons">

                <a href="dashboard.php"
                   class="btn cancel">

                    <i class="fa-solid fa-xmark"></i>
                    Cancel

                </a>


                <button type="submit"
                        class="btn submit">

                    <i class="fa-solid fa-paper-plane"></i>
                    Submit Lost Item

                </button>

            </div>

        </form>

    </div>

</main>

</body>
</html>