<?php
include 'session.php';
include 'connection.php';

$idVal = $_GET["offer_id"];
$sql = "select * from tbl_offer where id=$idVal";

$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

$name_val = $row['offer_name'];
$desc_val = $row['description'];
$image_val = $row['image'];
$validation_val = $row['validation'];

if (isset($_POST["btnSubmit"])) {
    //print_r($_POST);die; 
    $Oname_val = $_POST["oname"];
    $Odesc_val = $_POST["odesc"];
    $Ovalidation_val = $_POST["ovalidation"];

    if ($_FILES["offer_Image"]["name"] != '') {

        $exe = pathinfo($_FILES["offer_Image"]["name"], PATHINFO_EXTENSION);


        $filename = time() . random_int(1111, 9999) . "." . $exe;



        move_uploaded_file($_FILES["offer_Image"]["tmp_name"], "./uploads/" . $filename);
        unlink("./uploads/" . $image_val);
    } else {

        $filename = $image_val;
    }

    $sql = "update tbl_offer set offer_name='$Oname_val', description='$Odesc_val', image='$filename', validation='$Ovalidation_val' where id=$idVal";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        echo "<script>alert('record successfully updated');</script>";
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
    <!-- BEGIN: CSS Assets-->
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
                    Edit Offer
                </h2>
            </div>
            <div class="grid grid-cols-12 gap-6 mt-5">
                <div class="intro-y col-span-12 lg:col-span-12">
                    <!-- BEGIN: Input -->
                    <div class="intro-y box">
                        <div class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60 dark:border-darkmode-400">
                        </div>
                        <form method="POST" enctype="multipart/form-data">
                            <div id="input" class="p-5">
                                <div class="preview">
                                    <div>
                                        <label for="regular-form-1" class="form-label">Offer Name </label>
                                        <input id="regular-form-1" type="text" class="form-control" placeholder="Enter Offer Name" name="oname" <?php echo "value='$name_val'"; ?> required />
                                    </div>
                                    <br>

                                    <div>
                                        <label for="regular-form-1" class="form-label">Offer Description</label>
                                        <input id="regular-form-1" type="text" class="form-control" placeholder="Enter Offer Description" name="odesc" <?php echo "value='$desc_val'"; ?> required />
                                    </div>
                                    <br>

                                    <div>
                                        <label for="regular-form-1" class="form-label">Offer Validation</label>
                                        <input id="regular-form-1" type="date" class="form-control" placeholder="Enter Offer Validation" name="ovalidation" <?php echo "value='$validation_val'"; ?> required />
                                    </div>
                                    <br>

                                    <div>
                                        <label for="regular-form-1" class="form-label">Image</label>
                                        <img src="uploads/<?php echo $image_val; ?>" height="100px" width="100px" />
                                        <input id="regular-form-1" type="File" class="form-control"
                                            placeholder="Enter Image" name="offer_Image">

                                    </div>

                                    <input type="submit" class="btn btn-primary w-24 mr-1 mb-2" name="btnSubmit" value="Submit">
                                </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
        <div class="intro-y col-span-12 lg:col-span-6">

        </div>
    </div>
    </div>
    <!-- END: Content -->
    </div>
    <!-- BEGIN: JS Assets-->
    <script src="https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js"></script>
    <script src="dist/js/app.js"></script>
    <!-- END: JS Assets-->
</body>
</html>