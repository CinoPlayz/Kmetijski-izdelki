<?php 
    if(isset($_POST['ipPB']) && isset($_POST['upPB']) && isset($_POST['gesloPB'])){
        $ipfilter = filter_input(INPUT_POST, 'ipPB', FILTER_SANITIZE_STRING);
        $upfilter = filter_input(INPUT_POST, 'upPB', FILTER_SANITIZE_STRING);
        $geslofilter = filter_input(INPUT_POST, 'gesloPB', FILTER_SANITIZE_STRING);

        if(empty($ipfilter)){
            RedirectZNapako(1);
        }

        if(empty($upfilter)){
            RedirectZNapako(2);
        }

        if(empty($geslofilter)){
            RedirectZNapako(3);
        }

        if($geslofilter == "empty"){
            $geslofilter = "";
        }

        $datoteka = fopen("PovezavaZBazo.php", "w") or die("Nemorem odpreti datoteke!");

        $vpisvdatoteko = "<?php 
            \$uporabniskoime = \"$upfilter\";
            \$serverip = \"$ipfilter\";
            \$geslo = \"$geslofilter\";
            /*\$podatkovnabaza = \"Kmetijski_Izdelki\";*/
        
            \$povezava = mysqli_connect(\$serverip, \$uporabniskoime, \$geslo/*, \$podatkovnabaza*/);
            
            if(!\$povezava){
                /*die(\"Povezava ni uspela: \" . mysqli_connect_error());*/
            }
        
            mysqli_set_charset(\$povezava, 'utf8');
        
            ?>";
        
        fwrite($datoteka, $vpisvdatoteko);
        fclose($datoteka);

        require("PovezavaZBazo.php");

        if(!$povezava){
            RedirectZNapako("PovError");
        }
        else{
            RedirectZUspehom("UspesnaPov");
        }
    }

    function RedirectZNapako($napaka){
        header("location: install.php?napaka=$napaka");
        exit;
    }

    function RedirectZUspehom($uspeh){
        header("location: install.php?uspeh=$uspeh");
        exit;
    }

    if(isset($_POST['inicealizacija'])){
        if($_POST['inicealizacija'] == "DA"){
            require("PovezavaZBazo.php");
            $sqlustvarjanje = "DROP DATABASE IF EXISTS Kmetijski_Izdelki;

            CREATE DATABASE Kmetijski_Izdelki;
            
            USE Kmetijski_Izdelki;
            
            CREATE TABLE Uporabnik(
                Uporabnisko_ime VARCHAR(50) PRIMARY KEY,
                Ime VARCHAR(50) NOT NULL,
                Priimek VARCHAR(50) NOT NULL,
                Geslo VARCHAR(512) NOT NULL,
                Token VARCHAR(64),
                Pravila VARCHAR(9) DEFAULT 'Uporabnik' CHECK(Pravila IN('Admin', 'Uporabnik'))
                );
            
            CREATE TABLE Stranka(
                id_stranke INT PRIMARY KEY AUTO_INCREMENT,
                Ime VARCHAR(50) NOT NULL,
                Priimek VARCHAR(50) NOT NULL
            );
            
            CREATE TABLE Izdelek(
                Izdelek VARCHAR(50) PRIMARY KEY	
            );
            
            CREATE TABLE Prodaja(
                id_prodaje INT PRIMARY KEY AUTO_INCREMENT,
                Datum_Prodaje DATETIME NOT NULL,
                Datum_Vpisa DATETIME NOT NULL,
                Koliko INT NOT NULL,
                id_stranke INT NOT NULL,
                Uporabnisko_ime VARCHAR(50) NOT NULL,
                Izdelek VARCHAR(50) NOT NULL,
                FOREIGN KEY (id_stranke) REFERENCES Stranka(id_stranke), 
                FOREIGN KEY (Uporabnisko_ime) REFERENCES Uporabnik(Uporabnisko_ime), 
                FOREIGN KEY (Izdelek) REFERENCES Izdelek(Izdelek)
            );
            
            CREATE TABLE Nacrtovani_Prevzemi(
                id_nacrtovani_prevzem INT PRIMARY KEY AUTO_INCREMENT,
                Kolicina INT NOT NULL,
                Dan VARCHAR(40) NOT NULL CHECK(Dan IN('Ponedeljek', 'Torek', 'Sreda', 'Četrtek', 'Petek', 'Sobota', 'Nedelja')),
                Cas VARCHAR(40) DEFAULT 'Cel' CHECK(Cas IN('Zjutraj', 'Zvečer', 'Sredi', 'Cel')),
                Izdelek VARCHAR(50) NOT NULL,
                id_stranke INT NOT NULL,
                FOREIGN KEY (id_stranke) REFERENCES Stranka(id_stranke), 
                FOREIGN KEY (Izdelek) REFERENCES Izdelek(Izdelek)
            );";

            if(mysqli_multi_query($povezava, $sqlustvarjanje)){
                mysqli_close($povezava);

                $vrstice = file("PovezavaZBazo.php");

                $rezultat = "";
                foreach($vrstice as $vrstica){
                    if(strpos($vrstica, "/*") !== false && strpos($vrstica, "*/") !== false){
                        $rezultat .= str_replace(["/*", "*/"], "", $vrstica);
                    }
                    else{
                        $rezultat .= $vrstica;
                    }
                }

                file_put_contents('PovezavaZBazo.php', $rezultat);
                RedirectZUspehom("UspešnoUst");
            }
            else{                
                echo "Neka napaka: " . mysqli_error($povezava);
                exit;
            }


            

        }
    }

    if(isset($_POST['upAdmin']) && isset($_POST['gesloAdmin']) && isset($_POST['gesloPoAdmin'])){
        require("PovezavaZBazo.php");

        $upfilter = filter_input(INPUT_POST, 'upAdmin', FILTER_SANITIZE_STRING);
        $geslofilter = filter_input(INPUT_POST, 'gesloAdmin', FILTER_SANITIZE_STRING);
        $gesloponovnofilter = filter_input(INPUT_POST, 'gesloPoAdmin', FILTER_SANITIZE_STRING);

        if(empty($upfilter)){
            RedirectZNapakoAdmin(1, $povezava);
        }

        if(empty($geslofilter)){
            RedirectZNapakoAdmin(2, $povezava);
        }

        if(empty($gesloponovnofilter)){
            RedirectZNapakoAdmin(3, $povezava);
        }
        
        $up = mysqli_real_escape_string($povezava, $upfilter);
        $geslo = mysqli_real_escape_string($povezava, $geslofilter);
        $gesloponovno = mysqli_real_escape_string($povezava, $gesloponovnofilter);

        if(empty($up)){
            RedirectZNapakoAdmin(1, $povezava);
        }

        if(empty($geslo)){
            RedirectZNapakoAdmin(2, $povezava);
        }

        if(empty($gesloponovno)){
            RedirectZNapakoAdmin(3, $povezava);
        }

        if($geslo != $gesloponovno){
            RedirectZNapakoAdmin(4, $povezava);
        }




        if(defined('PASSWORD_ARGON2ID')) {
            $geslohash = password_hash($geslo, PASSWORD_ARGON2ID, ['memory_cost' => 2048, 'time_cost' => 12, 'threads' => 2]);
        }
        else{
            $geslohash = password_hash($geslo, PASSWORD_DEFAULT, ['memory_cost' => 2048, 'time_cost' => 12, 'threads' => 2]);
        }

        $sql = "INSERT INTO Uporabnik (Uporabnisko_ime, Ime, Priimek, Geslo, Token, Pravila) VALUES ('$up', 'Admin', 'Admin', '$geslohash', NULL, 'Admin');";

        if(mysqli_query($povezava, $sql)){
            mysqli_close($povezava);
            RedirectZUspehom("UspesnoDod");
        }
        else{
            RedirectZNapakoAdmin("PovError", $povezava);
        }
    }

    function RedirectZNapakoAdmin($napaka, $povezava){    
        mysqli_close($povezava);    
        header("location: install.php?napakaAd=$napaka&uspeh=UspešnoUst");
        exit;
    }
?>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Inštalacija</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="install.css">
    </head>
    <body>
        <div class="vse">
            <div class="glava">
                <div>
                    <a href="Domov.php"><img src="Slike/nutrition.svg" width="40px" height="40px"></a>
                </div>

                <div class="flexfill"></div>
                
            </div>
            
            <div class="menu">
                <div class="menuItem"><a class="menuItemA" href="Domov.php">Domov</a></div>
            </div>

            <div class="vsebina">
                <?php 
                    if(!$_GET || isset($_GET['napaka'])){

                echo" 
                <div class='VzpostavljanjePBNaslov'>Vzpostavljanje povezave s podatkovno bazo</div>
                <div class='formdiv'>
                    <form method='post' action='install.php'>
                        <div class='formvnosi'>
                            <div class='formvnosItem'>
                                <div class='vnosNaslov'>IP Naslov:</div>
                                <input type='text' name='ipPB' class='ipPB'>
                            </div>

                            <div class='formvnosItem'>
                                <div class='vnosNaslov'>Uporabniško ime:</div>
                                <input type='text' name='upPB' class='ipPB'>
                            </div>

                            <div class='formvnosItem'>
                                <div class='vnosNaslov'>Geslo:</div>
                                <input type='text' name='gesloPB' class='ipPB'>
                            </div>  
                        </div>    "; 
                    }
                    ?>                 
                        <?php 
                            if(isset($_GET['napaka'])){
                                switch($_GET['napaka']){
                                    case 1 : echo("<div class='napaka'>Vpišite veljaven IP naslov</div>");
                                        break;
                                    case 2 : echo("<div class='napaka'>Vpišite veljaveno Uporabniško ime</div>");
                                        break;
                                    case 3 : echo("<div class='napaka'>Vpišite veljaveno Geslo</div>");
                                        break;
                                    case "PovError": echo("<div class='napaka'>Povezava ni uspela(poglejte, da so podatki pravilni)</div>");
                                        break;
                                }

                                
                            }
                        
                        ?>
                      <?php  
                      if(!$_GET || isset($_GET['napaka'])){
                        echo
                      
                                "<div style='text-align: center;'>
                            <input type='submit'>
                        </div>
                    </form>
                </div>"; }?>

                <?php 
                 if(isset($_GET['uspeh']) && $_GET['uspeh'] == "UspesnaPov"){
                    echo"

                <div class='uspesno'><img src='Slike/tick-green.svg' width='17px' height='17px' style='padding-right: 4px;'>Uspešna povezava</div>
                <div class='VzpostavljanjePBNaslov'>Inicializiraj podatkovno bazo</div>
                <div class='formdiv'>
                    <form method='post' action='install.php'>
                        <input type='hidden' name='inicealizacija' value='DA'>
                        <input type='submit' value='Inicializiraj'>
                    </form>
                </div>
                <div class='napaka'>(To izbriše podatkovno bazo, če ste jo imeli ter jo ponovno naredi!!!!!)</div>
                ";}?>

                <?php if(isset($_GET['uspeh']) && $_GET['uspeh'] == "UspešnoUst"){
                echo"<div class='uspesno' style='padding-bottom: 1px;'><img src='Slike/tick-green.svg' width='17px' height='17px' style='padding-right: 4px;'>Uspešna povezava</div>
                <div class='uspesno'><img src='Slike/tick-green.svg' width='17px' height='17px' style='padding-right: 4px;'>Uspešno inicializirana podatkovna baza</div>
                <div class='VzpostavljanjePBNaslov'>Dodaj Administratorja</div>
                <div class='formdiv'>
                    <form method='post' action='install.php'>
                        <div class='formvnosi'>
                            <div class='formvnosItem'>
                                <div class='vnosNaslov'>Uporabniško ime:</div>
                                <input type='text' name='upAdmin' class='ipPB'>
                            </div>

                            <div class='formvnosItem'>
                                <div class='vnosNaslov'>Geslo:</div>
                                <input type='password' name='gesloAdmin' class='ipPB'>
                            </div>

                            <div class='formvnosItem'>
                                <div class='vnosNaslov'>Geslo ponovno:</div>
                                <input type='password' name='gesloPoAdmin' class='ipPB'>
                            </div>"; }?>
                        
                        <?php 
                            if(isset($_GET['napakaAd']) && $_GET['uspeh'] == "UspešnoUst"){
                                switch($_GET['napakaAd']){
                                    case 1 : echo("<div class='napaka'>Vpišite veljaveno Uporabniško ime</div>");
                                        break;
                                    case 2 : echo("<div class='napaka'>Vpišite veljaveno Geslo</div>");
                                        break;
                                    case 3 : echo("<div class='napaka'>Vpišite veljaveno Ponovno geslo</div>");
                                        break;
                                    case 4 : echo("<div class='napaka'>Vpišite isti gesli</div>");
                                        break;
                                    case "PovError": echo("<div class='napaka'>Povezava ni uspela</div>");
                                        break;
                                }

                                
                            }
                        
                        ?>

                        <?php
                        if(isset($_GET['uspeh']) && $_GET['uspeh'] == "UspešnoUst"){
                        echo"<div style='text-align: center;'>
                                <input type='submit'>
                            </div> 
                        </div>
                    </form>
                </div>";}
                ?>

                <?php
                if(isset($_GET['uspeh']) && $_GET['uspeh'] == "UspesnoDod"){
                echo"<div class='uspesno' style='padding-bottom: 1px;'><img src='Slike/tick-green.svg' width='17px' height='17px' style='padding-right: 4px;'>Uspešna povezava</div>
                <div class='uspesno' style='padding-bottom: 1px;'><img src='Slike/tick-green.svg' width='17px' height='17px' style='padding-right: 4px;'>Uspešno inicializirana podatkovna baza</div>
                <div class='uspesno'><img src='Slike/tick-green.svg' width='17px' height='17px' style='padding-right: 4px;'>Uspešno dodan Administrator</div>

                <div class='uspesno' style='font-size: 20px; padding-bottom: 1px;'>Vse vredu</div>
                <div class='uspesno' style='color:red'>(Izbrišite datoteko install.php, za večjo varnost)</div>
                <div class='VzpostavljanjePBNaslov'>Zdaj se lahko <a href='Prijava.php' style='color:F68D2F'>prijavite</a></div>"; }
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