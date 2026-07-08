<?php
require_once __DIR__ . '/../../config/session.php';

header('Content-Type: application/json');

$_SESSION = [];
session_destroy();

echo json_encode(['sucesso' => true]);
