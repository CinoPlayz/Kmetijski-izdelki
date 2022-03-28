<?php 
session_start();
if(!isset($_SESSION['UprIme']) && !isset($_SESSION['Pravila'])){
    header("location: ../Prijava.php");
    exit;
}

if($_SESSION['Pravila'] != "Admin"){
    header("location: ../Domov.php");
    exit;
}

if(isset($_POST['tabela'])){

    require("../PovezavaZBazo.php");

    $tabelafilter = filter_input(INPUT_POST, 'tabela', FILTER_SANITIZE_STRING);

    $tabela = mysqli_real_escape_string($povezava, $tabelafilter);

    $sql = "SHOW columns FROM $tabela;";

    $rezultat = mysqli_query($povezava, $sql);

    $tabele = array();
    if($rezultat == true && mysqli_num_rows($rezultat) > 0){
        while($vrstica = mysqli_fetch_assoc($rezultat)){

            if($vrstica['Field'] != "TokenWeb" && $vrstica['Field'] != "TokenAndroid"){
                if($vrstica['Key'] == "PRI" && $vrstica['Extra'] == "auto_increment"){
                                       
                }
                /*else if($vrstica['Key'] == "MUL" && $vrstica['Field'] == "Uporabnisko_ime"){

                }*/
                else{
                    array_push($tabele, $vrstica['Field']);
                }
                
            }
            
        }
    }
    else{
        mysqli_close($povezava);
        header("location: DomovAdmin.php");
        exit;
    }

    $podatkiZaPoslat = array();

    for($i = 0; $i < count($tabele); $i++){
        $preskoci = false;

        $podatekpost = filter_input(INPUT_POST, $tabele[$i], FILTER_SANITIZE_STRING);

        $podatekpostSQL = mysqli_real_escape_string($povezava, $podatekpost);

        if($tabela == "Prodaja" && $tabele[$i] == "Uporabnisko_ime"){
            array_push($podatkiZaPoslat, array($tabele[$i] => $_SESSION['UprIme']));
            $preskoci = true;
        }

        if($preskoci === false){
            if(empty($podatekpostSQL) && $tabela != "Nacrtovani_Prevzemi" && $tabele[$i] != "Cas_Enkrat"){
                mysqli_close($povezava);
                header("location: DodajanjeAdmin.php?tabela=$tabela&napaka=$i");
                exit;
            }
            else{              

                if($tabela == "Nacrtovani_Prevzemi" && $tabele[$i] == "id_stranke"){
                    $idNahaja = strpos($podatekpostSQL, " - ");
                    $id = substr($podatekpostSQL, ($idNahaja+3));
                    array_push($podatkiZaPoslat, array($tabele[$i] => $id));

                }
                else if($tabela == "Prodaja" && $tabele[$i] == "id_stranke"){
                    $idNahaja = strpos($podatekpostSQL, " - ");
                    $id = substr($podatekpostSQL, ($idNahaja+3));
                    array_push($podatkiZaPoslat, array($tabele[$i] => $id));
                }            
                else{
                    $_SESSION['temp'][$tabele[$i]] = $podatekpostSQL;
                    array_push($podatkiZaPoslat, array($tabele[$i] => $podatekpostSQL));
                }  
                        
            }
        } 
    }

    //Parsiranje za pošiljanje, ker json_encode() ne deluje pravilno
    $jsonZaPoslat = "{";

    $kolikoPodatkov = count($podatkiZaPoslat);
    
    for($i = 0; $i < $kolikoPodatkov; $i++){
        $keys = array_keys($podatkiZaPoslat[$i]);

        if($i == ($kolikoPodatkov - 1)){
            $jsonZaPoslat .= "\"" . $keys[0]. "\" : \"" . $podatkiZaPoslat[$i][$keys[0]] . "\"";
        }
        else{
            $jsonZaPoslat .= "\"" . $keys[0]. "\" : \"" . $podatkiZaPoslat[$i][$keys[0]] . "\",";
        }
    }
    
    
    $jsonZaPoslat .= "}";
   
    mysqli_close($povezava);

    //Dobimo URL za curl
    $povnaslov =  $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
    $urldel = str_replace("Admin/DodajanjeAdmin.php", "api/ustvarjanje.php", $povnaslov) . "?tabela=" . urlencode($tabela);

    //URL spremenimo tako da presledge zamenjamo z %20 (rabi bit encodan)
    $urlneki = str_replace ( ' ', '%20', $urldel);

    //Preveri če uporablja http oz. https
    if( isset($_SERVER['HTTPS'] ) ) {
        $url = "https://" . $urlneki;
    }
    else{
        $url = "http://" . $urlneki;
    }

    
    
    //Začne se curl
    $curl = curl_init();
    //Nastavimo URL za pošiljanje
    curl_setopt($curl, CURLOPT_URL, $url);

    //Nastavimo, da naj vrne kot string
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    //Nastavimo, da vrne tudi Header
    curl_setopt($curl, CURLOPT_HEADER, 1);

    //Nastavimo, da pošlje kot POST (neki takega)
    curl_setopt($curl, CURLOPT_POST, 1);

    //Nastavimo podatke, ki jih pošlje
    curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonZaPoslat);

    //Nastavimo headerje
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $_SESSION['Token'],
        'Content-Type: application/json;charset=UTF-8',
    ));

    $rezultat = curl_exec($curl);

    $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $headers = substr($rezultat, 0, $header_size);
    $body = substr($rezultat, $header_size);

    curl_close($curl);

    if(isset($body) && !empty($body)){

        $vrnjeno = json_decode($body, true);
        $vrnjenosporocilo = $vrnjeno["sporocilo"];

        header("location: DodajanjeAdmin.php?tabela=$tabela&napaka=$vrnjenosporocilo");
        exit;
    }
    else{
        unset($_SESSION['temp']);
        header("location: BranjeAdmin.php?tabela=$tabela&uspeh=dodano");
        exit;
    }

}


?>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Dodajanje Admin</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="DodajanjeAdmin.css">
    </head>
    <body>
        <div class="vse">
            <div class="glava">
                <div>
                    <a href="Domov.php"><img src="../Slike/nutrition.svg" width="40px" height="40px"></a>
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
                <div>
                    <div class="formdiv">
                        <form method="post" action="DodajanjeAdmin.php">                            
                            <div class="formvnosi">

                                <?php 
                                    require("../PovezavaZBazo.php");

                                    $tabelafilter = filter_input(INPUT_GET, 'tabela', FILTER_SANITIZE_STRING);

                                    $tabela = mysqli_real_escape_string($povezava, $tabelafilter);

                                    $sql = "SHOW columns FROM $tabela;";

                                    $rezultat = mysqli_query($povezava, $sql);

                                    $tabele = array();

                                    if($rezultat == true && mysqli_num_rows($rezultat) > 0){
                                        $sql = "SELECT * FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = '$podatkovnabaza' AND TABLE_NAME = '$tabela'";

                                        $rezultat2 = mysqli_query($povezava, $sql);

                                        if($rezultat2 == true && mysqli_num_rows($rezultat2) > 0){

                                            $omejitve = array();
                                            while($vrstica = mysqli_fetch_assoc($rezultat2)){

                                                if(strpos($vrstica['CHECK_CLAUSE'], " in ") !== false){
                                                    $stolpecPrvaPozicija = strpos($vrstica['CHECK_CLAUSE'], "`");
                                                    $stolpecPrvaPozicija++;
                        
                                                    $stolpecDrugaPozicija = strpos($vrstica['CHECK_CLAUSE'], "`", $stolpecPrvaPozicija);
                                                    $stolpecDrugaPozicija--;
    
                                                    $stolpec = substr($vrstica['CHECK_CLAUSE'], $stolpecPrvaPozicija, $stolpecDrugaPozicija);
        
                                                    $omejitveZVejico =  MedDvemaStringa($vrstica['CHECK_CLAUSE'], "(", ")");

                                                    $omejitveArray = explode(",", $omejitveZVejico[0]);

                                                    array_push($omejitve, array($stolpec => $omejitveArray));
                                                }

                                               
                                                
                                            }
                                        }

                                        while($vrstica = mysqli_fetch_assoc($rezultat)){
                                            
                                            if($vrstica['Field'] != "TokenWeb" && $vrstica['Field'] != "TokenAndroid"){
                                                //Če je Geslo za vnos spremeni input type v password
                                                if($vrstica['Field'] == "Geslo"){
                                                    echo "<div class='formvnosItem'>
                                                    <div class='vnosNaslov'>". str_replace("_", " ", $vrstica['Field']).":</div>
                                                    <input type='password' name='". $vrstica['Field'] ."' class='ipPB'>
                                                    </div>";
                                                }
                                                else if($vrstica['Key'] == "PRI" && $vrstica['Extra'] == "auto_increment" ){
                                                    //Nej ne prikaže
                                                }
                                                else if($vrstica['Key'] == "MUL" && $vrstica['Field'] == "Uporabnisko_ime" && $tabela = "Prodaja"){
                                                    //Nej ne prikaže (Za tabelo Prodaja)
                                                } 
                                                //Če je Datum_Prodaje za vnos prikaže vnos z izbero datuma
                                                else if($vrstica['Field'] == "Datum_Prodaje" && $tabela = "Prodaja"){
                                                    if(isset($_SESSION['temp'][$vrstica['Field']])){
                                                        echo "<div class='formvnosItem' style='display:flex; flex-direction: column; align-items: center;'>
                                                        <div class='vnosNaslov'>". str_replace("_", " ", $vrstica['Field']).":</div>
                                                        <input type='date' name='". $vrstica['Field'] ."' class='ipPB' value='". $_SESSION['temp'][$vrstica['Field']] ."'>
                                                        </div>";
                                                    }
                                                    else{
                                                        echo "<div class='formvnosItem' style='display:flex; flex-direction: column; align-items: center;'>
                                                        <div class='vnosNaslov'>". str_replace("_", " ", $vrstica['Field']).":</div>
                                                        <input type='date' name='". $vrstica['Field'] ."' class='ipPB'>
                                                        </div>";
                                                    }
                                                }
                                                //Če je Datum_Vpisa za vnos prikaže vnos z izbero datuma
                                                else if($vrstica['Field'] == "Datum_Vpisa" && $tabela = "Prodaja"){
                                                    if(isset($_SESSION['temp'][$vrstica['Field']])){
                                                        echo "<div class='formvnosItem' style='display:flex; flex-direction: column; align-items: center;'>
                                                        <div class='vnosNaslov'>". str_replace("_", " ", $vrstica['Field']).":</div>
                                                        <input type='date' name='". $vrstica['Field'] ."' class='ipPB' value='". $_SESSION['temp'][$vrstica['Field']] ."'>
                                                        </div>";
                                                    }
                                                    else{
                                                        echo "<div class='formvnosItem' style='display:flex; flex-direction: column; align-items: center;'>
                                                        <div class='vnosNaslov'>". str_replace("_", " ", $vrstica['Field']).":</div>
                                                        <input type='date' name='". $vrstica['Field'] ."' class='ipPB'>
                                                        </div>";
                                                    }
                                                }
                                                //Če je Cas_Enkrat za vnos prikaže vnos z izbero datuma
                                                else if($vrstica['Field'] == "Cas_Enkrat" && $tabela = "Nacrtovani_Prevzemi"){
                                                    if(isset($_SESSION['temp'][$vrstica['Field']])){
                                                        echo "<div class='formvnosItem' style='display:flex; flex-direction: column; align-items: center;'>
                                                        <div class='vnosNaslov'>". str_replace("_", " ", $vrstica['Field']).":</div>
                                                        <input type='date' name='". $vrstica['Field'] ."' class='ipPB' value='". $_SESSION['temp'][$vrstica['Field']] ."'>
                                                        </div>";
                                                    }
                                                    else{
                                                        echo "<div class='formvnosItem' style='display:flex; flex-direction: column; align-items: center;'>
                                                        <div class='vnosNaslov'>". str_replace("_", " ", $vrstica['Field']).":</div>
                                                        <input type='date' name='". $vrstica['Field'] ."' class='ipPB'>
                                                        </div>";
                                                    }
                                                }
                                                //Če je Izdelek kot vnos vendar, če je foreign key da dropdown za izbiro
                                                else if($vrstica['Key'] == "MUL" && $vrstica['Field'] == "Izdelek" ){
                                                    $sql = "SELECT Izdelek FROM Izdelek";

                                                    $rezultatIzdelek = mysqli_query($povezava, $sql);

                                                    if(mysqli_num_rows($rezultatIzdelek) > 0){
                                                        echo "<div class='formvnosItem' style='display:flex; flex-direction: column; align-items: center;'>
                                                                <div class='vnosNaslov'>". str_replace("_", " ", $vrstica['Field']).":</div>
                                                                <select name='". $vrstica['Field'] ."' class='select'>";
                                                    
                                                        while($vrsticaIzdelek = mysqli_fetch_assoc($rezultatIzdelek)){
                                                            echo "<option value='" . $vrsticaIzdelek["Izdelek"] . "'>". $vrsticaIzdelek["Izdelek"] ."</option>";
                                
                                                            
                                                        }
                                                        echo "</select></div>";
                                                    }
                                                }
                                                //Če je Stranka kot vnos vendar, če je foreign key da dropdown za izbiro stranke
                                                else if($vrstica['Key'] == "MUL" && $vrstica['Field'] == "id_stranke" ){                                                    

                                                    $sql = "SELECT Priimek, Ime, id_stranke FROM Stranka";

                                                    $rezultatStranka = mysqli_query($povezava, $sql);

                                                    if(mysqli_num_rows($rezultatStranka) > 0){
                                                        echo "<div class='formvnosItem'>";
                                                        echo "<div class='vnosNaslov'>Stranka:</div>";
                                                        echo "<input list='Stranke' name='". $vrstica['Field'] ."' />";
                                                        echo "<datalist id='Stranke'>";

                                                        while($vrsticaStranka = mysqli_fetch_assoc($rezultatStranka)){
                                                            echo "<option value='" . $vrsticaStranka['Priimek'] . " " . $vrsticaStranka['Ime'] . " - " . $vrsticaStranka['id_stranke'] . "'>";
                                                        }
                                                        echo "</datalist>";
                                                        echo "</div>";
                                                    }

                                                }
                                                else{
                                                    $nadaljuj = "da";
                                                    //Preveri če ima kolumn omejitve, če jih ima jih da v dropdown za izbiro
                                                    if(isset($omejitve)){
                                                        for($i = 0; $i < count($omejitve); $i++){
                                                            if(isset($omejitve[$i][$vrstica['Field']])){

                                                                echo "<div class='formvnosItem' style='display:flex; flex-direction: column; align-items: center;'>
                                                                <div class='vnosNaslov'>". str_replace("_", " ", $vrstica['Field']).":</div>
                                                                <select name='". $vrstica['Field'] ."' class='select'>";
                                                                    foreach($omejitve[$i][$vrstica['Field']] as $omejitev){
                                                                        echo "<option value='$omejitev'>$omejitev</option>";
                                                                    }
                                                                    

                                                                echo "</select></div>";
                                                                $nadaljuj = "ne";
                                                            }
                                                        }
                                                    }
                                                    
                                                    //Če ni nič od navedenega vzgoraj izvede to spodaj
                                                    if($nadaljuj != "ne"){                                                    
                                                        if(isset($_SESSION['temp'][$vrstica['Field']])){
                                                            echo "<div class='formvnosItem'>
                                                            <div class='vnosNaslov'>". str_replace("_", " ", $vrstica['Field']).":</div>
                                                            <input type='text' name='". $vrstica['Field'] ."' class='ipPB' value='". $_SESSION['temp'][$vrstica['Field']] ."'>
                                                            </div>";
                                                        }
                                                        else{
                                                            echo "<div class='formvnosItem'>
                                                            <div class='vnosNaslov'>". str_replace("_", " ", $vrstica['Field']).":</div>
                                                            <input type='text' name='". $vrstica['Field'] ."' class='ipPB'>
                                                            </div>";
                                                        }
                                                    }
                                                    
                                                }
                                                
                                                if($vrstica['Key'] == "PRI" && $vrstica['Extra'] == "auto_increment" ){
                                                    //Naj ne da v tabele
                                                }
                                                else if($vrstica['Key'] == "MUL" && $vrstica['Field'] == "Uporabnisko_ime" && $tabela = "Prodaja"){
                                                    //Naj ne da v tabele
                                                } 
                                                else{
                                                    array_push($tabele, $vrstica['Field']);
                                                }
                                                
                                            }
                                           
                                        }

                                        echo "<input type='hidden' name='tabela' value='$tabela'>";
                                    }
                                    
                                    mysqli_close($povezava);

                                    function MedDvemaStringa($string, $zacetek, $konec){
                                        foreach (explode($zacetek, $string) as $key => $value) {
                                            if(strpos($value, $konec) !== FALSE){
                                                $rezultat[] = str_replace("'", "", substr($value, 0, strpos($value, $konec)));
                                            }
                                        }
                                        return $rezultat;
                                    }
                                ?>                                

                                <div class="formvnosItem">
                                    <input type="submit">
                                </div> 

                                <?php 
                                    if(isset($_GET['napaka'])){

                                        if(isset($tabele[$_GET['napaka']])){
                                            if($tabela = "Prodaja" && $tabele[$_GET['napaka']] == "id_stranke"){
                                                echo "<div class='napaka'>Vpišite veljaveno Stranko</div>";
                                            }
                                            else{
                                                echo "<div class='napaka'>Vpišite veljaveno ". str_replace("_", " ", $tabele[$_GET['napaka']]) ."</div>";
                                            }
                                        }
                                        else{
                                            $napaka = str_replace ( '%20', ' ', $_GET['napaka']);
                                            echo "<div class='napaka'>$napaka</div>";
                                        }

                                    }
                                
                                ?>
                            </div>
                        </form>
                    </div>
                
                
                </div>
            </div>

            <div class="noga">
                <div>
                    <img src="../Slike/nutrition.svg" width="80px" height="80px">
                </div>

                <div class="nogaMenu">
                    <div class="nogaMenuItem"><a href="Domov.php" class="nogaMenuItemA">Domov</a></div>
                </div>
            </div>
        </div>
    </body>
</html>