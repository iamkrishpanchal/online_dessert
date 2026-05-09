<?php
// About Us page

include 'connection.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>About Us - Dessert Magic</title>
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
        <!-- hero banner -->
<section class="py-2 text-center bg-light">
            <!-- background image removed per request -->
            <div style="background:rgba(255,255,255,0.8);padding:30px 0;">
                <h1 class="display-4 fw-bold">About Us</h1>
                <p class="lead">Learn more about Dessert Magic and our story</p>
            </div>
        </section>

        <!-- introduction / bakery section -->
        <section class="container py-2">
            <div class="row align-items-center">
                <div class="col-md-6 mb-4 mb-md-0">
                    <img src="images/sweetmoments.png" alt="sweetmoments" class="img-fluid rounded">
                </div>
                <div class="col-md-6">
                    <h2 class="fw-bold">Home Sweet Bakery</h2>
                    <p>At Dessert Magic, every treat is crafted with passion. Our journey began with a few dedicated bakers in a small kitchen, and today we're proud to partner with dozens of local vendors across the region.</p>
                    <p>From classic pastries to modern confections, we make sure each product is prepared using the finest ingredients and traditional techniques.
                        At Dessert Magic, we believe desserts are more than just food—they are experiences filled with joy, celebration, and comfort. From fluffy pancakes to rich chocolate cakes and refreshing thickshakes, every item is crafted to bring happiness in every bite.
                        <br>

                        <br>
                   <h5 class="fw-bold">🎯Our Mission :</h5>
                    
                    Our mission is to Deliver fresh, high-quality desserts
                    Create memorable sweet experiences
                    Provide fast and reliable service
                    Make every celebration extra special.
                    <br>
                    <br>
                     <h5 class="fw-bold">🌟Our Vision :</h5>
                    We aim to become a trusted and loved dessert destination, known for Unique and creative dessert combinations
                    Premium taste at affordable prices
                    A delightful experience for every customer.
                    <br>
                    <br>
                        <h5 class="fw-bold">🍰Our Specialties :</h5>
                    🧁 Cakes (Chocolate, Fruit, Custom Cakes)
                    <br>
                    🧇 Waffles & Pancakes
                    <br>
                    🥤 Thickshakes & Milkshakes
                    <br>
                    🍨 Ice Creams & Sundaes
                    <br>
                    🍩 Donuts & Cupcakes
                    <br>
                    
                    Each product is made using fresh ingredients and hygienic preparation methods.

                    </p>
                </div>
            </div>
        </section>
                    </p>
                </div>
            </div>
        </section>

        <!-- benefits grid -->
        <section class="py-5 bg-white">
            <div class="container">
                <h3 class="text-center fw-bold mb-4">Benefits of Choosing Dessert Magic</h3>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="p-3 border rounded">Wide variety of sweets</div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded">Fresh ingredients</div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded">Support local vendors</div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded">Easy online ordering</div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded">Fast home delivery</div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded">Customizable orders</div>
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