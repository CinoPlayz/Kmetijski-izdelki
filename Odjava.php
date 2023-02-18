<?php 
session_start();

if(isset($_SESSION['Token'])){
    define('LahkoPovezava', TRUE);
    require("PovezavaZBazo.php");

    $stmt = $povezava->prepare("UPDATE Uporabnik SET TokenWeb = NULL WHERE TokenWeb=?;");
    $token = hash("sha256", $_SESSION['Token']);
    $stmt->bind_param("s", $token); 
    $stmt->execute();
}

session_unset();
session_destroy();

header("location: Prijava.php");
exit;
?>