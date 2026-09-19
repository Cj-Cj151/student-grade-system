<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #f4f2ff, #eef8ff);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 15px;
            color: #263248;
        }

        .register-card {
            width: 100%;
            max-width: 650px;
            background: rgba(255, 255, 255, 0.92);
            border-radius: 24px;
            padding: 35px;
            box-shadow: 0 20px 50px rgba(65, 70, 120, 0.15);
        }

        .brand {
            text-align: center;
            margin-bottom: 28px;
        }

        .brand-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            border-radius: 18px;
            background: linear-gradient(135deg, #5146e5, #7278f2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            box-shadow: 0 10px 25px rgba(81, 70, 229, 0.25);
        }

        .brand h1 {
            margin: 0 0 8px;
            font-size: 25px;
        }

        .brand p {
            margin: 0;
            color: #68758d;
            font-size: 14px;
        }

        .form-section {
            margin-top: 20px;
        }

        .form-section h2 {
            font-size: 17px;
            margin: 0 0 18px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 7px;
        }

        input,
        select {
            width: 100%;
            height: 45px;
            border: 1px solid #d9deea;
            border-radius: 11px;
            padding: 0 13px;
            font-size: 14px;
            background: white;
            color: #263248;
            outline: none;
        }

        input:focus,
        select:focus {
            border-color: #6157e8;
            box-shadow: 0 0 0 3px rgba(97, 87, 232, 0.10);
        }

        .password-note {
            margin-top: 6px;
            font-size: 11px;
            color: #7a8599;
        }

        .button-row {
            margin-top: 25px;
            display: flex;
            gap: 12px;
        }

        .btn {
            flex: 1;
            height: 46px;
            border: none;
            border-radius: 11px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-primary {
            color: white;
            background: linear-gradient(135deg, #5146e5, #7278f2);
            box-shadow: 0 8px 18px rgba(81, 70, 229, 0.20);
        }

        .btn-secondary {
            color: #4d5870;
            background: #f1f3f8;
        }

        .login-link {
            text-align: center;
            margin-top: 22px;
            font-size: 13px;
            color: #69758b;
        }

        .login-link a {
            color: #5146e5;
            font-weight: 600;
            text-decoration: none;
        }

        @media (max-width: 600px) {
            .register-card {
                padding: 25px 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .button-row {
                flex-direction: column;
            }
        }

        .password-error {
    color: #dc2626;
    font-size: 13px;
    margin-top: 5px;
    display: none;
}

.input-error {
    border-color: #dc2626 !important;
    box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.1);
}
    </style>
</head>

<body>

<div class="register-card">

    <div class="brand">
        <div class="brand-icon">RS</div>

        <h1>Create Student Account</h1>

        <p>
Enter your information to create your account        </p>
    </div>

    <div class="form-section">

        <h2>Student Information</h2>

        <form id="registerForm">

            <div class="form-grid">

                <!-- Student ID -->
                <div class="form-group">
                    <label for="student_id">Student ID</label>
                    <input
                        type="text"
                        id="student_id"
                        name="student_id"
                        placeholder="e.g. S-2005"
                        maxlength="50"
                        required
                    >
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="student@example.com"
                        maxlength="150"
                        required
                    >
                </div>

                <!-- First Name -->
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        placeholder="Enter first name"
                        maxlength="100"
                        required
                    >
                </div>

                <!-- Last Name -->
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        placeholder="Enter last name"
                        maxlength="100"
                        required
                    >
                </div>

                <!-- Course -->
                <div class="form-group">
                    <label for="course">Course</label>

                    <select id="course" name="course" required>
                        <option value="">Select course</option>
                        <option value="BS Computer Science">
                            BS Computer Science
                        </option>
                        <option value="BS Information Technology">
                            BS Information Technology
                        </option>
                    </select>
                </div>

                <!-- Year Level -->
                <div class="form-group">
                    <label for="year_level">Year Level</label>

                    <select id="year_level" name="year_level" required>
                        <option value="">Select year level</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                        <option value="5">5th Year</option>
                        <option value="6">6th Year</option>
                    </select>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Create a password"
                        minlength="6"
                        required
                    >

                    <span class="password-note">
                        Minimum of 6 characters
                    </span>
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Re-enter your password"
                        minlength="6"
                        required
                    >
                     <p id="passwordError" class="password-error">
                     Passwords do not match.
                  </p>
                </div>

            </div>

            <div class="button-row">

                <a
                    href="login.php"
                    class="btn btn-secondary"
                >
                    Back to Login
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Create Account
                </button>

            </div>

        </form>

        <div class="login-link">
            Already have an account?
            <a href="login.php">Log in here</a>
        </div>

    </div>

</div>
<script>
const passwordInput = document.querySelector('input[name="password"]');
const confirmPasswordInput = document.querySelector('input[name="confirm_password"]');
const passwordError = document.getElementById('passwordError');

function checkPasswords() {
    const password = passwordInput.value;
    const confirmPassword = confirmPasswordInput.value;

    if (confirmPassword !== '' && password !== confirmPassword) {
        confirmPasswordInput.classList.add('input-error');
        passwordError.style.display = 'block';
        return false;
    }

    confirmPasswordInput.classList.remove('input-error');
    passwordError.style.display = 'none';
    return true;
}

passwordInput.addEventListener('input', checkPasswords);
confirmPasswordInput.addEventListener('input', checkPasswords);

document
    .getElementById('registerForm')
    .addEventListener('submit', async function (event) {

        event.preventDefault();

        if (!checkPasswords()) {
            return;
        }

        const form = event.target;

        const data = {
            student_id: form.student_id.value.trim(),
            first_name: form.first_name.value.trim(),
            last_name: form.last_name.value.trim(),
            email: form.email.value.trim(),
            course: form.course.value,
            year_level: Number(form.year_level.value),
            password: form.password.value,
            confirm_password: form.confirm_password.value
        };

        try {

            const response = await fetch('../api/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (!response.ok) {
                alert(result.message || 'Unable to create account.');
                return;
            }

            alert('Account created successfully!');

            window.location.href = 'login.php';

        } catch (error) {

            console.error('Registration error:', error);

            alert(
                'Unable to connect to the server. ' +
                'Please make sure the PHP server is running.'
            );
        }
    });
</script>
</body>
</html>