<?php
include 'session.php';
include 'connection.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=, initial-scale=1.0">
    <title>View Offer</title>
</head>

<body>
    <!DOCTYPE html>
    <html lang="en" class="light">
    <!-- BEGIN: Head -->

    <head>
        <title>View Offer</title>
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
                    <h2 class="text-lg font-medium mr-auto"></h2>
                </div>
                <div class="grid grid-cols-12 gap-6 mt-5">
                    <div class="intro-y col-span-12 lg:col-span-12">
                        <!-- BEGIN: Basic Table -->
                        <div class="intro-y box">
                            <div class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60">
                                <h2 class="font-medium text-base mr-auto">
                                    Offer

                                </h2>
                                <div class="form-check form-switch w-full sm:w-auto sm:ml-auto mt-3 sm:mt-0">

                                    <input id="show-example-1" data-target="#basic-table" class="show-code form-check-input mr-0 ml-3" type="checkbox">
                                </div>
                            </div>
                            <div class="p-5" id="basic-table">
                                <div class="preview">
                                    <div class="overflow-x-auto">
                                        <table class="table">
                                            <thead>
                                                <tr>

                                                    <th class="whitespace-nowrap">Sr No.</th>
                                                    <th class="whitespace-nowrap">Name</th>
                                                    <th class="whitespace-nowrap">Description</th>
                                                    <th class="whitespace-nowrap">Validation</th>
                                                    <th class="whitespace-nowrap">Image</th>
                                                    <th class="whitespace-nowrap">Operation</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $query = "select * from tbl_offer";
                                                $result = mysqli_query($conn, $query);
                                                $count = 1;
                                                while ($row = mysqli_fetch_assoc($result)) {
                                                ?>
                                                    <tr>
                                                        <td> <?php echo $count++; ?></td>
                                                        <td> <?php echo $row["offer_name"]; ?></td>
                                                        <td> <?php echo $row["description"]; ?></td>
                                                        <td> <?php echo date('d-m-Y',strtotime($row["validation"])); ?></td>
                                                        <td>
                                                            <img src="uploads/<?php echo $row["image"]; ?>" height="100" width="200">

                                                        </td>
                                                        <td>
                                                            <input type="Submit" class="btn btn-primary w-24 mr-1 mb-2" onclick="handleEdit(<?php echo $row['id']; ?>)" name="edit"
                                                                value="Edit" style="background-color:darkblue;">
                                                            <input type="Submit" class="btn btn-primary w-24 mr-1 mb-2" onclick="handleDelete(<?php echo $row['id']; ?>)" name="delete"
                                                                value="Delete" style="background-color:red;">

                                                        </td>
                                                    </tr>
                                                <?php
                                                }
                                                ?>
                                            </tbody>



                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- END: Basic Table -->

                    </div>


                    <!-- BEGIN: JS Assets-->
                    <script src="https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js"></script>
                    <script src="dist/js/app.js"></script>
                    <!-- END: JS Assets-->
    </body>
    <script>
        function handleEdit(id) {

            alert(id)
            window.location.href = "editOffer.php?offer_id=" + id;
        }
    </script>

    <script>
        function handleDelete(id) {

            alert(id)
            window.location.href = "deleteOffer.php?offer_id=" + id;
        }
    </script>
</html>