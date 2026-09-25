<?php
require_once __DIR__ . '/../includes/session.php';

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
<title>Create Student Account - Rosemont Student Grade Viewing System</title>
<link rel="stylesheet" href="/public/css/style.css">

<style>
.register-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 30px 15px;
}

.register-card {
    width: 100%;
    max-width: 760px;
}

.register-header {
    text-align: center;
    margin-bottom: 28px;
}

.register-header h1 {
    margin: 0 0 8px;
    font-size: 26px;
}

.register-header p {
    margin: 0;
    color: var(--text-muted);
    font-size: 14px;
}

.register-section {
    margin-bottom: 24px;
}

.register-section-title {
    margin: 0 0 18px;
    font-size: 17px;
    color: var(--text-main);
}

.register-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.register-grid .form-group.full {
    grid-column: 1 / -1;
}

.password-error {
    display: none;
    margin-top: 6px;
    color: var(--danger);
    font-size: 12px;
}

.input-error {
    border-color: var(--danger) !important;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1) !important;
}

.register-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

.register-actions .btn {
    flex: 1;
}

.register-footer {
    text-align: center;
    margin-top: 20px;
    color: var(--text-muted);
    font-size: 13px;
}

.register-footer a {
    color: var(--primary);
    font-weight: 600;
    text-decoration: none;
}

.register-footer a:hover {
    text-decoration: underline;
}

@media (max-width: 650px) {
    .register-card {
        padding: 24px 20px;
    }

    .register-grid {
        grid-template-columns: 1fr;
    }

    .register-grid .form-group.full {
        grid-column: auto;
    }

    .register-actions {
        flex-direction: column;
    }
}
</style>
</head>

<body>

<div class="register-page">

    <div class="glass-card panel register-card">

        <div class="register-header">

            <h1>
                Create Student Account
            </h1>

            <p>
                Enter your information to create your account
            </p>

        </div>

        <div
            id="registerAlert"
            class="alert alert-error"
        ></div>

        <form
            id="registerForm"
            autocomplete="off"
        >

            <div class="register-section">

                <h2 class="register-section-title">
                    Student Information
                </h2>

                <div class="register-grid">

                    <div class="form-group">

                        <label for="student_id">
                            Student ID
                        </label>

                        <input
                            type="text"
                            id="student_id"
                            name="student_id"
                            class="form-control"
                            placeholder="e.g. S-2005"
                            maxlength="50"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="student@example.com"
                            maxlength="150"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="first_name">
                            First Name
                        </label>

                        <input
                            type="text"
                            id="first_name"
                            name="first_name"
                            class="form-control"
                            placeholder="Enter first name"
                            maxlength="100"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="last_name">
                            Last Name
                        </label>

                        <input
                            type="text"
                            id="last_name"
                            name="last_name"
                            class="form-control"
                            placeholder="Enter last name"
                            maxlength="100"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="course">
                            Course
                        </label>

                        <select
                            id="course"
                            name="course"
                            class="form-control"
                            required
                        >

                            <option value="">
                                Select course
                            </option>

                            <option value="Bachelor of Science in Criminology">
                                Bachelor of Science in Criminology
                            </option>

                            <option value="Bachelor of Science in Information Technology">
                                Bachelor of Science in Information Technology
                            </option>

                            <option value="Bachelor of Elementary Education">
                                Bachelor of Elementary Education
                            </option>

                            <option value="Bachelor of Science in Office Administration">
                                Bachelor of Science in Office Administration
                            </option>

                        </select>

                    </div>

                    <div class="form-group">

                        <label for="year_level">
                            Year Level
                        </label>

                        <select
                            id="year_level"
                            name="year_level"
                            class="form-control"
                            required
                        >

                            <option value="">
                                Select year level
                            </option>

                            <option value="1">
                                1st Year
                            </option>

                            <option value="2">
                                2nd Year
                            </option>

                            <option value="3">
                                3rd Year
                            </option>

                            <option value="4">
                                4th Year
                            </option>

                        </select>

                    </div>

                </div>

            </div>

            <div class="register-section">

                <h2 class="register-section-title">
                    Account Security
                </h2>

                <div class="register-grid">

                    <div class="form-group">

                        <label for="password">
                            Password
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Create a password"
                            minlength="6"
                            required
                        >

                        <div class="form-hint">
                            Minimum of 6 characters
                        </div>

                    </div>

                    <div class="form-group">

                        <label for="confirm_password">
                            Confirm Password
                        </label>

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-control"
                            placeholder="Re-enter your password"
                            minlength="6"
                            required
                        >

                        <p
                            id="passwordError"
                            class="password-error"
                        >
                            Passwords do not match.
                        </p>

                    </div>

                </div>

            </div>

            <div class="register-actions">

                <a
                    href="login.php"
                    class="btn btn-secondary"
                >
                    Back to Login
                </a>

                <button
                    type="submit"
                    id="registerBtn"
                    class="btn btn-primary"
                >
                    Create Account
                </button>

            </div>

        </form>

        <div class="register-footer">

            Already have an account?

            <a href="login.php">
                Log in here
            </a>

        </div>

    </div>

</div>

<script>
const registerForm =
    document.getElementById('registerForm');

const passwordInput =
    document.getElementById('password');

const confirmPasswordInput =
    document.getElementById('confirm_password');

const passwordError =
    document.getElementById('passwordError');

const registerBtn =
    document.getElementById('registerBtn');

const registerAlert =
    document.getElementById('registerAlert');

function showRegisterError(message) {

    registerAlert.textContent = message;
    registerAlert.classList.add('show');

}

function hideRegisterError() {

    registerAlert.classList.remove('show');

}

function checkPasswords() {

    const password =
        passwordInput.value;

    const confirmPassword =
        confirmPasswordInput.value;

    if (
        confirmPassword !== '' &&
        password !== confirmPassword
    ) {

        confirmPasswordInput.classList.add(
            'input-error'
        );

        passwordError.style.display = 'block';

        return false;
    }

    confirmPasswordInput.classList.remove(
        'input-error'
    );

    passwordError.style.display = 'none';

    return true;
}

passwordInput.addEventListener(
    'input',
    checkPasswords
);

confirmPasswordInput.addEventListener(
    'input',
    checkPasswords
);

registerForm.addEventListener(
    'submit',
    async (event) => {

        event.preventDefault();

        hideRegisterError();

        if (!checkPasswords()) {
            return;
        }

        const form = event.target;

        const data = {
            student_id:
                form.student_id.value.trim(),

            first_name:
                form.first_name.value.trim(),

            last_name:
                form.last_name.value.trim(),

            email:
                form.email.value.trim(),

            course:
                form.course.value,

            year_level:
                Number(form.year_level.value),

            password:
                form.password.value,

            confirm_password:
                form.confirm_password.value
        };

        registerBtn.disabled = true;

        registerBtn.textContent =
            'Creating Account...';

        try {

            const response =
                await fetch(
                    '../api/register.php',
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type':
                                'application/json'
                        },
                        body:
                            JSON.stringify(data)
                    }
                );

            const result =
                await response.json();

            if (!response.ok) {

                showRegisterError(
                    result.message ||
                    'Unable to create account.'
                );

                registerBtn.disabled = false;

                registerBtn.textContent =
                    'Create Account';

                return;
            }

            alert(
                'Account created successfully!'
            );

            window.location.href =
                'login.php';

        } catch (error) {

            showRegisterError(
                'Unable to connect to the server. Please make sure the PHP server is running.'
            );

            registerBtn.disabled = false;

            registerBtn.textContent =
                'Create Account';
        }

    }
);
</script>

</body>
</html>