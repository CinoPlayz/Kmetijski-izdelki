<?php 
session_start();
if(!isset($_SESSION['UprIme']) && !isset($_SESSION['Pravila'])){
    header("location: Prijava.php");
    exit;
}

if($_SESSION['Pravila'] == "Admin"){
    header("location: Admin/DomovAdmin.php");    
    exit;
}

require("../PovezavaZBazo.php");

if(isset($_POST['DatumOd']) && isset($_POST['DatumDo']) && isset($_POST['kako_sestavit'])){    
    
    $naprej = true;

    $DatumOdfilter = htmlspecialchars($_POST['DatumOd']);
    $DatumOd = mysqli_real_escape_string($povezava, $DatumOdfilter);

    if(empty($DatumOd)){
        mysqli_close($povezava);
        header("Location: RacuniXLSX.php?napaka=1");
        exit;
    }

    $DatumDofilter = htmlspecialchars($_POST['DatumDo']);
    $DatumDo = mysqli_real_escape_string($povezava, $DatumDofilter);    

    if(empty($DatumDo)){
        mysqli_close($povezava);
        header("Location: RacuniXLSX.php?napaka=2");
        exit;
    }
    

    $Kakosestavitfilter = htmlspecialchars($_POST['kako_sestavit']);
    $Kakosestavit = mysqli_real_escape_string($povezava, $Kakosestavitfilter);

    if(empty($Kakosestavit)){
        mysqli_close($povezava);
        header("Location: RacuniXLSX.php?napaka=3");
        exit;
    }


    $sql = "SELECT p.Datum_Prodaje, s.Priimek, s.Ime, i.Izdelek, i.Ekolosko, p.Koliko, i.Merska_enota, i.Cena  FROM Prodaja p INNER JOIN Stranka s ON p.id_stranke = s.id_stranke INNER JOIN Izdelek i ON p.Izdelek = i.Izdelek WHERE p.Datum_Prodaje >= '$DatumOd' AND p.Datum_Prodaje < '$DatumDo'  ORDER BY p.Datum_Prodaje DESC";

    $rezultat = mysqli_query($povezava, $sql);

    $podatki = array();       

    if($rezultat == true && mysqli_num_rows($rezultat) > 0){

        require_once("XLSX/xlsxwriter.class.php");
        $zadnjanapaka = NULL;

        if($Kakosestavit == "podatumu"){

            while($vrstica = mysqli_fetch_assoc($rezultat)){
                if($vrstica['Ekolosko'] == "NE"){
                    array_push($podatki, array($vrstica['Datum_Prodaje'], $vrstica['Priimek'], $vrstica['Ime'], $vrstica['Izdelek'], $vrstica['Koliko'], $vrstica['Merska_enota'], $vrstica['Cena']));
                }
                else{
                    array_push($podatki, array($vrstica['Datum_Prodaje'], $vrstica['Priimek'], $vrstica['Ime'], $vrstica['Izdelek'] . " - Ekološko", $vrstica['Koliko'], $vrstica['Merska_enota'], $vrstica['Cena']));
                }
            }

            mysqli_close($povezava);

            $glava = array(
                'Datum Prodaje' => 'DD.MM.YYYY',
                'Priimek' => 'string',
                'Ime' => 'string',
                'Izdelek' => 'string',
                'Koliko' => 'integer',
                'Merska Enota' => 'string',
                'Cena' => 'euro'
            );

            
        
            $writer = new XLSXWriter();
            $writer->writeSheetHeader('Prodaja', $glava );

            foreach($podatki as $vrstica){ 
                $writer->writeSheetRow('Prodaja', $vrstica);
            }
            
            set_error_handler("ErrorHandler");

            $writer->writeToFile('Ustvarjeni/Racuni.xlsx');

            if(isset($GLOBALS['napaka_global']) && !empty($GLOBALS['napaka_global'])){    

                if(strpos($GLOBALS['napaka_global'], "Renaming temporary file failed: Permission denied")){
                    unset($GLOBALS['napaka_global']);
                    header("Location: RacuniXLSX.php?napaka=4");
                    exit;
                }
                else{

                    //TODO https://stackoverflow.com/questions/48340007/delay-unlink-after-header
                    $zapis = $GLOBALS['napaka_global'];
                    unset($GLOBALS['napaka_global']);
                    header("Location: RacuniXLSX.php?napaka=Neka&zapis=". $zapis);
                    exit; 
                }
            }
            else{
                header("Location: Ustvarjeni/Racuni.xlsx");
                unlink("Ustvarjeni/Racuni.xlsx");
                exit; 
            }
            


        }
        else{
            
        }

    }

    


}

function ErrorHandler($errno, $errstr, $errfile, $errline) {
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
                                <div><input type="date" name="DatumOd" id="DatumOd"></div>
                            </div>

                            <div class="divdatum">
                                <div>Do:</div>
                                <div><input type="date" name="DatumDo" id="DatumDo"></div>
                            </div>
                        </div>

                        <div class="kako_sestaviti_div">
                            <div>Kako sestaviti račun:</div>
                            <div>
                                <select id="kako_sestavit" name="kako_sestavit">
                                    <option value="skupaj">Izdelki skupaj</option>
                                    <option value="podatumu">Po datumu</option>
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
                                    case "Neka":
                                        echo "<div class='napaka'>"; 
                                        if(isset($_GET['zapis']) && !empty($_GET['zapis'])) echo $_GET['zapis'] ."</div>";
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
            </div>

            <script>
                var datumzacetek = moment().subtract(1, 'months').startOf('month').format('YYYY-MM-DD');
                var datumkonec = moment().subtract(1, 'months').endOf('month').format('YYYY-MM-DD');

                document.getElementById("DatumOd").value = datumzacetek;
                document.getElementById("DatumDo").value = datumkonec;

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