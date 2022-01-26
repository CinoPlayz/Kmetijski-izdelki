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

if(isset($_POST['izbris'])){
    require("PovezavaZBazo.php");
    $tabelafilter = filter_input(INPUT_POST, 'tabela', FILTER_SANITIZE_STRING);

    $tabela = mysqli_real_escape_string($povezava, $tabelafilter);


    if($_POST['izbris'] == "DA"){

        $sql = "SHOW columns FROM $tabela;";

        $stolpec = "";

        $rezultat = mysqli_query($povezava, $sql);

        if($rezultat == true && mysqli_num_rows($rezultat) > 0){
            while($vrstica = mysqli_fetch_assoc($rezultat)){
                if($vrstica['Key'] == "PRI"){
                     $stolpec = $vrstica['Field'];
                } 
            }
        }

        

        $podatekfilter = filter_input(INPUT_POST, $stolpec, FILTER_SANITIZE_STRING);

        $podatek = mysqli_real_escape_string($povezava, $podatekfilter);

        //Parsiranje za pošiljanje, ker json_encode() ne deluje pravilno
        $jsonZaPoslat = "{ \"$stolpec\" : \"$podatek\"";
        
        $jsonZaPoslat .= "}";
       
        mysqli_close($povezava);


        //Dobimo URL za curl
        $povnaslov =  $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
        $urldel = str_replace("Izbris.php", "api/izbris.php", $povnaslov) . "?tabela=" . urlencode($tabela);;

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

            header("location: Izbris.php?tabela=$tabela&$stolpec=$podatek&napaka=$vrnjenosporocilo");
            exit;
        }
        else{
            unset($_SESSION['temp']);
            header("location: Branje.php?tabela=$tabela&uspeh=izbrisano");
            exit;
        }
    }
    else{
        mysqli_close($povezava);
        header("location: Branje.php?tabela=$tabela");
        exit;
    }
}


?>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Izbris</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="Izbris.css">
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

            <?php 
                require("PovezavaZBazo.php");

                $tabelafilter = filter_input(INPUT_GET, 'tabela', FILTER_SANITIZE_STRING);

                $tabela = mysqli_real_escape_string($povezava, $tabelafilter); 

                $stolpec = "";

                $sql = "SHOW columns FROM $tabela;";

                $rezultat = mysqli_query($povezava, $sql);

                if($rezultat == true && mysqli_num_rows($rezultat) > 0){
                    while($vrstica = mysqli_fetch_assoc($rezultat)){
                        if($vrstica['Key'] == "PRI"){
                            $stolpec = $vrstica['Field'];
                        }
                    }

                    if(!empty($stolpec)){
                        $podatekfilter = filter_input(INPUT_GET, $stolpec , FILTER_SANITIZE_STRING);

                        $podatek = mysqli_real_escape_string($povezava, $podatekfilter);
                    }
                    else{
                        mysqli_close($povezava);
                        header("location: Domov.php");
                        exit;  
                    }
                    
                }
                else{
                    mysqli_close($povezava);
                    header("location: Domov.php");
                    exit;
                }
            ?>

            <div class="vsebina">
                <div class="prepricani">Ali res želite izbrisati ta vnos?</div>
                <div>
                    <form action="Izbris.php" method="POST">
                        <input type="submit" value="DA" name="izbris">
                        <span style="padding-right:  3vw;"></span>
                        <input type="submit" value="NE" name="izbris">
                        <input type="hidden" value="<?php echo $tabela; ?>" name="tabela">
                        <input type="hidden" value="<?php echo $podatek; ?>" name="<?php echo $stolpec; ?>">
                    </form>
                </div>

                <?php 
                    if(isset($_GET['napaka'])){
                        $napaka = str_replace ( '%20', ' ', $_GET['napaka']);
                        echo "<div class='napaka'>$napaka</div>";                        

                    }
                
                ?>

                <div class="prepricani" style="color: red;">To bo izbrisalo naslednje podatke:</div>

            <?php 
                if(!empty($podatek)){                                

                    echo "<div class='tablediv'>";
                    echo "<div class='tabela'>". ucfirst($tabela) .":</div>";
                    echo "<table>";

                    $sql = "SHOW columns FROM $tabela;";

                    $rezultatStolpci = mysqli_query($povezava, $sql);

                    while($vrstica = mysqli_fetch_array($rezultatStolpci)){
                        echo "<th>" . str_replace("_", " ", $vrstica['Field'])  . "</th>";
                    }

                    if(is_numeric($podatek)){
                        $sql = "SELECT * FROM $tabela WHERE $stolpec=$podatek;";
                    }
                    else{
                        $sql = "SELECT * FROM $tabela WHERE $stolpec='$podatek';";
                    }


                    $rezultat = mysqli_query($povezava, $sql);

                    while($vrstica = mysqli_fetch_row($rezultat)){
                        echo "<tr>";

                        for($p = 0; $p < count($vrstica); $p++){
                            echo"<td>" . $vrstica[$p] . "</td>";
                        }
                        echo "</tr>";
                    }

                    echo "</table>";

                    echo "</div>";



                    $sql = "SELECT 
                        TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
                    FROM
                        INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE
                        REFERENCED_TABLE_SCHEMA = '$podatkovnabaza' AND
                        REFERENCED_TABLE_NAME = '$tabela' AND
                        REFERENCED_COLUMN_NAME = '$stolpec'";

                    $ForeignKeyTabeleAtribut = array();

                    $rezultat = mysqli_query($povezava, $sql);

                    if(mysqli_num_rows($rezultat) > 0){
                        while($vrstica = mysqli_fetch_assoc($rezultat)){
                            array_push($ForeignKeyTabeleAtribut, array("TABLE_NAME" => $vrstica['TABLE_NAME'], "COLUMN_NAME" => $vrstica['COLUMN_NAME']));
                        }

                        $sql = "";

                        $tabelainQuery = array();
                        for($i = 0; $i < count($ForeignKeyTabeleAtribut); $i++){

                            if(is_numeric($podatek)){
                                $sql = "SELECT * FROM ". $ForeignKeyTabeleAtribut[$i]['TABLE_NAME'] . " WHERE " . $ForeignKeyTabeleAtribut[$i]['COLUMN_NAME'] . "=$podatek;";
                            }
                            else{
                                $sql = "SELECT * FROM ". $ForeignKeyTabeleAtribut[$i]['TABLE_NAME'] . " WHERE " . $ForeignKeyTabeleAtribut[$i]['COLUMN_NAME'] . "='$podatek';";
                            }

                            array_push($tabelainQuery, array($ForeignKeyTabeleAtribut[$i]['TABLE_NAME'], $sql));
                            
                        }

                        for($i = 0; $i < count($tabelainQuery); $i++){
                            $tabelaIzpis = str_replace("_", " ", $tabelainQuery[$i][0]);
                            echo "<div class='tablediv'>";
                            echo "<div class='tabela'>". ucfirst($tabelaIzpis) .":</div>";
                            echo "<table>";

                            $sql = "SHOW columns FROM " . $tabelainQuery[$i][0] . ";";
                            $rezultatStolpci = mysqli_query($povezava, $sql);

                            while($vrstica = mysqli_fetch_array($rezultatStolpci)){
                                echo "<th>" . str_replace("_", " ", $vrstica['Field'])  . "</th>";
                            }

                            $rezultat = mysqli_query($povezava, $tabelainQuery[$i][1]);

                            while($vrstica = mysqli_fetch_row($rezultat)){
                                echo "<tr>";

                                for($p = 0; $p < count($vrstica); $p++){
                                    echo"<td>" . $vrstica[$p] . "</td>";
                                }
                                echo "</tr>";
                            }

                            echo "</table>";

                            echo "</div>";
                        }


                    }

                    mysqli_close($povezava);

                    }
                    else{
                        mysqli_close($povezava);
                        header("location: Domov.php");
                        exit;  
                    }
                
            

                
                ?>

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