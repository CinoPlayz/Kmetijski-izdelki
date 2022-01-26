<?php 
session_start();
if(!isset($_SESSION['UprIme']) && !isset($_SESSION['Pravila'])){
    header("location: Prijava.php");
    exit;
}

if($_SESSION['Pravila'] == "Admin"){
    header("location: Domov.php");
    exit;
}

if(isset($_POST['tabela'])){

    require("PovezavaZBazo.php");

    $tabelafilter = filter_input(INPUT_POST, 'tabela', FILTER_SANITIZE_STRING);

    $tabela = mysqli_real_escape_string($povezava, $tabelafilter);

    if($tabela == "Uporabnik"){
        mysqli_close($povezava);
        header("location: Domov.php");
        exit;
    }

    $sql = "SHOW columns FROM $tabela;";

    $rezultat = mysqli_query($povezava, $sql);

    $tabele = array();
    if($rezultat == true && mysqli_num_rows($rezultat) > 0){
        while($vrstica = mysqli_fetch_assoc($rezultat)){

            if($vrstica['Field'] != "TokenWeb" && $vrstica['Field'] != "TokenAndroid"){
                if($vrstica['Key'] == "PRI" && $vrstica['Extra'] == "auto_increment"){
                    array_push($tabele, $vrstica['Field']);   
                    $primaryKey = $vrstica['Field'];             
                }
                else if($vrstica['Key'] == "PRI" && strpos($vrstica['Type'], 'varchar') !== false){
                    array_push($tabele, $vrstica['Field']);
                    array_push($tabele, $vrstica['Field']."Nov");
                    $primaryKey = $vrstica['Field'];
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
        header("location: Domov.php");
        exit;
    }

    $podatkiZaPoslat = array();

    for($i = 0; $i < count($tabele); $i++){
        $preskoci = false;

        $podatekpost = filter_input(INPUT_POST, $tabele[$i], FILTER_SANITIZE_STRING);

        $podatekpostSQL = mysqli_real_escape_string($povezava, $podatekpost);

        if(isset($primaryKey) &&$primaryKey == $tabele[$i]){
            $primaryPodatek = $podatekpostSQL;
        }

        if($tabela == "Prodaja" && $tabele[$i] == "Uporabnisko_ime"){
            array_push($podatkiZaPoslat, array($tabele[$i] => $_SESSION['UprIme']));
            $preskoci = true;
        }

        if($preskoci === false){
            if(empty($podatekpostSQL)){

                if($tabele[$i] == "Geslo"){
                    
                    $_SESSION['temp'][$tabele[$i]] = "";
                    array_push($podatkiZaPoslat, array($tabele[$i] => ""));
                }
                else{
                    mysqli_close($povezava);

                    if(isset($primaryPodatek)){
                        --$i;
                        header("location: Spreminjanje.php?tabela=$tabela&$primaryKey=$primaryPodatek&napaka=$i");                        
                    }
                    else{
                        header("location: Spreminjanje.php?tabela=$tabela&$primaryKey=$primaryPodatek&napaka=$i");                        
                    }
                    
                    exit;
                }
               
            }
            else{              

                if($tabela == "Nacrtovani_prevzemi" && $tabele[$i] == "id_stranke"){
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
    $urldel = str_replace("Spreminjanje.php", "api/spreminjanje.php", $povnaslov) . "?tabela=" . urlencode($tabela);;

    //URL spremenimo tako da presledge zamenjamo z %20 (rabi bit encodan)
    $url = str_replace ( ' ', '%20', $urldel);
    
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

        header("location: Spreminjanje.php?tabela=$tabela&$primaryKey=$primaryPodatek&napaka=$vrnjenosporocilo");
        exit;
    }
    else{
        unset($_SESSION['temp']);
        header("location: Branje.php?tabela=$tabela&uspeh=spremenjeno");
        exit;
    }

}

?>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Spreminjanje</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="Spreminjanje.css">
    </head>
    <body>
        <div class="vse">
            <div class="glava">
                <div>
                    <a href="Domov.php"><img src="Slike/nutrition.svg" width="40px" height="40px"></a>
                </div>

                <div class="flexfill"></div>

                <div class="odjava">
                    <span class="odjava"><a href="Odjava.php" class="odjavaA">Odjava</a></span>
                </div>
            </div>
            
            <div class="menu">
                <div class="menuItem"><a class="menuItemA" href="Domov.php">Domov</a></div>
            </div>

            <div class="vsebina">
                <div>
                    <div class="formdiv">
                        <form method="post" action="Spreminjanje.php">                            
                            <div class="formvnosi">

                                <?php 
                                    require("PovezavaZBazo.php");

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

                                        //Dobimo Primary key za tabelo in preverimo GET vnos, ki je vpisan v URL glede na ime tega primary keya, ki smo ga dobil
                                            
                                        while($vrstica = mysqli_fetch_assoc($rezultat)){
                                            if($vrstica['Key'] == "PRI"){

                                                if(strpos($vrstica['Type'], 'varchar') !== false){
                                                    $primaryKey = array($vrstica['Field'], "string");
                                                }
                                                else{
                                                    $primaryKey = array($vrstica['Field']);
                                                }
                                                
                                            }
                                        }

                                        $primaryfilter = filter_input(INPUT_GET, $primaryKey[0], FILTER_SANITIZE_STRING);

                                        $primary = mysqli_real_escape_string($povezava, $primaryfilter);

                                        if(isset($primaryKey[1])){
                                            $sql = "SELECT * FROM $tabela WHERE  " . $primaryKey[0]. "='$primary';";
                                        }
                                        else{
                                            $sql = "SELECT * FROM $tabela WHERE  " . $primaryKey[0]. "=$primary;";
                                        }

                                        $rezultatpodatki = mysqli_query($povezava, $sql);

                                        //Vpišemo podatke v array podatke, ki so zapisani za tisti Primary key (pač tisto vrstico, kjer je ta primary key)

                                        $VrsticaPodatki = array();

                                        while($vrstica = mysqli_fetch_assoc($rezultatpodatki)){

                                            $VrsticaPodatki= $vrstica;
                                        }

                                        //Ponovno izvedemo sql stavek, da dobimo atribute v tabeli
                                        $sql = "SHOW columns FROM $tabela;";

                                        $rezultat = mysqli_query($povezava, $sql);

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
                                                    echo "<input type='hidden' name='" . $vrstica['Field'] . "' value='". $_GET[$vrstica['Field']] ."'>";
                                                }
                                                //Če je primary key string
                                                else if($primaryKey[0] == $vrstica['Field'] && $primaryKey[1] == "string"){
                                                    echo "<input type='hidden' name='" . $vrstica['Field'] . "' value='". $_GET[$vrstica['Field']] ."'>";
                                                    echo "<div class='formvnosItem'>
                                                            <div class='vnosNaslov'>". str_replace("_", " ", $vrstica['Field']).":</div>
                                                            <input type='text' name='". $vrstica['Field'] ."Nov' class='ipPB' value='". $VrsticaPodatki[$vrstica['Field']] ."'>
                                                            </div>";
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
                                                    else if(isset($VrsticaPodatki[$vrstica['Field']])){

                                                        $DatumObjekt = new DateTime($VrsticaPodatki[$vrstica['Field']]);
                                                        $Datum = $DatumObjekt->format('Y-m-d');

                                                        echo "<div class='formvnosItem' style='display:flex; flex-direction: column; align-items: center;'>
                                                        <div class='vnosNaslov'>". str_replace("_", " ", $vrstica['Field']).":</div>
                                                        <input type='date' name='". $vrstica['Field'] ."' class='ipPB' value='". $Datum  ."'>
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
                                                    else if(isset($VrsticaPodatki[$vrstica['Field']])){

                                                        $DatumObjekt = new DateTime($VrsticaPodatki[$vrstica['Field']]);
                                                        $Datum = $DatumObjekt->format('Y-m-d');
                                                        
                                                        echo "<div class='formvnosItem' style='display:flex; flex-direction: column; align-items: center;'>
                                                        <div class='vnosNaslov'>". str_replace("_", " ", $vrstica['Field']).":</div>
                                                        <input type='date' name='". $vrstica['Field'] ."' class='ipPB' value='".  $Datum ."'>
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

                                                                //Preveri, če je vpisan podatek o Izdelku v vrstici, ki je iz tabele Prodaja
                                                                if(isset($VrsticaPodatki[$vrstica['Field']])){
                                                                    $obstaja = "da";
                                                                }
                                                    
                                                        while($vrsticaIzdelek = mysqli_fetch_assoc($rezultatIzdelek)){

                                                            if($obstaja == "da"){
                                                                //Preveri, če sta enaki vrednosti iz vrstice (Glede na primary key) in vsemi izdelki, če je bo selected kot default
                                                                if($VrsticaPodatki[$vrstica['Field']] == $vrsticaIzdelek["Izdelek"]){
                                                                    echo "<option selected='selected' value='" . $vrsticaIzdelek["Izdelek"] . "'>". $vrsticaIzdelek["Izdelek"] ."</option>";
                                                                }
                                                                else{
                                                                    echo "<option value='" . $vrsticaIzdelek["Izdelek"] . "'>". $vrsticaIzdelek["Izdelek"] ."</option>"; 
                                                                }
                                                            }
                                                            else{
                                                                echo "<option value='" . $vrsticaIzdelek["Izdelek"] . "'>". $vrsticaIzdelek["Izdelek"] ."</option>";
                                                            }
                                                            
                                                            
                                
                                                            
                                                        }
                                                        echo "</select></div>";
                                                    }
                                                }
                                                //Če je Stranka kot vnos vendar, če je foreign key da dropdown za izbiro stranke
                                                else if($vrstica['Key'] == "MUL" && $vrstica['Field'] == "id_stranke" ){   

                                                    //Dobi stranko glede na Primary key(id_prodaje)
                                                    $strankaGledeVpis = array("Priimek" => "", "Ime" => "", "id_stranke" => "");

                                                    if(isset($VrsticaPodatki[$vrstica['Field']])){
                                                        $sql = "SELECT Priimek, Ime, id_stranke FROM Stranka WHERE id_stranke=". $VrsticaPodatki[$vrstica['Field']]. "";

                                                        $rezultatStrankaGledeVpis = mysqli_query($povezava, $sql);

                                                        $strankaGledeVpis = mysqli_fetch_assoc($rezultatStrankaGledeVpis);
                                                    }
                                                    
                                                    


                                                    $sql = "SELECT Priimek, Ime, id_stranke FROM Stranka";

                                                    $rezultatStranka = mysqli_query($povezava, $sql);

                                                    if(mysqli_num_rows($rezultatStranka) > 0){
                                                        echo "<div class='formvnosItem'>";
                                                        echo "<div class='vnosNaslov'>Stranka:</div>";
                                                        echo "<input list='Stranke' name='". $vrstica['Field'] ."' value='" . $strankaGledeVpis['Priimek'] . " " . $strankaGledeVpis['Ime'] . " - " . $strankaGledeVpis['id_stranke'] . "'/>";
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

                                                                        if($omejitev == $VrsticaPodatki[$vrstica['Field']]){
                                                                            echo "<option selected='selected' value='$omejitev'>$omejitev</option>";
                                                                        }
                                                                        else{
                                                                            echo "<option value='$omejitev'>$omejitev</option>";
                                                                        }
                                                                       
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
                                                        else if(isset($VrsticaPodatki[$vrstica['Field']]) && $primaryKey[0] == $vrstica['Field'] && $primaryKey[1] == "string"){
                                                            
                                                        }
                                                        else if(isset($VrsticaPodatki[$vrstica['Field']])){
                                                            echo "<div class='formvnosItem'>
                                                            <div class='vnosNaslov'>". str_replace("_", " ", $vrstica['Field']).":</div>
                                                            <input type='text' name='". $vrstica['Field'] ."' class='ipPB' value='". $VrsticaPodatki[$vrstica['Field']] ."'>
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
                                                echo "<div class='napaka'>Vpišite veljavno ". str_replace("_", " ", $tabele[$_GET['napaka']]) ."</div>";
                                                
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
                    <img src="Slike/nutrition.svg" width="80px" height="80px">
                </div>

                <div class="nogaMenu">
                    <div class="nogaMenuItem"><a href="Domov.php" class="nogaMenuItemA">Domov</a></div>
                </div>
            </div>
        </div>
    </body>
</html>