<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "petcare");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['pet_id'])) {
    header("Location: dashboard.php");
    exit();
}

$pet_id = $_GET['pet_id'];
$user_id = $_SESSION['user_id'];

// Fetch pet details
$stmt = $conn->prepare("SELECT * FROM pets WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $pet_id, $user_id);
$stmt->execute();
$pet = $stmt->get_result()->fetch_assoc();
if (!$pet) {
    header("Location: dashboard.php");
    exit();
}

// Fetch health records
$health_records = $conn->query("SELECT * FROM health_records WHERE pet_id = $pet_id ORDER BY date DESC");

// Fetch journal entries
$journal_entries = $conn->query("SELECT * FROM pet_journal WHERE pet_id = $pet_id ORDER BY entry_date DESC");

// Fetch all pending reminders for this pet
$reminders = $conn->query("SELECT * FROM reminders WHERE pet_id = $pet_id AND status = 'pending' ORDER BY due_date, due_time");

// Calendar events for this pet
$calendar_events = [];
$rem_q = $conn->prepare("SELECT id, task, due_date, due_time, type, status FROM reminders WHERE pet_id = ?");
$rem_q->bind_param("i", $pet_id);
$rem_q->execute();
$rem_res = $rem_q->get_result();
while ($row = $rem_res->fetch_assoc()) {
    $calendar_events[] = [
        'title' => $row['task'],
        'start' => $row['due_date'] . 'T' . $row['due_time'],
        'type' => $row['type'],
        'status' => $row['status'],
        'id' => $row['id'],
    ];
}
$health_q = $conn->prepare("SELECT id, type, details, date FROM health_records WHERE pet_id = ?");
$health_q->bind_param("i", $pet_id);
$health_q->execute();
$health_res = $health_q->get_result();
while ($row = $health_res->fetch_assoc()) {
    $calendar_events[] = [
        'title' => ucfirst(str_replace('_', ' ', $row['type'])) . ': ' . $row['details'],
        'start' => $row['date'],
        'type' => 'health_' . $row['type'],
        'status' => 'completed',
        'id' => $row['id'],
    ];
}
$journal_q = $conn->prepare("SELECT id, entry, entry_date FROM pet_journal WHERE pet_id = ?");
$journal_q->bind_param("i", $pet_id);
$journal_q->execute();
$journal_res = $journal_q->get_result();
while ($row = $journal_res->fetch_assoc()) {
    $calendar_events[] = [
        'title' => 'Journal: ' . substr($row['entry'], 0, 24),
        'start' => $row['entry_date'],
        'type' => 'journal',
        'status' => 'completed',
        'id' => $row['id'],
    ];
}

// Handle health record submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_health_record'])) {
    $type = $_POST['type'];
    $details = $_POST['details'];
    $date = $_POST['date'];
    $file_path = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $target_dir = "uploads/";
        $file_path = $target_dir . uniqid() . "_" . basename($_FILES["file"]["name"]);
        move_uploaded_file($_FILES["file"]["tmp_name"], $file_path);
    }
    $stmt = $conn->prepare("INSERT INTO health_records (pet_id, type, details, date, file_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $pet_id, $type, $details, $date, $file_path);
    $stmt->execute();
    header("Location: pet_profile.php?pet_id=$pet_id");
    exit();
}

// Handle journal entry submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_journal_entry'])) {
    $entry = $_POST['entry'];
    $mood = $_POST['mood'];
    $meal = $_POST['meal'];
    $poop_log = $_POST['poop_log'];
    $entry_date = date("Y-m-d H:i:s");
    $stmt = $conn->prepare("INSERT INTO pet_journal (pet_id, entry, mood, meal, poop_log, entry_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $pet_id, $entry, $mood, $meal, $poop_log, $entry_date);
    $stmt->execute();
    header("Location: pet_profile.php?pet_id=$pet_id");
    exit();
}

// Handle edit pet submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_pet'])) {
    $pet_name = $_POST['pet_name'];
    $species = $_POST['species'];
    $breed = $_POST['breed'];
    $age = $_POST['age'] ? (int)$_POST['age'] : null;
    $vaccinations = $_POST['vaccinations'];
    $allergies = $_POST['allergies'];
    $photo = $pet['photo'];

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $target_dir = "uploads/";
        $new_photo = $target_dir . uniqid() . "_" . basename($_FILES["photo"]["name"]);
        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $new_photo)) {
            // Delete old photo if it exists and isn't the default
            if ($photo && file_exists($photo) && strpos($photo, 'placeholder.com') === false) {
                unlink($photo);
            }
            $photo = $new_photo;
        }
    }

    $stmt = $conn->prepare("UPDATE pets SET name = ?, species = ?, breed = ?, age = ?, vaccinations = ?, allergies = ?, photo = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sssisssii", $pet_name, $species, $breed, $age, $vaccinations, $allergies, $photo, $pet_id, $user_id);
    $stmt->execute();
    header("Location: pet_profile.php?pet_id=$pet_id");
    exit();
}

// Handle pet deletion
if (isset($_GET['delete_pet']) && $_GET['delete_pet'] == $pet_id) {
    // Delete associated health records
    $conn->query("DELETE FROM health_records WHERE pet_id = $pet_id");
    // Delete associated journal entries
    $conn->query("DELETE FROM pet_journal WHERE pet_id = $pet_id");
    // Delete associated reminders and diet records
    $conn->query("DELETE FROM reminders WHERE pet_id = $pet_id");
    $conn->query("DELETE FROM diet_charts WHERE pet_id = $pet_id");
    // Delete pet photo if it exists and isn't the default
    if ($pet['photo'] && file_exists($pet['photo']) && strpos($pet['photo'], 'placeholder.com') === false) {
        unlink($pet['photo']);
    }
    // Delete the pet
    $stmt = $conn->prepare("DELETE FROM pets WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $pet_id, $user_id);
    $stmt->execute();
    header("Location: dashboard.php");
    exit();
}

// Handle reminder completion
if (isset($_GET['complete_reminder']) && is_numeric($_GET['complete_reminder'])) {
    $reminder_id = $_GET['complete_reminder'];
    $stmt = $conn->prepare("UPDATE reminders SET status = 'completed' WHERE id = ? AND pet_id = ?");
    $stmt->bind_param("ii", $reminder_id, $pet_id);
    $stmt->execute();
    header("Location: pet_profile.php?pet_id=$pet_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Profile - <?php echo htmlspecialchars($pet['name']); ?> | PetCare</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js"></script>
    <style>
        :root {
            --primary-light: #a78bfa;
            --primary: #8b5cf6;
            --primary-dark: #7c3aed;
            --secondary: #f3f4f6;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --text-light: #9ca3af;
            --white: #ffffff;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
            --info: #3b82f6;
            --border-radius: 12px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9fafb;
            color: var(--text-primary);
            line-height: 1.6;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Header Styles */
        .header {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: var(--white);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--white);
        }

        .logo img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            background-color: var(--white);
            padding: 5px;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.2);
        }

        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .nav-desktop {
            display: flex;
            gap: 1.5rem;
        }

        .nav-link {
            color: var(--white);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            transition: background-color 0.3s;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-link i {
            font-size: 1.1rem;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .nav-mobile {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background-color: var(--primary);
            padding: 1rem;
            box-shadow: var(--shadow);
        }

        .nav-mobile .nav-link {
            padding: 0.75rem 1rem;
            border-radius: var(--border-radius);
        }

        /* Main Content Styles */
        .main {
            padding: 2rem 0;
        }

        .section {
            margin-bottom: 2rem;
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--secondary);
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--primary);
        }

        .section-content {
            padding: 1.5rem;
        }

        /* Pet Profile Styles */
        .pet-profile {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        @media (min-width: 768px) {
            .pet-profile {
                flex-direction: row;
                align-items: center;
            }
        }

        .pet-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-light);
            box-shadow: var(--shadow);
        }

        .pet-info {
            flex: 1;
        }

        .pet-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .pet-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
            color: var(--text-secondary);
        }

        .pet-detail i {
            color: var(--primary);
            width: 20px;
            text-align: center;
        }

        /* Button Styles */
        .btn-group {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-family: inherit;
            font-size: 0.875rem;
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: var(--danger);
            color: var(--white);
        }

        .btn-danger:hover {
            background-color: #dc2626;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--secondary);
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background-color: #e5e7eb;
            transform: translateY(-2px);
        }

        .btn-success {
            background-color: var(--success);
            color: var(--white);
        }

        .btn-success:hover {
            background-color: #059669;
            transform: translateY(-2px);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: var(--border-radius);
            font-family: inherit;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        /* List Styles */
        .list {
            list-style: none;
        }

        .list-item {
            padding: 1rem 0;
            border-bottom: 1px solid var(--secondary);
        }

        .list-item:last-child {
            border-bottom: none;
        }

        .list-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .list-item-title {
            font-weight: 600;
            color: var(--primary-dark);
        }

        .list-item-date {
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .list-item-content {
            color: var(--text-secondary);
        }

        .list-item-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }

        .list-item-meta-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .list-item-meta-item i {
            color: var(--primary);
        }

        /* Modal Styles */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }

        .modal-backdrop.active {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(20px);
            transition: transform 0.3s;
        }

        .modal-backdrop.active .modal {
            transform: translateY(0);
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--secondary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-light);
            cursor: pointer;
            transition: color 0.3s;
        }

        .modal-close:hover {
            color: var(--text-primary);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--secondary);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary-light);
        }

        .empty-state-text {
            font-size: 1.125rem;
            margin-bottom: 1rem;
        }

        /* Badge */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-primary {
            background-color: var(--primary-light);
            color: var(--white);
        }

        .badge-success {
            background-color: var(--success);
            color: var(--white);
        }

        .badge-warning {
            background-color: var(--warning);
            color: var(--white);
        }

        .badge-info {
            background-color: var(--info);
            color: var(--white);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-desktop {
                display: none;
            }

            .mobile-menu-btn {
                display: block;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .btn-group {
                width: 100%;
                justify-content: space-between;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }

        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 1px solid var(--secondary);
            overflow-x: auto;
            scrollbar-width: none;
        }

        .tabs::-webkit-scrollbar {
            display: none;
        }

        .tab {
            padding: 1rem 1.5rem;
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            white-space: nowrap;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
        }

        .tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .tab-content {
            display: none;
            padding: 1.5rem;
            animation: fadeIn 0.3s;
        }

        .tab-content.active {
            display: block;
        }

        /* File Upload */
        .file-upload {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background-color: var(--secondary);
            color: var(--text-primary);
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .file-upload-label:hover {
            background-color: #e5e7eb;
        }

        .file-upload input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-name {
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        /* Toast Notification */
        .toast-container {
            position: fixed;
            bottom: 1rem;
            right: 1rem;
            z-index: 1000;
        }

        .toast {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            margin-top: 0.5rem;
            transform: translateX(100%);
            opacity: 0;
            transition: transform 0.3s, opacity 0.3s;
        }

        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }

        .toast-icon {
            font-size: 1.25rem;
        }

        .toast-success .toast-icon {
            color: var(--success);
        }

        .toast-error .toast-icon {
            color: var(--danger);
        }

        .toast-warning .toast-icon {
            color: var(--warning);
        }

        .toast-info .toast-icon {
            color: var(--info);
        }

        .toast-content {
            flex: 1;
        }

        .toast-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .toast-message {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .toast-close {
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 1.25rem;
            transition: color 0.3s;
        }

        .toast-close:hover {
            color: var(--text-primary);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="dashboard.php" class="logo">
                    <img src="uploads/logo.jpg" alt="PetCare Logo" onerror="this.src='https://via.placeholder.com/40?text=PC'">
                    <span class="logo-text">PetCare</span>
                </a>
                
                <nav class="nav-desktop">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="set_reminder.php" class="nav-link">
                        <i class="fas fa-bell"></i>
                        <span>Reminders</span>
                    </a>
                    <a href="gallery.php" class="nav-link">
                        <i class="fas fa-images"></i>
                        <span>Gallery</span>
                    </a>
                    <a href="diet_tracker.php" class="nav-link">
                        <i class="fas fa-utensils"></i>
                        <span>Diet Tracker</span>
                    </a>
                    <a href="VetShopsLocator/public/index.html" class="nav-link" target="_blank" rel="noopener">
                        <i class="fas fa-stethoscope"></i>
                        <span>Vet Locator</span>
                    </a>
                    <a href="settings.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </nav>
                
                <button class="mobile-menu-btn" aria-label="Toggle menu" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <nav id="mobileMenu" class="nav-mobile">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="set_reminder.php" class="nav-link">
                    <i class="fas fa-bell"></i>
                    <span>Reminders</span>
                </a>
                <a href="gallery.php" class="nav-link">
                    <i class="fas fa-images"></i>
                    <span>Gallery</span>
                </a>
                <a href="diet_tracker.php" class="nav-link">
                    <i class="fas fa-utensils"></i>
                    <span>Diet Tracker</span>
                </a>
                <a href="VetShopsLocator/public/index.html" class="nav-link" target="_blank" rel="noopener">
                    <i class="fas fa-stethoscope"></i>
                    <span>Vet Locator</span>
                </a>
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="main">
        <div class="container">
            <!-- Pet Profile Section -->
            <section class="section fade-in">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-paw"></i>
                        Pet Profile
                    </h2>
                    <div class="btn-group">
                        <button class="btn btn-primary" onclick="openModal('editPetModal')">
                            <i class="fas fa-edit"></i>
                            Edit Details
                        </button>
                        <button class="btn btn-danger" onclick="confirmDelete()">
                            <i class="fas fa-trash-alt"></i>
                            Delete Pet
                        </button>
                    </div>
                </div>
                <div class="section-content">
                    <div class="pet-profile">
                        <img src="<?php echo $pet['photo'] ? htmlspecialchars($pet['photo']) : 'https://via.placeholder.com/120?text=Pet'; ?>" alt="<?php echo htmlspecialchars($pet['name']); ?>" class="pet-image">
                        <div class="pet-info">
                            <h3 class="pet-name"><?php echo htmlspecialchars($pet['name']); ?></h3>
                            <div class="pet-detail">
                                <i class="fas fa-paw"></i>
                                <span>Species: <?php echo htmlspecialchars($pet['species']); ?></span>
                            </div>
                            <?php if ($pet['breed']): ?>
                            <div class="pet-detail">
                                <i class="fas fa-dna"></i>
                                <span>Breed: <?php echo htmlspecialchars($pet['breed']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($pet['age']): ?>
                            <div class="pet-detail">
                                <i class="fas fa-birthday-cake"></i>
                                <span>Age: <?php echo $pet['age']; ?> years</span>
                            </div>
                            <?php endif; ?>
                            <?php if ($pet['vaccinations']): ?>
                            <div class="pet-detail">
                                <i class="fas fa-syringe"></i>
                                <span>Vaccinations: <?php echo htmlspecialchars($pet['vaccinations']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($pet['allergies']): ?>
                            <div class="pet-detail">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Allergies: <?php echo htmlspecialchars($pet['allergies']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Upcoming Reminders -->
            <section class="section fade-in">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-bell"></i> Upcoming Reminders</h2>
                    <a href="set_reminder.php?pet_id=<?php echo $pet_id; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Set a New Reminder
                    </a>
                </div>
                <div class="section-content">
                    <?php
                    $reminders->data_seek(0);
                    if ($reminders->num_rows > 0):
                        while ($up = $reminders->fetch_assoc()):
                    ?>
                    <div class="list-item" style="margin-bottom: 0.75rem;">
                        <div class="list-item-header">
                            <span class="list-item-title"><?php echo htmlspecialchars($up['task']); ?></span>
                            <span class="badge badge-info"><?php echo ucwords(str_replace('_', ' ', $up['type'])); ?></span>
                        </div>
                        <div class="list-item-meta">
                            <span><i class="far fa-calendar-alt"></i> <?php echo $up['due_date']; ?> at <?php echo $up['due_time']; ?></span>
                            <a href="pet_profile.php?pet_id=<?php echo $pet_id; ?>&complete_reminder=<?php echo $up['id']; ?>" class="btn btn-success btn-sm">Complete</a>
                        </div>
                    </div>
                    <?php endwhile; else: ?>
                    <p class="empty-state-text">No upcoming reminders. <a href="set_reminder.php?pet_id=<?php echo $pet_id; ?>">Add one</a>.</p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Pet Care Calendar -->
            <section class="section fade-in">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-calendar"></i> Pet Care Calendar</h2>
                </div>
                <div class="section-content" style="background: #fff; padding: 1rem; border-radius: var(--border-radius);">
                    <div id="petCalendar"></div>
                </div>
            </section>

            <!-- Tabs Section -->
            <section class="section fade-in">
                <div class="tabs">
                    <div class="tab active" data-tab="health-records">
                        <i class="fas fa-heartbeat"></i> Health Records
                    </div>
                    <div class="tab" data-tab="reminders">
                        <i class="fas fa-bell"></i> Reminders
                    </div>
                    <div class="tab" data-tab="journal">
                        <i class="fas fa-book"></i> Pet Journal
                    </div>
                </div>

                <!-- Health Records Tab -->
                <div id="health-records" class="tab-content active">
                    <?php if ($health_records->num_rows > 0): ?>
                    <ul class="list">
                        <?php while ($record = $health_records->fetch_assoc()): ?>
                        <li class="list-item">
                            <div class="list-item-header">
                                <span class="list-item-title">
                                    <?php 
                                    $icon = '';
                                    switch($record['type']) {
                                        case 'vet_visit': $icon = '<i class="fas fa-stethoscope"></i>'; break;
                                        case 'vaccination': $icon = '<i class="fas fa-syringe"></i>'; break;
                                        case 'medication': $icon = '<i class="fas fa-pills"></i>'; break;
                                        case 'weight': $icon = '<i class="fas fa-weight"></i>'; break;
                                        default: $icon = '<i class="fas fa-notes-medical"></i>';
                                    }
                                    echo $icon . ' ' . ucfirst(str_replace('_', ' ', $record['type']));
                                    ?>
                                </span>
                                <span class="list-item-date">
                                    <i class="far fa-calendar-alt"></i> <?php echo $record['date']; ?>
                                </span>
                            </div>
                            <div class="list-item-content">
                                <?php echo htmlspecialchars($record['details']); ?>
                            </div>
                            <?php if ($record['file_path']): ?>
                            <div class="list-item-meta">
                                <a href="<?php echo htmlspecialchars($record['file_path']); ?>" class="btn btn-secondary btn-sm" target="_blank">
                                    <i class="fas fa-file-medical"></i> View Document
                                </a>
                            </div>
                            <?php endif; ?>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-notes-medical"></i>
                        <p class="empty-state-text">No health records available</p>
                        <button class="btn btn-primary" onclick="openModal('addHealthRecordModal')">
                            <i class="fas fa-plus"></i> Add Health Record
                        </button>
                    </div>
                    <?php endif; ?>

                    <?php if ($health_records->num_rows > 0): ?>
                    <div class="section-content" style="text-align: center; padding-top: 0;">
                        <button class="btn btn-primary" onclick="openModal('addHealthRecordModal')">
                            <i class="fas fa-plus"></i> Add Health Record
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Reminders Tab -->
                <div id="reminders" class="tab-content">
                    <?php if ($reminders->num_rows > 0): ?>
                    <ul class="list">
                        <?php while ($reminder = $reminders->fetch_assoc()): ?>
                        <li class="list-item">
                            <div class="list-item-header">
                                <span class="list-item-title">
                                    <?php if ($reminder['type'] == 'medication'): ?>
                                    <i class="fas fa-pills"></i> Medication: <?php echo htmlspecialchars($reminder['task']); ?>
                                    <?php else: ?>
                                    <i class="fas fa-stethoscope"></i> Vet Appointment: <?php echo htmlspecialchars($reminder['task']); ?>
                                    <?php endif; ?>
                                </span>
                                <span class="badge <?php echo $reminder['type'] == 'medication' ? 'badge-info' : 'badge-warning'; ?>">
                                    <?php echo ucfirst($reminder['type']); ?>
                                </span>
                            </div>
                            <div class="list-item-content">
                                <div class="list-item-meta">
                                    <div class="list-item-meta-item">
                                        <i class="far fa-calendar-alt"></i>
                                        <span><?php echo $reminder['due_date']; ?></span>
                                    </div>
                                    <div class="list-item-meta-item">
                                        <i class="far fa-clock"></i>
                                        <span><?php echo $reminder['due_time']; ?></span>
                                    </div>
                                    <div class="list-item-meta-item">
                                        <i class="fas fa-redo-alt"></i>
                                        <span><?php echo ucfirst($reminder['recurrence']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="list-item-meta" style="justify-content: flex-end;">
                                <a href="pet_profile.php?pet_id=<?php echo $pet_id; ?>&complete_reminder=<?php echo $reminder['id']; ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-check"></i> Mark as Completed
                                </a>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-bell"></i>
                        <p class="empty-state-text">No active reminders</p>
                        <a href="set_reminder.php?pet_id=<?php echo $pet_id; ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Reminder
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if ($reminders->num_rows > 0): ?>
                    <div class="section-content" style="text-align: center; padding-top: 0;">
                        <a href="set_reminder.php?pet_id=<?php echo $pet_id; ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Reminder
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Journal Tab -->
                <div id="journal" class="tab-content">
                    <?php if ($journal_entries->num_rows > 0): ?>
                    <ul class="list">
                        <?php while ($entry = $journal_entries->fetch_assoc()): ?>
                        <li class="list-item">
                            <div class="list-item-header">
                                <span class="list-item-title">
                                    <i class="fas fa-book"></i> Journal Entry
                                </span>
                                <span class="list-item-date">
                                    <i class="far fa-calendar-alt"></i> <?php echo date('M d, Y', strtotime($entry['entry_date'])); ?>
                                    <i class="far fa-clock"></i> <?php echo date('h:i A', strtotime($entry['entry_date'])); ?>
                                </span>
                            </div>
                            <div class="list-item-content">
                                <?php echo htmlspecialchars($entry['entry']); ?>
                            </div>
                            <div class="list-item-meta">
                                <?php if ($entry['mood']): ?>
                                <div class="list-item-meta-item">
                                    <i class="fas fa-smile"></i>
                                    <span>Mood: <?php echo htmlspecialchars($entry['mood']); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($entry['meal']): ?>
                                <div class="list-item-meta-item">
                                    <i class="fas fa-utensils"></i>
                                    <span>Meal: <?php echo htmlspecialchars($entry['meal']); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($entry['poop_log']): ?>
                                <div class="list-item-meta-item">
                                    <i class="fas fa-poo"></i>
                                    <span>Poop Log: <?php echo htmlspecialchars($entry['poop_log']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-book"></i>
                        <p class="empty-state-text">No journal entries available</p>
                        <button class="btn btn-primary" onclick="openModal('addJournalEntryModal')">
                            <i class="fas fa-plus"></i> Add Journal Entry
                        </button>
                    </div>
                    <?php endif; ?>

                    <?php if ($journal_entries->num_rows > 0): ?>
                    <div class="section-content" style="text-align: center; padding-top: 0;">
                        <button class="btn btn-primary" onclick="openModal('addJournalEntryModal')">
                            <i class="fas fa-plus"></i> Add Journal Entry
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- Edit Pet Modal -->
    <div id="editPetModal" class="modal-backdrop">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Edit Pet Details</h3>
                <button class="modal-close" onclick="closeModal('editPetModal')">&times;</button>
            </div>
            <form action="pet_profile.php?pet_id=<?php echo $pet_id; ?>" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="pet_name" class="form-label">Pet Name</label>
                        <input type="text" id="pet_name" name="pet_name" value="<?php echo htmlspecialchars($pet['name']); ?>" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="species" class="form-label">Species</label>
                        <input type="text" id="species" name="species" value="<?php echo htmlspecialchars($pet['species']); ?>" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="breed" class="form-label">Breed</label>
                        <input type="text" id="breed" name="breed" value="<?php echo htmlspecialchars($pet['breed']); ?>" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="age" class="form-label">Age (years)</label>
                        <input type="number" id="age" name="age" value="<?php echo $pet['age']; ?>" min="0" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="vaccinations" class="form-label">Vaccinations</label>
                        <textarea id="vaccinations" name="vaccinations" class="form-control"><?php echo htmlspecialchars($pet['vaccinations']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="allergies" class="form-label">Allergies</label>
                        <textarea id="allergies" name="allergies" class="form-control"><?php echo htmlspecialchars($pet['allergies']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pet Photo</label>
                        <div class="file-upload">
                            <label for="photo" class="file-upload-label">
                                <i class="fas fa-upload"></i> Choose a file
                            </label>
                            <input type="file" id="photo" name="photo" accept="image/*" onchange="updateFileName(this)">
                            <div id="file-name" class="file-name">No file chosen</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editPetModal')">Cancel</button>
                    <button type="submit" name="edit_pet" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Health Record Modal -->
    <div id="addHealthRecordModal" class="modal-backdrop">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Add Health Record</h3>
                <button class="modal-close" onclick="closeModal('addHealthRecordModal')">&times;</button>
            </div>
            <form action="pet_profile.php?pet_id=<?php echo $pet_id; ?>" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="type" class="form-label">Record Type</label>
                        <select id="type" name="type" required class="form-control">
                            <option value="vet_visit">Vet Visit</option>
                            <option value="vaccination">Vaccination</option>
                            <option value="medication">Medication</option>
                            <option value="weight">Weight</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="details" class="form-label">Details</label>
                        <textarea id="details" name="details" required class="form-control" placeholder="e.g., Annual checkup, Rabies shot"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" id="date" name="date" required class="form-control" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Upload File (optional)</label>
                        <div class="file-upload">
                            <label for="file" class="file-upload-label">
                                <i class="fas fa-upload"></i> Choose a file
                            </label>
                            <input type="file" id="file" name="file" accept="image/*,application/pdf" onchange="updateFileNameRecord(this)">
                            <div id="file-name-record" class="file-name">No file chosen</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addHealthRecordModal')">Cancel</button>
                    <button type="submit" name="add_health_record" class="btn btn-primary">Add Record</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Journal Entry Modal -->
    <div id="addJournalEntryModal" class="modal-backdrop">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Add Journal Entry</h3>
                <button class="modal-close" onclick="closeModal('addJournalEntryModal')">&times;</button>
            </div>
            <form action="pet_profile.php?pet_id=<?php echo $pet_id; ?>" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="entry" class="form-label">Entry</label>
                        <textarea id="entry" name="entry" required class="form-control" placeholder="Describe your pet's day"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="mood" class="form-label">Mood</label>
                        <input type="text" id="mood" name="mood" class="form-control" placeholder="e.g., Happy, Lethargic">
                    </div>
                    <div class="form-group">
                        <label for="meal" class="form-label">Meal</label>
                        <input type="text" id="meal" name="meal" class="form-control" placeholder="e.g., Dry kibble, Chicken">
                    </div>
                    <div class="form-group">
                        <label for="poop_log" class="form-label">Poop Log</label>
                        <input type="text" id="poop_log" name="poop_log" class="form-control" placeholder="e.g., Normal, Loose">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addJournalEntryModal')">Cancel</button>
                    <button type="submit" name="add_journal_entry" class="btn btn-primary">Add Entry</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>

    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            mobileMenu.classList.toggle('hidden');
        }

        // Modal functions
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        // File upload name display
        function updateFileName(input) {
            const fileName = input.files[0] ? input.files[0].name : 'No file chosen';
            document.getElementById('file-name').textContent = fileName;
        }

        function updateFileNameRecord(input) {
            const fileName = input.files[0] ? input.files[0].name : 'No file chosen';
            document.getElementById('file-name-record').textContent = fileName;
        }

        // Tab switching
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                // Remove active class from all tabs and tab contents
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding content
                tab.classList.add('active');
                const tabId = tab.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });

        // Delete confirmation
        function confirmDelete() {
            if (confirm('Are you sure you want to delete this pet? This action cannot be undone.')) {
                window.location.href = 'pet_profile.php?pet_id=<?php echo $pet_id; ?>&delete_pet=<?php echo $pet_id; ?>';
            }
        }

        // Toast notification
        function showToast(type, title, message, duration = 3000) {
            const toastContainer = document.getElementById('toastContainer');
            
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
                </div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
            `;
            
            toastContainer.appendChild(toast);
            
            // Show the toast with animation
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);
            
            // Auto remove after duration
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, duration);
        }

        // Show success toast if redirected after an action
        <?php if (isset($_GET['success'])): ?>
        document.addEventListener('DOMContentLoaded', () => {
            showToast('success', 'Success', '<?php echo $_GET['success']; ?>');
        });
        <?php endif; ?>

        document.addEventListener('DOMContentLoaded', function() {
            var calEl = document.getElementById('petCalendar');
            if (calEl && typeof FullCalendar !== 'undefined') {
                var calendar = new FullCalendar.Calendar(calEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,listWeek'
                    },
                    events: <?php echo json_encode($calendar_events); ?>,
                    height: 'auto'
                });
                calendar.render();
            }
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
