<?php
require_once __DIR__ . '/../includes/bootstrap.php';
redirect(admin_logged_in() ? '/admin/posts.php' : '/admin/login.php');
