<?php 
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
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

define('LahkoPovezava', TRUE);
require("../PovezavaZBazo.php");

//Filtrira header, da nima kej z SQL ter XSS
$headersfilterSQL = mysqli_real_escape_string($povezava, $headers);
$headersfilter = htmlspecialchars($headersfilterSQL, ENT_QUOTES);

//Preveri, če sploh obstaja ta token v bazi
$token = str_replace("Bearer ", "", $headersfilter);
$tokensha = hash("sha256", $token);

//Nastavi statment
$stmt = $povezava->prepare("SELECT * FROM Uporabnik WHERE TokenWeb=? OR TokenAndroid=?");
//Da spremenljivke v statmente
$stmt->bind_param("ss", $tokensha, $tokensha);
//Izvede statment
$stmt->execute();
//Dobi rezultate
$rezultat = $stmt->get_result();


if(mysqli_num_rows($rezultat) > 0){
    if(isset($_GET['tabela'])){
        $tabelafilter = htmlspecialchars($_GET['tabela'], ENT_QUOTES);

        $tabela = mysqli_real_escape_string($povezava, $tabelafilter);

        $upr = mysqli_fetch_assoc($rezultat);

        //Pogleda če je Admin, drugače nima dostopa do tabele Uporabnik
        if($upr['Pravila'] == "Admin"){

            //Dobi Stolpce za tabele ter, če dovolijo vnos NULL
            $Stolpci = BranjeStolpcev($tabela, $povezava);
        }
        else{
            if($tabela == "Uporabnik" || $tabela == "Prenosi" || $tabela == "Posta"){
                mysqli_close($povezava);
                http_response_code(403); 
                exit;
            }
            else{
                $Stolpci = BranjeStolpcev($tabela, $povezava);
            }
        }  

        if(empty($Stolpci)){
            mysqli_close($povezava);
            http_response_code(400);
            echo json_encode(array("sporocilo" => "Vse ni vključeno"), JSON_UNESCAPED_UNICODE);
            exit;
        }

        $podatki = json_decode(file_get_contents("php://input"));

        

        $stolpec = $Stolpci[0];

        if(!empty($podatki->$stolpec)){
            $podatkifilterSQL = mysqli_real_escape_string($povezava, $podatki->$stolpec);
            
            $podatkifilter = htmlspecialchars($podatkifilterSQL, ENT_QUOTES);
        }  
        else{
            mysqli_close($povezava);
            http_response_code(400);
            echo json_encode(array("sporocilo" => "Vse ni vključeno"), JSON_UNESCAPED_UNICODE);
            exit;  
        }

        //Dobimo ime tabele, ime atributa v tej tabeli, ter ime tabele in atrikuta od katere tabele je ta foreign key
        $stmt = $povezava->prepare("SELECT 
            TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
        FROM
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE
            REFERENCED_TABLE_SCHEMA = ? AND
            REFERENCED_TABLE_NAME = ? AND
            REFERENCED_COLUMN_NAME = ?");
        
        $stmt->bind_param("sss", $podatkovnabaza, $tabela, $stolpec);
        $stmt->execute();
        $rezultat = $stmt->get_result();

        

        $ForeignKeyTabeleAtribut = array();     

        if($rezultat == true){

            //Če je sploh ta foreign key kje drugje uporabljen            
            if(mysqli_num_rows($rezultat) > 0){
               
                while($vrstica = mysqli_fetch_assoc($rezultat)){
                    
                    array_push($ForeignKeyTabeleAtribut, array("TABLE_NAME" => $vrstica['TABLE_NAME'], "COLUMN_NAME" => $vrstica['COLUMN_NAME']));
                }
            }

            //Ustvari sql kodo tako da izbriše vse vrstice, kjer je uporabljen ta atribut s temi podatki
            if(!empty($ForeignKeyTabeleAtribut)){
                $sql = "";

                for($i = 0; $i < count($ForeignKeyTabeleAtribut); $i++){
                    $stmt = $povezava->prepare("DELETE FROM ". $ForeignKeyTabeleAtribut[$i]['TABLE_NAME'] . " WHERE " . $ForeignKeyTabeleAtribut[$i]['COLUMN_NAME']. "=?");

                    if(is_int($podatkifilter)){
                        $stmt->bind_param("i", $podatkifilter);
                    }
                    else{
                        $stmt->bind_param("s", $podatkifilter);
                    }
                    
                    $rezultat = $stmt->execute();                    

                    if($rezultat == false){
                        mysqli_close($povezava);
                        http_response_code(500);
                        echo json_encode(array("sporocilo" => "Neka napaka se je zglodila pri izvajanju"), JSON_UNESCAPED_UNICODE);
                        exit;
                    }                     
                }
            }

            //Odstrani podatek
            $stmt = $povezava->prepare("DELETE FROM $tabela WHERE $stolpec=?");
            if(is_int($podatkifilter)){
                $stmt->bind_param("i", $podatkifilter);
            }
            else{
                $stmt->bind_param("s", $podatkifilter);
            }
            

            if($stmt->execute()){
                mysqli_close($povezava);
                http_response_code(200); 
            }
            else{
                mysqli_error($povezava);
                mysqli_close($povezava);
                
                http_response_code(500);
                echo json_encode(array("sporocilo" => "Neka napaka se je zglodila pri izvajanju"), JSON_UNESCAPED_UNICODE);
                exit;  
            }
            
        }
        else{
            mysqli_close($povezava);
            http_response_code(500);
            echo json_encode(array("sporocilo" => "Neka napaka se je zglodila pri izvajanju"), JSON_UNESCAPED_UNICODE);
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

    //Preveri če je tabela ena, ki je že navedena s tem se izognemo injekciji saj je samo določena dovoljena
    $tabele_dovoljene = array("Uporabnik", "Prenosi", "Posta", "Prodaja", "Nacrtovani_Prevzemi", "Stranka", "Izdelek");
    if (!in_array($tabela, $tabele_dovoljene)){
        mysqli_close($povezava);
        http_response_code(404);
        echo json_encode(array("sporocilo" => "Ni najdena tabela oz. tabela je prazna"), JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sql = "SHOW columns FROM $tabela";
            
    $rezultat = mysqli_query($povezava, $sql);
    if($rezultat == true && mysqli_num_rows($rezultat) > 0){

        $podatki = array();

        while($vrstica = mysqli_fetch_assoc($rezultat)){
            if($vrstica['Key'] == "PRI"){
                array_push($podatki, $vrstica['Field']);
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