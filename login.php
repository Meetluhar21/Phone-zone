<?php
require_once 'config.php';
$pageTitle = 'Login';

if (isLoggedIn()) redirect('index.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = 'Email and password are required.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];
            flashMessage('success', 'Welcome back, ' . $user['name'] . '!');
            redirect($_GET['redirect'] ?? 'index.php');
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}

include 'includes/header.php';
?>

<div style="min-height:calc(100vh - 200px);display:flex;align-items:center;justify-content:center;padding:40px 20px;">
    <div style="width:100%;max-width:420px;">
        <div style="text-align:center;margin-bottom:32px;">
            <div style="font-size:3rem;margin-bottom:12px;">📱</div>
            <div class="section-title">Welcome back</div>
            <div style="color:var(--text2);">Sign in to your PhoneZone account</div>
        </div>

        <div class="card" style="padding:32px;">
            <?php if(!empty($errors)): ?>
                <div class="flash error" style="margin-bottom:20px;">
                    ❌ <?= sanitize($errors[0]) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="you@example.com" value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;font-size:1rem;margin-top:8px;">
                    Sign In →
                </button>
            </form>

            <div style="text-align:center;margin-top:20px;color:var(--text2);font-size:0.9rem;">
                Don't have an account? <a href="register.php" style="color:var(--accent);font-weight:600;">Create one</a>
            </div>

            <div style="text-align:center;margin-top:12px;padding:12px;background:var(--surface2);border-radius:8px;font-size:0.8rem;color:var(--text3);">
                Demo admin: <strong>admin@phonestore.com</strong> / <strong>password</strong>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
