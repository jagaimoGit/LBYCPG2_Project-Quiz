<?php
/**
 * Registration Page
 * Allows users to create an account as host or participant
 */
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../models/UserModel.php';

$page_title = 'Register - LSQuiz';
$errors = [];

// Redirect if already logged in
if (current_user()) {
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'participant';
    
    // Validation
    if (!require_field($name)) {
        $errors[] = 'Name is required.';
    }
    
    if (!require_field($email)) {
        $errors[] = 'Email is required.';
    } elseif (!validate_email($email)) {
        $errors[] = 'Invalid email format.';
    } elseif (UserModel::getByEmail($email)) {
        $errors[] = 'Email already registered.';
    }
    
    if (!require_field($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (!in_array($role, ['host', 'participant'])) {
        $role = 'participant';
    }
    
    // Create user if no errors
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $user_id = UserModel::create($name, $email, $password_hash, $role);
        
        if ($user_id) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_role'] = $role;
            set_flash('success', 'Registration successful! Welcome to LSQuiz.');
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}
?>
<div class="container">
    <div class="card" style="max-width: 500px; margin: 2rem auto;">
        <div class="card-header">
            <h2 class="card-title">Create Account</h2>
        </div>
        <form method="POST" action="">
            <?php if (!empty($errors)): ?>
                <div class="flash flash-error">
                    <ul style="margin: 0; padding-left: 1.5rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" required value="<?php echo e($_POST['name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required value="<?php echo e($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required minlength="6">
                <small style="color: #666;">Minimum 6 characters</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <label for="role">Account Type *</label>
                <select id="role" name="role" required>
                    <option value="participant" <?php echo (($_POST['role'] ?? 'participant') === 'participant') ? 'selected' : ''; ?>>Participant</option>
                    <option value="host" <?php echo (($_POST['role'] ?? '') === 'host') ? 'selected' : ''; ?>>Host</option>
                </select>
                <small style="color: #666;">Hosts can create and manage quizzes. Participants can take quizzes.</small>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Register</button>
                <a href="login.php" class="btn btn-secondary">Already have an account? Login</a>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
