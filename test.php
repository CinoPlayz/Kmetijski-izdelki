<?php 

require("PovezavaZBazo.php");

$sql = "SELECT concat('ALTER TABLE ', TABLE_NAME, ' DROP FOREIGN KEY ', CONSTRAINT_NAME, ';') 
FROM information_schema.key_column_usage 
WHERE CONSTRAINT_SCHEMA = 'Kmetijski_Izdelki' 
AND referenced_table_name IS NOT NULL;";

$rez = mysqli_query($povezava, $sql);

$sqlalter = "";
while($row = mysqli_fetch_row($rez)){
    $sqlalter .= $row[0];
}

$sql = "SHOW TABLE STATUS FROM Kmetijski_Izdelki";

$rez = mysqli_query($povezava, $sql);

$sqltabele = "";
while($row = mysqli_fetch_row($rez)){
    $sqltabele .= "DROP TABLE IF EXISTS " . $row[0] . ";";
}

print_r($sqltabele);

