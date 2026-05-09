<?php
include 'session.php';
    include 'connection.php';

    $idVal= $_GET["offer_id"];

    $sql_product="select * from tbl_offer where id=$idVal";
    
    $row=mysqli_query($conn,$sql_product);
    $result=mysqli_fetch_assoc($row);

    $deletedImg=$result["image"];
    unlink("./uploads/".$deletedImg);


    $sql="delete from tbl_offer where id=$idVal";

    $result=mysqli_query($conn,$sql);
    if($result){

        echo "<script>
        window.location.href='viewOffer.php'
        </script>";
    }




?>