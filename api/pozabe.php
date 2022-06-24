<?php

header("Content-Type: application/json; charset=UTF-8");

if (isset($_SERVER['Authorization'])) {
    $headers = trim($_SERVER["Authorization"]);
}
else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx
    $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
}
elseif (function_exists('apache_request_headers')) { //Apache, ter drugi
    $requestHeaders = apache_request_headers();
    $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));

    if (isset($requestHeaders['Authorization'])) {
        $headers = trim($requestHeaders['Authorization']);
    }
}

if(empty($headers)){
    http_response_code(401);
    exit;
}

require("../PovezavaZBazo.php");

$headersfilterSQL = mysqli_real_escape_string($povezava, $headers);
$headersfilter = htmlspecialchars($headersfilterSQL, ENT_QUOTES);

$token = str_replace("Bearer ", "", $headersfilter);


$sql = "SELECT * FROM Uporabnik WHERE TokenWeb='". hash("sha256", $token) . "' OR TokenAndroid='". hash("sha256", $token) . "'";

$rezultat = mysqli_query($povezava, $sql);

if(mysqli_num_rows($rezultat) > 0){

    $tabela = "Prodaja";

    $upr = mysqli_fetch_assoc($rezultat);

    //Dobimo podatke od katerega datuma naprej naj pozabe napiše
    $podatki = json_decode(file_get_contents("php://input"));

    if(isset($podatki->Datum_Zacetek)){

        $podatkifilterSQL = mysqli_real_escape_string($povezava, $podatki->Datum_Zacetek);
            
        $podatkifilter = htmlspecialchars($podatkifilterSQL, ENT_QUOTES);

        if(!empty($podatkifilter)){      

            //Ustvarimo datum objekt kolikonazajzacetek
            $datumobjekt = date_create($podatkifilter);
    
            $kolikonazajzacetek = date_format($datumobjekt, 'Y-m-d 00:00:00');
    
            //Ustvarimo datum objekt kolikonazajkonec
            $datumobjekt = new DateTime($kolikonazajzacetek);
                    
            $novcas = date_time_set($datumobjekt, 23, 59, 59);
            $novdatum = date_add($novcas, date_interval_create_from_date_string('6 days'));
            
            $kolikonazajkonec = date_format($novdatum, 'Y-m-d H:i:s');        
            
            //Načtrovani prevzemi, ki so vpisani v tabeli prodaja
            $sqlProdaja = "SELECT p.id_prodaje, p.id_stranke, p.Izdelek, DATE_FORMAT(p.Datum_Prodaje,'%Y-%m-%d 00:00:00') AS Datum FROM Prodaja p 
            WHERE WEEKDAY(p.Datum_Prodaje) IN (SELECT CASE Dan WHEN 'Ponedeljek' THEN '0'
            WHEN 'Torek' THEN '1'
            WHEN 'Sreda' THEN '2'
            WHEN 'Cetrtek' THEN '3'
            WHEN 'Petek' THEN '4'
            WHEN 'Sobota' THEN '5'
            WHEN 'Nedelja' THEN '6'
            ELSE '1'
            END AS Dan FROM Nacrtovani_Prevzemi  WHERE Nacrtovani_Prevzemi.id_stranke = p.id_stranke) AND p.Datum_Prodaje BETWEEN '$kolikonazajzacetek' AND '$kolikonazajkonec';";
     
            $rezultatProdaja = mysqli_query($povezava, $sqlProdaja);

            $prodaja = array();

            if(mysqli_num_rows($rezultatProdaja) > 0){
                while($vrstica = mysqli_fetch_assoc($rezultatProdaja)){
                    array_push($prodaja, array("id_stranke" => $vrstica['id_stranke'], "Izdelek" => $vrstica['Izdelek'], "Datum" => $vrstica['Datum']));
                }
            }

            //Načrtovani prevzemi glede na datum, ki je vpisan. Najprej ustvari temp podatkovne baze z datumi katere se nanašajo na slovenski napis ("Ponedeljek", "Torek"...)            
            $sqlNacrtovaniPrevzemi = "SET @datum = CONVERT('$kolikonazajzacetek', DATETIME);

            CREATE TEMPORARY TABLE temp1 AS SELECT n.id_nacrtovani_prevzem, n.id_stranke, n.Kolicina, n.Izdelek, n.Dan, n.Cas_Enkrat, @datum AS 'Datum' FROM Nacrtovani_Prevzemi  n WHERE n.Dan = (SELECT CASE WEEKDAY(@datum) 
                WHEN '0' THEN 'Ponedeljek'
                WHEN '1' THEN 'Torek'
                WHEN '2' THEN 'Sreda'
                WHEN '3' THEN 'Cetrtek'
                WHEN '4' THEN 'Petek'
                WHEN '5' THEN 'Sobota'
                WHEN '6' THEN 'Nedelja'
                ELSE 'Ponedeljek'
                END AS Dan FROM Nacrtovani_Prevzemi LIMIT 1);
            
                SET @datum = DATE_ADD(@datum, INTERVAL 1 DAY);
                
                CREATE TEMPORARY TABLE temp2 AS SELECT n.id_nacrtovani_prevzem, n.id_stranke, n.Kolicina, n.Izdelek, n.Dan, n.Cas_Enkrat, @datum AS 'Datum' FROM Nacrtovani_Prevzemi  n WHERE n.Dan = (SELECT CASE WEEKDAY(@datum) 
                WHEN '0' THEN 'Ponedeljek'
                WHEN '1' THEN 'Torek'
                WHEN '2' THEN 'Sreda'
                WHEN '3' THEN 'Cetrtek'
                WHEN '4' THEN 'Petek'
                WHEN '5' THEN 'Sobota'
                WHEN '6' THEN 'Nedelja'
                ELSE 'Ponedeljek'
                END AS Dan FROM Nacrtovani_Prevzemi  LIMIT 1);
            
                SET @datum = DATE_ADD(@datum, INTERVAL 1 DAY);
                
                CREATE TEMPORARY TABLE temp3 AS SELECT n.id_nacrtovani_prevzem, n.id_stranke, n.Kolicina, n.Izdelek, n.Dan, n.Cas_Enkrat, @datum AS 'Datum' FROM Nacrtovani_Prevzemi  n WHERE n.Dan = (SELECT CASE WEEKDAY(@datum) 
                WHEN '0' THEN 'Ponedeljek'
                WHEN '1' THEN 'Torek'
                WHEN '2' THEN 'Sreda'
                WHEN '3' THEN 'Cetrtek'
                WHEN '4' THEN 'Petek'
                WHEN '5' THEN 'Sobota'
                WHEN '6' THEN 'Nedelja'
                ELSE 'Ponedeljek'
                END AS Dan FROM Nacrtovani_Prevzemi  LIMIT 1);
            
                SET @datum = DATE_ADD(@datum, INTERVAL 1 DAY);
                
                CREATE TEMPORARY TABLE temp4 AS SELECT n.id_nacrtovani_prevzem, n.id_stranke, n.Kolicina, n.Izdelek, n.Dan, n.Cas_Enkrat, @datum AS 'Datum' FROM Nacrtovani_Prevzemi  n WHERE n.Dan = (SELECT CASE WEEKDAY(@datum) 
                WHEN '0' THEN 'Ponedeljek'
                WHEN '1' THEN 'Torek'
                WHEN '2' THEN 'Sreda'
                WHEN '3' THEN 'Cetrtek'
                WHEN '4' THEN 'Petek'
                WHEN '5' THEN 'Sobota'
                WHEN '6' THEN 'Nedelja'
                ELSE 'Ponedeljek'
                END AS Dan FROM Nacrtovani_Prevzemi  LIMIT 1);
            
                SET @datum = DATE_ADD(@datum, INTERVAL 1 DAY);
                
                CREATE TEMPORARY TABLE temp5 AS SELECT n.id_nacrtovani_prevzem, n.id_stranke, n.Kolicina, n.Izdelek, n.Dan, n.Cas_Enkrat, @datum AS 'Datum' FROM Nacrtovani_Prevzemi  n WHERE n.Dan = (SELECT CASE WEEKDAY(@datum) 
                WHEN '0' THEN 'Ponedeljek'
                WHEN '1' THEN 'Torek'
                WHEN '2' THEN 'Sreda'
                WHEN '3' THEN 'Cetrtek'
                WHEN '4' THEN 'Petek'
                WHEN '5' THEN 'Sobota'
                WHEN '6' THEN 'Nedelja'
                ELSE 'Ponedeljek'
                END AS Dan FROM Nacrtovani_Prevzemi  LIMIT 1);
            
                SET @datum = DATE_ADD(@datum, INTERVAL 1 DAY);
                
                CREATE TEMPORARY TABLE temp6 AS SELECT n.id_nacrtovani_prevzem, n.id_stranke, n.Kolicina, n.Izdelek, n.Dan, n.Cas_Enkrat, @datum AS 'Datum' FROM Nacrtovani_Prevzemi  n WHERE n.Dan = (SELECT CASE WEEKDAY(@datum) 
                WHEN '0' THEN 'Ponedeljek'
                WHEN '1' THEN 'Torek'
                WHEN '2' THEN 'Sreda'
                WHEN '3' THEN 'Cetrtek'
                WHEN '4' THEN 'Petek'
                WHEN '5' THEN 'Sobota'
                WHEN '6' THEN 'Nedelja'
                ELSE 'Ponedeljek'
                END AS Dan FROM Nacrtovani_Prevzemi  LIMIT 1);
            
                SET @datum = DATE_ADD(@datum, INTERVAL 1 DAY);
                
                CREATE TEMPORARY TABLE temp7 AS SELECT n.id_nacrtovani_prevzem, n.id_stranke, n.Kolicina, n.Izdelek, n.Dan, n.Cas_Enkrat, @datum AS 'Datum' FROM Nacrtovani_Prevzemi  n WHERE n.Dan = (SELECT CASE WEEKDAY(@datum) 
                WHEN '0' THEN 'Ponedeljek'
                WHEN '1' THEN 'Torek'
                WHEN '2' THEN 'Sreda'
                WHEN '3' THEN 'Cetrtek'
                WHEN '4' THEN 'Petek'
                WHEN '5' THEN 'Sobota'
                WHEN '6' THEN 'Nedelja'
                ELSE 'Ponedeljek'
                END AS Dan FROM Nacrtovani_Prevzemi  LIMIT 1);               
                ";

            $rezultatNacrtovaniPrevzemi = mysqli_multi_query($povezava, $sqlNacrtovaniPrevzemi);

            while(mysqli_next_result($povezava));

            //Združi spodnje tabele kot eden rezultat

            $sqlNacrtovaniPrevzemiPodatki = "SELECT * FROM temp1
                        UNION
                        SELECT * FROM temp2
                        UNION
                        SELECT * FROM temp3
                        UNION
                        SELECT * FROM temp4
                        UNION
                        SELECT * FROM temp5
                        UNION
                        SELECT * FROM temp6
                        UNION
                        SELECT * FROM temp7;";

            //Te podatke da v array za primerjat ter enega, v katerega je še vpisana količina

            $rezultatNacrtovaniPrevzemi = mysqli_query($povezava, $sqlNacrtovaniPrevzemiPodatki);

            $nacrtovaniprevzemi = array();
            $nacrtovaniprevzemiKolicina = array();

            if(mysqli_num_rows($rezultatNacrtovaniPrevzemi) > 0){
                while($vrstica = mysqli_fetch_assoc($rezultatNacrtovaniPrevzemi)){

                    array_push($nacrtovaniprevzemi, array("id_stranke" => $vrstica['id_stranke'], "Izdelek" => $vrstica['Izdelek'], "Datum" => $vrstica['Datum']));

                    array_push($nacrtovaniprevzemiKolicina, array("id_stranke" => $vrstica['id_stranke'], "Izdelek" => $vrstica['Izdelek'], "Datum" => $vrstica['Datum'], "Kolicina" => $vrstica['Kolicina'], "Cas_Enkrat" => $vrstica['Cas_Enkrat']));
                }
            }



            //Ustvari array z manjkajočimi vnosi
            $manjkajociArray = $nacrtovaniprevzemiKolicina;

            foreach ($nacrtovaniprevzemi as $kljuc => $vrednostnacrt) {

                //Preveri če ima načrtovani prevzem vpisan Cas_Enkrat, če ima potem preveri če se datuma ujemta, če se ne ga izvrže
                if((!empty($nacrtovaniprevzemiKolicina[$kljuc]['Cas_Enkrat']))){
                    if($nacrtovaniprevzemiKolicina[$kljuc]['Datum'] != $nacrtovaniprevzemiKolicina[$kljuc]['Cas_Enkrat']){
                        unset($manjkajociArray[$kljuc]);
                    }
                }

                //Preveri če se načrtovani prevzem in prodaja ujemata, če se ga uniči
                foreach($prodaja as $vrednostprodaja){
                    if($vrednostnacrt != $vrednostprodaja){
                        
                        
                    }
                    else{
                        unset($manjkajociArray[$kljuc]);
                    }
                }

            }

            //V ta array doda polja Ime in Priimek stranke, ter Mersko Enoto in odstrani polje Cas_Enkrat
            foreach ($manjkajociArray as $kljuc => $vrednost) {

                $sqlStranke = "SELECT Ime, Priimek FROM Stranka WHERE id_stranke = " . $vrednost['id_stranke'] . ";";

                $rezultatstrankaneki = mysqli_query($povezava, $sqlStranke);
                

                $vrstica = mysqli_fetch_assoc($rezultatstrankaneki);

                $manjkajociArray[$kljuc]['Ime'] = $vrstica['Ime'];
                $manjkajociArray[$kljuc]['Priimek'] = $vrstica['Priimek'];



                $sqlIzdelek= "SELECT Merska_enota FROM Izdelek WHERE Izdelek = '" . $vrednost['Izdelek'] . "';";
                
                $rezultatizdelekneki = mysqli_query($povezava, $sqlIzdelek);
                

                $vrsticaIzdelek = mysqli_fetch_assoc($rezultatizdelekneki);

                $manjkajociArray[$kljuc]['Merska_enota'] = $vrsticaIzdelek['Merska_enota'];

                

                unset($manjkajociArray[$kljuc]['Cas_Enkrat']);
                
            }

            mysqli_close($povezava);
            

            $remanjkajociArray = array_values($manjkajociArray);

            http_response_code(200);
            echo(json_encode(array("data" => $remanjkajociArray)));
            exit;

            
    
        }
        else{
            mysqli_close($povezava);
            http_response_code(400);
            echo json_encode(array("sporocilo" => "Vse ni vključeno"), JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    else{
        mysqli_close($povezava);
        http_response_code(400);
        echo json_encode(array("sporocilo" => "Vse ni vključeno"), JSON_UNESCAPED_UNICODE);
        exit;
    }

    
    
    
    
}
else{
    mysqli_close($povezava);
    http_response_code(401);
    exit;
}

