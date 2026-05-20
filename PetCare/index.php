<?php 
session_start(); 
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PetCare - Virtual Pet Care System</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <script>
    tailwind.config = {
      darkMode: 'class',
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
            'fade-in': 'fadeIn 1s ease-in-out',
            'slide-up': 'slideUp 0.6s ease-out',
            'slide-right': 'slideRight 0.6s ease-out',
            'zoom-in': 'zoomIn 0.6s ease-out',
          },
          keyframes: {
            float: {
              '0%, 100%': { transform: 'translateY(0)' },
              '50%': { transform: 'translateY(-10px)' },
            },
            fadeIn: {
              '0%': { opacity: '0' },
              '100%': { opacity: '1' },
            },
            slideUp: {
              '0%': { transform: 'translateY(20px)', opacity: '0' },
              '100%': { transform: 'translateY(0)', opacity: '1' },
            },
            slideRight: {
              '0%': { transform: 'translateX(-20px)', opacity: '0' },
              '100%': { transform: 'translateX(0)', opacity: '1' },
            },
            zoomIn: {
              '0%': { transform: 'scale(0.95)', opacity: '0' },
              '100%': { transform: 'scale(1)', opacity: '1' },
            },
          },
        }
      }
    }
  </script>
  <style>
    body {
      font-family: 'Outfit', sans-serif;
      scroll-behavior: smooth;
    }
    .hero-bg {
      background-image: linear-gradient(to right, rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.5)), url('https://images.unsplash.com/photo-1534361960057-19889db9621e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
      background-size: cover;
      background-position: center;
      position: relative;
    }
    .hero-bg::before {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 100px;
      background: linear-gradient(to top, var(--bg-gradient-end, white), transparent);
    }
    .feature-card {
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      border-radius: 16px;
      overflow: hidden;
    }
    .feature-card:hover {
      transform: translateY(-12px);
      box-shadow: 0 20px 30px rgba(0, 0, 0, 0.1);
    }
    .feature-card:hover .feature-icon {
      transform: scale(1.1);
    }
    .feature-icon {
      transition: transform 0.4s ease;
    }
    .cta-button {
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      z-index: 1;
    }
    .cta-button::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(255, 255, 255, 0.1);
      transform: scaleX(0);
      transform-origin: right;
      transition: transform 0.4s ease;
      z-index: -1;
    }
    .cta-button:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
    .cta-button:hover::after {
      transform: scaleX(1);
      transform-origin: left;
    }
    .testimonial-card {
      transition: all 0.3s ease;
    }
    .testimonial-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }
    .mobile-menu {
      transition: transform 0.4s cubic-bezier(0.19, 1, 0.22, 1);
    }
    .mobile-menu-hidden {
      transform: translateX(100%);
    }
    .nav-link {
      position: relative;
    }
    .nav-link::after {
      content: '';
      position: absolute;
      bottom: -4px;
      left: 0;
      width: 100%;
      height: 2px;
      background-color: currentColor;
      transform: scaleX(0);
      transition: transform 0.3s ease;
    }
    .nav-link:hover::after {
      transform: scaleX(1);
    }
    .scroll-indicator {
      animation: bounce 2s infinite;
    }
    @keyframes bounce {
      0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
      }
      40% {
        transform: translateY(-20px);
      }
      60% {
        transform: translateY(-10px);
      }
    }
    .testimonial-dots .dot {
      transition: all 0.3s ease;
    }
    .testimonial-dots .dot.active {
      width: 24px;
      background-color: #0ea5e9;
    }
    
    /* Dark mode styles */
    .dark {
      color-scheme: dark;
    }
    
    .dark body {
      background-color: #1e293b;
      color: #f1f5f9;
    }
    
    .dark .bg-white {
      background-color: #1e293b;
    }
    
    .dark .bg-gray-50 {
      background-color: #0f172a;
    }
    
    .dark .text-gray-700, 
    .dark .text-gray-800 {
      color: #f1f5f9;
    }
    
    .dark .text-gray-600 {
      color: #cbd5e1;
    }
    
    .dark .border-gray-100 {
      border-color: #334155;
    }
    
    .dark .shadow-lg {
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -4px rgba(0, 0, 0, 0.2);
    }
    
    .dark .feature-card,
    .dark .testimonial-card {
      background-color: #334155;
      border-color: #475569;
    }
    
    .dark .hero-bg::before {
      --bg-gradient-end: #1e293b;
    }
    
    /* Dark mode toggle button */
    .theme-toggle {
      cursor: pointer;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease;
    }
    
    .theme-toggle:hover {
      background-color: rgba(0, 0, 0, 0.1);
    }
    
    .dark .theme-toggle:hover {
      background-color: rgba(255, 255, 255, 0.1);
    }
    
    @media (max-width: 767px) {
      header {
        background-color: rgba(255, 255, 255, 0.9); /* Ensure header is visible */
        backdrop-filter: blur(5px);
      }
      
      .dark header {
        background-color: rgba(30, 41, 59, 0.9);
      }
      
      #mobile-menu-toggle {
        color: #ffffff; /* White icon for contrast */
        background-color: rgba(0, 0, 0, 0.3); /* Slight background for visibility */
        padding: 8px;
        border-radius: 50%;
      }
      .mobile-menu {
        padding-top: 60px; /* Space for header overlap */
        color: #1e293b; /* Dark text for readability */
      }
      
      .dark .mobile-menu {
        background-color: #1e293b;
        color: #f1f5f9;
      }
      
      .mobile-menu a {
        color: #1e293b; /* Ensure links are readable */
      }
      
      .dark .mobile-menu a {
        color: #f1f5f9;
      }
    }
    
    /* Testimonials continuous scrolling styles */
    .testimonials-container {
      width: 100%;
      overflow: hidden;
      position: relative;
    }
    
    .testimonials-track {
      display: flex;
      animation: scroll 40s linear infinite;
    }
    
    .testimonial-card {
      flex-shrink: 0;
      border-radius: 12px; /* Less rounded for more rectangular appearance */
    }
    
    /* Animation for continuous scrolling */
    @keyframes scroll {
      0% {
        transform: translateX(0);
      }
      100% {
        transform: translateX(calc(-350px * 5 - 6rem)); /* Width of 5 cards plus margins */
      }
    }
    
    /* Pause animation on hover */
    .testimonials-container:hover .testimonials-track {
      animation-play-state: paused;
    }
    .mobile-vet-button {
  background-color: #ffefef;
  color: #d62828;
  padding: 10px 15px;
  border-radius: 10px;
  margin-left: 20px; /* creates space from other buttons */
  border: 2px solid #d62828;
  font-weight: bold;
  box-shadow: 2px 2px 8px rgba(0,0,0,0.1);
  transition: background-color 0.3s, transform 0.2s;
}

.mobile-vet-button:hover {
  background-color: #ffdada;
  transform: scale(1.05);
}

  </style>
</head>
<body class="bg-gray-50">
  <!-- Header -->
  <header class="bg-white shadow-md sticky top-0 z-50 backdrop-blur-md bg-white/90">
    <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
      <!-- Logo -->
      <div class="flex items-center space-x-2">
        <div class="text-3xl font-bold bg-gradient-to-r from-primary-600 to-secondary-600 bg-clip-text text-transparent">
          <i class="fas fa-paw mr-2"></i>PetCare
        </div>
      </div>
      <!-- Desktop Navigation -->
      <div class="hidden md:flex space-x-8 items-center">
      <a href="<?php echo VET_LOCATOR_URL; ?>" class="mobile-vet-button">
  <i class="fas fa-stethoscope"></i> Nearby Veterinary
</a>

        <a href="#features" class="nav-link text-gray-700 hover:text-primary-600 font-medium transition-colors">Features</a>
        <a href="#testimonials" class="nav-link text-gray-700 hover:text-primary-600 font-medium transition-colors">Testimonials</a>
        <a href="#about" class="nav-link text-gray-700 hover:text-primary-600 font-medium transition-colors">About</a>
        <?php
          if (isset($_SESSION['user_id'])) {
            echo '<a href="dashboard.php" class="nav-link text-gray-700 hover:text-primary-600 font-medium transition-colors">Dashboard</a>';
            echo '<a href="logout.php" class="nav-link text-gray-700 hover:text-primary-600 font-medium transition-colors">Log Out</a>';
          } else {
            echo '<a href="login.php" class="nav-link text-gray-700 hover:text-primary-600 font-medium transition-colors">Login</a>';
            echo '<a href="signup.php" class="bg-gradient-to-r from-primary-600 to-secondary-600 text-white px-6 py-3 rounded-full font-semibold shadow-lg hover:shadow-xl cta-button">Sign Up</a>';
          }
        ?>
        <!-- Dark Mode Toggle Button -->
        <button id="theme-toggle" class="theme-toggle" aria-label="Toggle dark mode">
          <i class="fas fa-moon text-gray-700 dark:hidden text-xl"></i>
          <i class="fas fa-sun text-yellow-300 hidden dark:block text-xl"></i>
        </button>
      </div>
      <!-- Mobile Menu Toggle -->
      <div class="md:hidden flex items-center space-x-4">
        <!-- Dark Mode Toggle Button for Mobile -->
        <button id="theme-toggle-mobile" class="theme-toggle" aria-label="Toggle dark mode">
          <i class="fas fa-moon text-gray-700 dark:hidden text-xl"></i>
          <i class="fas fa-sun text-yellow-300 hidden dark:block text-xl"></i>
        </button>
        <button id="mobile-menu-toggle" class="text-gray-700 focus:outline-none" aria-label="Toggle mobile menu">
          <i class="fas fa-bars text-2xl"></i>
        </button>
      </div>
    </nav>
    <!-- Mobile Menu -->
    <div id="mobile-menu" class="mobile-menu mobile-menu-hidden fixed top-0 right-0 h-full w-72 bg-white shadow-2xl md:hidden z-50">
      <div class="flex justify-end p-6">
        <button id="mobile-menu-close" class="text-gray-700 focus:outline-none" aria-label="Close mobile menu">
          <i class="fas fa-times text-2xl"></i>
        </button>
      </div>
      <div class="flex flex-col space-y-6 px-8">
      <a href="<?php echo VET_LOCATOR_URL; ?>" class="mobile-vet-button"><i class="fas fa-stethoscope"></i>Nearby Veterinary</a>
        <a href="#features" class="text-gray-700 hover:text-primary-600 font-medium text-lg">Features</a>
        <a href="#testimonials" class="text-gray-700 hover:text-primary-600 font-medium text-lg">Testimonials</a>
        <a href="#about" class="text-gray-700 hover:text-primary-600 font-medium text-lg">About</a>
        <?php
          if (isset($_SESSION['user_id'])) {
            echo '<a href="dashboard.php" class="text-gray-700 hover:text-primary-600 font-medium text-lg">Dashboard</a>';
            echo '<a href="logout.php" class="text-gray-700 hover:text-primary-600 font-medium text-lg">Log Out</a>';
          } else {
            echo '<a href="login.php" class="text-gray-700 hover:text-primary-600 font-medium text-lg">Login</a>';
            echo '<a href="signup.php" class="bg-gradient-to-r from-primary-600 to-secondary-600 text-white px-6 py-3 rounded-full font-semibold shadow-lg hover:shadow-xl text-center mt-4">Sign Up</a>';
          }
        ?>
      </div>
      <div class="absolute bottom-10 left-0 right-0 px-8">
        <div class="text-center">
          <div class="text-2xl font-bold bg-gradient-to-r from-primary-600 to-secondary-600 bg-clip-text text-transparent mb-2">
            <i class="fas fa-paw mr-2"></i>PetCare
          </div>
          <p class="text-gray-500 text-sm">Your pet's happiness, our priority</p>
        </div>
      </div>
    </div>
  </header>
  <!-- Hero Section -->
  <section class="hero-bg text-white py-32 md:py-48 relative" role="banner">
    <div class="container mx-auto px-6 text-center relative z-10">
      <h1 class="text-5xl md:text-7xl font-bold mb-6 animate-fade-in leading-tight">
        Your Pet's <span class="bg-gradient-to-r from-primary-400 to-secondary-400 bg-clip-text text-transparent">Happiness</span>, Our Priority
      </h1>
      <p class="text-xl md:text-2xl mb-10 max-w-2xl mx-auto font-light animate-slide-up opacity-90">PetCare helps you stay on top of feeding, exercise, and vet visits with personalized reminders and health tracking.</p>
      <div class="flex flex-col sm:flex-row justify-center gap-4 animate-slide-up" style="animation-delay: 0.2s;">
        <a href="signup.php" class="bg-gradient-to-r from-primary-600 to-secondary-600 text-white px-8 py-4 rounded-full font-semibold text-lg cta-button shadow-lg hover:shadow-xl">Start Caring Now</a>
        <a href="#features" class="bg-white/20 backdrop-blur-sm text-white border border-white/30 px-8 py-4 rounded-full font-semibold text-lg hover:bg-white/30 transition-all">Learn More</a>
      </div>
      <?php
        if (isset($_SESSION['user_id'])) {
          echo '<p class="mt-6 text-lg animate-fade-in" style="animation-delay: 0.4s;">Welcome back!</p>';
        }
      ?>
      <div class="absolute bottom-10 left-0 right-0 text-center scroll-indicator">
        <a href="#features" class="text-white/80 hover:text-white transition-colors">
          <i class="fas fa-chevron-down text-2xl"></i>
        </a>
      </div>
    </div>
    <div class="absolute bottom-0 left-0 right-0">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" class="w-full h-auto">
        <path fill="#ffffff" fill-opacity="1" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,224C672,245,768,267,864,250.7C960,235,1056,181,1152,165.3C1248,149,1344,171,1392,181.3L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z" class="dark:fill-[#1e293b]"></path>
      </svg>
    </div>
  </section>

  <!-- Features Section -->
  <section id="features" class="py-24 bg-white">
    <div class="container mx-auto px-6">
      <div class="text-center mb-16">
        <span class="inline-block px-4 py-1 rounded-full bg-primary-100 text-primary-700 font-medium text-sm mb-4">FEATURES</span>
        <h2 class="text-4xl md:text-5xl font-bold mb-6 text-gray-800">What Makes PetCare Special?</h2>
        <p class="text-xl text-gray-600 max-w-2xl mx-auto">Discover how our platform simplifies pet care with these amazing features</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
        <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-gray-100 animate-zoom-in" style="animation-delay: 0.1s;">
          <div class="bg-primary-100 w-16 h-16 rounded-2xl flex items-center justify-center mb-6">
            <i class="fas fa-bell text-3xl text-primary-600 feature-icon" aria-hidden="true"></i>
          </div>
          <h3 class="text-2xl font-semibold mb-4 text-gray-800">Smart Reminders</h3>
          <p class="text-gray-600 leading-relaxed">Never miss a task with customizable reminders for feeding, walks, and medications. Set schedules that work for you and your pet.</p>
          <div class="mt-6">
            <a href="#" class="text-primary-600 font-medium flex items-center hover:text-primary-700 transition-colors">
              Learn more <i class="fas fa-arrow-right ml-2 text-sm"></i>
            </a>
          </div>
        </div>
        <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-gray-100 animate-zoom-in" style="animation-delay: 0.2s;">
          <div class="bg-secondary-100 w-16 h-16 rounded-2xl flex items-center justify-center mb-6">
            <i class="fas fa-paw text-3xl text-secondary-600 feature-icon" aria-hidden="true"></i>
          </div>
          <h3 class="text-2xl font-semibold mb-4 text-gray-800">Pet Profiles</h3>
          <p class="text-gray-600 leading-relaxed">Store your pet's details, including diet, medical history, and special needs. Keep all important information in one secure place.</p>
          <div class="mt-6">
            <a href="#" class="text-primary-600 font-medium flex items-center hover:text-primary-700 transition-colors">
              Learn more <i class="fas fa-arrow-right ml-2 text-sm"></i>
            </a>
          </div>
        </div>
        <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-gray-100 animate-zoom-in" style="animation-delay: 0.3s;">
          <div class="bg-primary-100 w-16 h-16 rounded-2xl flex items-center justify-center mb-6">
            <i class="fas fa-heartbeat text-3xl text-primary-600 feature-icon" aria-hidden="true"></i>
          </div>
          <h3 class="text-2xl font-semibold mb-4 text-gray-800">Health Insights</h3>
          <p class="text-gray-600 leading-relaxed">Track vaccinations and vet visits to keep your pet in top shape. Get insights and analytics on your pet's health over time.</p>
          <div class="mt-6">
            <a href="#" class="text-primary-600 font-medium flex items-center hover:text-primary-700 transition-colors">
              Learn more <i class="fas fa-arrow-right ml-2 text-sm"></i>
            </a>
          </div>
        </div>
      </div>
      <div class="mt-20 text-center">
        <a href="signup.php" class="inline-block bg-gradient-to-r from-primary-600 to-secondary-600 text-white px-8 py-4 rounded-full font-semibold text-lg cta-button shadow-lg hover:shadow-xl">
          Try All Features
        </a>
      </div>
    </div>
  </section>

  <!-- App Preview Section -->
  <section class="py-24 bg-gray-50">
    <div class="container mx-auto px-6">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
        <div class="order-2 lg:order-1 animate-slide-right">
          <span class="inline-block px-4 py-1 rounded-full bg-secondary-100 text-secondary-700 font-medium text-sm mb-4">EASY TO USE</span>
          <h2 class="text-4xl font-bold mb-6 text-gray-800">Manage Your Pet's Care With Ease</h2>
          <p class="text-xl text-gray-600 mb-8">Our intuitive interface makes it simple to keep track of everything your pet needs, from daily care to medical appointments.</p>
          <ul class="space-y-4">
            <li class="flex items-start">
              <div class="bg-primary-100 p-1 rounded-full mr-3 mt-1">
                <i class="fas fa-check text-primary-600"></i>
              </div>
              <span class="text-gray-700">Intuitive dashboard for all your pets</span>
            </li>
            <li class="flex items-start">
              <div class="bg-primary-100 p-1 rounded-full mr-3 mt-1">
                <i class="fas fa-check text-primary-600"></i>
              </div>
              <span class="text-gray-700">Customizable care schedules</span>
            </li>
            <li class="flex items-start">
              <div class="bg-primary-100 p-1 rounded-full mr-3 mt-1">
                <i class="fas fa-check text-primary-600"></i>
              </div>
              <span class="text-gray-700">Health records at your fingertips</span>
            </li>
            <li class="flex items-start">
              <div class="bg-primary-100 p-1 rounded-full mr-3 mt-1">
                <i class="fas fa-check text-primary-600"></i>
              </div>
              <span class="text-gray-700">Medication tracking and reminders</span>
            </li>
          </ul>
          <div class="mt-10">
            <a href="signup.php" class="inline-block bg-gradient-to-r from-primary-600 to-secondary-600 text-white px-8 py-4 rounded-full font-semibold text-lg cta-button shadow-lg hover:shadow-xl">
              Get Started Free
            </a>
          </div>
        </div>
        <div class="order-1 lg:order-2 flex justify-center">
          <div class="relative">
            <div class="absolute -top-6 -left-6 w-32 h-32 bg-primary-100 rounded-full opacity-70 animate-float"></div>
            <div class="absolute -bottom-8 -right-8 w-40 h-40 bg-secondary-100 rounded-full opacity-70 animate-float" style="animation-delay: 1s;"></div>
            <img src="https://images.unsplash.com/photo-1583337130417-3346a1be7dee?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Pet care app preview" class="relative z-10 rounded-3xl shadow-2xl border-8 border-white dark:border-gray-700 max-w-full h-auto" />
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Testimonials Section - Modified for continuous scrolling -->
  <section id="testimonials" class="py-24 bg-white">
    <div class="container mx-auto px-6">
      <div class="text-center mb-16">
        <span class="inline-block px-4 py-1 rounded-full bg-secondary-100 text-secondary-700 font-medium text-sm mb-4">TESTIMONIALS</span>
        <h2 class="text-4xl md:text-5xl font-bold mb-6 text-gray-800">What Pet Owners Say</h2>
        <p class="text-xl text-gray-600 max-w-2xl mx-auto">Join thousands of happy pet parents who trust PetCare</p>
      </div>
      <div class="testimonials-container relative overflow-hidden">
        <div class="testimonials-track flex">
          <!-- Original 3 testimonials -->
          <div class="testimonial-card min-w-[350px] mx-3 p-8 bg-white rounded-2xl shadow-lg border border-gray-100">
            <div class="flex items-center mb-6">
              <div class="text-amber-400 flex">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
              <span class="ml-2 text-gray-600 font-medium">5.0</span>
            </div>
            <p class="text-gray-700 text-xl italic mb-6">"PetCare has been a lifesaver! I never forget my dog's meds anymore, and the health tracking feature gives me peace of mind knowing I'm taking good care of my furry friend."</p>
            <div class="flex items-center">
              <img src="https://randomuser.me/api/portraits/women/45.jpg" alt="Sarah M." class="w-14 h-14 rounded-full mr-4 border-2 border-primary-200" />
              <div>
                <p class="font-semibold text-gray-800">Sarah M.</p>
                <p class="text-gray-500">Dog Owner</p>
              </div>
            </div>
          </div>
          <div class="testimonial-card min-w-[350px] mx-3 p-8 bg-white rounded-2xl shadow-lg border border-gray-100">
            <div class="flex items-center mb-6">
              <div class="text-amber-400 flex">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
              <span class="ml-2 text-gray-600 font-medium">5.0</span>
            </div>
            <p class="text-gray-700 text-xl italic mb-6">"The reminders and health tracker make caring for my cat so easy! I love how I can set custom schedules for feeding and medication. The interface is beautiful and intuitive."</p>
            <div class="flex items-center">
              <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Rohit K." class="w-14 h-14 rounded-full mr-4 border-2 border-primary-200" />
              <div>
                <p class="font-semibold text-gray-800">Rohit K.</p>
                <p class="text-gray-500">Cat Owner</p>
              </div>
            </div>
          </div>
          <div class="testimonial-card min-w-[350px] mx-3 p-8 bg-white rounded-2xl shadow-lg border border-gray-100">
            <div class="flex items-center mb-6">
              <div class="text-amber-400 flex">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star-half-alt"></i>
              </div>
              <span class="ml-2 text-gray-600 font-medium">4.5</span>
            </div>
            <p class="text-gray-700 text-xl italic mb-6">"I love how I can manage all my pets in one place. With three dogs and a cat, keeping track of everything was a nightmare before PetCare. Now it's all organized and I get timely reminders."</p>
            <div class="flex items-center">
              <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Emma R." class="w-14 h-14 rounded-full mr-4 border-2 border-primary-200" />
              <div>
                <p class="font-semibold text-gray-800">Emma R.</p>
                <p class="text-gray-500">Multi-Pet Owner</p>
              </div>
            </div>
          </div>
          <!-- Additional 2 testimonials to make 5 total -->
          <div class="testimonial-card min-w-[350px] mx-3 p-8 bg-white rounded-2xl shadow-lg border border-gray-100">
            <div class="flex items-center mb-6">
              <div class="text-amber-400 flex">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
              <span class="ml-2 text-gray-600 font-medium">5.0</span>
            </div>
            <p class="text-gray-700 text-xl italic mb-6">"As a first-time pet owner, PetCare has been invaluable. The app guides me through everything I need to know about caring for my rabbit, from diet to exercise needs."</p>
            <div class="flex items-center">
              <img src="https://randomuser.me/api/portraits/men/54.jpg" alt="James L." class="w-14 h-14 rounded-full mr-4 border-2 border-primary-200" />
              <div>
                <p class="font-semibold text-gray-800">James L.</p>
                <p class="text-gray-500">Rabbit Owner</p>
              </div>
            </div>
          </div>
          <div class="testimonial-card min-w-[350px] mx-3 p-8 bg-white rounded-2xl shadow-lg border border-gray-100">
            <div class="flex items-center mb-6">
              <div class="text-amber-400 flex">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star-half-alt"></i>
              </div>
              <span class="ml-2 text-gray-600 font-medium">4.5</span>
            </div>
            <p class="text-gray-700 text-xl italic mb-6">"The specialized care options for exotic pets is what sold me on PetCare. My parakeets have specific needs, and this app helps me stay on top of everything with ease."</p>
            <div class="flex items-center">
              <img src="https://randomuser.me/api/portraits/women/33.jpg" alt="Aisha T." class="w-14 h-14 rounded-full mr-4 border-2 border-primary-200" />
              <div>
                <p class="font-semibold text-gray-800">Aisha T.</p>
                <p class="text-gray-500">Bird Owner</p>
              </div>
            </div>
          </div>
          <!-- Duplicate testimonials for seamless scrolling -->
          <div class="testimonial-card min-w-[350px] mx-3 p-8 bg-white rounded-2xl shadow-lg border border-gray-100">
            <div class="flex items-center mb-6">
              <div class="text-amber-400 flex">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
              <span class="ml-2 text-gray-600 font-medium">5.0</span>
            </div>
            <p class="text-gray-700 text-xl italic mb-6">"PetCare has been a lifesaver! I never forget my dog's meds anymore, and the health tracking feature gives me peace of mind knowing I'm taking good care of my furry friend."</p>
            <div class="flex items-center">
              <img src="https://randomuser.me/api/portraits/women/45.jpg" alt="Sarah M." class="w-14 h-14 rounded-full mr-4 border-2 border-primary-200" />
              <div>
                <p class="font-semibold text-gray-800">Sarah M.</p>
                <p class="text-gray-500">Dog Owner</p>
              </div>
            </div>
          </div>
          <div class="testimonial-card min-w-[350px] mx-3 p-8 bg-white rounded-2xl shadow-lg border border-gray-100">
            <div class="flex items-center mb-6">
              <div class="text-amber-400 flex">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
              </div>
              <span class="ml-2 text-gray-600 font-medium">5.0</span>
            </div>
            <p class="text-gray-700 text-xl italic mb-6">"The reminders and health tracker make caring for my cat so easy! I love how I can set custom schedules for feeding and medication. The interface is beautiful and intuitive."</p>
            <div class="flex items-center">
              <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Rohit K." class="w-14 h-14 rounded-full mr-4 border-2 border-primary-200" />
              <div>
                <p class="font-semibold text-gray-800">Rohit K.</p>
                <p class="text-gray-500">Cat Owner</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- About Section -->
  <section id="about" class="py-24 bg-gray-50">
    <div class="container mx-auto px-6">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
        <div>
          <span class="inline-block px-4 py-1 rounded-full bg-primary-100 text-primary-700 font-medium text-sm mb-4">ABOUT US</span>
          <h2 class="text-4xl font-bold mb-6 text-gray-800">About PetCare</h2>
          <p class="text-xl text-gray-600 mb-6">PetCare is designed to simplify pet parenting. Whether you have a dog, cat, or any furry friend, our app ensures you never miss a moment of care with tailored reminders and health tracking tools.</p>
          <p class="text-gray-600 mb-8">Founded by pet lovers, we understand the challenges of modern pet ownership. Our mission is to help you provide the best care possible for your beloved companions through technology that's both powerful and easy to use.</p>
          <div class="flex flex-wrap gap-6">
            <div class="flex items-center">
              <div class="bg-primary-100 w-12 h-12 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-users text-primary-600"></i>
              </div>
              <div>
                <p class="font-semibold text-gray-800">10,000+</p>
                <p class="text-gray-600">Happy Users</p>
              </div>
            </div>
            <div class="flex items-center">
              <div class="bg-secondary-100 w-12 h-12 rounded-full flex items-center justify-center mr-4">
                <i class="fas fa-paw text-secondary-600"></i>
              </div>
              <div>
                <p class="font-semibold text-gray-800">25,000+</p>
                <p class="text-gray-600">Pets Cared For</p>
              </div>
            </div>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-6">
          <img src="https://images.unsplash.com/photo-1548199973-03cce0bbc87b?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Dog with owner" class="rounded-2xl shadow-lg transform rotate-2 animate-float" />
          <img src="https://images.unsplash.com/photo-1543852786-1cf6624b9987?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Cat being petted" class="rounded-2xl shadow-lg transform -rotate-2 mt-8 animate-float" style="animation-delay: 0.5s;" />
          <img src="https://images.unsplash.com/photo-1560743641-3914f2c45636?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Veterinarian with pet" class="rounded-2xl shadow-lg transform -rotate-1 animate-float" style="animation-delay: 1s;" />
          <img src="https://images.unsplash.com/photo-1601758124510-52d02ddb7cbd?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Pet playing outdoors" class="rounded-2xl shadow-lg transform rotate-1 mt-8 animate-float" style="animation-delay: 1.5s;" />
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="py-24 bg-gradient-to-r from-primary-600 to-secondary-600 text-white text-center relative overflow-hidden">
    <div class="container mx-auto px-6 relative z-10">
      <h2 class="text-4xl md:text-5xl font-bold mb-6">Join the PetCare Family</h2>
      <p class="text-xl mb-10 max-w-2xl mx-auto opacity-90">Sign up today and take the stress out of pet care with our easy-to-use platform. Your pets will thank you!</p>
      <div class="flex flex-col sm:flex-row justify-center gap-4">
        <a href="signup.php" class="bg-white text-primary-600 px-8 py-4 rounded-full font-semibold text-lg cta-button shadow-lg hover:shadow-xl">Get Started Free</a>
        <a href="#features" class="bg-transparent border-2 border-white/50 text-white px-8 py-4 rounded-full font-semibold text-lg hover:bg-white/10 transition-all">Learn More</a>
      </div>
      <div class="mt-12 flex justify-center space-x-8">
        <div class="flex items-center">
          <i class="fas fa-check-circle text-2xl mr-2"></i>
          <span>Free 14-day trial</span>
        </div>
        <div class="flex items-center">
          <i class="fas fa-check-circle text-2xl mr-2"></i>
          <span>No credit card required</span>
        </div>
        <div class="flex items-center">
          <i class="fas fa-check-circle text-2xl mr-2"></i>
          <span>Cancel anytime</span>
        </div>
      </div>
    </div>
    <!-- Decorative elements -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden opacity-10">
      <i class="fas fa-paw absolute text-6xl top-1/4 left-1/4 animate-float"></i>
      <i class="fas fa-bone absolute text-5xl top-3/4 left-1/3 animate-float" style="animation-delay: 0.5s;"></i>
      <i class="fas fa-fish absolute text-7xl top-1/3 right-1/4 animate-float" style="animation-delay: 1s;"></i>
      <i class="fas fa-cat absolute text-6xl bottom-1/4 right-1/3 animate-float" style="animation-delay: 1.5s;"></i>
      <i class="fas fa-dog absolute text-5xl bottom-1/3 left-1/5 animate-float" style="animation-delay: 2s;"></i>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-gray-900 text-white py-16">
    <div class="container mx-auto px-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
        <div class="col-span-1 md:col-span-1">
          <div class="text-3xl font-bold bg-gradient-to-r from-primary-400 to-secondary-400 bg-clip-text text-transparent mb-4">
            <i class="fas fa-paw mr-2"></i>PetCare
          </div>
          <p class="text-gray-400 mb-6">Your trusted partner in pet care management.</p>
          <div class="flex space-x-4">
            <a href="#" class="bg-gray-800 hover:bg-primary-600 w-10 h-10 rounded-full flex items-center justify-center transition-colors" aria-label="Facebook">
              <i class="fab fa-facebook-f"></i>
            </a>
            <a href="#" class="bg-gray-800 hover:bg-primary-600 w-10 h-10 rounded-full flex items-center justify-center transition-colors" aria-label="Twitter">
              <i class="fab fa-twitter"></i>
            </a>
            <a href="#" class="bg-gray-800 hover:bg-primary-600 w-10 h-10 rounded-full flex items-center justify-center transition-colors" aria-label="Instagram">
              <i class="fab fa-instagram"></i>
            </a>
          </div>
        </div>
        <div>
          <h3 class="text-xl font-semibold mb-6">Quick Links</h3>
          <ul class="space-y-3">
            <li><a href="#features" class="text-gray-400 hover:text-white transition-colors">Features</a></li>
            <li><a href="#testimonials" class="text-gray-400 hover:text-white transition-colors">Testimonials</a></li>
            <li><a href="#about" class="text-gray-400 hover:text-white transition-colors">About</a></li>
            <li><a href="login.php" class="text-gray-400 hover:text-white transition-colors">Login</a></li>
            <li><a href="signup.php" class="text-gray-400 hover:text-white transition-colors">Sign Up</a></li>
          </ul>
        </div>
        <div>
          <h3 class="text-xl font-semibold mb-6">Resources</h3>
          <ul class="space-y-3">
            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Pet Care Tips</a></li>
            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Blog</a></li>
            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">FAQs</a></li>
            <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Support</a></li>
          </ul>
        </div>
        <div>
          <h3 class="text-xl font-semibold mb-6">Stay Connected</h3>
          <p class="text-gray-400 mb-4">Subscribe to our newsletter for tips and updates.</p>
          <form action="subscribe.php" method="POST" class="flex flex-col space-y-3">
            <input type="email" name="email" placeholder="Enter your email" class="w-full px-4 py-3 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-primary-500" aria-label="Email for newsletter" required>
            <button type="submit" class="bg-gradient-to-r from-primary-600 to-secondary-600 text-white px-4 py-3 rounded-lg hover:opacity-90 transition-opacity">Subscribe</button>
          </form>
          <?php
            if (isset($_SESSION['subscribe_message'])) {
              echo '<p class="mt-3 ' . (strpos($_SESSION['subscribe_message'], 'success') !== false ? 'text-green-400' : 'text-red-400') . '">' . htmlspecialchars($_SESSION['subscribe_message']) . '</p>';
              unset($_SESSION['subscribe_message']); // Clear message after display
            }
          ?>
        </div>
      </div>
      <div class="mt-12 pt-8 border-t border-gray-800 text-center">
        <p class="text-gray-400">© 2025 PetCare. All rights reserved.</p>
        <div class="mt-4 flex justify-center space-x-6">
          <a href="#" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a>
          <a href="#" class="text-gray-400 hover:text-white transition-colors">Terms of Service</a>
          <a href="#" class="text-gray-400 hover:text-white transition-colors">Contact Us</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- JavaScript for Interactivity -->
  <script>
    // Dark Mode Toggle
    const htmlElement = document.documentElement;
    const themeToggle = document.getElementById('theme-toggle');
    const themeToggleMobile = document.getElementById('theme-toggle-mobile');
    
    // Check for saved theme preference or use system preference
    const savedTheme = localStorage.getItem('theme');
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    // Set initial theme
    if (savedTheme === 'dark' || (!savedTheme && systemPrefersDark)) {
      htmlElement.classList.add('dark');
    }
    
    // Function to toggle theme
    function toggleTheme() {
      if (htmlElement.classList.contains('dark')) {
        htmlElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
      } else {
        htmlElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
      }
    }
    
    // Add event listeners to toggle buttons
    themeToggle.addEventListener('click', toggleTheme);
    themeToggleMobile.addEventListener('click', toggleTheme);
    
    // Smooth Scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          window.scrollTo({
            top: target.offsetTop - 80,
            behavior: 'smooth'
          });
        }
      });
    });

    // Mobile Menu Toggle
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenuClose = document.getElementById('mobile-menu-close');
    const mobileMenu = document.getElementById('mobile-menu');

    mobileMenuToggle.addEventListener('click', () => {
      mobileMenu.classList.remove('mobile-menu-hidden');
      document.body.style.overflow = 'hidden'; // Prevent scrolling when menu is open
    });

    mobileMenuClose.addEventListener('click', () => {
      mobileMenu.classList.add('mobile-menu-hidden');
      document.body.style.overflow = ''; // Re-enable scrolling
    });

    // Close mobile menu when clicking a link
    mobileMenu.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        mobileMenu.classList.add('mobile-menu-hidden');
        document.body.style.overflow = ''; // Re-enable scrolling
      });
    });

    // Testimonials continuous scrolling
    document.addEventListener("DOMContentLoaded", () => {
      // Get the testimonials container
      const testimonialsContainer = document.querySelector(".testimonials-container");
      const testimonialsTrack = document.querySelector(".testimonials-track");

      // Function to reset the animation when it completes
      function checkPosition() {
        // Get the current position
        const currentPosition = testimonialsTrack.getBoundingClientRect().left;

        // If we've scrolled far enough, reset to the beginning
        if (currentPosition < -2100) {
          // Approximate width of 5 cards plus margins
          // Temporarily disable animation
          testimonialsTrack.style.animation = "none";
          // Reset position
          testimonialsTrack.style.transform = "translateX(0)";
          // Force reflow
          void testimonialsTrack.offsetWidth;
          // Re-enable animation
          testimonialsTrack.style.animation = "scroll 40s linear infinite";
        }

        // Continue checking
        requestAnimationFrame(checkPosition);
      }

      // Start checking position
      requestAnimationFrame(checkPosition);

      // Pause animation on hover
      testimonialsContainer.addEventListener("mouseenter", () => {
        testimonialsTrack.style.animationPlayState = "paused";
      });

      testimonialsContainer.addEventListener("mouseleave", () => {
        testimonialsTrack.style.animationPlayState = "running";
      });
    });

    // Intersection Observer for animations
    const animatedElements = document.querySelectorAll('.animate-fade-in, .animate-slide-up, .animate-slide-right, .animate-zoom-in');
    
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0) translateX(0) scale(1)';
        }
      });
    }, { threshold: 0.1 });

    animatedElements.forEach(element => {
      element.style.opacity = '0';
      observer.observe(element);
    });
  </script>
</body>
</html>