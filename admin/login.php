<?php
require_once __DIR__ . '/../includes/bootstrap.php';
if (admin_logged_in()) {
    redirect('posts.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email = strtolower(trim(post_string('email')));
    $password = post_string('password');
    try {
        $statement = db()->prepare('SELECT id, password_hash FROM admins WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        $admin = $statement->fetch();
        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            redirect('posts.php');
        }
        $error = 'Email or password is incorrect.';
    } catch (Throwable $exception) {
        service_unavailable();
    }
}

admin_header('Admin sign in');
echo '<section class="card blog-admin-form"><h2>Sign in</h2>';
if ($error !== '') {
    echo '<p class="blog-admin-error" role="alert">' . e($error) . '</p>';
}
echo '<form method="post" action="admin/login.php">' . csrf_field();
echo '<label>Email<input type="email" name="email" autocomplete="username" maxlength="254" required></label>';
echo '<label>Password<input type="password" name="password" autocomplete="current-password" required></label>';
echo '<button class="btn btn-primary" type="submit">Sign in</button></form></section>';
admin_footer();
