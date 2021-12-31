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


$sql = "SELECT * FROM Uporabnik WHERE TokenWeb='". hash("sha256", $token) . "'";

$rezultat = mysqli_query($povezava, $sql);

if(mysqli_num_rows($rezultat) > 0){
    if(isset($_GET['tabela'])){
        $tabelafilter = filter_input(INPUT_GET, 'tabela', FILTER_SANITIZE_STRING);

        $tabela = mysqli_real_escape_string($povezava, $tabelafilter);

        $upr = mysqli_fetch_assoc($rezultat);
        
        
        if($upr['Pravila'] == "Admin"){
           Branje($tabela, $povezava);            
        }
        else{
            if($tabela == "Uporabnik"){
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
    $sql = "SELECT * FROM $tabela";
            
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