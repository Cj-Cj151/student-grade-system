<?php
require_once __DIR__ . '/../includes/session.php';

// If already logged in, skip the login page entirely.
if (isLoggedIn()) {
    header('Location: ' . dashboardUrlForRole(currentRole()));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Rosemont Student Grade Viewing System</title>
<link rel="stylesheet" href="/public/css/style.css">
</head>
<body>

<div class="login-page">
    <div class="glass-card login-card">
        <div class="login-logo">RS</div>
        <h1 class="login-title">Rosemont Grade System</h1>
        <p class="login-subtitle">Sign in to view or manage grades</p>

        <div id="loginAlert" class="alert alert-error"></div>

        <form id="loginForm" class="login-form" autocomplete="off">
            <div class="form-group">
                <label for="login_id">Student ID / Teacher ID / Admin Username</label>
                <input type="text" id="login_id" name="login_id" class="form-control" placeholder="e.g. S-2001" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                    <button type="button" class="toggle-password" data-target="password">Show</button>
                </div>
            </div>

            <button type="submit" id="loginBtn" class="btn btn-primary btn-block">
                <span id="loginBtnText">Login</span>
            </button>

            <div class="register-prompt">
    <p>Don't have an account?</p>
    <a href="register.php">Create Account</a>
</div>
        </form>

        <div class="login-credentials-hint">
            <strong>Test accounts</strong><br>
            Admin &nbsp;— admin / admin123<br>
            Teacher — T-1001 / teacher123<br>
            Student — S-2001 / student123
        </div>
    </div>
</div>

<script src="/public/js/app.js"></script>
<script>
const loginForm = document.getElementById('loginForm');
const loginBtn = document.getElementById('loginBtn');
const loginBtnText = document.getElementById('loginBtnText');
const loginAlert = document.getElementById('loginAlert');

function showLoginError(message) {
    loginAlert.textContent = message;
    loginAlert.classList.add('show');
}

function hideLoginError() {
    loginAlert.classList.remove('show');
}

function setLoginLoading(isLoading) {
    loginBtn.disabled = isLoading;
    loginBtnText.innerHTML = isLoading
        ? '<span class="spinner"></span> Signing in...'
        : 'Log In';
}

loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideLoginError();

    const login_id = document.getElementById('login_id').value.trim();
    const password = document.getElementById('password').value;

    if (!login_id || !password) {
        showLoginError('Please enter both your login ID and password.');
        return;
    }

    setLoginLoading(true);

    const result = await apiFetch('/api/login.php', {
        method: 'POST',
        body: JSON.stringify({ login_id, password }),
    });

    setLoginLoading(false);

    if (result.success) {
        window.location.href = result.data.redirect;
    } else {
        showLoginError(result.message || 'Login failed. Please try again.');
    }
});
</script>
</body>
</html>
