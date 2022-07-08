<?php 
        if(!defined('LahkoPovezava')) {
            http_response_code(403);
            exit;
         }

            $uporabniskoime = "root";
            $serverip = "localhost";
            $geslo = "";
            $podatkovnabaza = "Kmetijski_Izdelki";
        
            $povezava = mysqli_connect($serverip, $uporabniskoime, $geslo, $podatkovnabaza);
            
            if(!$povezava){
                die("Povezava ni uspela: " . mysqli_connect_error());
            }
        
            mysqli_set_charset($povezava, 'utf8');
        
            ?>