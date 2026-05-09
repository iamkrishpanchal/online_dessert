<?php
// Contact Us page
include 'connection.php';
session_start();

$contact_success = '';
$contact_error = '';
$name = '';
$email = '';
$subject = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $contact_error = 'Please fill out all fields before sending your message.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $contact_error = 'Please enter a valid email address.';
    } else {
        // create contact table if missing
        $createTableSql = "CREATE TABLE IF NOT EXISTS tbl_contacts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        mysqli_query($conn, $createTableSql);

        $insertSql = "INSERT INTO tbl_contacts (name, email, subject, message) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insertSql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ssss', $name, $email, $subject, $message);
            if (mysqli_stmt_execute($stmt)) {
                $contact_success = 'Thanks for reaching out! Your message has been received.';
                // clear fields after successful submit
                $name = $email = $subject = $message = '';
            } else {
                $contact_error = 'Oops! We could not save your message right now. Please try again.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $contact_error = 'Database error: failed to prepare save statement.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Contact Us - Dessert Magic</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/vendor.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        .contact-card { background:#f9f9f9; border-radius:8px; padding:20px; text-align:center; }
        .contact-card svg { width:24px; height:24px; margin-bottom:8px; }
        .contact-form .form-control { border-radius:4px; }
        .contact-form .btn { border-radius:20px; }
    </style>
</head>
<body>
    <header><?php include 'header.php'; ?></header>

    <main>
        <!-- hero -->
        <section class="py-5 text-center bg-light" style="background-image:url('images/contact-hero.jpg');background-size:cover;background-position:center;position:relative;">
            <div style="background:rgba(255,255,255,0.8);padding:60px 0;">
                <h1 class="display-4 fw-bold">Contact Us</h1>
                <p class="lead">Reach out to Dessert Magic for orders, support! </p>
            </div>
        </section>

        <!-- details & form -->
        <section class="container py-5">
            <div class="row g-4 justify-content-center">
                <div class="col-lg-8">
                    <div class="row g-4">
                        <div class="col-6">
                            <div class="contact-card">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="bi bi-telephone" viewBox="0 0 16 16">
  <path d="M1.885.511a1.745 1.745 0 0 1 2.61.163L6.29 2.98c.329.38.445.896.289 1.35l-.897 2.99a.678.678 0 0 0 .178.677l2.457 2.457a.678.678 0 0 0 .677.178l2.99-.897a1.745 1.745 0 0 1 1.35.289l2.306 1.794c.829.645.905 1.87.163 2.61l-1.034 1.034c-.74.74-1.922.695-2.621-.062C10.784 13.88 8.098 12 5.454 9.357 2.81 6.713.93 4.027.541 2.27c-.757-.7-.802-1.882-.062-2.621L1.885.511z"/>
</svg>
                                <h5 class="mt-2">Phone</h5>
                                <p>+1 234 567 890</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="contact-card">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16">
  <path d="M13.601 2.326A7.487 7.487 0 0 0 8.999.5a7.49 7.49 0 0 0-6.708 4.014c-.659 1.241-.684 2.554-.078 3.826L.5 15.5l6.53-1.716a7.482 7.482 0 0 0 3.942 1.132h.001c1.98 0 3.857-.756 5.262-2.121a7.49 7.49 0 0 0 2.121-5.262 7.486 7.486 0 0 0-.5-2.213 7.483 7.483 0 0 0-1.282-2.499l-.001-.001zM8.999 13.5a5.5 5.5 0 1 1 0-11a5.5 5.5 0 0 1 0 11z"/>
</svg>
                                <h5 class="mt-2">WhatsApp</h5>
                                <p>+1 987 654 321</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="contact-card">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="bi bi-envelope" viewBox="0 0 16 16">
  <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v1l-8 4.5L0 5V4z"/>
  <path d="M0 6.383v5.234l5.803-3.258L0 6.383z"/>
  <path d="M6.761 8.83l-6.761 3.798V12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-.372l-6.761-3.798L8 10.293l-1.239-.464z"/>
</svg>
                                <h5 class="mt-2">Email</h5>
                                <p>support@dessertmagic.com</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="contact-card">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="bi bi-shop" viewBox="0 0 16 16">
  <path d="M2 2a1 1 0 0 0-1 1v1h14V3a1 1 0 0 0-1-1H2zm0 4v7a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V6H2zm1 2h2v2H3V8z"/>
</svg>
                                <h5 class="mt-2">Our Shop</h5>
                                <p>123 Sweet St, Bakersville</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer><?php include 'footer.php'; ?></footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>