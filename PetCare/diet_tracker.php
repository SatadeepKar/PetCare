<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$conn = get_db();

date_default_timezone_set('Asia/Kolkata'); // Set timezone to IST for India

$user_id = $_SESSION['user_id'];
$pets = $conn->query("SELECT * FROM pets WHERE user_id = $user_id");

// Handle diet chart submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_diet'])) {
    $pet_id = $_POST['pet_id'];
    $meal_type = $_POST['meal_type'];
    $quantity = $_POST['quantity'];
    $frequency = $_POST['frequency'];
    $notes = $_POST['notes'] ?? '';
    $date_added = date("Y-m-d H:i:s");

    $stmt = $conn->prepare("SELECT id FROM pets WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $pet_id, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows) {
        $stmt = $conn->prepare("INSERT INTO diet_charts (pet_id, meal_type, quantity, frequency, notes, date_added) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $pet_id, $meal_type, $quantity, $frequency, $notes, $date_added);
        if ($stmt->execute()) {
            header("Location: diet_tracker.php?success=1");
            exit();
        } else {
            $error = "Failed to add diet entry: " . $stmt->error;
        }
    } else {
        $error = "Invalid pet selected.";
    }
}

// Handle AJAX request for diet entries
if (isset($_GET['fetch_diets']) && isset($_GET['pet_id'])) {
    header('Content-Type: application/json');
    $pet_id = (int)$_GET['pet_id'];
    $stmt = $conn->prepare("SELECT * FROM diet_charts WHERE pet_id = ? ORDER BY date_added DESC");
    $stmt->bind_param("i", $pet_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $diets = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($diets);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diet Tracker - PetCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        purple: {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            200: '#ddd6fe',
                            300: '#c4b5fd',
                            400: '#a78bfa',
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9',
                            800: '#5b21b6',
                            900: '#4c1d95',
                        }
                    },
                    fontFamily: {
                        'nunito': ['Nunito', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --bg-main: #f5f3ff;
            --bg-card: #ffffff;
            --text-primary: #4c1d95;
            --text-secondary: #6d28d9;
            --border-color: #c4b5fd;
            --header-gradient-from: #7c3aed;
            --header-gradient-to: #8b5cf6;
            --header-text: #ffffff;
            --accent-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --error-color: #ef4444;
        }
        
        body {
            background-color: var(--bg-main);
            color: var(--text-primary);
            font-family: 'Nunito', sans-serif;
            transition: background-color 0.3s, color 0.3s;
        }
        
        .bg-card {
            background-color: var(--bg-card);
            border-color: var(--border-color);
        }
        
        .text-primary-color {
            color: var(--text-primary);
        }
        
        .text-secondary-color {
            color: var(--text-secondary);
        }
        
        .header-gradient {
            background-image: linear-gradient(to right, var(--header-gradient-from), var(--header-gradient-to));
            color: var(--header-text);
        }
        
        .pet-card {
            transition: all 0.3s ease;
        }
        
        .pet-card:hover {
            transform: translateY(-5px);
        }
        
        .badge {
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .badge-dog, .badge-Dog {
            background-color: #e0f2fe;
            color: #0284c7;
        }
        
        .badge-cat, .badge-Cat {
            background-color: #fef3c7;
            color: #d97706;
        }
        
        .badge-bird, .badge-Bird {
            background-color: #dcfce7;
            color: #16a34a;
        }
        
        .badge-other, .badge-Other {
            background-color: #f3e8ff;
            color: #9333ea;
        }
        
        .header-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            color: var(--header-text);
            font-weight: 600;
        }
        
        .header-button:hover {
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-1px);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            transform: translateY(-1px);
        }
        
        .btn-primary {
            background-color: var(--accent-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #7c3aed;
            box-shadow: 0 4px 6px -1px rgba(124, 58, 237, 0.2), 0 2px 4px -1px rgba(124, 58, 237, 0.1);
        }
        
        .btn-secondary {
            background-color: #64748b;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #475569;
            box-shadow: 0 4px 6px -1px rgba(100, 116, 139, 0.2), 0 2px 4px -1px rgba(100, 116, 139, 0.1);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
        }
        
        .btn-outline:hover {
            background-color: #f8fafc;
            color: var(--text-primary);
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid var(--border-color);
            background-color: var(--bg-card);
            color: var(--text-primary);
            transition: all 0.2s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .alert {
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background-color: #ecfdf5;
            border: 1px solid #d1fae5;
            color: #065f46;
        }
        
        .alert-error {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            color: #b91c1c;
        }
        
        .modal {
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background-color: var(--bg-card);
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(20px);
            transition: transform 0.3s ease;
        }
        
        .modal.active .modal-content {
            transform: translateY(0);
        }
        
        .diet-entry {
            background-color: #f8fafc;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border-left: 4px solid var(--accent-color);
            transition: all 0.2s ease;
        }
        
        .diet-entry:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .pet-image-container {
            position: relative;
            width: 100%;
            height: 160px;
            overflow: hidden;
            border-radius: 0.75rem 0.75rem 0 0;
        }
        
        .pet-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .pet-card:hover .pet-image {
            transform: scale(1.05);
        }
        
        .pet-info {
            position: relative;
            padding: 1.25rem;
            background-color: var(--bg-card);
            border-radius: 0 0 0.75rem 0.75rem;
            border: 1px solid var(--border-color);
            border-top: none;
        }
        
        .pet-avatar {
            position: absolute;
            top: -2.5rem;
            right: 1.25rem;
            width: 4rem;
            height: 4rem;
            border-radius: 50%;
            border: 3px solid var(--bg-card);
            background-color: var(--bg-card);
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .pet-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .loader {
            display: inline-block;
            width: 1.5rem;
            height: 1.5rem;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f5f3ff;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c4b5fd;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a78bfa;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease forwards;
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .animate-slide-up {
            animation: slideUp 0.5s ease forwards;
        }

        /* Notification badge */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 18px;
            height: 18px;
            background-color: #ef4444;
            color: white;
            border-radius: 50%;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* Mobile menu */
        .mobile-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 80%;
            max-width: 300px;
            height: 100vh;
            background-color: var(--bg-card);
            z-index: 50;
            transition: right 0.3s ease;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        
        .mobile-menu.active {
            right: 0;
        }
        
        .mobile-menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 40;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        
        .mobile-menu-overlay.active {
            opacity: 1;
            visibility: visible;
        }
    </style>
</head>
<body class="font-nunito">
    <!-- Mobile Menu Overlay -->
    <div id="mobileMenuOverlay" class="mobile-menu-overlay"></div>
    
    <!-- Mobile Menu -->
    <div id="mobileMenu" class="mobile-menu">
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center space-x-2">
            <div class="bg-white p-2 rounded-full shadow-md">
                            <img src="Uploads/logo.jpg" alt="PetCare Logo" class="w-10 h-10 rounded-full object-cover" onerror="this.src='https://via.placeholder.com/48?text=PC';">
                        </div>
                <span class="font-bold text-lg">PetCare</span>
            </div>
            <button id="closeMobileMenu" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <nav class="flex flex-col space-y-1">
            <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-purple-700 hover:bg-purple-50">
                <i class="fas fa-home w-5 text-center"></i>
                <span>Dashboard</span>
            </a>
            <!-- <a href="settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-purple-700 hover:bg-purple-50">
                <i class="fas fa-cog w-5 text-center"></i>
                <span>Settings</span>
            </a> -->
            <div class="border-t border-gray-200 my-2"></div>
            <a href="logout.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-red-600 hover:bg-red-50">
                <i class="fas fa-sign-out-alt w-5 text-center"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <div class="min-h-screen flex flex-col">
        <!-- Header/Navigation -->
        <header class="header-gradient shadow-lg sticky top-0 z-10">
            <div class="container mx-auto px-4 py-3">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-3">
                    <div class="bg-white p-2 rounded-full shadow-md">
                            <img src="Uploads/logo.jpg" alt="PetCare Logo" class="w-10 h-10 rounded-full object-cover" onerror="this.src='https://via.placeholder.com/48?text=PC';">
                        </div>
                        <h1 class="text-xl font-bold tracking-tight">PetCare</h1>
                    </div>
                    
                    <nav class="hidden md:flex items-center space-x-4">
                        <a href="dashboard.php" class="header-button">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="<?php echo VET_LOCATOR_URL; ?>" class="header-button" target="_blank" rel="noopener">
                            <i class="fas fa-stethoscope"></i>
                            <span>Vet Locator</span>
                        </a>
                        <!-- <a href="settings.php" class="header-button">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a> -->
                        <a href="logout.php" class="header-button">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </nav>
                    
                    <div class="flex items-center md:hidden">
                        <div class="relative mr-4">
                            <button class="text-white">
                                <i class="fas fa-bell text-xl"></i>
                                <span class="notification-badge">3</span>
                            </button>
                        </div>
                        <button id="mobileMenuBtn" class="text-white p-2">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>
        
        <main class="container mx-auto px-4 py-8 flex-1">
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <div id="successAlert" class="alert alert-success animate-fade-in" role="alert">
        <i class="fas fa-check-circle text-green-600 text-xl"></i>
        <p>Diet entry added successfully!</p>
    </div>
    <script>
        // Auto-dismiss success alert after 3 seconds
        setTimeout(() => {
            const successAlert = document.getElementById('successAlert');
            if (successAlert) {
                successAlert.style.transition = 'opacity 0.5s ease';
                successAlert.style.opacity = '0';
                setTimeout(() => {
                    successAlert.remove();
                }, 500); // Remove after fade-out transition

            }
        }, 3000); // 3 seconds
    </script>
<?php endif; ?>
            
            <section class="mb-8 animate-slide-up">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-3xl font-bold text-primary-color">Diet Tracker</h2>
                        <p class="text-secondary-color mt-1">Monitor and manage your pets' nutrition</p>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-card rounded-xl shadow-sm p-6 border border-purple-100 flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
                            <i class="fas fa-bone text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-secondary-color text-sm">Total Pets</p>
                            <h3 class="text-2xl font-bold text-primary-color"><?php echo $pets->num_rows; ?></h3>
                        </div>
                    </div>
                    
                    <div class="bg-card rounded-xl shadow-sm p-6 border border-purple-100 flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
                            <i class="fas fa-utensils text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-secondary-color text-sm">Diet Entries</p>
                            <h3 class="text-2xl font-bold text-primary-color">
                                <?php 
                                    $diet_count = $conn->query("SELECT COUNT(*) as count FROM diet_charts WHERE pet_id IN (SELECT id FROM pets WHERE user_id = $user_id)")->fetch_assoc()['count'];
                                    echo $diet_count;
                                ?>
                            </h3>
                        </div>
                    </div>
                    
                    <div class="bg-card rounded-xl shadow-sm p-6 border border-purple-100 flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
                            <i class="fas fa-calendar-check text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-secondary-color text-sm">Last Updated</p>
                            <h3 class="text-lg font-bold text-primary-color">
                                <?php 
                                    $last_update = $conn->query("SELECT MAX(date_added) as last_date FROM diet_charts WHERE pet_id IN (SELECT id FROM pets WHERE user_id = $user_id)")->fetch_assoc()['last_date'];
                                    echo $last_update ? date("d M Y, h:i A", strtotime($last_update)) : 'No entries yet';
                                ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </section>
            
            <section class="bg-card rounded-xl shadow-sm border border-purple-100 overflow-hidden animate-slide-up">
                <div class="p-6 border-b border-purple-100">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-primary-color flex items-center">
                            <i class="fas fa-paw mr-2 text-purple-500"></i>
                            Your Pets
                        </h3>
                    </div>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php
                        $pets->data_seek(0); // Reset pointer
                        if ($pets->num_rows > 0):
                            while ($pet = $pets->fetch_assoc()): 
                                // Get background image based on species
                                $bgImage = '';
                                switch(strtolower($pet['species'])) {
                                    case 'dog':
                                        $bgImage = 'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60';
                                        break;
                                    case 'cat':
                                        $bgImage = 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60';
                                        break;
                                    case 'bird':
                                        $bgImage = 'https://images.unsplash.com/photo-1522926193341-e9ffd686c60f?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60';
                                        break;
                                    default:
                                        $bgImage = 'https://images.unsplash.com/photo-1425082661705-1834bfd09dca?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60';
                                }
                            ?>
                            <div class="pet-card rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all">
                                <div class="pet-image-container">
                                    <img src="<?php echo $bgImage; ?>" alt="Pet background" class="pet-image">
                                </div>
                                <div class="pet-info">
                                    <div class="pet-avatar">
                                        <img src="<?php echo $pet['photo'] ? htmlspecialchars($pet['photo']) : 'https://via.placeholder.com/150?text=' . substr($pet['name'], 0, 1); ?>" 
                                             alt="<?php echo htmlspecialchars($pet['name']); ?>" 
                                             onerror="this.src='https://via.placeholder.com/150?text=<?php echo substr($pet['name'], 0, 1); ?>'">
                                    </div>
                                    <div class="mb-4">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h3 class="text-xl font-bold text-primary-color"><?php echo htmlspecialchars($pet['name']); ?></h3>
                                                <p class="text-secondary-color text-sm"><?php echo isset($pet['breed']) ? htmlspecialchars($pet['breed']) : htmlspecialchars($pet['species']); ?></p>
                                            </div>
                                            <span class="badge badge-<?php echo strtolower($pet['species']); ?>">
                                                <?php 
                                                    $icon = '';
                                                    switch(strtolower($pet['species'])) {
                                                        case 'dog':
                                                            $icon = 'fa-dog';
                                                            break;
                                                        case 'cat':
                                                            $icon = 'fa-cat';
                                                            break;
                                                        case 'bird':
                                                            $icon = 'fa-dove';
                                                            break;
                                                        default:
                                                            $icon = 'fa-paw';
                                                    }
                                                ?>
                                                <i class="fas <?php echo $icon; ?>"></i>
                                                <?php echo htmlspecialchars($pet['species']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <?php
                                    // Get latest diet entry
                                    $latest_diet = $conn->query("SELECT * FROM diet_charts WHERE pet_id = {$pet['id']} ORDER BY date_added DESC LIMIT 1");
                                    if ($latest_diet && $latest_diet->num_rows > 0):
                                        $diet = $latest_diet->fetch_assoc();
                                    ?>
                                    <div class="bg-purple-50 p-3 rounded-lg mb-4 text-sm">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="font-medium text-primary-color">Latest Diet</span>
                                            <span class="text-xs text-secondary-color"><?php echo date("d M", strtotime($diet['date_added'])); ?></span>
                                        </div>
                                        <p class="text-secondary-color"><?php echo htmlspecialchars($diet['meal_type']); ?> - <?php echo htmlspecialchars($diet['quantity']); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex space-x-2">
                                        <button onclick="openDietForm(<?php echo $pet['id']; ?>, '<?php echo htmlspecialchars($pet['name']); ?>')" class="btn btn-primary flex-1">
                                            <i class="fas fa-plus"></i> Add Diet
                                        </button>
                                        <button onclick="openShowDietForm(<?php echo $pet['id']; ?>, '<?php echo htmlspecialchars($pet['name']); ?>')" class="btn btn-outline flex-1">
                                            <i class="fas fa-history"></i> History
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                       
                        <?php else: ?>
                            <div class="col-span-full text-center py-12">
                                <div class="bg-purple-50 rounded-xl p-8 max-w-md mx-auto">
                                    <div class="w-16 h-16 bg-purple-200 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-paw text-purple-600 text-2xl"></i>
                                    </div>
                                    <h3 class="text-xl font-semibold text-primary-color mb-2">No pets registered</h3>
                                    <p class="text-secondary-color mb-6">Add your first pet to start tracking their diet</p>
                                    <a href="add_pet.php" class="btn btn-primary">
                                        <i class="fas fa-plus"></i>
                                        <span>Add Your First Pet</span>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
            
            <!-- Add Diet Form Modal -->
            <div id="dietModal" class="modal">
                <div class="modal-content p-6 max-w-md w-full">
                    <div class="flex justify-between items-center mb-6">
                        <h4 class="text-xl font-semibold text-primary-color flex items-center">
                            <i class="fas fa-utensils text-purple-500 mr-2"></i>
                            Add Diet for <span id="petName" class="ml-1"></span>
                        </h4>
                        <button type="button" onclick="closeDietForm()" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <form action="diet_tracker.php" method="POST" class="space-y-4">
                        <input type="hidden" id="pet_id" name="pet_id">
                        
                        <div>
                            <label for="meal_type" class="form-label">
                                <i class="fas fa-drumstick-bite text-purple-500 mr-1"></i> Meal Type
                            </label>
                            <input type="text" id="meal_type" name="meal_type" required class="form-input" placeholder="e.g., Dry Kibble, Wet Food">
                        </div>
                        
                        <div>
                            <label for="quantity" class="form-label">
                                <i class="fas fa-weight text-purple-500 mr-1"></i> Quantity
                            </label>
                            <input type="text" id="quantity" name="quantity" required class="form-input" placeholder="e.g., 100g, 1 cup">
                        </div>
                        
                        <div>
                            <label for="frequency" class="form-label">
                                <i class="fas fa-clock text-purple-500 mr-1"></i> Frequency
                            </label>
                            <input type="text" id="frequency" name="frequency" required class="form-input" placeholder="e.g., Daily, Twice a day">
                        </div>
                        
                        <div>
                            <label for="notes" class="form-label">
                                <i class="fas fa-sticky-note text-purple-500 mr-1"></i> Notes (Optional)
                            </label>
                            <textarea id="notes" name="notes" class="form-input" rows="3" placeholder="e.g., No dairy, Special treats"></textarea>
                        </div>
                        
                        <div class="flex justify-end space-x-3 pt-2">
                            <button type="button" onclick="closeDietForm()" class="btn btn-outline">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" name="add_diet" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Diet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Show Diet Form Modal -->
            <div id="showDietModal" class="modal">
                <div class="modal-content p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h4 class="text-xl font-semibold text-primary-color flex items-center">
                            <i class="fas fa-history text-purple-500 mr-2"></i>
                            Diet History for <span id="showPetName" class="ml-1"></span>
                        </h4>
                        <button type="button" onclick="closeShowDietForm()" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <div id="dietListLoading" class="text-center py-8">
                        <div class="w-8 h-8 border-4 border-purple-200 border-t-purple-600 rounded-full animate-spin mx-auto mb-4"></div>
                        <p class="text-secondary-color">Loading diet history...</p>
                    </div>
                    
                    <div id="dietList" class="space-y-4 max-h-96 overflow-y-auto pr-2 hidden"></div>
                    
                    <div id="noDietEntries" class="text-center py-8 hidden">
                        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-utensils text-purple-400 text-xl"></i>
                        </div>
                        <h5 class="text-lg font-medium text-primary-color mb-2">No diet entries yet</h5>
                        <p class="text-secondary-color mb-4">Add your first diet entry to start tracking</p>
                    </div>
                    
                    <div class="flex justify-end mt-6">
                        <button type="button" onclick="closeShowDietForm()" class="btn btn-outline">
                            <i class="fas fa-times"></i> Close
                        </button>
                    </div>
                </div>
            </div>
        </main>
        
        <footer class="bg-white border-t border-purple-100 py-6">
            <div class="container mx-auto px-4">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="flex items-center space-x-2 mb-4 md:mb-0">
                        <div class="w-8 h-8 rounded-full bg-purple-500 flex items-center justify-center">
                            <i class="fas fa-paw text-white text-sm"></i>
                        </div>
                        <span class="text-primary-color font-semibold">PetCare</span>
                    </div>
                    <div class="text-secondary-color text-sm">
                        &copy; <?php echo date('Y'); ?> PetCare. All rights reserved.
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const closeMobileMenu = document.getElementById('closeMobileMenu');
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
        
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.add('active');
            mobileMenuOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
        
        function closeMobile() {
            mobileMenu.classList.remove('active');
            mobileMenuOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        closeMobileMenu.addEventListener('click', closeMobile);
        mobileMenuOverlay.addEventListener('click', closeMobile);

        // Modal functionality
        function showModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function hideModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Open Add Diet form modal
        function openDietForm(petId, petName) {
            document.getElementById('pet_id').value = petId;
            document.getElementById('petName').textContent = petName;
            showModal('dietModal');
        }

        // Close Add Diet form modal
        function closeDietForm() {
            hideModal('dietModal');
            setTimeout(() => {
                document.getElementById('meal_type').value = '';
                document.getElementById('quantity').value = '';
                document.getElementById('frequency').value = '';
                document.getElementById('notes').value = '';
            }, 300);
        }

        // Open Show Diet form modal
        function openShowDietForm(petId, petName) {
            document.getElementById('showPetName').textContent = petName;
            document.getElementById('dietListLoading').classList.remove('hidden');
            document.getElementById('dietList').classList.add('hidden');
            document.getElementById('noDietEntries').classList.add('hidden');
            showModal('showDietModal');
            fetchDietEntries(petId);
        }

        // Close Show Diet form modal
        function closeShowDietForm() {
            hideModal('showDietModal');
        }

        // Format date for display
        function formatDate(dateString) {
            const options = { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            return new Date(dateString).toLocaleDateString('en-US', options);
        }

        // Fetch and display diet entries
        function fetchDietEntries(petId) {
            fetch(`diet_tracker.php?fetch_diets=1&pet_id=${petId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                const dietList = document.getElementById('dietList');
                dietList.innerHTML = '';
                
                document.getElementById('dietListLoading').classList.add('hidden');
                
                if (data.length > 0) {
                    document.getElementById('dietList').classList.remove('hidden');
                    
                    data.forEach((diet, index) => {
                        const div = document.createElement('div');
                        div.className = 'diet-entry animate-fade-in';
                        div.style.animationDelay = `${index * 0.05}s`;
                        
                        let mealIcon = '';
                        if (diet.meal_type.toLowerCase().includes('dry')) {
                            mealIcon = 'fa-bone';
                        } else if (diet.meal_type.toLowerCase().includes('wet')) {
                            mealIcon = 'fa-drumstick-bite';
                        } else if (diet.meal_type.toLowerCase().includes('treat')) {
                            mealIcon = 'fa-cookie';
                        } else {
                            mealIcon = 'fa-utensils';
                        }
                        
                        div.innerHTML = `
                            <div class="flex justify-between items-start mb-2">
                                <h5 class="font-semibold text-primary-color flex items-center">
                                    <i class="fas ${mealIcon} text-purple-500 mr-2"></i>
                                    ${diet.meal_type}
                                </h5>
                                <span class="text-xs text-secondary-color">${formatDate(diet.date_added)}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 mb-2">
                                <div>
                                    <span class="text-xs text-secondary-color">Quantity</span>
                                    <p class="font-medium">${diet.quantity}</p>
                                </div>
                                <div>
                                    <span class="text-xs text-secondary-color">Frequency</span>
                                    <p class="font-medium">${diet.frequency}</p>
                                </div>
                            </div>
                            ${diet.notes ? `
                                <div class="mt-2 pt-2 border-t border-purple-100">
                                    <span class="text-xs text-secondary-color">Notes</span>
                                    <p class="text-sm">${diet.notes}</p>
                                </div>
                            ` : ''}
                        `;
                        dietList.appendChild(div);
                    });
                } else {
                    document.getElementById('noDietEntries').classList.remove('hidden');
                }
            })
            .catch(error => {
                document.getElementById('dietListLoading').classList.add('hidden');
                document.getElementById('dietList').classList.remove('hidden');
                document.getElementById('dietList').innerHTML = `
                    <div class="text-center py-4">
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-exclamation-triangle text-red-500"></i>
                        </div>
                        <p class="text-red-500">Error fetching diet entries: ${error.message}</p>
                    </div>
                `;
                console.error('Error:', error);
            });
        }
        
        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            const dietModal = document.getElementById('dietModal');
            const showDietModal = document.getElementById('showDietModal');
            
            if (event.target === dietModal) {
                closeDietForm();
            }
            
            if (event.target === showDietModal) {
                closeShowDietForm();
            }
        });
        
        // Prevent event propagation from modal content
        document.querySelectorAll('.modal-content').forEach(content => {
            content.addEventListener('click', function(event) {
                event.stopPropagation();
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
