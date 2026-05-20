<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$conn = get_db();

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$username = $user['username'];

// Fetch pets for dropdown
$pets = $conn->query("SELECT * FROM pets WHERE user_id = $user_id");
$selected_pet_id = isset($_GET['pet_id']) ? (int)$_GET['pet_id'] : 0;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_reminder'])) {
    $pet_id = $_POST['pet_id'];
    $task = $_POST['task'];
    $type = $_POST['type'];
    $due_date = $_POST['due_date'];
    $due_time = $_POST['due_time'];
    $recurrence = $_POST['recurrence'];
    $stmt = $conn->prepare("INSERT INTO reminders (pet_id, task, type, due_date, due_time, recurrence, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("isssss", $pet_id, $task, $type, $due_date, $due_time, $recurrence);
    $stmt->execute();
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set a New Reminder - PetCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'outfit': ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        primary: {
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
                    animation: {
                        'bounce-slow': 'bounce 3s infinite',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --bg-main: #f9fafb;
            --bg-card: #ffffff;
            --text-primary: #1f2937;
            --text-secondary: #4b5563;
            --border-color: #e5e7eb;
            --header-gradient-from: #7c3aed;
            --header-gradient-to: #8b5cf6;
            --header-text: #ffffff;
        }
        
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-primary);
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
            background-image: linear-gradient(to bottom right, #c4b5fd, #a78bfa);
        }
        
        .header-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease-in-out;
            color: var(--header-text);
        }
        
        .header-button:hover {
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(4px);
            transform: translateY(-1px);
        }
        
        .header-button i {
            font-size: 1rem;
        }
        
        /* Form section styling */
        .form-section {
            border-left: 3px solid #ddd6fe;
            padding-left: 20px;
            margin-bottom: 30px;
            transition: border-color 0.3s ease;
        }
        
        .form-section:hover {
            border-color: #7c3aed;
        }
        
        .form-section-title {
            font-weight: 600;
            color: #6d28d9;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .form-section-title i {
            margin-right: 10px;
        }
        
        /* Form animations */
        .form-control {
            transition: all 0.3s ease;
        }
        
        .form-control:focus-within {
            transform: translateY(-2px);
        }
        
        .form-control label {
            transition: all 0.3s ease;
        }
        
        .form-control:focus-within label {
            color: #7c3aed;
        }
        
        /* Animated background */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.05;
            pointer-events: none;
        }
        
        .animated-bg-shape {
            position: absolute;
            background: linear-gradient(45deg, #c4b5fd, #a78bfa);
            border-radius: 50%;
            filter: blur(60px);
        }
        
        .shape1 {
            width: 400px;
            height: 400px;
            top: -100px;
            right: -100px;
            animation: float 15s ease-in-out infinite alternate;
        }
        
        .shape2 {
            width: 300px;
            height: 300px;
            bottom: -50px;
            left: -50px;
            animation: float 20s ease-in-out infinite alternate-reverse;
        }
        
        @keyframes float {
            0% {
                transform: translate(0, 0) rotate(0deg);
            }
            100% {
                transform: translate(50px, 50px) rotate(45deg);
            }
        }
        
        /* Mobile menu animation */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .mobile-menu-active {
            animation: slideDown 0.3s ease forwards;
        }
        
        /* Progress indicator */
        .progress-container {
            width: 100%;
            background-color: #f3f4f6;
            border-radius: 9999px;
            height: 8px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(to right, #c4b5fd, #7c3aed);
            width: 0;
            transition: width 0.5s ease;
        }
        
        /* Custom select styling */
        .custom-select-wrapper {
            position: relative;
        }
        
        .custom-select-wrapper select {
            appearance: none;
            -webkit-appearance: none;
            padding-right: 40px;
        }
        
        .custom-select-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: #6b7280;
        }
        
        /* Date and time picker styling */
        input[type="date"], input[type="time"] {
            position: relative;
        }
        
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator {
            background: transparent;
            color: transparent;
            cursor: pointer;
            height: 100%;
            left: 0;
            position: absolute;
            right: 0;
            top: 0;
            width: 100%;
        }
        
        /* Reminder type badges */
        .reminder-type-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-right: 0.5rem;
            transition: all 0.2s ease;
        }
        
        .reminder-type-badge.active {
            transform: scale(1.05);
        }
        
        .reminder-type-general {
            background-color: #e5e7eb;
            color: #4b5563;
        }
        
        .reminder-type-medication {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .reminder-type-vet {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        /* Time picker visual enhancement */
        .time-picker-wrapper {
            position: relative;
        }
        
        .time-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            pointer-events: none;
        }
        
        /* Recurrence options styling */
        .recurrence-options {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }
        
        .recurrence-option {
            flex: 1;
            min-width: 80px;
            text-align: center;
            padding: 8px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .recurrence-option:hover {
            border-color: #c4b5fd;
            background-color: #f5f3ff;
        }
        
        .recurrence-option.active {
            border-color: #7c3aed;
            background-color: #f5f3ff;
            color: #6d28d9;
        }
        
        .recurrence-option i {
            display: block;
            margin-bottom: 4px;
            font-size: 1.25rem;
        }
    </style>
</head>
<body class="font-outfit">
    <!-- Animated background -->
    <div class="animated-bg">
        <div class="animated-bg-shape shape1"></div>
        <div class="animated-bg-shape shape2"></div>
    </div>

    <div class="min-h-screen flex flex-col">
        <!-- Header/Navigation -->
        <header class="header-gradient shadow-lg sticky top-0 z-10">
            <div class="container mx-auto px-4 py-3">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <img src="Uploads/logo.jpg" alt="PetCare Logo" class="w-12 h-12 rounded-full object-cover bg-white p-1 shadow-md" onerror="this.src='https://via.placeholder.com/48';">
                            <span class="absolute -top-1 -right-1 bg-white rounded-full p-1">
                                <i class="fas fa-paw text-primary-600 text-xs"></i>
                            </span>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold">PetCare</h1>
                            <p class="text-xs text-primary-100">Welcome, <?php echo htmlspecialchars($username); ?></p>
                        </div>
                    </div>
                    
                    <nav class="hidden md:flex items-center space-x-4">
                        <a href="dashboard.php" class="header-button">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="logout.php" class="header-button">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </nav>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Mobile Menu Trigger -->
                        <button class="md:hidden text-white p-2 rounded-lg hover:bg-primary-700 transition-colors" id="mobileMenuButton" aria-label="Toggle menu">
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Mobile Menu -->
                <nav id="mobileMenu" class="hidden md:hidden bg-white rounded-lg shadow-lg mt-4 overflow-hidden">
                    <div class="p-4 bg-primary-50 border-l-4 border-primary-500">
                        <p class="font-medium text-primary-800">Hello, <?php echo htmlspecialchars($username); ?></p>
                        <p class="text-sm text-primary-600">Manage your pet reminders</p>
                    </div>
                    <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-primary-50 transition-colors border-b border-gray-100">
                        <i class="fas fa-home text-primary-500"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="logout.php" class="flex items-center space-x-3 px-4 py-3 hover:bg-primary-50 transition-colors text-red-500">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </nav>
            </div>
        </header>
        
        <main class="container mx-auto px-4 py-8 flex-grow">
            <div class="max-w-3xl mx-auto">
                <!-- Page header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-3xl font-bold text-primary-800">Set a New Reminder</h2>
                        <p class="text-gray-500 mt-1">Never miss an important pet care task</p>
                    </div>
                    <div class="hidden md:block">
                        <div class="bg-primary-100 text-primary-600 p-3 rounded-full">
                            <i class="fas fa-bell text-2xl"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Progress indicator -->
                <div class="progress-container">
                    <div class="progress-bar" id="progressBar"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500 mb-8">
                    <span>Start</span>
                    <span>Pet & Type</span>
                    <span>Details</span>
                    <span>Complete</span>
                </div>
                
                <!-- Form card -->
                <div class="bg-card p-8 rounded-2xl shadow-lg border border-gray-200">
                    <form action="set_reminder.php" method="POST" class="space-y-6" id="reminderForm">
                        <!-- Pet Selection Section -->
                        <div class="form-section" id="section-pet">
                            <h3 class="form-section-title">
                                <i class="fas fa-paw"></i>
                                Select Pet
                            </h3>
                            
                            <div class="form-control">
                                <label for="pet_id" class="block text-primary-color text-sm font-medium mb-2">Which pet is this reminder for?</label>
                                <div class="custom-select-wrapper">
                                    <select id="pet_id" name="pet_id" required 
                                        class="w-full p-3 pl-10 bg-gray-50 border rounded-lg border-gray-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 outline-none transition-all">
                                        <?php
                                        if ($pets->num_rows > 0) {
                                            $pets->data_seek(0); // Reset pointer
                                            while ($pet = $pets->fetch_assoc()):
                                        ?>
                                            <option value="<?php echo $pet['id']; ?>"<?php echo ($selected_pet_id === (int)$pet['id']) ? ' selected' : ''; ?>><?php echo htmlspecialchars($pet['name']); ?></option>
                                        <?php 
                                            endwhile;
                                        } else {
                                        ?>
                                            <option value="" disabled>No pets found. Please add a pet first.</option>
                                        <?php } ?>
                                    </select>
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-paw text-gray-400"></i>
                                    </div>
                                    <div class="custom-select-icon">
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Reminder Type Section -->
                        <div class="form-section" id="section-type">
                            <h3 class="form-section-title">
                                <i class="fas fa-tag"></i>
                                Reminder Type
                            </h3>
                            
                            <div class="form-control">
                                <label class="block text-primary-color text-sm font-medium mb-2">What type of reminder is this?</label>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2">
                                    <div class="reminder-type-option" data-value="general">
                                        <input type="radio" id="type-general" name="type" value="general" class="hidden" checked>
                                        <label for="type-general" class="block p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-all text-center">
                                            <i class="fas fa-tasks text-gray-500 text-2xl mb-2"></i>
                                            <div class="font-medium">General Task</div>
                                            <p class="text-xs text-gray-500 mt-1">Regular pet care activities</p>
                                        </label>
                                    </div>
                                    
                                    <div class="reminder-type-option" data-value="medication">
                                        <input type="radio" id="type-medication" name="type" value="medication" class="hidden">
                                        <label for="type-medication" class="block p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-all text-center">
                                            <i class="fas fa-pills text-blue-500 text-2xl mb-2"></i>
                                            <div class="font-medium">Medication</div>
                                            <p class="text-xs text-gray-500 mt-1">Medicine and treatments</p>
                                        </label>
                                    </div>
                                    
                                    <div class="reminder-type-option" data-value="vet_appointment">
                                        <input type="radio" id="type-vet_appointment" name="type" value="vet_appointment" class="hidden">
                                        <label for="type-vet_appointment" class="block p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-all text-center">
                                            <i class="fas fa-stethoscope text-amber-600 text-2xl mb-2"></i>
                                            <div class="font-medium">Vet Appointment</div>
                                            <p class="text-xs text-gray-500 mt-1">Checkups and visits</p>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Task Details Section -->
                        <div class="form-section" id="section-details">
                            <h3 class="form-section-title">
                                <i class="fas fa-clipboard-list"></i>
                                Task Details
                            </h3>
                            
                            <div class="form-control mb-6">
                                <label for="task" class="block text-primary-color text-sm font-medium mb-2">Task Description</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-pencil-alt text-gray-400"></i>
                                    </div>
                                    <input type="text" id="task" name="task" required 
                                        class="w-full pl-10 p-3 bg-gray-50 border rounded-lg border-gray-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 outline-none transition-all"
                                        placeholder="e.g., Give insulin, Vet checkup">
                                </div>
                            </div>
                            
                            <div class="grid md:grid-cols-2 gap-6">
                                <div class="form-control">
                                    <label for="due_date" class="block text-primary-color text-sm font-medium mb-2">Due Date</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-calendar-alt text-gray-400"></i>
                                        </div>
                                        <input type="date" id="due_date" name="due_date" required 
                                            class="w-full pl-10 p-3 bg-gray-50 border rounded-lg border-gray-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 outline-none transition-all"
                                            min="<?php echo date('Y-m-d'); ?>">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <i class="fas fa-calendar-day text-primary-400"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-control">
                                    <label for="due_time" class="block text-primary-color text-sm font-medium mb-2">Due Time</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-clock text-gray-400"></i>
                                        </div>
                                        <input type="time" id="due_time" name="due_time" required 
                                            class="w-full pl-10 p-3 bg-gray-50 border rounded-lg border-gray-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 outline-none transition-all">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <i class="fas fa-bell text-primary-400"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recurrence Section -->
                        <div class="form-section" id="section-recurrence">
                            <h3 class="form-section-title">
                                <i class="fas fa-redo-alt"></i>
                                Recurrence
                            </h3>
                            
                            <div class="form-control">
                                <label class="block text-primary-color text-sm font-medium mb-2">How often should this reminder repeat?</label>
                                <div class="recurrence-options">
                                    <div class="recurrence-option active" data-value="none">
                                        <i class="fas fa-ban"></i>
                                        <span>None</span>
                                    </div>
                                    <div class="recurrence-option" data-value="daily">
                                        <i class="fas fa-calendar-day"></i>
                                        <span>Daily</span>
                                    </div>
                                    <div class="recurrence-option" data-value="weekly">
                                        <i class="fas fa-calendar-week"></i>
                                        <span>Weekly</span>
                                    </div>
                                    <div class="recurrence-option" data-value="monthly">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span>Monthly</span>
                                    </div>
                                </div>
                                <select id="recurrence" name="recurrence" class="hidden">
                                    <option value="none" selected>None</option>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="pt-6 border-t border-gray-100 flex flex-col-reverse md:flex-row justify-between items-center gap-4">
                            <a href="dashboard.php" class="w-full md:w-auto text-center bg-white border border-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center gap-2">
                                <i class="fas fa-arrow-left"></i>
                                <span>Back to Dashboard</span>
                            </a>
                            
                            <button type="submit" name="add_reminder" class="w-full md:w-auto bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-md hover:shadow-lg">
                                <i class="fas fa-bell"></i>
                                <span>Set Reminder</span>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Tips card -->
                <div class="mt-8 bg-primary-50 rounded-xl p-6 border border-primary-100">
                    <h3 class="text-lg font-semibold text-primary-800 flex items-center gap-2 mb-3">
                        <i class="fas fa-lightbulb text-primary-500"></i>
                        Reminder Tips
                    </h3>
                    <ul class="space-y-2 text-sm text-primary-700">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-primary-500 mt-1"></i>
                            <span>Set specific times for medication to ensure consistent dosing.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-primary-500 mt-1"></i>
                            <span>Use recurring reminders for regular tasks like feeding or walks.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-primary-500 mt-1"></i>
                            <span>Schedule vet appointments well in advance for better availability.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </main>
        
        <footer class="bg-gray-50 border-t border-gray-200 py-6 mt-12">
            <div class="container mx-auto px-4 text-center text-gray-500 text-sm">
                <p>&copy; 2023 PetCare. All rights reserved.</p>
                <p class="mt-1">Helping pet owners take better care of their furry friends.</p>
            </div>
        </footer>
    </div>
    
    <script>
        // Mobile menu toggle
        const mobileMenuButton = document.getElementById('mobileMenuButton');
        const mobileMenu = document.getElementById('mobileMenu');
        
        function toggleMobileMenu() {
            if (mobileMenu.classList.contains('hidden')) {
                mobileMenu.classList.remove('hidden');
                mobileMenu.classList.add('mobile-menu-active');
                mobileMenuButton.innerHTML = '<i class="fas fa-times"></i>';
            } else {
                mobileMenu.classList.add('hidden');
                mobileMenu.classList.remove('mobile-menu-active');
                mobileMenuButton.innerHTML = '<i class="fas fa-bars"></i>';
            }
        }
        
        mobileMenuButton.addEventListener('click', toggleMobileMenu);
        
        // Reminder type selection
        const reminderTypeOptions = document.querySelectorAll('.reminder-type-option');
        
        reminderTypeOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Update visual selection
                reminderTypeOptions.forEach(opt => {
                    const radio = opt.querySelector('input[type="radio"]');
                    const label = opt.querySelector('label');
                    
                    if (opt === this) {
                        radio.checked = true;
                        label.classList.add('border-primary-500', 'bg-primary-50');
                    } else {
                        radio.checked = false;
                        label.classList.remove('border-primary-500', 'bg-primary-50');
                    }
                });
                
                // Update form fields based on type
                const selectedType = this.dataset.value;
                updateFormBasedOnType(selectedType);
            });
        });
        
        function updateFormBasedOnType(type) {
            const taskInput = document.getElementById('task');
            
            // Update placeholder based on type
            switch(type) {
                case 'medication':
                    taskInput.placeholder = 'e.g., Give insulin, Apply flea medication';
                    break;
                case 'vet_appointment':
                    taskInput.placeholder = 'e.g., Annual checkup, Vaccination';
                    break;
                default:
                    taskInput.placeholder = 'e.g., Grooming, Training session';
            }
        }
        
        // Recurrence option selection
        const recurrenceOptions = document.querySelectorAll('.recurrence-option');
        const recurrenceSelect = document.getElementById('recurrence');
        
        recurrenceOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Update visual selection
                recurrenceOptions.forEach(opt => {
                    if (opt === this) {
                        opt.classList.add('active');
                    } else {
                        opt.classList.remove('active');
                    }
                });
                
                // Update hidden select value
                recurrenceSelect.value = this.dataset.value;
            });
        });
        
        // Form progress tracking
        const formInputs = document.querySelectorAll('#reminderForm input, #reminderForm select, #reminderForm textarea');
        const progressBar = document.getElementById('progressBar');
        const totalFields = formInputs.length;
        let filledFields = 0;
        
        function updateProgress() {
            filledFields = 0;
            formInputs.forEach(input => {
                if (input.value.trim() !== '') {
                    filledFields++;
                }
            });
            
            const progressPercentage = Math.min(100, Math.round((filledFields / totalFields) * 100));
            progressBar.style.width = `${progressPercentage}%`;
        }
        
        formInputs.forEach(input => {
            input.addEventListener('input', updateProgress);
            input.addEventListener('change', updateProgress);
        });
        
        // Initialize progress
        updateProgress();
        
        // Set default date to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('due_date').value = today;
            
            // Set default time to current time rounded to nearest hour
            const now = new Date();
            now.setMinutes(0);
            now.setSeconds(0);
            const hours = String(now.getHours()).padStart(2, '0');
            document.getElementById('due_time').value = `${hours}:00`;
            
            // Update progress after setting defaults
            updateProgress();
        });
        
        // Form validation enhancement
        const reminderForm = document.getElementById('reminderForm');
        
        reminderForm.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredInputs = document.querySelectorAll('#reminderForm [required]');
            
            requiredInputs.forEach(input => {
                if (input.value.trim() === '') {
                    isValid = false;
                    input.classList.add('border-red-500', 'bg-red-50');
                    
                    // Add error message if it doesn't exist
                    const errorId = `${input.id}-error`;
                    if (!document.getElementById(errorId)) {
                        const errorMsg = document.createElement('p');
                        errorMsg.id = errorId;
                        errorMsg.className = 'text-red-500 text-xs mt-1';
                        errorMsg.innerHTML = `<i class="fas fa-exclamation-circle mr-1"></i>This field is required`;
                        input.parentNode.appendChild(errorMsg);
                    }
                } else {
                    input.classList.remove('border-red-500', 'bg-red-50');
                    
                    // Remove error message if it exists
                    const errorMsg = document.getElementById(`${input.id}-error`);
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                
                // Scroll to the first error
                const firstError = document.querySelector('.border-red-500');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
        
        // Add subtle animation to form sections
        const formSections = document.querySelectorAll('.form-section');
        
        formSections.forEach(section => {
            section.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(5px)';
                this.style.transition = 'transform 0.3s ease';
            });
            
            section.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>