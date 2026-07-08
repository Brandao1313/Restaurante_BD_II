<?php
require_once __DIR__ . '/../config/session.php';

$_SESSION = [];
session_destroy();

header('Location: ' . BASE_URL . '/pages/login.php');
exit;
