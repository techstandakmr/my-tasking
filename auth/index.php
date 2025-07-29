<?php
// Redirect to the main Task Management System homepage
// This prevents direct access to the /auth/ directory
require_once __DIR__ .'/../vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
header("Location: {$_ENV['APP_URL']}");
