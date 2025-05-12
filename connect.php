
<?php
$conn = mysqli_connect("localhost", "root","","supermarket");
 if($conn){
    echo "";

 }
 else {
    die("DB not connected".mysqli_connect_error());
 }
?>