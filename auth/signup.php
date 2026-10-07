<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard/profile');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($conn, $_POST['name']);
    $email = clean($conn, strtolower($_POST['email']));
    $password = $_POST['password'];
    $profession = clean($conn, $_POST['profession']);

    if (!$name || !$email || strlen($password) < 6) {
        $error = 'Please fill all fields. Password must be at least 6 characters.';
    } else {
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check, 's', $email);
        mysqli_stmt_execute($check);

        if (mysqli_stmt_get_result($check)->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $slug = generate_unique_slug($conn, $name);

            $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, profession, public_slug) VALUES (?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'sssss', $name, $email, $hash, $profession, $slug);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['user_id'] = mysqli_insert_id($conn);
                header('Location: ../dashboard/profile?welcome=1');
                exit;
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account - SaaqiFolio</title>
<?php
$ogTitle = 'Create Account — SaaqiFolio';
$ogDescription = 'Join SaaqiFolio to build your creative designer portfolio in minutes. Showcase your work and grow your brand.';
include __DIR__ . '/../includes/og_meta.php';
?>
<link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">
<link rel="alternate icon" type="image/png" href="../assets/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
<script src="../assets/js/ui.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/ui.js'); ?>"></script>
</head>
<body>

<div class="auth-wrap">
  <div class="auth-visual">
    <div class="mark-row">
      <div class="brand-mark"></div>
      <div class="brand-name">SaaqiFolio</div>
    </div>
    <div class="auth-headline">Build your creative <em>portfolio</em> in minutes</div>
    <p class="auth-sub">Upload your work, organize with smart categories, and share a sleek, professional link with clients worldwide.</p>
  </div>

  <div class="auth-form-side">
    <div class="auth-card">
      <div class="auth-tabs">
        <a href="login" class="auth-tab" style="text-decoration:none;display:flex;align-items:center;justify-content:center;">Sign in</a>
        <button class="auth-tab active" type="button">Create account</button>
      </div>

      <?php if ($error): ?><div class="auth-error"><?php echo e($error); ?></div><?php endif; ?>

      <form method="POST">
        <div class="field">
          <label>Full name</label>
          <input type="text" name="name" placeholder="e.g. Ayesha Khan" required value="<?php echo e($_POST['name'] ?? ''); ?>">
        </div>
        <div class="field">
          <label>Email</label>
          <input type="email" name="email" placeholder="you@example.com" required value="<?php echo e($_POST['email'] ?? ''); ?>">
        </div>
        <div class="field">
          <label>Password</label>
          <div class="password-wrap">
            <input type="password" name="password" placeholder="Choose a password" required minlength="6">
            <button type="button" class="btn-toggle-pw" aria-label="Toggle password visibility" tabindex="-1">
              <svg class="eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" style="display:none;"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
            </button>
          </div>
        </div>
        <div class="field">
          <label>Profession</label>
          <?php $selectedProfession = $_POST['profession'] ?? (get_professions()[0] ?? ''); ?>
          <div class="form-select">
            <input type="hidden" name="profession" value="<?php echo e($selectedProfession); ?>">
            <button type="button" class="form-select-trigger"><span><?php echo e($selectedProfession ?: 'Select profession'); ?></span><span class="fs-caret">▾</span></button>
            <ul class="form-select-list" hidden>
              <?php foreach (get_professions() as $p): ?>
                <li class="form-select-item <?php echo $selectedProfession === $p ? 'active' : ''; ?>" data-value="<?php echo e($p); ?>"><?php echo e($p); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
        <button class="btn btn-primary" type="submit">Create account</button>
      </form>
      <div class="auth-hint">Join thousands of creative professionals showcasing their work with SaaqiFolio.</div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/tutorial_video_modal.php'; ?>
</body>
</html>
