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
define('LahkoPovezava', TRUE);
require("../PovezavaZBazo.php");

$headersfilterSQL = mysqli_real_escape_string($povezava, $headers);
$headersfilter = htmlspecialchars($headersfilterSQL, ENT_QUOTES);

$token = str_replace("Bearer ", "", $headersfilter);


$sql = "SELECT * FROM Uporabnik WHERE TokenWeb='". hash("sha256", $token) . "' OR TokenAndroid='". hash("sha256", $token) . "'";

$rezultat = mysqli_query($povezava, $sql);

if(mysqli_num_rows($rezultat) > 0){
    if(isset($_GET['tabela'])){
        $tabelafilter = htmlspecialchars($_GET['tabela'], ENT_QUOTES);

        $tabela = mysqli_real_escape_string($povezava, $tabelafilter);

        $upr = mysqli_fetch_assoc($rezultat);
        
        
        if($upr['Pravila'] == "Admin"){
           Branje($tabela, $povezava);            
        }
        else{
            if($tabela == "Uporabnik" || $tabela == "Prenosi" || $tabela == "Posta"){
                mysqli_close($povezava);
                http_response_code(403); 
                exit;
            }
            else{
                Branje($tabela, $povezava); 
            }
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


function Branje($tabela, $povezava){
    if($tabela == "Nacrtovani_Prevzemi"){
        if(isset($_GET['dan'])){
            $danfilter = htmlspecialchars($_GET['dan'], ENT_QUOTES);
    
            $dan = mysqli_real_escape_string($povezava, $danfilter);

            $sql = "SELECT * FROM $tabela n INNER JOIN Stranka s ON n.id_stranke = s.id_stranke INNER JOIN Izdelek i ON n.Izdelek = i.Izdelek WHERE Dan = '$dan'";
        }
        else{
            $sql = "SELECT * FROM $tabela n INNER JOIN Stranka s ON n.id_stranke = s.id_stranke INNER JOIN Izdelek i ON n.Izdelek = i.Izdelek";
        }
    }
    else if($tabela == "Prodaja"){
        if(isset($_GET['omejitev'])){
            $omejitevfilter = htmlspecialchars($_GET['omejitev'], ENT_QUOTES);
    
            $omejitev = mysqli_real_escape_string($povezava, $omejitevfilter);

            $sql = "SELECT * FROM $tabela p INNER JOIN Stranka s ON p.id_stranke = s.id_stranke INNER JOIN Izdelek i ON p.Izdelek = i.Izdelek ORDER BY p.Datum_Prodaje DESC LIMIT $omejitev ";
        }
        else if(isset($_GET['DatumOd']) && isset($_GET['DatumDo']) && isset($_GET['Stranka']) && isset($_GET['Izdelek'])){
            //Za sestavljanje računov
            
            $DatumOdfilter = htmlspecialchars($_GET['DatumOd'], ENT_QUOTES);
    
            $DatumOd = mysqli_real_escape_string($povezava, $DatumOdfilter);


            $DatumDofilter = htmlspecialchars($_GET['DatumDo'], ENT_QUOTES);
    
            $DatumDo = mysqli_real_escape_string($povezava, $DatumDofilter);


            $Strankafilter = htmlspecialchars($_GET['Stranka'], ENT_QUOTES);            
    
            $Stranka = mysqli_real_escape_string($povezava, $Strankafilter);

            $idNahaja = strpos($Stranka, " - ");
            $id = substr($Stranka, ($idNahaja+3));



            $izdelekfilter = htmlspecialchars($_GET['Izdelek'], ENT_QUOTES);
    
            $Izdelek = mysqli_real_escape_string($povezava, $izdelekfilter);

            //Preveri da niso prazni
            if(empty($Izdelek) || empty($DatumOd) || empty($DatumDo) || empty($id)){
                mysqli_close($povezava);
                http_response_code(400);
                echo json_encode(array("sporocilo" => "Vse ni vključeno"), JSON_UNESCAPED_UNICODE);
                exit;
            }
            else{
                if($Izdelek == "*"){
                    $sql = "SELECT *  FROM $tabela p INNER JOIN Stranka s ON p.id_stranke = s.id_stranke INNER JOIN Izdelek i ON p.Izdelek = i.Izdelek WHERE s.id_stranke = $id AND p.Datum_Prodaje >= '$DatumOd' AND p.Datum_Prodaje <= '$DatumDo 23:59:59'  ORDER BY p.Datum_Prodaje DESC";
                }
                else{
                    $sql = "SELECT *  FROM $tabela p INNER JOIN Stranka s ON p.id_stranke = s.id_stranke INNER JOIN Izdelek i ON p.Izdelek = i.Izdelek WHERE i.Izdelek='$Izdelek' AND s.id_stranke = $id AND p.Datum_Prodaje >= '$DatumOd' AND p.Datum_Prodaje <= '$DatumDo 23:59:59'  ORDER BY p.Datum_Prodaje DESC";
                }

            }

            
            
        }
        else{
            $sql = "SELECT *  FROM $tabela p INNER JOIN Stranka s ON p.id_stranke = s.id_stranke INNER JOIN Izdelek i ON p.Izdelek = i.Izdelek ORDER BY p.Datum_Prodaje DESC";
        }
        
    }
    else if($tabela == "Stranka"){
        //Izpis podatkov za tabelo Stranka
        $sql = "SELECT id_stranke, Ime, Priimek, Naslov, s.Posta, Kraj FROM $tabela s LEFT JOIN Posta p ON s.Posta = p.Postana_stevilka";
    }
    else{
        $sql = "SELECT * FROM $tabela";
    }


    
            
    $rezultat = mysqli_query($povezava, $sql);
    if($rezultat == true && mysqli_num_rows($rezultat) > 0){

        $podatki = array();                
        $koncenarray = array("data");

        while($vrstica = mysqli_fetch_assoc($rezultat)){
            array_push($podatki, $vrstica);
        }

        $Vsipodatki = array_fill_keys($koncenarray, $podatki);

        echo json_encode($Vsipodatki, JSON_UNESCAPED_UNICODE);
        
        mysqli_close($povezava);
        http_response_code(200);
        exit;
    }
    else{
        mysqli_close($povezava);
        http_response_code(404);
        echo json_encode(array("sporocilo" => "Ni najdena tabela oz. tabela je prazna"), JSON_UNESCAPED_UNICODE);
        exit;
    } 
}