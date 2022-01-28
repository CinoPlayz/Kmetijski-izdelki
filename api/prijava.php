<?php

    header("Content-Type: application/json; charset=UTF-8");

    require("../PovezavaZBazo.php");

    $podatki = json_decode(file_get_contents("php://input"));

    if(isset($podatki->Uporabnisko_ime) && !empty($podatki->Uporabnisko_ime)){
        $uprimefilterSQL = mysqli_real_escape_string($povezava, $podatki->Uporabnisko_ime);
                    
        $uprime = htmlspecialchars($uprimefilterSQL, ENT_QUOTES);

        if(empty($uprime)){
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

    if(isset($podatki->Geslo) && !empty($podatki->Geslo)){
        $geslofilterSQL = mysqli_real_escape_string($povezava, $podatki->Geslo);
                    
        $geslo = htmlspecialchars($geslofilterSQL, ENT_QUOTES);

        if(empty($geslo)){
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



    $sql = "SELECT Geslo, Pravila FROM Uporabnik WHERE Uporabnisko_ime='$uprime'";

    $rezultat = mysqli_query($povezava, $sql);
    if(mysqli_num_rows($rezultat) > 0){

        $vrstica = mysqli_fetch_assoc($rezultat);

        if(password_verify($geslo, $vrstica['Geslo'])){

            $token = newToken(80);

            $sql = "UPDATE Uporabnik SET TokenAndroid = '" . hash("sha256", $token) . "' WHERE Uporabnisko_ime='$uprime'";
            mysqli_query($povezava, $sql);


            echo json_encode(array("podatki" => "$token"), JSON_UNESCAPED_UNICODE);
            mysqli_close($povezava);
            http_response_code(200);
            exit;
        }
        else{
            mysqli_close($povezava);
            http_response_code(400);
            echo json_encode(array("sporocilo" => "Uporabniško ime oz. geslo je narobe"), JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    else{
        mysqli_close($povezava);
        http_response_code(400);
        echo json_encode(array("sporocilo" => "Uporabniško ime oz. geslo je narobe"), JSON_UNESCAPED_UNICODE);
        exit; 
    }



    function newToken($velikost) {
        $vsecrke = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $token = '';

        for($i = 0; $i < $velikost; $i++){
            $indeks = random_int(1, strlen($vsecrke) - 1);

            $token .= $vsecrke[$indeks];
        }

        return $token;

    }