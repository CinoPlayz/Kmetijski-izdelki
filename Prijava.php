<?php 
session_start();

if(isset($_SESSION['UprIme']) || isset($_SESSION['Pravila'])){
    header("location: Domov.php");
    exit;
}

if(isset($_POST['upime']) && isset($_POST['geslo'])){
    $upfilter = htmlspecialchars($_POST['upime'], ENT_QUOTES);
    $geslofilter = htmlspecialchars($_POST['geslo'], ENT_QUOTES);

    if(empty($upfilter)){
        RedirectZNapako(1);
    }

    if(empty($geslofilter)){
        $_SESSION['temp'] = $upfilter;  
        RedirectZNapako(2);
    }

    define('LahkoPovezava', TRUE);

    require("PovezavaZBazo.php");

    $up = mysqli_real_escape_string($povezava, $upfilter);
    $geslo = mysqli_real_escape_string($povezava, $geslofilter);

    if(empty($upfilter)){
        mysqli_close($povezava);
        RedirectZNapako(1);
    }

    if(empty($geslofilter)){
        mysqli_close($povezava);
        $_SESSION['temp'] = $up; 
        RedirectZNapako(2);
    }

    unset($_SESSION['temp']);

    $sql = "SELECT Geslo, Pravila FROM Uporabnik WHERE Uporabnisko_ime='$up'";

    $rezultat = mysqli_query($povezava, $sql);
    if(mysqli_num_rows($rezultat) > 0){

        $vrstica = mysqli_fetch_assoc($rezultat);

        if(password_verify($geslo, $vrstica['Geslo'])){
            $_SESSION['UprIme'] = $up;
            $_SESSION['Pravila'] = $vrstica['Pravila'];

            $token = newToken(80);
            $_SESSION['Token'] = $token;

            $sql = "UPDATE Uporabnik SET TokenWeb = '" . hash("sha256", $token) . "' WHERE Uporabnisko_ime='$up'";
            mysqli_query($povezava, $sql);

            mysqli_close($povezava);
            header("location: Domov.php");
            exit;
        }
        else{
            mysqli_close($povezava);
            RedirectZNapako(3);
        }
    }
    else{
        mysqli_close($povezava);
        RedirectZNapako(3); 
    }
}






function RedirectZNapako($napaka){
    header("location: Prijava.php?napaka=$napaka");
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

?>

<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Prijava</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="Prijava.css">
    </head>
    <body>
        <div class="vse">
            <div class="glava">
                <div>
                    <a href="Domov.php"><img src="Slike/nutrition.svg" width="40px" height="40px"></a>
                </div>

                <div class="flexfill"></div>

                <div class="prijava">
                    <span class="prijava"><a href="Prijava.php" class="prijavaA">Prijava</a></span>
                </div>
            </div>
            
            <div class="menu">
                <div class="menuItem"><a class="menuItemA" href="Domov.php">Domov</a></div>
            </div>

            <div class="vsebina">
                <div class="formdiv">
                    <form method="post" action="Prijava.php">
                        <div class="formvnosi">

                            <div class="formvnosItem">
                                <div class="vnosNaslov">Uporabni??ko ime:</div>
                                <input type="text" name="upime" class="ipPB" value="<?php if(isset($_SESSION['temp'])){echo $_SESSION['temp'];} ?>">
                            </div>

                            <div class="formvnosItem">
                                <div class="vnosNaslov">Geslo:</div>
                                <input type="password" name="geslo" class="ipPB">
                            </div>

                            <div class="formvnosItem">
                                <input type="submit">
                            </div> 

                            <?php 
                                if(isset($_GET['napaka'])){
                                    switch($_GET['napaka']){
                                        case 1 : echo "<div class='napaka'>Vpi??ite veljaveno Uporabni??ko ime</div>";
                                            break;
                                        case 2 : echo "<div class='napaka'>Vpi??ite veljaveno Geslo</div>";
                                            break;
                                        case 3 : echo "<div class='napaka'>Uporabni??ko ime/Geslo je narobe</div>";
                                            break;
                                    }
                                }
                            
                            ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="noga">
                <div>
                    <img src="Slike/nutrition.svg" width="80px" height="80px">
                </div>

                <div class="nogaMenu">
                    <div class="nogaMenuItem"><a href="Domov.php" class="nogaMenuItemA">Domov</a></div>
                    <div class="nogaMenuItem"><a href="Prijava.php" class="nogaMenuItemA">Prijava</a></div>
                </div>
            </div>
        </div>
    </body>
</html>