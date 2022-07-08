<?php 
session_start();
if(!isset($_SESSION['UprIme']) && !isset($_SESSION['Pravila'])){
    header("location: ../Prijava.php");
    exit;
}

    if(isset($_GET['kljuc']) ){

        define('LahkoPovezava', TRUE);
        require("../PovezavaZBazo.php");

        $kljucfilter = htmlspecialchars($_GET['kljuc']);
        $kljuc = mysqli_real_escape_string($povezava, $kljucfilter);
        //TODO poprav da bo pravilno deloval, da se datoteka prenese, izbriše in nato redirecta
        if(!empty($kljuc) ){

            

            $sql = "SELECT * FROM Prenosi WHERE Kljuc = '$kljuc' LIMIT 1";

            $rezultat = mysqli_query($povezava, $sql);

            if($rezultat == true && mysqli_num_rows($rezultat) > 0){

                $vrstica = mysqli_fetch_assoc($rezultat);

                if($vrstica['Status_prenesenosti'] == 0){
                    $imedatoteke = $vrstica['Ime_datoteke'];

                    $datoteka = 'Ustvarjeni/'. $imedatoteke .'.xlsx';

                    if(file_exists($datoteka)){

                        //odpre datoteko
                        $fp = fopen($datoteka, 'rb');

                        $razdeljeno = explode("_", $imedatoteke);

                        //echo 'Content-Disposition: attachment; filename="'. "Podatki ". $razdeljeno[1] ." - ". $razdeljeno[3] .".xlsx".'"';

                        //pošlje headerje, za prenos
                        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                        header('Content-Disposition: attachment; filename="'. "Podatki ". $razdeljeno[1] ." - ". $razdeljeno[3] .".xlsx".'"');
                        header('Content-Transfer-Encoding: binary');
                        header("Content-Length: " . filesize($datoteka));
                        header('Expires: 0');


                        //Da v buffer in s tem omogoči da se datoteka prenese do konca preden se izbriše
                        fpassthru($fp);
                        //More bit exit drugače je xlsx corruptiran
                        unlink("Ustvarjeni/$imedatoteke.xlsx");
                        exit;
                        //TODO Spremeni Status_prenesenosti na 1 
                    }    
                }
                //TODO če je 1 mora se izbrisat oz. preverit če je izbrisana.

                
            }
            
                    
        }
    } 
?>
