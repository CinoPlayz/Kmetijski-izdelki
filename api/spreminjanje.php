<?php 
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

//Dobi podatke o Authorization, ki so v headerju
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

//Če ni vrne, da nima dostopa
if(empty($headers)){
    http_response_code(401);
    exit;
}

require("../PovezavaZBazo.php");

//Filtrira header, da nima kej z SQL ter XSS
$headersfilterSQL = mysqli_real_escape_string($povezava, $headers);
$headersfilter = htmlspecialchars($headersfilterSQL, ENT_QUOTES);

$token = str_replace("Bearer ", "", $headersfilter);

//Preveri, če sploh obstaja ta token v bazi
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
            if($tabela == "Uporabnik"){
                mysqli_close($povezava);
                http_response_code(403); 
                exit;
            }
            else{
                $Stolpci = BranjeStolpcev($tabela, $povezava);
            }
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

            //Preveri, če je stolpec Primary
            if(isset($Stolpci[$i][2]) && $Stolpci[$i][2] == "PRI"){
                if(empty($podatkifilter)){
                    mysqli_close($povezava);
                    http_response_code(400);
                    echo json_encode(array("sporocilo" => "Vse ni vključeno"), JSON_UNESCAPED_UNICODE);
                    exit;
                }
                else{
                    //Preveri, če je ta primarykey nov (kot da bo prmary key preimenovan v to)
                    if(isset($Stolpci[$i][3]) && $Stolpci[$i][3] == "Nov"){
                        array_push($StolpciZPodatki, array($stolpec, $podatkifilter, "Nov"));
                        $podatkifilter = "";
                    }
                    else{
                        array_push($StolpciZPodatki, array($stolpec, $podatkifilter, "PRI"));
                        $podatkifilter = "";
                    }
                    
                }
            }
            else{
                if($Stolpci[$i][1] == "NO"){
                
                    if(empty($podatkifilter)){
                        mysqli_close($povezava);
                        http_response_code(400);
                        echo json_encode(array("sporocilo" => "Vse ni vključeno"), JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                    else{
                        //Najprej preveri če je Geslo, ter to nato Hasha in shrani podatke, če niso prazni, ter spremeni $podatkifilter tako da je empty. Zadnji del isto za else
                        if($stolpec == "Geslo"){

                            if(defined('PASSWORD_ARGON2ID')) {
                                $geslohash = password_hash($podatkifilter, PASSWORD_ARGON2ID, ['memory_cost' => 2048, 'time_cost' => 12, 'threads' => 2]);
                            }
                            else{
                                $geslohash = password_hash($podatkifilter, PASSWORD_DEFAULT, ['memory_cost' => 2048, 'time_cost' => 12, 'threads' => 2]);
                            }

                            array_push($StolpciZPodatki, array($stolpec, $geslohash, "NO"));
                            $podatkifilter = "";
                        }//Pregleda, da so pravila Admin oz Uporabnik, drugače ne izvede nič pozneje
                        else if($stolpec == "Pravila"){
                            if($podatkifilter != "Admin" && $podatkifilter != "Uporabnik"){
                                http_response_code(400);
                                echo json_encode(array("sporocilo" => "Pravilo ne obstaja"), JSON_UNESCAPED_UNICODE);
                                exit;
                            }

                            array_push($StolpciZPodatki, array($stolpec, $podatkifilter, "NO"));
                            $podatkifilter = "";

                        }
                        else{
                            array_push($StolpciZPodatki, array($stolpec, $podatkifilter, "NO"));
                            $podatkifilter = "";
                        }

                        
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
        }

        //SQL stavek razdljen v dva dela za vnos ter kako velik je array $StolpciZpodatki
        $sqlPrviDel = "UPDATE $tabela SET ";
        $sqlDrugiDel = "WHERE ";

        $kolikoPodatkov = count($StolpciZPodatki);

        //print_r($StolpciZPodatki);
        
        //Gre čez vsak element v arrayu(V tem primeru je 2D)
        for($i = 0; $i < $kolikoPodatkov; $i++){

            //Preveri, da ne po slučajno vpisal podatkov v spodnja dva stolpca
            if($StolpciZPodatki[$i][0] != "TokenWeb" && $StolpciZPodatki[$i][0] != "TokenAndroid" && $StolpciZPodatki[$i][2] != "PRI"){

                if($StolpciZPodatki[$i][2] == "Nov"){
                    $sqlPrviDel .= str_replace("Nov", "", $StolpciZPodatki[$i][0]) . " = ";
                    $sqlPrviDel .= "'" . $StolpciZPodatki[$i][1] . "'";

                    if($i == ($kolikoPodatkov-1)){
                        $sqlPrviDel .= " ";
                    }
                    else{
                        $sqlPrviDel .= ", ";
                    }
                }
                else{
                    //Dodaja stavek skupaj, če je zadenj vnos se ne bo vpisala "," v stavek

                    $sqlPrviDel .= $StolpciZPodatki[$i][0] . " = ";            
                    

                    //Prveri kateri element je če je string doda še '' v stavek drugače pusti prazno, za NULL doda samo NULL v stavek
                    if(is_numeric($StolpciZPodatki[$i][1])){
                        $sqlPrviDel .= $StolpciZPodatki[$i][1];
                    }
                    else if($StolpciZPodatki[$i][1] == "NULL"){
                        $sqlPrviDel .= "NULL";
                    }
                    else{
                        $sqlPrviDel .= "'" . $StolpciZPodatki[$i][1] . "'";
                    }

                    //Doda vejico, če ni zadenj vnos drugače samo zaključi stavek
                    if($i == ($kolikoPodatkov-1)){
                        $sqlPrviDel .= " ";
                    }
                    else{
                        $sqlPrviDel .= ", ";
                    }

                }


                
            }
            else if($StolpciZPodatki[$i][2] == "PRI"){

                if(is_numeric($StolpciZPodatki[$i][1])){
                    $sqlDrugiDel .= $StolpciZPodatki[$i][0] . " = " . $StolpciZPodatki[$i][1] . ";";
                }
                else{
                    $sqlDrugiDel .= $StolpciZPodatki[$i][0] . " = '" . $StolpciZPodatki[$i][1] . "';";
                }
                
            }

        }
        
        $sql = $sqlPrviDel . $sqlDrugiDel;
        echo $sql;

        if(mysqli_query($povezava, $sql)){  
            mysqli_close($povezava);          
            http_response_code(200);
            exit;
        }
        else{

            if(mysqli_errno($povezava) == 1062){
                mysqli_close($povezava);
                http_response_code(400);
                echo json_encode(array("sporocilo" => "Vnos že obstaja"), JSON_UNESCAPED_UNICODE);
                exit;
            }

            mysqli_close($povezava);
            echo json_encode(array("sporocilo" => "Neka napaka se je zglodila pri izvajanju"), JSON_UNESCAPED_UNICODE);
            http_response_code(500);
            exit; 
        }

        
    }
    else{
        //Če ni vpisana tabela vrne to
        mysqli_close($povezava);
        http_response_code(400);
        echo json_encode(array("sporocilo" => "Vse ni vključeno"), JSON_UNESCAPED_UNICODE);
        exit;
    }
}
else{
    //Če Token ne obstaja vrne, da nima dostopa
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
            //Preveri, ali je atribut primary ali ne
            if($vrstica['Key'] == "PRI"){
                //Preveri, če je primary key varchar ali če je število, če je varchar ima možnost, da spremeni primary key
                if(strpos($vrstica['Type'], 'varchar') !== false){
                    array_push($podatki, array($vrstica['Field'], $vrstica['Null'], $vrstica['Key']));

                    array_push($podatki, array($vrstica['Field']."Nov", $vrstica['Null'], $vrstica['Key'], "Nov"));
                }
                else{
                    array_push($podatki, array($vrstica['Field'], $vrstica['Null'], $vrstica['Key']));
                }
                
            }
            else{
                array_push($podatki, array($vrstica['Field'], $vrstica['Null']));
            }
        }
        return $podatki;
    }
    else{
        mysqli_close($povezava);
        http_response_code(404);
        echo json_encode(array("sporocilo" => "Ni najdena tabela oz. tabela je prazna"), JSON_UNESCAPED_UNICODE);
        exit;
    } 
}