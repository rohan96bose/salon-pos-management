<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login/Register – Salon Bliss</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); }
    .form-container { box-shadow: 0 10px 30px rgba(0,0,0,0.1); transition: all 0.3s ease; }
    .form-container:hover { box-shadow: 0 15px 35px rgba(0,0,0,0.15); }
    .input-field:focus { box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2); }
    .btn-primary { transition: all 0.3s ease; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4); }
    .btn-secondary { transition: all 0.3s ease; background: linear-gradient(135deg, #10b981 0%, #34d399 100%); }
    .btn-secondary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4); }
    .tab-active { position: relative; }
    .tab-active::after {
      content: '';
      position: absolute;
      bottom: -2px;
      left: 0;
      width: 100%;
      height: 3px;
      background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
      border-radius: 3px;
    }
  </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">

  <div class="form-container bg-white rounded-2xl p-8 w-full max-w-md">
    <div class="text-center mb-8">
      <div class="flex justify-center mb-3">
        <div class="bg-purple-100 p-3 rounded-full">
          <i class="fas fa-cut text-purple-600 text-2xl"></i>
        </div>
      </div>
      <h1 class="text-2xl font-bold text-gray-800">Salon Bliss</h1>
      <p class="text-gray-500 mt-1">Manage your salon with elegance</p>
    </div>

    <div class="flex justify-around mb-8 bg-gray-100 p-1 rounded-full">
      <button id="loginToggle" class="tab-active py-2 px-6 rounded-full font-medium text-purple-600 focus:outline-none transition-all" type="button">Login</button>
      <button id="registerToggle" class="py-2 px-6 rounded-full font-medium text-gray-600 hover:text-purple-600 focus:outline-none transition-all" type="button">Register</button>
    </div>

    <!-- Login Form -->
    <form id="loginForm" class="" method="POST" novalidate>
      <input type="hidden" name="action" value="login" />
      <div class="mb-5">
        <label for="loginEmail" class="block text-gray-700 mb-2 font-medium">Email</label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <i class="fas fa-envelope text-gray-400"></i>
          </div>
          <input id="loginEmail" name="email" type="email" placeholder="Enter email"
                 class="input-field w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none" required />
        </div>
      </div>

      <div class="mb-6">
        <label for="loginPassword" class="block text-gray-700 mb-2 font-medium">Password</label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <i class="fas fa-lock text-gray-400"></i>
          </div>
          <input id="loginPassword" name="password" type="password" placeholder="Enter password"
                 class="input-field w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:outline-none" required />
          <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center" aria-label="Toggle password visibility" tabindex="-1">
            <i class="fas fa-eye-slash text-gray-400 hover:text-gray-600"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-primary w-full text-white py-3 rounded-lg font-medium mb-4">
        Sign In <i class="fas fa-arrow-right ml-2"></i>
      </button>

      <div class="text-center text-sm text-gray-500">
        Don't have an account?
        <button type="button" id="switchToRegister" class="text-purple-600 font-medium hover:underline">Sign up</button>
      </div>

      <div id="loginMessage" class="mt-4 text-center text-sm"></div>
    </form>

    <!-- Register Form (Hidden by default) -->
    <form id="registerForm" class="hidden" method="POST" novalidate>
      <input type="hidden" name="action" value="register" />
      <div class="mb-5">
        <label for="registerName" class="block text-gray-700 mb-2 font-medium">Full Name</label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <i class="fas fa-user-tie text-gray-400"></i>
          </div>
          <input id="registerName" name="name" type="text" placeholder="Enter full name"
                 class="input-field w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none" required />
        </div>
      </div>

      <div class="mb-5">
        <label for="registerEmail" class="block text-gray-700 mb-2 font-medium">Email</label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <i class="fas fa-envelope text-gray-400"></i>
          </div>
          <input id="registerEmail" name="email" type="email" placeholder="Enter email"
                 class="input-field w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none" required />
        </div>
      </div>

      <div class="mb-5">
        <label for="registerPassword" class="block text-gray-700 mb-2 font-medium">Password</label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <i class="fas fa-lock text-gray-400"></i>
          </div>
          <input id="registerPassword" name="password" type="password" placeholder="Enter password"
                 class="input-field w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:outline-none" required />
          <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center" aria-label="Toggle password visibility" tabindex="-1">
            <i class="fas fa-eye-slash text-gray-400 hover:text-gray-600"></i>
          </button>
        </div>
      </div>

      <div class="mb-6">
        <label for="registerConfirmPassword" class="block text-gray-700 mb-2 font-medium">Confirm Password</label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <i class="fas fa-lock text-gray-400"></i>
          </div>
          <input id="registerConfirmPassword" name="confirmPassword" type="password" placeholder="Confirm password"
                 class="input-field w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:outline-none" required />
          <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center" aria-label="Toggle password visibility" tabindex="-1">
            <i class="fas fa-eye-slash text-gray-400 hover:text-gray-600"></i>
          </button>
        </div>
      </div>

      <div class="mb-4 flex items-center">
        <input type="checkbox" id="terms" name="terms" class="rounded text-purple-600 focus:ring-purple-500" required />
        <label for="terms" class="ml-2 text-sm text-gray-600">
          I agree to the
          <a href="#" class="text-purple-600 hover:underline">Terms</a> and
          <a href="#" class="text-purple-600 hover:underline">Privacy Policy</a>
        </label>
      </div>

      <button type="submit" class="btn-secondary w-full text-white py-3 rounded-lg font-medium mb-4">
        Create Account <i class="fas fa-user-plus ml-2"></i>
      </button>

      <div class="text-center text-sm text-gray-500">
        Already have an account?
        <button type="button" id="switchToLogin" class="text-purple-600 font-medium hover:underline">Sign in</button>
      </div>

      <div id="registerMessage" class="mt-4 text-center text-sm"></div>
    </form>
  </div>

<script src="../js/login.js" ></script>

</body>
</html>
