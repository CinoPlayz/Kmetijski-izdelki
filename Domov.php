<?php 
session_start();
if(!isset($_SESSION['UprIme']) && !isset($_SESSION['Pravila'])){
    header("location: Prijava.php");
    exit;
}

if($_SESSION['Pravila'] == "Admin"){
    header("location: Admin/DomovAdmin.php");    
    exit;
}

?>

<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Domov</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="Domov.css">
    </head>
    <body>
        <div class="vse">
            <div class="glava">
                <div>
                    <a href="Domov.php"><img src="Slike/nutrition.svg" width="40px" height="40px"></a>
                </div>

                <div class="flexfill"></div>

                <div class="odjava">
                    <span class="odjava"><a href="Odjava.php" class="odjavaA">Odjava</a></span>
                </div>
            </div>
            
            <div class="menu">
                <div class="menuItem"><a class="menuItemA" href="Domov.php">Domov</a></div>
            </div>

            <div class="vsebina">
                <div class="izbira">Izberite kaj želite urejati/brati:</div>
                <div class="bazadiv" style="padding-top: 20px;"><a class="bazaA" href="Branje.php?tabela=Izdelek">Izdeleki</a></div>
                <div class="bazadiv"><a class="bazaA" href="Branje.php?tabela=Prodaja">Prodaje</a></div>
                <div class="bazadiv"><a class="bazaA" href="Branje.php?tabela=Stranka">Stranke</a></div>
                <div class="bazadiv"><a class="bazaA" href="Branje.php?tabela=Nacrtovani_prevzemi">Načrtovani prevzemi</a></div>
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