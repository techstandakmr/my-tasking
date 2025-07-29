<?php
require_once __DIR__ . '/config/config.php'; // Optional: Include config file for database or other settings, if not already included elsewhere
require __DIR__ . '/vendor/autoload.php'; // Include Composer autoload for PHPMailer and dependencies
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/'); // adjust path if .env is in parent folder
$dotenv->load();

use PHPMailer\PHPMailer\PHPMailer; // Import PHPMailer main class
use PHPMailer\PHPMailer\Exception; // Import PHPMailer exception class

// === Gmail SMTP Settings ===
$mailHost = $_ENV['MAILHOST']; // SMTP server host for Gmail
$mailPort =  (int) $_ENV['MAILPORT']; // SMTP port for TLS
$mailUsername = $_ENV['APP_EMAIL_USERNAME']; // Gmail address used to send emails
$mailPassword = $_ENV['APP_EMAIL_PASSWORD']; // App password generated from Google account for SMTP authentication

// Function to send an email via Gmail SMTP using PHPMailer
function sendMail($toEmail, $subject, $html)
{
  global $mailHost, $mailPort, $mailUsername, $mailPassword; // Access global SMTP config variables

  $mail = new PHPMailer(true); // Instantiate PHPMailer with exceptions enabled

  try {
    $mail->isSMTP(); // Use SMTP protocol
    $mail->Host = $mailHost; // Set SMTP server
    $mail->SMTPAuth = true; // Enable SMTP authentication
    $mail->Username = $mailUsername; // SMTP username (Gmail address)
    $mail->Password = $mailPassword; // SMTP password (App password)
    $mail->SMTPSecure = 'tls'; // Enable TLS encryption
    $mail->Port = $mailPort; // Set SMTP port

    $mail->setFrom($mailUsername, $_ENV['APP_NAME']); // Set sender email and name
    $mail->addAddress($toEmail); // Add recipient email address
    $mail->Subject = $subject; // Set email subject
    $mail->isHTML(true); // Enable HTML content in email body
    $mail->Body = "$html"; // Set email body content

    $mail->SMTPDebug = 0; // Disable SMTP debug output (set to 2 for debugging)
    return $mail->send(); // Send email and return true on success
  } catch (Exception $e) {
    error_log('Mailer Error: ' . $mail->ErrorInfo); // Log error message if email sending fails
    return false; // Return false on failure
  }
}
