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

// Fetch pets
$pets = $conn->query("SELECT * FROM pets WHERE user_id = $user_id");

// Handle pet deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_pet'])) {
    $pet_id = $_POST['pet_id'];
    // Delete associated data
    $conn->query("DELETE FROM reminders WHERE pet_id = $pet_id");
    $conn->query("DELETE FROM health_records WHERE pet_id = $pet_id");
    $conn->query("DELETE FROM pet_journal WHERE pet_id = $pet_id");
    // Fetch pet to delete photo
    $stmt = $conn->prepare("SELECT photo FROM pets WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $pet_id, $user_id);
    $stmt->execute();
    $pet = $stmt->get_result()->fetch_assoc();
    if ($pet['photo'] && file_exists($pet['photo']) && strpos($pet['photo'], 'placeholder.com') === false) {
        unlink($pet['photo']);
    }
    // Delete pet
    $stmt = $conn->prepare("DELETE FROM pets WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $pet_id, $user_id);
    $stmt->execute();
    header("Location: my_pets.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Pets - PetCare</title>
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
        
        .badge {
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-dog, .badge-Dog {
            background-color: #e0f2fe;
            color: #0284c7;
        }
        
        .badge-cat, .badge-Cat {
            background-color: #fef3c7;
            color: #d97706;
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
                <h2 class="text-3xl font-bold text-primary-color mb-4">My Pets</h2>
                <p class="text-secondary-color mb-6">Manage your pets below.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if ($pets->num_rows > 0): ?>
                        <?php while ($pet = $pets->fetch_assoc()): ?>
                            <div class="bg-card rounded-xl overflow-hidden shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                                <div class="pet-card h-32 relative">
                                    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-24 h-24 bg-white bg-opacity-30 rounded-full flex items-center justify-center">
                                        <img src="<?php echo $pet['photo'] ? htmlspecialchars($pet['photo']) : 'https://via.placeholder.com/150'; ?>" alt="Pet Photo" class="w-20 h-20 rounded-full object-cover bg-white">
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="text-xl font-bold text-primary-color"><?php echo htmlspecialchars($pet['name']); ?></h4>
                                            <p class="text-secondary-color">
                                                <?php echo htmlspecialchars($pet['species']); ?>
                                                <?php echo $pet['breed'] ? ' (' . htmlspecialchars($pet['breed']) . ')' : ''; ?>
                                                <?php echo $pet['age'] ? ' • ' . $pet['age'] . ' years' : ''; ?>
                                            </p>
                                        </div>
                                        <span class="badge badge-<?php echo strtolower($pet['species']); ?>"><?php echo htmlspecialchars($pet['species']); ?></span>
                                    </div>
                                    <div class="flex justify-center space-x-4 mt-4">
                                        <a href="edit_pet.php?pet_id=<?php echo $pet['id']; ?>" class="bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 rounded transition-colors">Edit</a>
                                        <form action="my_pets.php" method="POST" onsubmit="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($pet['name']); ?>?');">
                                            <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
                                            <button type="submit" name="delete_pet" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded transition-colors">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-secondary-color p-4 text-center">No pets added yet. Add one below!</p>
                    <?php endif; ?>
                </div>
                <!-- Add Pet Button -->
                <div class="mt-6 text-center">
                    <a href="add_pet.php" class="bg-primary-500 hover:bg-primary-600 text-white px-6 py-3 rounded-lg transition-colors">Add a New Pet</a>
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