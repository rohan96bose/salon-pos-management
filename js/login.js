
  const loginToggle = document.getElementById('loginToggle');
  const registerToggle = document.getElementById('registerToggle');
  const loginForm = document.getElementById('loginForm');
  const registerForm = document.getElementById('registerForm');
  const switchToRegister = document.getElementById('switchToRegister');
  const switchToLogin = document.getElementById('switchToLogin');
  const loginMessage = document.getElementById('loginMessage');
  const registerMessage = document.getElementById('registerMessage');

  // Form toggle
  function showLoginForm() {
    loginForm.classList.remove('hidden');
    registerForm.classList.add('hidden');
    loginToggle.classList.add('tab-active', 'text-purple-600');
    loginToggle.classList.remove('text-gray-600');
    registerToggle.classList.remove('tab-active', 'text-purple-600');
    registerToggle.classList.add('text-gray-600');
    loginMessage.textContent = '';
    registerMessage.textContent = '';
  }

  function showRegisterForm() {
    registerForm.classList.remove('hidden');
    loginForm.classList.add('hidden');
    registerToggle.classList.add('tab-active', 'text-purple-600');
    registerToggle.classList.remove('text-gray-600');
    loginToggle.classList.remove('tab-active', 'text-purple-600');
    loginToggle.classList.add('text-gray-600');
    loginMessage.textContent = '';
    registerMessage.textContent = '';
  }

  // Toggle buttons
  loginToggle?.addEventListener('click', showLoginForm);
  registerToggle?.addEventListener('click', showRegisterForm);
  switchToRegister?.addEventListener('click', showRegisterForm);
  switchToLogin?.addEventListener('click', showLoginForm);

  // Password visibility toggle
  document.querySelectorAll('input[type="password"]').forEach(input => {
    const toggleBtn = input.parentElement.querySelector('button');
    if (toggleBtn) {
      toggleBtn.addEventListener('click', () => {
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        toggleBtn.innerHTML = isPassword
          ? '<i class="fas fa-eye text-gray-600"></i>'
          : '<i class="fas fa-eye-slash text-gray-400 hover:text-gray-600"></i>';
      });
    }
  });

  // Submit via fetch
  async function submitForm(form, messageBox) {
    messageBox.textContent = '';
    const formData = new FormData(form);

    try {
      const res = await fetch('../actions/login_auth.php', {
        method: 'POST',
        body: formData
      });

      const data = await res.json();

      if (data.success) {
        messageBox.classList.remove('text-red-600');
        messageBox.classList.add('text-green-600');
        messageBox.textContent = data.message;

        if (form === loginForm && data.redirect) {
          setTimeout(() => {
             window.location.href = 'customer-management.php';
          }, 1500);
        } else {
          form.reset();
        }
      } else {
        messageBox.classList.remove('text-green-600');
        messageBox.classList.add('text-red-600');
        messageBox.textContent = data.message;
      }

    } catch (error) {
      console.error(error);
      messageBox.classList.remove('text-green-600');
      messageBox.classList.add('text-red-600');
      messageBox.textContent = 'An error occurred. Please try again.';
    }
  }

  // Form submit listeners
  loginForm?.addEventListener('submit', e => {
    e.preventDefault();
    submitForm(loginForm, loginMessage);
  });

  registerForm?.addEventListener('submit', e => {
    e.preventDefault();
    const termsChecked = registerForm.querySelector('input[name="terms"]')?.checked;
    if (!termsChecked) {
      registerMessage.classList.remove('text-green-600');
      registerMessage.classList.add('text-red-600');
      registerMessage.textContent = 'You must agree to the terms and privacy policy.';
      return;
    }
    submitForm(registerForm, registerMessage);
  });