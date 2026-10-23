<?php
require_once __DIR__ . '/../includes/bootstrap.php';
redirect(admin_logged_in() ? 'posts.php' : 'login.php');
