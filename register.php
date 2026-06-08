<?php
require_once 'config.php';
$pageTitle = 'Register';

if (isLoggedIn()) redirect('index.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = sanitize(trim($_POST['name'] ?? ''));
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    $phone    = sanitize(trim($_POST['phone'] ?? ''));

    if (empty($name))     $errors[] = 'Name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        // Check duplicate email
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone) VALUES (?,?,?,?)");
            $stmt->execute([$name, $email, $hash, $phone]);
            $userId = $pdo->lastInsertId();

            $_SESSION['user_id'] = $userId;
            $_SESSION['name']    = $name;
            $_SESSION['email']   = $email;
            $_SESSION['role']    = 'customer';
            flashMessage('success', 'Account created! Welcome to PhoneZone, ' . $name . '!');
            redirect('index.php');
        }
    }
}

include 'includes/header.php';
?>

<div style="min-height:calc(100vh - 200px);display:flex;align-items:center;justify-content:center;padding:40px 20px;">
    <div style="width:100%;max-width:460px;">
        <div style="text-align:center;margin-bottom:32px;">
            <div style="font-size:3rem;margin-bottom:12px;">🚀</div>
            <div class="section-title">Create Account</div>
            <div style="color:var(--text2);">Join PhoneZone to start shopping</div>
        </div>

        <div class="card" style="padding:32px;">
            <?php if(!empty($errors)): ?>
                <div style="background:rgba(255,107,107,0.1);border:1px solid var(--accent2);border-radius:8px;padding:12px 16px;margin-bottom:20px;">
                    <?php foreach($errors as $e): ?>
                        <div style="color:var(--accent2);font-size:0.875rem;">❌ <?= sanitize($e) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Full Name *</label>
                        <input type="text" name="name" placeholder="John Doe" value="<?= sanitize($_POST['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Phone Number</label>
                        <input type="text" name="phone" placeholder="9876543210" value="<?= sanitize($_POST['phone'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group" style="margin-top:16px;">
                    <label>Email Address *</label>
                    <input type="email" name="email" placeholder="you@example.com" value="<?= sanitize($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" placeholder="At least 6 characters" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm" placeholder="Repeat password" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;font-size:1rem;margin-top:4px;">
                    Create Account →
                </button>
            </form>

            <div style="text-align:center;margin-top:20px;color:var(--text2);font-size:0.9rem;">
                Already have an account? <a href="login.php" style="color:var(--accent);font-weight:600;">Sign in</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
