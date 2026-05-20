<?php
// Check if session is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
require_once 'config.php';
$conn = get_db();

// Include PHPMailer
require 'vendor/PHPMailer/src/PHPMailer.php';
require 'vendor/PHPMailer/src/SMTP.php';
require 'vendor/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Check if email already exists
        $check_sql = "SELECT email FROM newsletter WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows == 0) {
            // Insert new email
            $insert_sql = "INSERT INTO newsletter (email) VALUES (?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("s", $email);

            if ($insert_stmt->execute()) {
                // Send confirmation email
                $mail = new PHPMailer(true);
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP host
                    $mail->SMTPAuth = true;
                    $mail->Username = 'petcare2059@gmail.com'; // Your Gmail address
                    $mail->Password = 'wzdd bqsx xqsw jvcs'; // Gmail App Password (not regular password)
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // Recipients
                    $mail->setFrom('petcare2059@gmail.com', 'PetCare Team');
                    $mail->addAddress($email); // Subscriber's email

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Welcome to PetCare Newsletter!';
                    $mail->Body = '
                        <h2 style="color: #0ea5e9;">Thank You for Subscribing to PetCare!</h2>
                        <p>Dear Subscriber,</p>
                        <p>We\'re thrilled to have you join our community of pet lovers! You\'ll now receive regular updates, pet care tips, and exclusive offers from PetCare.</p>
                        <p>Stay tuned for our latest content to help you keep your furry friends happy and healthy.</p>
                        <p>Best regards,<br>The PetCare Team</p>
                        <p style="font-size: 0.9em; color: #666;">If you did not subscribe, please ignore this email or contact us at support@petcare.com.</p>
                    ';
                    $mail->AltBody = 'Thank you for subscribing to PetCare! You\'ll receive regular updates and pet care tips. If you did not subscribe, please ignore this email or contact support@petcare.com.';

                    $mail->send();
                    $_SESSION['subscribe_message'] = "Subscription successful! A confirmation email has been sent to your inbox.";
                } catch (Exception $e) {
                    $_SESSION['subscribe_message'] = "Subscription successful, but failed to send confirmation email. Error: {$mail->ErrorInfo}";
                }

                $insert_stmt->close();
            } else {
                $_SESSION['subscribe_message'] = "Error: Subscription failed. Please try again.";
            }
        } else {
            $_SESSION['subscribe_message'] = "Error: This email is already subscribed.";
        }
        $check_stmt->close();
    } else {
        $_SESSION['subscribe_message'] = "Error: Invalid email format.";
    }
} else {
    $_SESSION['subscribe_message'] = "Error: No email provided.";
}

$conn->close();
header("Location: index.php");
exit();
?>