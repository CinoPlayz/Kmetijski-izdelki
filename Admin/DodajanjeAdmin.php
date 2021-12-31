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
                array_push($tabele, $vrstica['Field']);
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

        $podatekpost = filter_input(INPUT_POST, $tabele[$i], FILTER_SANITIZE_STRING);

        $podatekpostSQL = mysqli_real_escape_string($povezava, $podatekpost);

        if(empty($podatekpostSQL)){
            mysqli_close($povezava);
            header("location: DodajanjeAdmin.php?tabela=$tabela&napaka=$i");
            exit;
        }
        else{
            $_SESSION['temp'][$tabele[$i]] = $podatekpostSQL;
            array_push($podatkiZaPoslat, array($tabele[$i] => $podatekpostSQL));
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
    $urldel = str_replace("Admin/DodajanjeAdmin.php", "api/ustvarjanje.php", $povnaslov) . "?tabela=" . urlencode($tabela);;

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

        header("location: DodajanjeAdmin.php?tabela=$tabela&napaka=$vrnjenosporocilo");
        exit;
    }
    else{
        unset($_SESSION['temp']);
        header("location: BranjeAdmin.php?tabela=$tabela");
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
                                        while($vrstica = mysqli_fetch_assoc($rezultat)){
                                            
                                            if($vrstica['Field'] != "TokenWeb" && $vrstica['Field'] != "TokenAndroid"){
                                                if($vrstica['Field'] == "Geslo"){
                                                    echo "<div class='formvnosItem'>
                                                    <div class='vnosNaslov'>". str_replace("_", " ", $vrstica['Field']).":</div>
                                                    <input type='password' name='". $vrstica['Field'] ."' class='ipPB'>
                                                    </div>";
                                                }
                                                else{

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
                                                
                                                array_push($tabele, $vrstica['Field']);
                                            }
                                           
                                        }

                                        echo "<input type='hidden' name='tabela' value='$tabela'>";
                                    } 
                                    
                                    mysqli_close($povezava);
                                ?>                                

                                <div class="formvnosItem">
                                    <input type="submit">
                                </div> 

                                <?php 
                                    if(isset($_GET['napaka'])){

                                        if(isset($tabele[$_GET['napaka']])){
                                            echo "<div class='napaka'>Vpišite veljaveno ". str_replace("_", " ", $tabele[$_GET['napaka']]) ."</div>";
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