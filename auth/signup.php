<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard/profile.php');
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
                header('Location: ../dashboard/profile.php?welcome=1');
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
<title>Create Account - Folivo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="auth-wrap">
  <div class="auth-visual">
    <div class="mark-row">
      <div class="brand-mark"></div>
      <div class="brand-name">Folivo</div>
    </div>
    <div class="auth-headline">Apna design <em>portfolio</em> minutes mein banayein</div>
    <p class="auth-sub">Apna kaam upload karein, categories mein organize karein, aur clients ko ek professional link share karein.</p>
  </div>

  <div class="auth-form-side">
    <div class="auth-card">
      <div class="auth-tabs">
        <a href="login.php" class="auth-tab" style="text-decoration:none;display:flex;align-items:center;justify-content:center;">Sign in</a>
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
          <input type="password" name="password" placeholder="Choose a password" required minlength="6">
        </div>
        <div class="field">
          <label>Profession</label>
          <select name="profession">
            <?php foreach (get_professions() as $p): ?>
              <option value="<?php echo e($p); ?>" <?php echo (($_POST['profession'] ?? '') === $p) ? 'selected' : ''; ?>><?php echo e($p); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-primary" type="submit">Create account</button>
      </form>
      <div class="auth-hint">Account bana kar aap apna portfolio building shuru kar sakte hain.</div>
    </div>
  </div>
</div>

</body>
</html>
