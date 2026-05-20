<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up - PetCare</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: {
              50: '#f0f9ff',
              100: '#e0f2fe',
              200: '#bae6fd',
              300: '#7dd3fc',
              400: '#38bdf8',
              500: '#0ea5e9',
              600: '#0284c7',
              700: '#0369a1',
              800: '#075985',
              900: '#0c4a6e',
            },
            secondary: {
              50: '#fdf4ff',
              100: '#fae8ff',
              200: '#f5d0fe',
              300: '#f0abfc',
              400: '#e879f9',
              500: '#d946ef',
              600: '#c026d3',
              700: '#a21caf',
              800: '#86198f',
              900: '#701a75',
            },
          },
          fontFamily: {
            sans: ['Outfit', 'sans-serif'],
          },
          animation: {
            'float': 'float 3s ease-in-out infinite',
            'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
          },
          keyframes: {
            float: {
              '0%, 100%': { transform: 'translateY(0) rotate(0deg)' },
              '50%': { transform: 'translateY(-20px) rotate(10deg)' },
            }
          },
        }
      }
    }
  </script>
  <style>
    body {
      font-family: 'Outfit', sans-serif;
    }
    .signup-bg {
      background-image: linear-gradient(to right, rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.5)), url('https://images.unsplash.com/photo-1548199973-03cce0bbc87b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
      background-size: cover;
      background-position: center;
      position: relative;
    }
    .signup-card {
      backdrop-filter: blur(16px);
      background-color: rgba(255, 255, 255, 0.9);
      border: 1px solid rgba(255, 255, 255, 0.2);
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .signup-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.3);
    }
    .floating-paws {
      position: absolute;
      width: 100%;
      height: 100%;
      overflow: hidden;
      z-index: 0;
    }
    .paw {
      position: absolute;
      color: rgba(255, 255, 255, 0.1);
      animation: float 6s infinite;
      opacity: 0.5;
    }
  </style>
</head>
<body class="bg-gray-50">
  <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
      $username = $_POST['username'];
      $email = $_POST['email'];
      $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password

      // Database connection
      require_once 'config.php';
      $conn = get_db();

      // Check for existing email or username
      $check_sql = "SELECT * FROM users WHERE email = ? OR username = ?";
      $check_stmt = $conn->prepare($check_sql);
      $check_stmt->bind_param("ss", $email, $username);
      $check_stmt->execute();
      $result = $check_stmt->get_result();

      if ($result->num_rows > 0) {
        $error_message = "Error: Username or email already exists.";
      } else {
        // Insert user
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $email, $password);

        if ($stmt->execute()) {
          echo "<p class='text-green-600 text-center mt-4'>Account created successfully! <a href='login.php' class='underline'>Log in</a></p>";
        } else {
          $error_message = "Error: Account creation failed.";
        }
        $stmt->close();
      }
      $check_stmt->close();
      $conn->close();
    }
  ?>

  <!-- Sign Up Section -->
  <section class="signup-bg min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative">
    <!-- Floating Paw Decorations -->
    <div class="floating-paws">
      <i class="fas fa-paw paw text-4xl" style="top: 15%; left: 10%; animation-delay: 0s;"></i>
      <i class="fas fa-paw paw text-5xl" style="top: 25%; left: 85%; animation-delay: 1s;"></i>
      <i class="fas fa-paw paw text-3xl" style="top: 60%; left: 15%; animation-delay: 2s;"></i>
      <i class="fas fa-paw paw text-6xl" style="top: 70%; left: 80%; animation-delay: 3s;"></i>
      <i class="fas fa-paw paw text-4xl" style="top: 85%; left: 30%; animation-delay: 4s;"></i>
      <i class="fas fa-paw paw text-5xl" style="top: 10%; left: 60%; animation-delay: 5s;"></i>
    </div>

    <div class="max-w-md w-full space-y-8 signup-card p-10 rounded-2xl relative z-10">
      <div class="text-center">
        <div class="text-3xl font-bold bg-gradient-to-r from-primary-600 to-secondary-600 bg-clip-text text-transparent mb-4">
          <i class="fas fa-paw mr-2"></i>PetCare
        </div>
        <h2 class="text-3xl font-extrabold text-gray-900 mb-2">Create Your Account</h2>
        <p class="text-gray-600">Join PetCare to start caring for your pet</p>
        <?php if (isset($error_message)): ?>
          <div class="mt-4 p-3 bg-red-100 text-red-700 rounded-lg">
            <?php echo $error_message; ?>
          </div>
        <?php endif; ?>
      </div>
      <form method="POST" action="signup.php" class="mt-8 space-y-6">
        <div class="rounded-md shadow-sm space-y-4">
          <div>
            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-user text-gray-400"></i>
              </div>
              <input type="text" name="username" id="username" required class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="Your username">
            </div>
          </div>
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-envelope text-gray-400"></i>
              </div>
              <input type="email" name="email" id="email" required class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="you@example.com">
            </div>
          </div>
          <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-lock text-gray-400"></i>
              </div>
              <input type="password" name="password" id="password" required class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors" placeholder="••••••••">
            </div>
          </div>
        </div>
        <div>
          <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent rounded-lg text-white bg-gradient-to-r from-primary-600 to-secondary-600 hover:from-primary-700 hover:to-secondary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 font-medium shadow-lg hover:shadow-xl transition-all">
            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
              <i class="fas fa-user-plus text-primary-300 group-hover:text-primary-200"></i>
            </span>
            Sign Up
          </button>
        </div>
      </form>
      <div class="mt-6 text-center">
        <p class="text-sm text-gray-600">
          Already have an account?
          <a href="login.php" class="font-medium text-primary-600 hover:text-primary-500">
            Sign in now
          </a>
        </p>
      </div>
    </div>
  </section>
</body>
</html>