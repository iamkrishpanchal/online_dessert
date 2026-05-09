<?php
include 'session.php';
include 'connection.php';


if (isset($_POST["btnSubmit"])) {
    //print_r($_POST);die; 
    $Oname_val = $_POST["offer_name"];
    $Odesc_val = $_POST["description"];
    $Ovalidation_val = $_POST["validation"];

    $exe = pathinfo($_FILES["image"]["name"],PATHINFO_EXTENSION);

    //echo $exe;die;
    $filename = time() . random_int(1111, 9999) . "." . $exe;

    //echo $filename;die;

    move_uploaded_file($_FILES["image"]["tmp_name"],"./uploads/".$filename);


    $sql = "INSERT INTO tbl_offer (offer_name,description,image,validation) VALUES ('$Oname_val','$Odesc_val','$filename','$Ovalidation_val')";

    // echo $sql;die;

    $result = mysqli_query($conn, $sql);

    // echo $result;die;

    if ($result) {
        echo "<script>alert('record succesfully inserted');</script>";

        echo "<script>
        
            window.location.href='viewOffer.php';
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light">
<!-- BEGIN: Head -->
<head>
    <title>Add Offer</title>
    <link rel="stylesheet" href="dist/css/app.css" />
    <!-- END: CSS Assets-->
</head>
<!-- END: Head -->

<body class="py-5 md:py-0 bg-black/[0.15] dark:bg-transparent">
    <div class="flex mt-[4.7rem] md:mt-0 overflow-hidden">
        <!-- BEGIN: Side Menu -->
        <?php include 'sideMenu.php' ?>
        <!-- END: Side Menu -->
        <!-- BEGIN: Content -->
        <div class="content">
            <!-- BEGIN: Top Bar -->
            <?php include 'topBar.php' ?>
            <!-- END: Top Bar -->
            <div class="intro-y flex items-center mt-8">
                <h2 class="text-lg font-medium mr-auto">
                    Add Offer
                </h2>
            </div>
            <div class="grid grid-cols-12 gap-6 mt-5">
                <div class="intro-y col-span-12 lg:col-span-12">
                    <!-- BEGIN: Input -->
                    <div class="intro-y box">


                        <form method="POST" enctype="multipart/form-data">
                            <div id="input" class="p-5">
                                <div class="preview">
                                    <div>
                                        <label for="regular-form-1" class="form-label">Offer Name</label>
                                        <input id="regular-form-1" type="text" class="form-control" placeholder="Enter Offer Name" name="offer_name">
                                    </div>
                                    <br>

                                    <div>
                                        <label for="regular-form-1" class="form-label">Offer Description</label>
                                        <input id="regular-form-1" type="text" class="form-control" placeholder="Enter offer Description" name="description">
                                    </div>
                                    <br>

                                    <div>
                                        <label for="regular-form-1" class="form-label">Image</label>
                                        <input id="regular-form-1" type="File" class="form-control" placeholder="Enter offer Image" name="image">
                                    </div>
                                    <br>

                                    <div>
                                        <label for="regular-form-1" class="form-label">Validation</label>
                                        <input id="regular-form-1" type="date" class="form-control" placeholder="Enter offer validation" name="validation">
                                    </div>
                                    <br>
                                    <input type="submit" class="btn btn-primary w-24 mr-1 mb-2" name="btnSubmit" value="Submit">
                                </div>
                        </form>



                    </div>
                </div>
            </div>
            <!-- END: Input -->

        </div>
        <div class="intro-y col-span-12 lg:col-span-6">

        </div>
    </div>

    </div>
    <!-- END: Content -->
    </div>
    </div>
    <!-- BEGIN: JS Assets-->
    <script src="https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js"></script>
     <script src="dist/js/app.js"></script>
    <!-- END: JS Assets-->
</body>
</html>