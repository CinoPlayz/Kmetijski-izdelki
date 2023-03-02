<?php 
session_start();
if(!isset($_SESSION['UprIme']) && !isset($_SESSION['Pravila'])){
    header("location: ../Prijava.php");
    exit;
}

if($_SESSION['Pravila'] == "Admin"){
    header("location: ../Admin/DomovAdmin.php");    
    exit;
}

define('LahkoPovezava', TRUE);
require("../PovezavaZBazo.php");

if(isset($_POST['DatumOd']) && isset($_POST['DatumDo']) && isset($_POST['kako_sestavit'])){   
    
    //Dobi podatke za ustvarjanje datoteke
    $naprej = true;

    $DatumOdfilter = htmlspecialchars($_POST['DatumOd']);
    $DatumOd = mysqli_real_escape_string($povezava, $DatumOdfilter);

    if(empty($DatumOd)){
        mysqli_close($povezava);
        header("Location: RacuniXLSX.php?napaka=1");
        exit;
    }

    $_SESSION['temp']['DatumOd'] = $DatumOd;

    $DatumDofilter = htmlspecialchars($_POST['DatumDo']);
    $DatumDo = mysqli_real_escape_string($povezava, $DatumDofilter);    

    if(empty($DatumDo)){
        mysqli_close($povezava);
        header("Location: RacuniXLSX.php?napaka=2");
        exit;
    }

    $_SESSION['temp']['DatumDo'] = $DatumDo;    

    $Kakosestavitfilter = htmlspecialchars($_POST['kako_sestavit']);
    $Kakosestavit = mysqli_real_escape_string($povezava, $Kakosestavitfilter);

    if(empty($Kakosestavit)){
        mysqli_close($povezava);
        header("Location: RacuniXLSX.php?napaka=3");
        exit;
    }

    $_SESSION['temp']['Kakosestavit'] = $Kakosestavit;

    require_once("XLSX/xlsxwriter.class.php");
    $zadnjanapaka = NULL;

    //Razdeli kako sestaviti datoteke glede na poslane podatke
    if($Kakosestavit == "podatumu"){

        //Dobimo podatke za ustvarjanje datoteke
        $stmt = $povezava->prepare("SELECT p.Datum_Prodaje, s.Priimek, s.Ime, s.Naslov, s.Posta, po.Kraj, 
        i.Izdelek, i.Ekolosko, p.Koliko, i.Merska_enota, i.Cena FROM Prodaja p 
        INNER JOIN Stranka s ON p.id_stranke = s.id_stranke 
        INNER JOIN Izdelek i ON p.Izdelek = i.Izdelek 
        LEFT JOIN Posta po ON s.Posta = po.Postana_stevilka 
        WHERE p.Datum_Prodaje >= ? AND p.Datum_Prodaje <= CONCAT(?, ' 23:59:59')  ORDER BY p.Datum_Prodaje DESC");
        $stmt->bind_param("ss", $DatumOd, $DatumDo);
        $stmt->execute();
        $rezultat = $stmt->get_result();

        $podatki = array();  

        if($rezultat == true && mysqli_num_rows($rezultat) > 0){    
            
            while($vrstica = mysqli_fetch_assoc($rezultat)){
                //Pogleda če je ekolosko, če je dodano za izdelek zraven - ekolosko
                if($vrstica['Ekolosko'] == "NE"){
                    array_push($podatki, array($vrstica['Datum_Prodaje'], $vrstica['Priimek'], $vrstica['Ime'], $vrstica['Naslov'], $vrstica['Posta'], $vrstica['Kraj'], $vrstica['Izdelek'], $vrstica['Koliko'], $vrstica['Merska_enota'], $vrstica['Cena']));
                }
                else{
                    array_push($podatki, array($vrstica['Datum_Prodaje'], $vrstica['Priimek'], $vrstica['Ime'], $vrstica['Naslov'], $vrstica['Posta'], $vrstica['Kraj'], $vrstica['Izdelek'] . " - Ekološko", $vrstica['Koliko'], $vrstica['Merska_enota'], $vrstica['Cena']));
                }
            }

            
            //Ustvari XLSX datoteko
            $glava = array(
                'Datum Prodaje' => 'DD.MM.YYYY',
                'Priimek' => 'string',
                'Ime' => 'string',
                'Naslov' => 'string',
                'Poštna številka' => 'integer',
                'Kraj' => 'string',
                'Izdelek' => 'string',
                'Količina' => 'integer',
                'Merska Enota' => 'string',
                'Cena' => 'price'
            );

            
        
            $writer = new XLSXWriter();
            $writer->writeSheetHeader('Prodaja', $glava );

            foreach($podatki as $vrstica){ 
                $writer->writeSheetRow('Prodaja', $vrstica);
            }
            
            //Ustvari svoj error hendler, tako da se napake lažje prikažejo
            set_error_handler("ErrorHandler");

            //Usrvari random besedo za ime datoteke in kljuc
            $vsecrke = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

            $random = '';

            for($i = 0; $i < 16; $i++){
                $indeks = random_int(1, strlen($vsecrke) - 1);

                $random .= $vsecrke[$indeks];
            }

            $imedatoteke = "Racun_" . $DatumOd . "_-_" . $DatumDo . "_(" . $random . ")_-_Datum";

            $writer->writeToFile('Ustvarjeni/' . $imedatoteke . '.xlsx');

            //Če je kaka napaka pri ustvarjanju jo izbiše
            if(isset($GLOBALS['napaka_global']) && !empty($GLOBALS['napaka_global'])){    

                if(strpos($GLOBALS['napaka_global'], "Renaming temporary file failed: Permission denied")){
                    unset($GLOBALS['napaka_global']);
                    header("Location: RacuniXLSX.php?napaka=4");
                    exit;
                }
                else{
                    $zapis = $GLOBALS['napaka_global'];
                    unset($GLOBALS['napaka_global']);
                    header("Location: RacuniXLSX.php?napaka=Neka&zapis=". $zapis);
                    exit; 
                }
            }
            else{                              
                //Vstavi kljuc, ime datoteke in uporabniško ime v tabelo Prenosi
                $stmt = $povezava->prepare("INSERT INTO Prenosi(Kljuc, Ime_datoteke, Prenesel) VALUES(?,?,?)");
                $stmt->bind_param("sss", $random, $imedatoteke, $_SESSION['UprIme']);
                $stmt->execute();
    
                mysqli_close($povezava);
                //Redirecta nazaj in da kljuc v session
                header("Location: RacuniXLSX.php");
                $_SESSION['kljuc'] = $random;
                exit; 
            }            
           
        }
        else{
            $_SESSION['temp']['DatumOd'] = $DatumOd;
            $_SESSION['temp']['DatumDo'] = $DatumDo;
            $_SESSION['temp']['Kakosestavit'] = $Kakosestavit;
            header("Location: RacuniXLSX.php?napaka=5");
            exit;
        }

        


    }
    else{

        //Sql za dobivanje podatkov po izdelku skupaj
        $stmt = $povezava->prepare("SELECT s.Priimek, s.Ime, s.Naslov, s.Posta, po.Kraj, i.Izdelek, i.Ekolosko, SUM(p.Koliko) AS 'Skupaj_kolicina', i.Merska_enota, i.Cena  FROM Prodaja p 
        INNER JOIN Stranka s ON p.id_stranke = s.id_stranke 
        INNER JOIN Izdelek i ON p.Izdelek = i.Izdelek 
        LEFT JOIN Posta po ON s.Posta = po.Postana_stevilka
        WHERE p.Datum_Prodaje >= ? AND p.Datum_Prodaje <= CONCAT(?, ' 23:59:59') 
        GROUP BY s.id_stranke, i.Izdelek
        ORDER BY s.Priimek, s.Ime DESC;");
        $stmt->bind_param("ss", $DatumOd, $DatumDo);
        $stmt->execute();

        $rezultat = $stmt->get_result();

        $podatki = array();       

        if($rezultat == true && mysqli_num_rows($rezultat) > 0){

            while($vrstica = mysqli_fetch_assoc($rezultat)){
                //Pogleda če je ekolosko, če je dodano za izdelek zraven - ekolosko
                if($vrstica['Ekolosko'] == "NE"){
                    array_push($podatki, array($vrstica['Priimek'], $vrstica['Ime'], $vrstica['Naslov'], $vrstica['Posta'], $vrstica['Kraj'], $vrstica['Izdelek'], $vrstica['Skupaj_kolicina'], $vrstica['Merska_enota'], $vrstica['Cena']));
                }
                else{
                    array_push($podatki, array($vrstica['Priimek'], $vrstica['Ime'], $vrstica['Naslov'], $vrstica['Posta'], $vrstica['Kraj'], $vrstica['Izdelek'] . " - Ekološko", $vrstica['Skupaj_kolicina'], $vrstica['Merska_enota'], $vrstica['Cena']));
                }
            } 

            //Ustvari XLSX datoteko
            $glava = array(
                'Priimek' => 'string',
                'Ime' => 'string',
                'Naslov' => 'string',
                'Poštna številka' => 'integer',
                'Kraj' => 'string',
                'Izdelek' => 'string',
                'Količina Skupaj' => 'integer',
                'Merska Enota' => 'string',
                'Cena' => 'price'
            );

            
        
            $writer = new XLSXWriter();
            $writer->writeSheetHeader('Prodaja', $glava );

            foreach($podatki as $vrstica){ 
                $writer->writeSheetRow('Prodaja', $vrstica);
            }
            
            //Ustvari svoj error hendler, tako da se napake lažje prikažejo
            set_error_handler("ErrorHandler");

            //Usrvari random besedo za ime datoteke in kljuc
            $vsecrke = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

            $random = '';

            for($i = 0; $i < 16; $i++){
                $indeks = random_int(1, strlen($vsecrke) - 1);

                $random .= $vsecrke[$indeks];
            }

            $imedatoteke = "Racun_" . $DatumOd . "_-_" . $DatumDo . "_(" . $random . ")_-_Izdelki";

            $writer->writeToFile('Ustvarjeni/' . $imedatoteke . '.xlsx');

            //Če je kaka napaka pri ustvarjanju jo izbiše
            if(isset($GLOBALS['napaka_global']) && !empty($GLOBALS['napaka_global'])){    

                if(strpos($GLOBALS['napaka_global'], "Renaming temporary file failed: Permission denied")){
                    unset($GLOBALS['napaka_global']);
                    header("Location: RacuniXLSX.php?napaka=4");
                    exit;
                }
                else{
                    $zapis = $GLOBALS['napaka_global'];
                    unset($GLOBALS['napaka_global']);
                    header("Location: RacuniXLSX.php?napaka=Neka&zapis=". $zapis);
                    exit; 
                }
            }
            else{                              
                //Vstavi kljuc, ime datoteke in uporabniško ime v tabelo Prenosi
                $stmt = $povezava->prepare("INSERT INTO Prenosi(Kljuc, Ime_datoteke, Prenesel) VALUES(?,?,?)");
                $stmt->bind_param("sss", $random, $imedatoteke, $_SESSION['UprIme']);
                $stmt->execute();

                mysqli_close($povezava);
                //Redirecta nazaj in da kljuc v session
                header("Location: RacuniXLSX.php");
                $_SESSION['kljuc'] = $random;
                exit; 
            }       


        }
        else{
            $_SESSION['temp']['DatumOd'] = $DatumOd;
            $_SESSION['temp']['DatumDo'] = $DatumDo;
            $_SESSION['temp']['Kakosestavit'] = $Kakosestavit;
            header("Location: RacuniXLSX.php?napaka=5");
            exit;
        }

        
    }

         
    


    


}

function ErrorHandler($errno, $errstr, $errfile, $errline) {
    //Shrani napakao v globalno spremenljivko (drugače ne gredo podatki iz funkcije)
    $GLOBALS['napaka_global'] = $errstr;
}

?>

<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Sestavljanje Excel datoteke</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="RacuniXLSX.css">
        <script type="text/javascript" src="../DataTables/moment.js"></script>
    </head>
    <body>
        <div class="vse">
            <div class="glava">
                <div>
                    <a href="../Domov.php"><img src="../Slike/nutrition.svg" width="40px" height="40px"></a>
                </div>

                <div class="flexfill"></div>

                <div class="odjava">
                    <span class="odjava"><a href="../Odjava.php" class="odjavaA">Odjava</a></span>
                </div>
            </div>
            
            <div class="menu">
                <div class="menuItem"><a class="menuItemA" href="../Domov.php">Domov</a></div>
            </div>

            <div class="vsebina">
                <div class="racuninaslov">Sestavljanje Excel datoteke</div>
                <div class="formdiv">
                    <form method="post" action="RacuniXLSX.php">
                        <div class="divdatuma">
                            <div class="divdatum">
                                <div>Od:</div>
                                <div><input type="date" name="DatumOd" id="DatumOd" <?php if(isset($_SESSION['temp']['DatumOd'])) echo "value='" . $_SESSION['temp']['DatumOd'] . "'" ?>></div>
                            </div>

                            <div class="divdatum">
                                <div>Do:</div>
                                <div><input type="date" name="DatumDo" id="DatumDo" <?php if(isset($_SESSION['temp']['DatumDo'])) echo "value='" . $_SESSION['temp']['DatumDo'] . "'" ?>></div>
                            </div>
                        </div>

                        <div class="kako_sestaviti_div">
                            <div>Kako sestaviti datoteko:</div>
                            <div>
                                <select id="kako_sestavit" name="kako_sestavit">
                                    <?php 
                                    if(isset($_SESSION['temp']['Kakosestavit'])){
                                        if($_SESSION['temp']['Kakosestavit'] == "podatumu"){
                                            echo "<option value='skupaj'>Izdelki skupaj</option>
                                            <option value='podatumu' selected>Po datumu</option>";
                                        }
                                        else{
                                            echo "<option value='skupaj'>Izdelki skupaj</option>
                                            <option value='podatumu'>Po datumu</option>";
                                        }
                                    }
                                    else{
                                        echo "<option value='skupaj'>Izdelki skupaj</option>
                                        <option value='podatumu'>Po datumu</option>";
                                    }
                                    ?>
                                    
                                </select>
                            </div>
                        </div>

                        <?php 
                            if(isset($_GET['napaka'])){
                                switch($_GET['napaka']){
                                    case 1:
                                        echo "<div class='napaka'>Vpiši veljani Datum od</div>";
                                        break;
                                    case 2:
                                        echo "<div class='napaka'>Vpiši veljani Datum do</div>";
                                        break;
                                    case 3:
                                        echo "<div class='napaka'>Izberi ustrezni način sestavitve računa</div>";
                                        break;
                                    case 4:
                                        echo "<div class='napaka'>Nemorem ustvariti datoteke (mogoče je odprta)</div>";
                                        break;
                                    case 5:
                                        echo "<div class='napaka'>Ni podatkov za to časovno obdobje</div>";
                                        break;
                                    case "Neka":
                                        echo "<div class='napaka'>"; 
                                        if(isset($_GET['zapis']) && !empty($_GET['zapis'])) echo htmlspecialchars($_GET['zapis'], ENT_QUOTES) ."</div>";
                                        break;
                                    default:
                                        echo "<div class='napaka'></div>";
                                        break;
                                }
                                
                            }
                        
                        ?>

                        <div class="submitdiv">
                            <input type="submit" value="Sestavi">
                        </div>

                    </form>
                </div>
                <?php 
                    if(isset( $_SESSION['kljuc'])){
                        echo "<div class='prenostext'>Datoteka je sestavljena</div>";
                        
                        echo "<div class='prenos'>Kliknite <a id='prenosA' href='prenos.php?kljuc=". $_SESSION['kljuc'] ."'>tukaj</a>, če se ne prenese avtomatsko</div>";

                        echo "<script> var prenos = setTimeout(function () {
                            window.location = document.getElementById('prenosA').href;
                        }, 1000);</script>";
                        unset($_SESSION['kljuc']);
                        unset($_SESSION['temp']);
                    }
                
                ?>
            </div>

            <script>
                var datumzacetek = moment().subtract(1, 'months').startOf('month').format('YYYY-MM-DD');
                var datumkonec = moment().subtract(1, 'months').endOf('month').format('YYYY-MM-DD');
                
                if(!document.getElementById("DatumOd").value){
                    document.getElementById("DatumOd").value = datumzacetek;
                }
                
                if(!document.getElementById("DatumDo").value){
                    document.getElementById("DatumDo").value = datumkonec;
                }
                

            </script>

            <div class="noga">
                <div>
                    <img src="../Slike/nutrition.svg" width="80px" height="80px">
                </div>

                <div class="nogaMenu">
                    <div class="nogaMenuItem"><a href="../Domov.php" class="nogaMenuItemA">Domov</a></div>
                </div>
            </div>
        </div>
    </body>
</html>