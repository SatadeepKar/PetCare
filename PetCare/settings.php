<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$conn = get_db();

$user_id = $_SESSION['user_id'];
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_settings'])) {
    $new_username = trim($_POST['username']);
    $new_password = $_POST['password'];
    $new_email = trim($_POST['email']);

    // Validate username
    if (empty($new_username)) {
        $error = "Username cannot be empty.";
    } else {
        // Check if username is taken
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $new_username, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Username is already taken.";
        } else {
            // Check if email is taken
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $new_email, $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = "Email is already taken.";
            } else {
                // Update username, email, and password (if provided)
                if (!empty($new_password)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $new_username, $new_email, $hashed_password, $user_id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $new_username, $new_email, $user_id);
                }
                if ($stmt->execute()) {
                    $success = "Settings updated successfully.";
                    $username = $new_username; // Update displayed username
                } else {
                    $error = "Failed to update settings. Please try again.";
                }
            }
        }
    }
}

$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$username = $user['username'];
$email = $user['email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - PetCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
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
    </style>
</head>
<body class="font-sans">
    <div class="min-h-screen">
        <!-- Header/Navigation -->
        <header class="header-gradient shadow-md sticky top-0 z-10">
            <div class="container mx-auto px-4 py-3">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <img src="Uploads/logo.jpg" alt="PetCare Logo" class="w-12 h-12 rounded-full object-cover bg-white p-1 shadow-md" onerror="this.src='https://via.placeholder.com/48';">
                        <h1 class="text-xl font-bold">PetCare</h1>
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
                        <button class="md:hidden text-white" onclick="toggleMobileMenu()">
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Mobile Menu -->
                <nav id="mobileMenu" class="hidden md:hidden bg-primary-600 p-4 mt-2 rounded-lg">
                    <a href="dashboard.php" class="block text-white hover:text-primary-200 py-2">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>
                    <a href="logout.php" class="block text-white hover:text-primary-200 py-2">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </nav>
            </div>
        </header>
        
        <main class="container mx-auto px-4 py-8">
            <section class="mb-12">
                <h2 class="text-3xl font-bold text-primary-color mb-4">Settings</h2>
                <p class="text-secondary-color mb-6">Update your account settings below.</p>
                <div class="bg-card p-6 rounded-lg shadow-md border border-gray-200">
                    <?php if (!empty($error)): ?>
                        <p class="text-red-600 mb-4"><?php echo htmlspecialchars($error); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($success)): ?>
                        <p class="text-green-600 mb-4"><?php echo htmlspecialchars($success); ?></p>
                    <?php endif; ?>
                    <form action="settings.php" method="POST" class="space-y-4">
                        <div>
                            <label for="username" class="block text-primary-color">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required class="w-full p-2 border rounded border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        </div>
                        <div>
                            <label for="email" class="block text-primary-color">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required class="w-full p-2 border rounded border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        </div>
                        <div>
                            <label for="password" class="block text-primary-color">New Password (leave blank to keep current)</label>
                            <input type="password" id="password" name="password" class="w-full p-2 border rounded border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                        </div>
                        <div class="flex justify-end space-x-2">
                            <a href="dashboard.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition-colors">Cancel</a>
                            <button type="submit" name="update_settings" class="bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 rounded transition-colors">Save Changes</button>
                        </div>
                    </form>
                </div>
            </section>
        </main>
    </div>
    
    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>