<?php
require_once __DIR__ .'/../vendor/autoload.php';
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
// Database server name
$servername = $_ENV['DB_SERVER_NAME'];
// Database username
$username = $_ENV['DB_USERNAME'];
// Database password
$password = $_ENV['DB_PASSWORD'];
// Database name
$database = $_ENV['DATABASE'];

// Create connection to MySQL database
$conn = mysqli_connect($servername, $username, $password, $database);

// Check if connection was successful
if (!$conn) {
  // Terminate script and display error message if connection failed
  die("Connection failed: " . mysqli_connect_error());
};
?>
