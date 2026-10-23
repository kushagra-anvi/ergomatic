<?php
require_once __DIR__ . '/../includes/bootstrap.php';

try {
    if ((int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
        http_response_code(404);
        exit('Not found.');
    }
    $setupKey = (string) (app_config()['admin_setup_key'] ?? '');
} catch (Throwable $exception) {
    service_unavailable();
}
if (strlen($setupKey) < 32) {
    http_response_code(404);
    exit('First-admin setup is disabled.');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email = strtolower(trim(post_string('email')));
    $password = post_string('password');
    $providedKey = post_string('setup_key');
    if (!hash_equals($setupKey, $providedKey)) {
        $error = 'Setup key is incorrect.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 12 || strlen($password) > 72) {
        $error = 'Choose a password between 12 and 72 characters.';
    } else {
        try {
            $pdo = db();
            if ((int) $pdo->query("SELECT GET_LOCK('ergomatic_blog_first_admin', 5)")->fetchColumn() !== 1) {
                $error = 'Setup is busy. Please try again.';
            } else {
                try {
                    if ((int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
                        http_response_code(404);
                        exit('First-admin setup is closed.');
                    }
                    $insert = $pdo->prepare('INSERT INTO admins (email, password_hash) VALUES (?, ?)');
                    $insert->execute([$email, password_hash($password, PASSWORD_DEFAULT)]);
                    redirect('login.php');
                } finally {
                    $pdo->query("SELECT RELEASE_LOCK('ergomatic_blog_first_admin')");
                }
            }
        } catch (Throwable $exception) {
            $error = 'The admin account could not be created. Check that this email is not already in use.';
        }
    }
}

admin_header('Create first admin');
echo '<section class="card blog-admin-form"><h2>Initial setup</h2><p>Use this one-time page to create the first administrator.</p>';
if ($error !== '') {
    echo '<p class="blog-admin-error" role="alert">' . e($error) . '</p>';
}
echo '<form method="post" action="admin/setup.php">' . csrf_field();
echo '<label>Setup key<input type="password" name="setup_key" autocomplete="off" required></label>';
echo '<label>Admin email<input type="email" name="email" autocomplete="email" maxlength="254" required></label>';
echo '<label>Password (12 to 72 characters)<input type="password" name="password" autocomplete="new-password" minlength="12" maxlength="72" required></label>';
echo '<button class="btn btn-primary" type="submit">Create admin</button></form></section>';
admin_footer();
