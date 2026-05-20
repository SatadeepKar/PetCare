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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_pet'])) {
    $pet_name = $_POST['pet_name'];
    $species = $_POST['species'];
    $breed = $_POST['breed'];
    $age = $_POST['age'];
    $vaccinations = $_POST['vaccinations'];
    $allergies = $_POST['allergies'];
    $photo = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $target_dir = "Uploads/";
        $photo = $target_dir . uniqid() . "_" . basename($_FILES["photo"]["name"]);
        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $photo)) {
            // Photo uploaded successfully
        } else {
            $photo = null; // Fallback if upload fails
        }
    }
    $stmt = $conn->prepare("INSERT INTO pets (user_id, name, species, breed, age, vaccinations, allergies, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssisss", $user_id, $pet_name, $species, $breed, $age, $vaccinations, $allergies, $photo);
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
    <title>Add a New Pet - PetCare</title>
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
        
        /* Custom file input styling */
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            cursor: pointer;
        }
        
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-input-button {
            display: inline-block;
            padding: 10px 20px;
            background: #f3f4f6;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            color: #4b5563;
            font-weight: 500;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .file-input-wrapper:hover .file-input-button {
            background: #ede9fe;
            border-color: #a78bfa;
            color: #7c3aed;
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
        
        /* Image preview */
        .image-preview {
            width: 150px;
            height: 150px;
            border-radius: 12px;
            overflow: hidden;
            background-color: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
                            <!-- <span class="absolute -top-1 -right-1 bg-white rounded-full p-1">
                                <i class="fas fa-paw text-primary-600 text-xs"></i>
                            </span> -->
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
                        <p class="text-sm text-primary-600">Manage your pets</p>
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
                        <h2 class="text-3xl font-bold text-primary-800">Add a New Pet</h2>
                        <p class="text-gray-500 mt-1">Enter your pet's information below</p>
                    </div>
                    <div class="hidden md:block">
                        <img src="https://via.placeholder.com/80" alt="Pet illustration" class="w-20 h-20 object-contain">
                    </div>
                </div>
                
                <!-- Progress indicator -->
                <div class="progress-container">
                    <div class="progress-bar" id="progressBar"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-500 mb-8">
                    <span>Start</span>
                    <span>Basic Info</span>
                    <span>Health</span>
                    <span>Complete</span>
                </div>
                
                <!-- Form card -->
                <div class="bg-card p-8 rounded-2xl shadow-lg border border-gray-200">
                    <form action="add_pet.php" method="POST" enctype="multipart/form-data" class="space-y-6" id="petForm">
                        <!-- Basic Information Section -->
                        <div class="form-section" id="section-basic">
                            <h3 class="form-section-title">
                                <i class="fas fa-info-circle"></i>
                                Basic Information
                            </h3>
                            
                            <div class="grid md:grid-cols-2 gap-6">
                                <div class="form-control">
                                    <label for="pet_name" class="block text-primary-color text-sm font-medium mb-2">Pet Name</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-paw text-gray-400"></i>
                                        </div>
                                        <input type="text" id="pet_name" name="pet_name" required 
                                            class="w-full pl-10 p-3 bg-gray-50 border rounded-lg border-gray-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 outline-none transition-all"
                                            placeholder="Enter pet's name">
                                    </div>
                                </div>
                                
                                <div class="form-control">
                                    <label for="species" class="block text-primary-color text-sm font-medium mb-2">Species</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-feather-alt text-gray-400"></i>
                                        </div>
                                        <select id="species" name="species" required 
                                            class="w-full pl-10 p-3 bg-gray-50 border rounded-lg border-gray-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 outline-none transition-all appearance-none">
                                            <option value="" disabled selected>Select species</option>
                                            <option value="Dog">Dog</option>
                                            <option value="Cat">Cat</option>
                                            <option value="Bird">Bird</option>
                                            <option value="Fish">Fish</option>
                                            <option value="Rabbit">Rabbit</option>
                                            <option value="Hamster">Hamster</option>
                                            <option value="Guinea Pig">Guinea Pig</option>
                                            <option value="Reptile">Reptile</option>
                                            <option value="Other">Other</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <i class="fas fa-chevron-down text-gray-400"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-control">
                                    <label for="breed" class="block text-primary-color text-sm font-medium mb-2">Breed</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-tag text-gray-400"></i>
                                        </div>
                                        <input type="text" id="breed" name="breed" 
                                            class="w-full pl-10 p-3 bg-gray-50 border rounded-lg border-gray-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 outline-none transition-all"
                                            placeholder="Enter breed (optional)">
                                    </div>
                                </div>
                                
                                <div class="form-control">
                                    <label for="age" class="block text-primary-color text-sm font-medium mb-2">Age (years)</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-birthday-cake text-gray-400"></i>
                                        </div>
                                        <input type="number" id="age" name="age" min="0" step="0.1"
                                            class="w-full pl-10 p-3 bg-gray-50 border rounded-lg border-gray-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 outline-none transition-all"
                                            placeholder="Enter age in years">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Health Information Section -->
                        <div class="form-section" id="section-health">
                            <h3 class="form-section-title">
                                <i class="fas fa-heartbeat"></i>
                                Health Information
                            </h3>
                            
                            <div class="space-y-6">
                                <div class="form-control">
                                    <label for="vaccinations" class="block text-primary-color text-sm font-medium mb-2">Vaccinations</label>
                                    <div class="relative">
                                        <div class="absolute top-3 left-3 pointer-events-none">
                                            <i class="fas fa-syringe text-gray-400"></i>
                                        </div>
                                        <textarea id="vaccinations" name="vaccinations" rows="3"
                                            class="w-full pl-10 p-3 bg-gray-50 border rounded-lg border-gray-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 outline-none transition-all"
                                            placeholder="List vaccinations (e.g., Rabies, DHPP)"></textarea>
                                    </div>
                                </div>
                                
                                <div class="form-control">
                                    <label for="allergies" class="block text-primary-color text-sm font-medium mb-2">Allergies</label>
                                    <div class="relative">
                                        <div class="absolute top-3 left-3 pointer-events-none">
                                            <i class="fas fa-allergies text-gray-400"></i>
                                        </div>
                                        <textarea id="allergies" name="allergies" rows="3"
                                            class="w-full pl-10 p-3 bg-gray-50 border rounded-lg border-gray-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 outline-none transition-all"
                                            placeholder="List allergies (e.g., Peanuts, Flea medication)"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Photo Upload Section -->
                        <div class="form-section" id="section-photo">
                            <h3 class="form-section-title">
                                <i class="fas fa-camera"></i>
                                Pet Photo
                            </h3>
                            
                            <div class="flex flex-col md:flex-row md:items-center gap-6">
                                <div class="flex-1">
                                    <div class="file-input-wrapper w-full">
                                        <div class="file-input-button w-full py-6">
                                            <i class="fas fa-cloud-upload-alt text-2xl mb-2"></i>
                                            <p>Click to upload a photo of your pet</p>
                                            <p class="text-xs text-gray-500 mt-1">JPG, PNG or GIF (Max. 5MB)</p>
                                        </div>
                                        <input type="file" id="photo" name="photo" accept="image/*" class="cursor-pointer">
                                    </div>
                                </div>
                                
                                <div class="flex-shrink-0">
                                    <div class="image-preview" id="imagePreview">
                                        <i class="fas fa-image text-gray-300 text-4xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Wizard navigation -->
                        <div class="pt-6 border-t border-gray-100 flex flex-col-reverse md:flex-row justify-between items-center gap-4" id="wizardNav">
                            <a href="dashboard.php" class="w-full md:w-auto text-center bg-white border border-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center gap-2">
                                <i class="fas fa-arrow-left"></i>
                                <span>Back to Dashboard</span>
                            </a>
                            <div class="flex gap-3 w-full md:w-auto justify-end">
                                <button type="button" id="prevStep" class="hidden bg-primary-100 text-primary-700 px-6 py-3 rounded-lg font-medium hover:bg-primary-200 transition-colors">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </button>
                                <button type="button" id="nextStep" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                                    Next <i class="fas fa-chevron-right"></i>
                                </button>
                                <button type="submit" name="add_pet" id="submitPet" class="hidden bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg transition-colors flex items-center justify-center gap-2 shadow-md">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Add Pet</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Tips card -->
                <div class="mt-8 bg-primary-50 rounded-xl p-6 border border-primary-100">
                    <h3 class="text-lg font-semibold text-primary-800 flex items-center gap-2 mb-3">
                        <i class="fas fa-lightbulb text-primary-500"></i>
                        Pet Care Tips
                    </h3>
                    <ul class="space-y-2 text-sm text-primary-700">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-primary-500 mt-1"></i>
                            <span>Keep your pet's vaccinations up to date for optimal health.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-primary-500 mt-1"></i>
                            <span>Regular vet check-ups can help catch health issues early.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-primary-500 mt-1"></i>
                            <span>Maintain a record of your pet's medical history for reference.</span>
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
        
        // Image preview functionality
        const photoInput = document.getElementById('photo');
        const imagePreview = document.getElementById('imagePreview');
        
        photoInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    imagePreview.innerHTML = `<img src="${e.target.result}" alt="Pet preview">`;
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        // Form progress tracking
        const formInputs = document.querySelectorAll('#petForm input, #petForm textarea, #petForm select');
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
        });
        
        // Initialize progress
        updateProgress();

        // Multi-step wizard (Basic Info → Health → Photo)
        const wizardSteps = ['section-basic', 'section-health', 'section-photo'];
        let wizardIndex = 0;
        const prevBtn = document.getElementById('prevStep');
        const nextBtn = document.getElementById('nextStep');
        const submitBtn = document.getElementById('submitPet');

        function showWizardStep(index) {
            wizardSteps.forEach((id, i) => {
                const el = document.getElementById(id);
                if (el) el.style.display = i === index ? 'block' : 'none';
            });
            prevBtn.classList.toggle('hidden', index === 0);
            nextBtn.classList.toggle('hidden', index === wizardSteps.length - 1);
            submitBtn.classList.toggle('hidden', index !== wizardSteps.length - 1);
            progressBar.style.width = `${Math.round(((index + 1) / wizardSteps.length) * 100)}%`;
        }

        nextBtn.addEventListener('click', () => {
            if (wizardIndex === 0) {
                const name = document.getElementById('pet_name').value.trim();
                const species = document.getElementById('species').value;
                if (!name || !species) {
                    alert('Please enter your pet name and species before continuing.');
                    return;
                }
            }
            if (wizardIndex < wizardSteps.length - 1) {
                wizardIndex++;
                showWizardStep(wizardIndex);
            }
        });

        prevBtn.addEventListener('click', () => {
            if (wizardIndex > 0) {
                wizardIndex--;
                showWizardStep(wizardIndex);
            }
        });

        showWizardStep(0);
        
        // Form validation enhancement
        const petForm = document.getElementById('petForm');
        
        petForm.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredInputs = document.querySelectorAll('#petForm [required]');
            
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
        
        // Species-specific fields
        const speciesSelect = document.getElementById('species');
        
        speciesSelect.addEventListener('change', function() {
            // You could add species-specific fields or validations here
            // For example, showing different breed options based on species
            const selectedSpecies = this.value;
            
            // Update the pet icon in the preview based on species
            if (selectedSpecies) {
                let speciesIcon = 'fa-paw';
                
                switch(selectedSpecies) {
                    case 'Dog':
                        speciesIcon = 'fa-dog';
                        break;
                    case 'Cat':
                        speciesIcon = 'fa-cat';
                        break;
                    case 'Bird':
                        speciesIcon = 'fa-dove';
                        break;
                    case 'Fish':
                        speciesIcon = 'fa-fish';
                        break;
                    case 'Rabbit':
                        speciesIcon = 'fa-carrot';
                        break;
                    default:
                        speciesIcon = 'fa-paw';
                }
                
                // If no image is uploaded yet, update the icon
                if (!photoInput.files || !photoInput.files[0]) {
                    imagePreview.innerHTML = `<i class="fas ${speciesIcon} text-primary-400 text-5xl"></i>`;
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