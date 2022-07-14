<?php 
session_start();

if(isset($_SESSION['Token'])){
    define('LahkoPovezava', TRUE);
    require("PovezavaZBazo.php");

    $sql = "UPDATE Uporabnik SET TokenWeb = NULL WHERE TokenWeb='". hash("sha256", $_SESSION['Token']) . "'";

   mysqli_query($povezava, $sql);
}


session_unset();
session_destroy();



header("location: Prijava.php");
exit;
?>