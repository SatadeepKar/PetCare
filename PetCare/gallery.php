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

$user_id = $_SESSION['user_id'];

// Handle photo upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['pet_photo'])) {
    $target_dir = "Uploads/";
    $target_file = $target_dir . basename($_FILES["pet_photo"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    $error = '';

    if (!in_array($imageFileType, $allowed_types)) {
        $error = "Only JPG, JPEG, PNG, and GIF files are allowed.";
    } elseif ($_FILES["pet_photo"]["size"] > 5000000) { // 5MB limit
        $error = "File is too large. Maximum size is 5MB.";
    } else {
        $new_filename = $user_id . '_' . time() . '.' . $imageFileType;
        $target_file = $target_dir . $new_filename;
        if (move_uploaded_file($_FILES["pet_photo"]["tmp_name"], $target_file)) {
            // Save to database
            $stmt = $conn->prepare("INSERT INTO pet_photos (user_id, filename, upload_date) VALUES (?, ?, NOW())");
            $stmt->bind_param("is", $user_id, $new_filename);
            if ($stmt->execute()) {
                $success = "Photo uploaded successfully!";
            } else {
                $error = "Database error: " . $conn->error;
                unlink($target_file); // Remove file if database insert fails
            }
            $stmt->close();
        } else {
            $error = "Sorry, there was an error uploading your file.";
        }
    }
}

// Fetch user's uploaded photos
$stmt = $conn->prepare("SELECT filename, upload_date FROM pet_photos WHERE user_id = ? ORDER BY upload_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$photos = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Photo Gallery | PetCare</title>
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

        .photo-card {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .photo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .photo-card:hover .photo-overlay {
            opacity: 1;
        }
        
        .photo-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0) 100%);
            padding: 1.5rem 1rem 0.75rem;
            opacity: 0;
            transition: opacity 0.3s ease;
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
        
        .action-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .action-button:hover {
            transform: translateY(-2px);
        }
        
        .upload-zone {
            border: 2px dashed var(--border-color);
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .upload-zone:hover, .upload-zone.dragover {
            border-color: var(--accent-color);
            background-color: rgba(139, 92, 246, 0.05);
        }
        
        .upload-zone input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }
        
        .masonry-grid {
            column-count: 1;
            column-gap: 1rem;
        }
        
        @media (min-width: 640px) {
            .masonry-grid {
                column-count: 2;
            }
        }
        
        @media (min-width: 768px) {
            .masonry-grid {
                column-count: 3;
            }
        }
        
        @media (min-width: 1024px) {
            .masonry-grid {
                column-count: 4;
            }
        }
        
        .masonry-item {
            break-inside: avoid;
            margin-bottom: 1rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fadeIn {
            animation: fadeIn 0.5s ease forwards;
        }
        
        .animate-delay-100 {
            animation-delay: 0.1s;
        }
        
        .animate-delay-200 {
            animation-delay: 0.2s;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .animate-pulse {
            animation: pulse 2s infinite;
        }
        
        .shadow-card {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .shadow-card-hover {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
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

        .liked {
            color: #ef4444 !important;
            text-shadow: 0 0 5px rgba(239, 68, 68, 0.5);
        }
    </style>
</head>
<body class="font-nunito min-h-screen flex flex-col">
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
                    <a href="VetShopsLocator/public/index.html" class="header-button" target="_blank" rel="noopener">
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
        <!-- Page Header -->
        <section class="mb-8 animate-fadeIn">
            
<div class="flex flex-col md:flex-row md:items-center md:justify-between">
    <div class="flex items-center space-x-4 mb-4 md:mb-0">
        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center text-purple-600">
            <i class="fas fa-camera text-xl"></i>
        </div>
        <div>
            <h2 class="text-3xl font-bold text-primary-color">Pet Photo Gallery</h2>
            <p class="text-secondary-color">Capture and share your pet's precious moments</p>
        </div>
    </div>
    <div>
        <button id="uploadBtn" class="action-button bg-purple-500 hover:bg-purple-600 text-white shadow-md hover:shadow-lg">
            <i class="fas fa-cloud-upload-alt"></i>
            <span class="hidden sm:inline">Upload Photo</span>
        </button>
    </div>
</div>

        </section>

        <!-- Upload Form (Initially Hidden) -->
        <div id="uploadForm" class="bg-card rounded-2xl shadow-card p-6 mb-8 animate-fadeIn hidden">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-primary-color flex items-center">
                    <i class="fas fa-cloud-upload-alt text-purple-500 mr-2"></i>
                    Upload New Photo
                </h3>
                <button id="closeUploadBtn" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4 flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form action="gallery.php" method="post" enctype="multipart/form-data" class="space-y-6">
                <div class="upload-zone relative" id="dropZone">
                    <input type="file" name="pet_photo" id="pet_photo" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required>
                    <div class="flex flex-col items-center justify-center">
                        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center text-purple-500 mb-4">
                            <i class="fas fa-cloud-upload-alt text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-semibold text-primary-color mb-2">Drag & Drop your photo here</h4>
                        <p class="text-secondary-color mb-4">or click to browse files</p>
                        <p class="text-xs text-gray-500">Supported formats: JPG, JPEG, PNG, GIF (Max: 5MB)</p>
                    </div>
                </div>
                
                <div id="imagePreviewContainer" class="hidden">
                    <label class="block text-secondary-color mb-2">Preview</label>
                    <div class="relative">
                        <img id="imagePreview" src="#" alt="Preview" class="w-full h-64 object-contain bg-gray-50 rounded-lg">
                        <button type="button" id="removePreview" class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-8 h-8 flex items-center justify-center hover:bg-red-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="action-button bg-purple-500 hover:bg-purple-600 text-white shadow-md hover:shadow-lg">
                        <i class="fas fa-upload"></i>
                        <span>Upload Photo</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Photo Gallery -->
        <div class="bg-card rounded-2xl shadow-card p-6 animate-fadeIn animate-delay-200">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-primary-color flex items-center">
                    <i class="fas fa-images text-purple-500 mr-2"></i>
                    Your Pet Photos
                </h3>
                <div class="text-secondary-color">
                    <span class="font-semibold"><?php echo $photos->num_rows; ?></span> photos
                </div>
            </div>
            
            <?php if ($photos->num_rows > 0): ?>
                <div class="masonry-grid" id="photoGallery">
                    <?php 
                    $delay = 0;
                    while ($photo = $photos->fetch_assoc()): 
                        $delay += 0.05;
                    ?>
                        <div class="masonry-item animate-fadeIn" style="animation-delay: <?php echo $delay; ?>s">
                            <div class="photo-card cursor-pointer" onclick="openLightbox('Uploads/<?php echo htmlspecialchars($photo['filename']); ?>')" data-photo-id="<?php echo htmlspecialchars($photo['filename']); ?>">
                                <img src="Uploads/<?php echo htmlspecialchars($photo['filename']); ?>" alt="Pet Photo" class="w-full object-cover">
                                
<div class="photo-overlay">
    <div class="flex justify-between items-center">
        <p class="text-white text-sm">
            <i class="far fa-calendar-alt mr-1"></i>
            <?php echo date('M d, Y', strtotime($photo['upload_date'])); ?>
        </p>
        <div>
            <button class="like-btn w-8 h-8 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-white hover:bg-white/30" data-photo-id="<?php echo htmlspecialchars($photo['filename']); ?>">
                <i class="fas fa-heart"></i>
            </button>
        </div>
    </div>
</div>

                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-16">
                    <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center text-purple-400 mx-auto mb-4">
                        <i class="fas fa-camera-retro text-3xl"></i>
                    </div>
                    <h4 class="text-xl font-semibold text-primary-color mb-2">No photos yet</h4>
                    <p class="text-secondary-color mb-6 max-w-md mx-auto">Upload your first pet photo to start building your gallery</p>
                    <button id="emptyStateUploadBtn" class="action-button bg-purple-500 hover:bg-purple-600 text-white shadow-md hover:shadow-lg animate-pulse">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Upload Your First Photo</span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Lightbox -->
    
<div id="lightbox" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden flex items-center justify-center">
    <button id="closeLightbox" class="absolute top-4 right-4 text-white text-2xl hover:text-gray-300">
        <i class="fas fa-times"></i>
    </button>
    <button id="prevImage" class="absolute left-4 text-white text-4xl hover:text-gray-300">
        <i class="fas fa-chevron-left"></i>
    </button>
    <button id="nextImage" class="absolute right-4 text-white text-4xl hover:text-gray-300">
        <i class="fas fa-chevron-right"></i>
    </button>
    <div class="relative">
        <img id="lightboxImage" src="/placeholder.svg" alt="Enlarged pet photo" class="max-h-[80vh] max-w-[90vw] object-contain">
        <div class="absolute bottom-0 left-0 right-0 p-4 bg-black/50 backdrop-blur-sm flex justify-between items-center">
            <button id="likeImageBtn" class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-white hover:bg-white/30">
                <i class="fas fa-heart"></i>
            </button>
            <button id="deleteImageBtn" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg flex items-center gap-2">
                <i class="fas fa-trash"></i>
                <span>Delete Photo</span>
            </button>
        </div>
    </div>
</div>

    <footer class="bg-white py-6 mt-auto border-t border-gray-100">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center space-x-2 mb-4 md:mb-0">
                    <div class="w-8 h-8 rounded-full bg-purple-500 flex items-center justify-center text-white">
                        <i class="fas fa-paw"></i>
                    </div>
                    <span class="font-bold text-purple-800">PetCare</span>
                </div>
                <div class="text-gray-500 text-sm">
                    <p>© 2025 PetCare. All rights reserved.</p>
                </div>
                <div class="flex space-x-4 mt-4 md:mt-0">
                    <a href="#" class="text-gray-400 hover:text-purple-500 transition-colors">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-purple-500 transition-colors">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-purple-500 transition-colors">
                        <i class="fab fa-instagram"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu functionality
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const closeMobileMenu = document.getElementById('closeMobileMenu');
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
        
        mobileMenuBtn.addEventListener('click', () => {
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
        
        // Upload form toggle
        const uploadBtn = document.getElementById('uploadBtn');
        const emptyStateUploadBtn = document.getElementById('emptyStateUploadBtn');
        const closeUploadBtn = document.getElementById('closeUploadBtn');
        const uploadForm = document.getElementById('uploadForm');
        
        function toggleUploadForm() {
            uploadForm.classList.toggle('hidden');
            if (!uploadForm.classList.contains('hidden')) {
                uploadForm.scrollIntoView({ behavior: 'smooth' });
            }
        }
        
        uploadBtn.addEventListener('click', toggleUploadForm);
        if (emptyStateUploadBtn) {
            emptyStateUploadBtn.addEventListener('click', toggleUploadForm);
        }
        closeUploadBtn.addEventListener('click', toggleUploadForm);
        
        // Image preview functionality
        const fileInput = document.getElementById('pet_photo');
        const imagePreview = document.getElementById('imagePreview');
        const imagePreviewContainer = document.getElementById('imagePreviewContainer');
        const removePreview = document.getElementById('removePreview');
        const dropZone = document.getElementById('dropZone');
        
        fileInput.addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreviewContainer.classList.remove('hidden');
                    dropZone.classList.add('hidden');
                }
                reader.readAsDataURL(file);
            }
        });
        
        removePreview.addEventListener('click', function() {
            imagePreviewContainer.classList.add('hidden');
            dropZone.classList.remove('hidden');
            fileInput.value = '';
        });
        
        // Drag and drop functionality
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropZone.classList.add('dragover');
        }
        
        function unhighlight() {
            dropZone.classList.remove('dragover');
        }
        
        dropZone.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length) {
                fileInput.files = files;
                const event = new Event('change');
                fileInput.dispatchEvent(event);
            }
        }
        
        // Lightbox functionality
        const lightbox = document.getElementById('lightbox');
        const lightboxImage = document.getElementById('lightboxImage');
        const closeLightbox = document.getElementById('closeLightbox');
        const prevImage = document.getElementById('prevImage');
        const nextImage = document.getElementById('nextImage');
        let currentImageIndex = 0;
        let galleryImages = [];
        
        // Collect all gallery images
        function updateGalleryImages() {
            const gallery = document.getElementById('photoGallery');
            if (gallery) {
                const images = gallery.querySelectorAll('.photo-card img');
                galleryImages = Array.from(images).map(img => img.src);
            }
        }
        
        function openLightbox(imageSrc) {
            updateGalleryImages();
            lightboxImage.src = imageSrc;
            currentImageIndex = galleryImages.indexOf(imageSrc);
            lightbox.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        closeLightbox.addEventListener('click', () => {
            lightbox.classList.add('hidden');
            document.body.style.overflow = '';
        });
        
        prevImage.addEventListener('click', () => {
            if (galleryImages.length > 1) {
                currentImageIndex = (currentImageIndex - 1 + galleryImages.length) % galleryImages.length;
                lightboxImage.src = galleryImages[currentImageIndex];
            }
        });
        
        nextImage.addEventListener('click', () => {
            if (galleryImages.length > 1) {
                currentImageIndex = (currentImageIndex + 1) % galleryImages.length;
                lightboxImage.src = galleryImages[currentImageIndex];
            }
        });
        
        
// Like functionality
const likeButtons = document.querySelectorAll('.like-btn');
const likeImageBtn = document.getElementById('likeImageBtn');
const deleteImageBtn = document.getElementById('deleteImageBtn');
const likedPhotos = new Set();

likeButtons.forEach(button => {
    button.addEventListener('click', (e) => {
        e.stopPropagation(); // Prevent opening the lightbox
        const photoId = button.getAttribute('data-photo-id');
        
        if (likedPhotos.has(photoId)) {
            likedPhotos.delete(photoId);
            button.querySelector('i').classList.remove('liked');
        } else {
            likedPhotos.add(photoId);
            button.querySelector('i').classList.add('liked');
        }
    });
});

// Update lightbox like button state
function updateLightboxLikeButton() {
    const currentPhotoSrc = lightboxImage.src;
    const photoId = currentPhotoSrc.split('/').pop();
    
    if (likedPhotos.has(photoId)) {
        likeImageBtn.querySelector('i').classList.add('liked');
    } else {
        likeImageBtn.querySelector('i').classList.remove('liked');
    }
}

// Like button in lightbox
likeImageBtn.addEventListener('click', () => {
    const currentPhotoSrc = lightboxImage.src;
    const photoId = currentPhotoSrc.split('/').pop();
    
    if (likedPhotos.has(photoId)) {
        likedPhotos.delete(photoId);
        likeImageBtn.querySelector('i').classList.remove('liked');
        
        // Also update the gallery like button
        document.querySelector(`.like-btn[data-photo-id="${photoId}"] i`).classList.remove('liked');
    } else {
        likedPhotos.add(photoId);
        likeImageBtn.querySelector('i').classList.add('liked');
        
        // Also update the gallery like button
        document.querySelector(`.like-btn[data-photo-id="${photoId}"] i`).classList.add('liked');
    }
});

// Delete functionality
deleteImageBtn.addEventListener('click', () => {
    if (confirm('Are you sure you want to delete this photo? This action cannot be undone.')) {
        const currentPhotoSrc = lightboxImage.src;
        const photoId = currentPhotoSrc.split('/').pop();
        
        // Send AJAX request to delete the photo
        fetch('delete_photo.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `filename=${photoId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the photo from the gallery
                const photoElement = document.querySelector(`.photo-card[data-photo-id="${photoId}"]`).closest('.masonry-item');
                photoElement.remove();
                
                // Close the lightbox
                closeLightbox.click();
                
                // Show success message
                alert('Photo deleted successfully!');
                
                // Update the photo count
                const photoCountElement = document.querySelector('.text-secondary-color span.font-semibold');
                if (photoCountElement) {
                    const currentCount = parseInt(photoCountElement.textContent);
                    photoCountElement.textContent = currentCount - 1;
                }
                
                // If no photos left, reload the page to show empty state
                if (parseInt(photoCountElement.textContent) === 0) {
                    window.location.reload();
                } else {
                    // Update gallery images and navigate to next image if available
                    updateGalleryImages();
                    if (galleryImages.length > 0) {
                        nextImage.click();
                    } else {
                        closeLightbox.click();
                    }
                }
            } else {
                alert('Error deleting photo: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the photo.');
        });
    }
});

// Update the openLightbox function to also update the like button state
function openLightbox(imageSrc) {
    updateGalleryImages();
    lightboxImage.src = imageSrc;
    currentImageIndex = galleryImages.indexOf(imageSrc);
    lightbox.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Update like button state
    const photoId = imageSrc.split('/').pop();
    if (likedPhotos.has(photoId)) {
        likeImageBtn.querySelector('i').classList.add('liked');
    } else {
        likeImageBtn.querySelector('i').classList.remove('liked');
    }
}
    </script>
</body>
</html>

<?php
$conn->close();
?>
