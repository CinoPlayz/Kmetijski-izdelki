<?php 
session_start();
if(!isset($_SESSION['UprIme']) && !isset($_SESSION['Pravila'])){
    header("location: ../Prijava.php");
    exit;
}

if($_SESSION['Pravila'] != "Admin"){
    header("location: ../Domov.php");
    exit;
}

define('LahkoPovezava', TRUE);

//Preveri, če je veljaven token za tistega ko misli izbrisat drugim ta token
if(VeljavniToken($_SESSION['Token']) === false){
    mysqli_close($povezava);
    header("location: ../Odjava.php");
    exit;
}

if(isset($_POST['poslano'])){

    if(isset($_POST['kateri_uporabniki']) || isset($_POST['vrsta_tokena'])){

        require("../PovezavaZBazo.php");

        //Dobi podatke o katerem tokenu naj izbriše
        $KateriToken = array();

        foreach($_POST['vrsta_tokena'] as $podatekVnosToken){
            $podatekfilter = htmlspecialchars($podatekVnosToken, ENT_QUOTES);
            $podatek = mysqli_real_escape_string($povezava, $podatekfilter);

            array_push($KateriToken, $podatek);
        }

        if(empty($KateriToken)){
            mysqli_close($povezava);
            header("location: IzbrisTokenovAdmin.php?napaka=1");
            exit;
        }


        //Izbriše podatke uporabnikom

        if(empty($_POST['kateri_uporabniki'])){
            mysqli_close($povezava);
            header("location: IzbrisTokenovAdmin.php?napaka=2");
            exit;
        }

        foreach($_POST['kateri_uporabniki'] as $podatekVnos){
            $podatekfilter = htmlspecialchars($podatekVnos, ENT_QUOTES);
            $podatek = mysqli_real_escape_string($povezava, $podatekfilter);

            if(in_array("Mobilni", $KateriToken)){
                $sql = "UPDATE Uporabnik SET TokenAndroid = NULL WHERE Uporabnisko_ime='$podatek';";
                mysqli_query($povezava, $sql);
            }

            if(in_array("Web", $KateriToken)){
                $sql = "UPDATE Uporabnik SET TokenWeb = NULL WHERE Uporabnisko_ime='$podatek';";
                mysqli_query($povezava, $sql);
            }

        }

        mysqli_close($povezava);
        header("location: IzbrisTokenovAdmin.php?uspeh=uspeh");
        exit;
    }
    else{
        header("location: IzbrisTokenovAdmin.php?napaka=0");
        exit;
    }
}

function VeljavniToken($token){
    require("../PovezavaZBazo.php");
    $sql = "SELECT * FROM Uporabnik WHERE TokenWeb = '" . hash("sha256", $token) . "';";
    $rezultat = mysqli_query($povezava, $sql);

    $veljaven = false;

    if($rezultat == true && mysqli_num_rows($rezultat) > 0){
        $veljaven = true;
    }

    return $veljaven;
}


?>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Izbris Tokenov Admin</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="IzbrisTokenovAdmin.css">
    </head>
    <body>
        <div class="vse">
            <div class="glava">
                <div>
                    <a href="Domov.php"><img src="../Slike/nutrition.svg" width="40px" height="40px"></a>
                </div>

                <div class="flexfill"></div>

                <div class="odjava">
                    <span class="odjava"><a href="../Odjava.php" class="odjavaA">Odjava</a></span>
                </div>
            </div>
            
            <div class="menu">
                <div class="menuItem"><a class="menuItemA" href="../Domov.php">Domov</a></div>
            </div>

            <div class="vsebina">
                <div class="uspesnoizbrisano">
                    <?php 
                        if(isset($_GET['uspeh']) && $_GET['uspeh'] == "uspeh"){
                            echo "<div class='uspesno'><img src='../Slike/tick-green.svg' width='17px' height='17px' style='padding-right: 4px; padding-left: 4px;'>Uspešno izbrisano</div>";
                        }
                    ?>
                </div>
                
                <form action="IzbrisTokenovAdmin.php" method="post">
                    <div class="izbiradiv">
                        <div class="uporabniki">
                            <div class="izberivse">
                                <input type="checkbox" onClick="IzberiVse(this)" /><span>Izberi vse</span>
                            </div>
                            <?php 
                                require("../PovezavaZBazo.php");
                                $sql = "SELECT Uporabnisko_ime, Priimek, Ime FROM Uporabnik";     
                                
                                $rezultat = mysqli_query($povezava, $sql);

                                if($rezultat == true && mysqli_num_rows($rezultat) > 0){

                                    while($vrstica = mysqli_fetch_assoc($rezultat)){
                                        echo "<div class='uporabnik'>";
                                        echo "<input type='checkbox' id='kateri_uporabniki' name='kateri_uporabniki[]' value='". $vrstica['Uporabnisko_ime'] ."'>";
                                        echo "<div>";
                                        echo $vrstica['Uporabnisko_ime'] ." (". $vrstica['Priimek'] ." ". $vrstica['Ime'];
                                        echo ")</div>";
                                        echo "</div>";
                                    }
                                }
                            ?>
                        </div>
                        <div class="vrstatokenadiv">
                            <div>Vrsta tokena za izbris:</div>
                            <div>
                                <div>
                                    <input type='checkbox' id='kateri_uporabniki' name='vrsta_tokena[]' value='Mobilni' checked>
                                    <span>Mobilni</span>
                                </div>
                                <div>
                                    <input type='checkbox' id='kateri_uporabniki' name='vrsta_tokena[]' value='Web' checked>
                                    <span>Web</span>
                                </div>
                            </div>
                            
                            
                        </div>
                    </div>
                    
                    <?php
                    if(isset($_GET['napaka'])){
                        switch($_GET['napaka']){
                            case 0: echo "<div class='napaka'>Izberite uporabnike in vrsto tokena za izbris</div>";
                            break;

                            case 1: echo "<div class='napaka'>Izberite vrsto tokena za izbris</div>";
                            break;

                            case 2: echo "<div class='napaka'>Izberite uporabnik/-e za izbris</div>";
                            break;
                        }
                    }
                        
                    ?>
                    <input type="hidden" name="poslano" value="poslano">
                    <div class="submitdiv">
                        <input type="submit">
                    </div>
                    
                </form>
                
                
            </div>

            <script>
                function IzberiVse(prvoten_checkbox){
                    checkboxi = document.getElementsByName("kateri_uporabniki[]");

                    for(var i=0, n=checkboxi.length; i<n; i++) {
                        checkboxi[i].checked = prvoten_checkbox.checked;
                    }
                }                
            </script>
            
            <div class="noga">
                <div>
                    <img src="../Slike/nutrition.svg" width="80px" height="80px">
                </div>

                <div class="nogaMenu">
                    <div class="nogaMenuItem"><a href="Domov.php" class="nogaMenuItemA">Domov</a></div>
                </div>
            </div>
        </div>
    </body>
</html>