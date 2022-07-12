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

        if(!empty($kljuc) ){

            //Dobi podatke za prenos
            $sql = "SELECT * FROM Prenosi WHERE Kljuc = '$kljuc' LIMIT 1";

            $rezultat = mysqli_query($povezava, $sql);

            if($rezultat == true && mysqli_num_rows($rezultat) > 0){

                $vrstica = mysqli_fetch_assoc($rezultat);

                //Usrvari spremenljivke z imenom datoteke in pot datoteke
                $imedatoteke = $vrstica['Ime_datoteke'];
                $datoteka = 'Ustvarjeni/'. $imedatoteke .'.xlsx';

                if($vrstica['Status_prenesenosti'] == 0){
                   
                    if(file_exists($datoteka)){

                        $sqlupdate = "UPDATE Prenosi SET Status_prenesenosti=1 WHERE Kljuc = '$kljuc'";
                        mysqli_query($povezava, $sqlupdate);

                        //odpre datoteko
                        $fp = fopen($datoteka, 'rb');

                        $razdeljeno = explode("_", $imedatoteke);

                        //pošlje headerje, za prenos
                        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                        header('Content-Disposition: attachment; filename="'. "Podatki ". $razdeljeno[1] ." - ". $razdeljeno[3] .".xlsx".'"');
                        header('Content-Transfer-Encoding: binary');
                        header("Content-Length: " . filesize($datoteka));
                        header('Expires: 0');


                        //Da v buffer in s tem omogoči da se datoteka prenese do konca preden se izbriše
                        fpassthru($fp);

                        //More bit exit drugače je xlsx corruptiran
                        unlink("$datoteka");
                        exit;
                    }    
                }
                else{
                    //Pogleda če obstaja datoteka in jo izbriše
                    if(file_exists($datoteka)){
                        unlink("$datoteka");                        
                    } 
                    
                    header("Location: RacuniXLSX.php");
                    exit;
                }

                
            }            
                    
        }
    } 

    header("Location: RacuniXLSX.php");
    exit;
?>
