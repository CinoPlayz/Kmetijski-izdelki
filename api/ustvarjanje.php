<?php 
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
  

if (isset($_SERVER['Authorization'])) {
    $headers = trim($_SERVER["Authorization"]);
}
elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx
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

        //Pogleda če je Admin, drugače nima dostopa do tabele Uporabnik
        if($upr['Pravila'] == "Admin"){

            //Dobi Stolpce za tabele ter, če dovolijo vnos NULL
            $Stolpci = BranjeStolpcev($tabela, $povezava);
        }
        else{

        }

        $podatki = json_decode(file_get_contents("php://input"));

        $StolpciZPodatki = array();

        //Gre čez stolpce in preveri če je stolpec lahko NULL, če ne sme bit preveri, da ni vneseni podatek prazen.
        for($i = 0; $i < count($Stolpci); $i++){

            $stolpec = $Stolpci[$i][0];

            //Preveri, da ni podatek prazen ter nato preveri, da nima kaj SQL not ter spremeni nekatere stvari, da je manjša možnost za XSS.
            if(!empty($podatki->$stolpec)){
                $podatkifilterSQL = mysqli_real_escape_string($povezava, $podatki->$stolpec);
                
                $podatkifilter = htmlspecialchars($podatkifilterSQL, ENT_QUOTES);
            }  


            if($Stolpci[$i][1] == "NO"){
               
                if(empty($podatkifilter)){
                    mysqli_close($povezava);
                    http_response_code(400);
                    echo json_encode(array("sporocilo" => "Vse ni vključeno"));
                    exit;
                }
                else{
                    //Shrani podatke, če niso prazni, ter spremeni $podatkifilter tako da je empty
                    array_push($StolpciZPodatki, array($stolpec, $podatkifilter, "NO"));
                    $podatkifilter = "";
                }
            }  
            else{
                //Preveri, če je podatek, ki je lahko NULL vpisan, če je da to vrednost v array, drugače bo dal NULL
                if(!empty($podatkifilter)){
                    array_push($StolpciZPodatki, array($stolpec, $podatkifilter, "YES"));
                    $podatkifilter = "";
                }
                else{
                    array_push($StolpciZPodatki, array($stolpec, "NULL", "YES"));
                }
                  
            }          
        }
        
        //print_r($podatki);
        print_r($StolpciZPodatki);
        
        mysqli_close($povezava);
    }
    else{
        mysqli_close($povezava);
        http_response_code(400);
        echo json_encode(array("sporocilo" => "Vse ni vključeno"));
        exit;
    }
}
else{
    mysqli_close($povezava);
    http_response_code(401);
    exit;
}

//Dobi podatke o stolpcih v tabeli, in če so NULL
function BranjeStolpcev($tabela, $povezava){
    $sql = "SHOW columns FROM $tabela";
            
    $rezultat = mysqli_query($povezava, $sql);
    if($rezultat == true && mysqli_num_rows($rezultat) > 0){

        $podatki = array();

        while($vrstica = mysqli_fetch_assoc($rezultat)){
            array_push($podatki, array($vrstica['Field'], $vrstica['Null']));
        }
        return $podatki;
    }
    else{
        mysqli_close($povezava);
        http_response_code(404);
        echo json_encode(array("sporocilo" => "Ni najdena tabela oz. tabela je prazna"));
        exit;
    } 
}