<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

/* =========================
   GET USER INFORMATION
========================= */

$sql = "SELECT user_id, id_number, name, programme, email, role, profile_picture
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

$user = $result->fetch_assoc();
$role = strtolower(trim($user["role"]));

$stmt->close();


/* =========================
   UPDATE PROFILE
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $programme = trim($_POST["programme"] ?? "");
    
        /* =========================
       PROFILE PICTURE UPLOAD
    ========================= */

    $profile_picture = $user["profile_picture"] ?? null;

    if (
        isset($_FILES["profile_picture"]) &&
        $_FILES["profile_picture"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES["profile_picture"]["error"] !== UPLOAD_ERR_OK) {

            $error = "Failed to upload profile picture.";

        } elseif ($_FILES["profile_picture"]["size"] > 5 * 1024 * 1024) {

            $error = "Profile picture must be 5MB or smaller.";

        } else {

            $tmp_name = $_FILES["profile_picture"]["tmp_name"];

            $image_info = @getimagesize($tmp_name);

            $allowed_types = [
                IMAGETYPE_JPEG => "jpg",
                IMAGETYPE_PNG  => "png",
                IMAGETYPE_WEBP => "webp"
            ];

            if (
                $image_info === false ||
                !isset($allowed_types[$image_info[2]])
            ) {

                $error = "Only JPG, PNG and WEBP images are allowed.";

            } else {

                $extension = $allowed_types[$image_info[2]];

                $upload_directory =
                    __DIR__ . "/uploads/profile_pictures/";

                if (!is_dir($upload_directory)) {
                    mkdir($upload_directory, 0755, true);
                }

                $new_filename =
                    "profile_" .
                    $user_id .
                    "_" .
                    time() .
                    "." .
                    $extension;

                $destination =
                    $upload_directory . $new_filename;

                if (move_uploaded_file($tmp_name, $destination)) {

                    /* Delete old picture */
                    if (!empty($profile_picture)) {

                        $old_file =
                            __DIR__ . "/" . $profile_picture;

                        if (
                            is_file($old_file) &&
                            strpos(
                                realpath($old_file),
                                realpath($upload_directory)
                            ) === 0
                        ) {
                            unlink($old_file);
                        }
                    }

                    $profile_picture =
                        "uploads/profile_pictures/" .
                        $new_filename;

                } else {

                    $error = "Unable to save profile picture.";
                }
            }
        }
    }

    if (isset($error)) {

    } elseif ($name === "" || $email === "") {

        $error = "Please fill in your name and email address.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } else {

        $update_sql = "UPDATE users
               SET name = ?,
                   email = ?,
                   programme = ?,
                   profile_picture = ?
               WHERE user_id = ?";

        $update_stmt = $conn->prepare($update_sql);

        if (!$update_stmt) {
            die("SQL Error: " . $conn->error);
        }

        $update_stmt->bind_param(
            "ssssi",
            $name,
            $email,
            $programme,
            $profile_picture,
            $user_id
        );

        if ($update_stmt->execute()) {

            $_SESSION["name"] = $name;

            $success = "Profile updated successfully!";

            $user["name"] = $name;
            $user["email"] = $email;
            $user["programme"] = $programme;
            $user["profile_picture"] = $profile_picture;

        } else {

            $error = "Failed to update profile.";
        }

        $update_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <script src="theme.js?v=4"></script>

    <title>
        <?php echo ($role === "admin") ? "Admin Profile" : "My Profile"; ?>
        - CampusLost
    </title>

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


/* ================= LOGOUT ================= */

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


/* ================= PAGE HEADER ================= */

.page-header {
    margin-bottom: 25px;
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

.page-header h1 {
    color: #102342;
    font-size: 28px;
    margin-bottom: 7px;
}

.page-header p {
    color: #748199;
    font-size: 14px;
}


/* ================= PROFILE CARD ================= */

.profile-card {
    max-width: 1000px;
    background: white;
    border: 1px solid #e8edf5;
    border-radius: 18px;
    box-shadow: 0 7px 25px rgba(15,39,71,.05);
    overflow: hidden;
}


/* ================= PROFILE TOP ================= */

.profile-card-header {
    padding: 26px 28px;
    border-bottom: 1px solid #edf1f5;
    display: flex;
    align-items: center;
    gap: 16px;
}

.profile-picture-section {
    display: flex;
    align-items: center;
    gap: 14px;
}

.profile-avatar {
    width: 72px;
    height: 72px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #eff6ff;
    color: #2563eb;
    font-size: 26px;
    overflow: hidden;
    border: 3px solid #dbeafe;
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.change-photo-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 13px;
    border: 1px solid #d8e0eb;
    border-radius: 9px;
    background: #f8fafc;
    color: #334155;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s ease;
}

.change-photo-btn:hover {
    background: #eff6ff;
    border-color: #93c5fd;
    color: #2563eb;
}

.profile-info h2 {
    color: #102342;
    font-size: 18px;
    margin-bottom: 5px;
}

.profile-info p {
    color: #8a97aa;
    font-size: 12px;
}


/* ================= FORM ================= */

.profile-form {
    padding: 28px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 22px 24px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #334155;
    font-size: 13px;
    font-weight: 700;
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

.form-group input {
    width: 100%;
    height: 49px;
    padding: 0 14px 0 42px;
    border: 1px solid #d8e0eb;
    border-radius: 10px;
    background: #f9fbfd;
    color: #334155;
    font-family: Arial, sans-serif;
    font-size: 13px;
    outline: none;
    transition: .2s ease;
}

.form-group input:focus {
    background: white;
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37,99,235,.08);
}


/* ================= ROLE ================= */

.role-box {
    width: 100%;
    height: 49px;
    padding: 0 14px 0 42px;
    display: flex;
    align-items: center;
    border: 1px solid #d8e0eb;
    border-radius: 10px;
    background: #f1f5f9;
    color: #64748b;
    font-size: 13px;
}


/* ================= MESSAGES ================= */

.message {
    max-width: 1000px;
    padding: 13px 16px;
    margin-bottom: 18px;
    border-radius: 11px;
    font-size: 13px;
    font-weight: 600;
}

.success {
    background: #ecfdf3;
    border: 1px solid #bbf7d0;
    color: #15803d;
}

.error {
    background: #feecee;
    border: 1px solid #fecaca;
    color: #b91c1c;
}


/* ================= BUTTON ================= */

.buttons {
    display: flex;
    justify-content: flex-end;
    margin-top: 27px;
    padding-top: 22px;
    border-top: 1px solid #edf1f5;
}

.save-btn {
    min-height: 43px;
    padding: 0 19px;
    border: none;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #2563eb;
    color: white;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: .2s ease;
    box-shadow: 0 5px 14px rgba(37,99,235,.20);
}

.save-btn:hover {
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

html.dark-mode .profile-card {
    background: #1e293b;
    border-color: #27364c;
    box-shadow: 0 7px 25px rgba(0,0,0,.20);
}

html.dark-mode .profile-card-header {
    border-color: #334155;
}

html.dark-mode .profile-avatar {
    background: #172554;
    color: #93c5fd;
}

html.dark-mode .profile-info h2 {
    color: #f8fafc;
}

html.dark-mode .profile-info p {
    color: #94a3b8;
}

html.dark-mode .form-group label {
    color: #e2e8f0;
}

html.dark-mode .form-group input {
    background: #111b2f;
    color: #e2e8f0;
    border-color: #3a4a62;
}

html.dark-mode .form-group input:focus {
    background: #111b2f;
    border-color: #3b82f6;
}

html.dark-mode .role-box {
    background: #111b2f;
    color: #94a3b8;
    border-color: #3a4a62;
}

html.dark-mode .buttons {
    border-color: #334155;
}

html.dark-mode .success {
    background: #052e16;
    border-color: #166534;
    color: #86efac;
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

    .profile-card-header,
    .profile-form {
        padding: 20px;
    }

    .buttons {
        justify-content: stretch;
    }

    .save-btn {
        width: 100%;
    }
}

</style>

</head>

<body>


<!-- ================= SIDEBAR ================= -->

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

        <?php if ($role === "admin"): ?>


            <div class="menu-label">Administration</div>

            <a href="admin-dashboard.php">
                <i class="fa-solid fa-house"></i>
                Dashboard
            </a>

            <a href="admin-items.php">
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


            <div class="menu-label">Account</div>

            <a href="notifications.php">
                <i class="fa-solid fa-bell"></i>
                Notifications
            </a>

            <a href="profile.php" class="active">
                <i class="fa-solid fa-user"></i>
                Profile
            </a>

            <a href="settings.php">
                <i class="fa-solid fa-gear"></i>
                Settings
            </a>


        <?php elseif ($role === "staff"): ?>

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

            <a href="report-lost.php">
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
                <i class="fa-solid fa-user"></i>
                Claims
            </a>
            
           <div class="menu-label">Account</div>
           <a href="notifications.php">
            <i class="fa-solid fa-bell"></i>
            Notifications
          </a>
        
        <a href="profile.php" class="active">
            <i class="fa-solid fa-user"></i>
            Profile
        </a>

        <a href="settings.php">
            <i class="fa-solid fa-gear"></i>
            Settings
        </a>


    <?php else: ?>

        <!-- ================= STUDENT ================= -->

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


        <div class="menu-label">Account</div>

        <a href="notifications.php">
            <i class="fa-solid fa-bell"></i>
            Notifications
        </a>

        <a href="profile.php" class="active">
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


<!-- ================= MAIN ================= -->

<main class="main">


    <div class="page-header">

        <span class="page-label">

            <i class="fa-solid fa-user"></i>

            <?php echo ($role === "admin")
                ? "Administrator Account"
                : "My Account"; ?>

        </span>


        <h1>

            <?php echo ($role === "admin")
                ? "Admin Profile"
                : "My Profile"; ?>

        </h1>


        <p>

            <?php echo ($role === "admin")
                ? "View and manage your administrator account information."
                : "View and manage your account information."; ?>

        </p>

    </div>


    <!-- SUCCESS MESSAGE -->

    <?php if (isset($success)): ?>

        <div class="message success">

            <i class="fa-solid fa-circle-check"></i>

            <?php echo htmlspecialchars($success); ?>

        </div>

    <?php endif; ?>


    <!-- ERROR MESSAGE -->

    <?php if (isset($error)): ?>

        <div class="message error">

            <i class="fa-solid fa-circle-exclamation"></i>

            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>


    <!-- ================= PROFILE CARD ================= -->

    <div class="profile-card">


        <div class="profile-card-header">


            <div class="profile-picture-section">

    <div class="profile-avatar">

        <?php if (!empty($user["profile_picture"])): ?>

            <img
                src="<?php echo htmlspecialchars($user["profile_picture"]); ?>"
                alt="Profile Picture"
                id="profilePreview"
            >
        
        <?php else: ?>
            <i class="fa-solid fa-user" id="defaultProfileIcon"></i>
        <?php endif; ?>
    </div>
</div>

            <div class="profile-info">

                <h2>
                    <?php echo htmlspecialchars($user["name"]); ?>
                </h2>

                <p>

                    <?php
                    if ($role === "admin") {
                        echo "Administrator";
                    } elseif ($role === "staff") {
                        echo "Staff";
                    } else {
                        echo "Student";
                    }
                    ?>

                </p>

            </div>

        </div>


        <!-- ================= FORM ================= -->

        <form method="POST"
              enctype="multipart/form-data"
              class="profile-form">


            <div class="form-grid">

    <!-- FULL NAME -->

    <div class="form-group">

        <label>Full Name</label>

        <div class="input-wrap">

            <i class="fa-solid fa-user input-icon"></i>

            <input
                type="text"
                name="name"
                value="<?php echo htmlspecialchars($user["name"]); ?>"
                required
            >

        </div>

    </div>


    <!-- STUDENT / ADMIN ID -->

    <div class="form-group">

        <label>
            <?php
            if ($role === "admin") {
                echo "Admin ID";
            } elseif ($role === "staff") {
                echo "Staff ID";
            } else {
                echo "Student ID";
            }
            ?>
        </label>

        <div class="input-wrap">

            <i class="fa-solid fa-id-card input-icon"></i>

            <div class="role-box">

                <?php echo htmlspecialchars($user["id_number"]); ?>

            </div>

        </div>

    </div>


    <!-- EMAIL -->

    <div class="form-group">

        <label>Email Address</label>

        <div class="input-wrap">

            <i class="fa-solid fa-envelope input-icon"></i>

            <input
                type="email"
                name="email"
                value="<?php echo htmlspecialchars($user["email"]); ?>"
                required
            >

        </div>

    </div>


    <!-- PROGRAMME -->

    <div class="form-group">

        <label>Programme</label>

        <div class="input-wrap">

            <i class="fa-solid fa-graduation-cap input-icon"></i>

            <input
                type="text"
                name="programme"
                value="<?php echo htmlspecialchars($user["programme"] ?? ""); ?>"
                placeholder="Example: Diploma in Computer Science"
            >

        </div>

    </div>


    <!-- ACCOUNT ROLE -->

    <div class="form-group">

        <label>Account Role</label>

        <div class="input-wrap">

            <i class="fa-solid fa-user-shield input-icon"></i>

            <div class="role-box">

                <?php echo htmlspecialchars(
                    ucfirst($user["role"])
                ); ?>

            </div>

        </div>

    </div>

</div>


            <div class="buttons">

    <label for="profile_picture" class="change-photo-btn">
        <i class="fa-solid fa-camera"></i>

        <?php echo !empty($user["profile_picture"])
            ? "Change Photo"
            : "Upload Photo"; ?>
    </label>

    <input
        type="file"
        name="profile_picture"
        id="profile_picture"
        accept="image/jpeg,image/png,image/webp"
        hidden
    >

    <button
        type="submit"
        class="save-btn"
    >
        <i class="fa-solid fa-floppy-disk"></i>
        Save Changes
    </button>

</div>


        </form>

    </div>

</main>

<script>
const profileInput = document.getElementById("profile_picture");

profileInput.addEventListener("change", function () {

    const file = this.files[0];

    if (!file) {
        return;
    }

    // Check image type
    const allowedTypes = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];

    if (!allowedTypes.includes(file.type)) {
        alert("Please choose a JPG, PNG or WEBP image.");
        this.value = "";
        return;
    }

    // Check maximum 5MB
    if (file.size > 5 * 1024 * 1024) {
        alert("Profile picture must be 5MB or smaller.");
        this.value = "";
        return;
    }

    const reader = new FileReader();

    reader.onload = function (e) {

        const avatar = document.querySelector(".profile-avatar");

        avatar.innerHTML = `
            <img
                src="${e.target.result}"
                alt="Profile Picture"
                id="profilePreview"
            >
        `;

        const button = document.querySelector(".change-photo-btn");

        button.innerHTML = `
            <i class="fa-solid fa-camera"></i>
            Change Photo
        `;
    };

    reader.readAsDataURL(file);
});
</script>

</body>
</html>
