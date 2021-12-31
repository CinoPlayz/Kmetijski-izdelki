<?php 
require("PovezavaZBazo.php");

$sql = "SELECT * FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = '$podatkovnabaza' AND TABLE_NAME = '$tabela'";

$rezultat = mysqli_query($povezava, $sql);

while($row = mysqli_fetch_assoc($rezultat)){
    print_r($row);
}

echo "konec";