<?php
// This file is deprecated. The password reset functionality has been moved to forgot_password.php
// Redirecting users to the forgot password page for consistency
header('Location: forgot_password.php');
exit;
?>
        $message_type = 'danger';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Passwords do not match';
        $message_type = 'danger';
    } else {
        // Hash password and update
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $update_query = "UPDATE tbl_users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE user_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, 'si', $hashed_password, $user_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $message = 'Password has been reset successfully! You can now login with your new password.';
            $message_type = 'success';
            $token_valid = false;
            
            // Redirect to login after 3 seconds
            header('refresh:3;url=login.php');
        } else {
            $message = 'An error occurred while resetting your password. Please try again.';
            $message_type = 'danger';
        }
        mysqli_stmt_close($update_stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Dessert Magic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body {
            background: url('https://i.pinimg.com/1200x/77/6c/e5/776ce566d0db770b0d03c1281492083f.jpg') no-repeat center center !important;
            background-size: cover !important;
            background-attachment: fixed !important;
            min-height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        body {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 20px !important;
        }
        
        .reset-container {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255,255,255,0.3);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            max-width: 450px;
            width: 100%;
            padding: 40px 30px;
            margin: auto;
        }
        
        .reset-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .reset-header h2 {
            color: #333;
            font-weight: 700;
            font-size: 26px;
            margin-bottom: 10px;
        }
        
        .reset-header p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-reset {
            background: linear-gradient(135deg, #5c1f37 0%, #662d42 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            width: 100%;
            color: #ffffff;
            margin-bottom: 15px;
        }
        
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: #ffffff;
            text-decoration: none;
        }
        
        .btn-reset:disabled {
            background: #ccc;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            padding: 12px 15px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background-color: #ffe5e5;
            color: #d32f2f;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .form-text {
            font-size: 13px;
            color: #666;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(0,0,0,0.1);
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .back-link a:hover {
            color: #764ba2;
        }
        
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .password-wrapper .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            color: #667eea;
            font-size: 1.05rem;
            padding: 0;
            line-height: 1;
        }
        
        .password-wrapper .toggle-password:focus {
            outline: 2px solid rgba(102, 126, 234, 0.35);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h2>🔐 Set New Password</h2>
            <p>Enter your new password below</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($token_valid): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="password-wrapper">
                        <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Enter new password (min 6 chars)" required minlength="6">
                        <button type="button" class="toggle-password" data-target="new_password" aria-label="Show password">👁</button>
                    </div>
                    <small class="form-text">Must contain at least one letter and one number</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" required minlength="6">
                        <button type="button" class="toggle-password" data-target="confirm_password" aria-label="Show password">👁</button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-reset">Reset Password</button>
            </form>
        <?php else: ?>
            <div class="back-link">
                <p class="mb-0"><a href="login.php">← Back to Login</a></p>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        document.querySelectorAll('.toggle-password').forEach(function (button) {
            button.addEventListener('click', function () {
                const input = document.getElementById(button.dataset.target);
                if (!input) return;
                if (input.type === 'password') {
                    input.type = 'text';
                    button.textContent = '🙈';
                } else {
                    input.type = 'password';
                    button.textContent = '👁';
                }
            });
        });
    </script>
</body>
</html>
