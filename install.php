<?php 
    if(isset($_POST['ipPB']) && isset($_POST['upPB']) && isset($_POST['gesloPB'])){
        $ipfilter = filter_input(INPUT_POST, $ipPB, FILTER_SANITIZE_STRING);
        $upfilter = filter_input(INPUT_POST, $upPB, FILTER_SANITIZE_STRING);
        $geslofilter = filter_input(INPUT_POST, $gesloPB, FILTER_SANITIZE_STRING);

        if(empty($ipfilter)){
            RedirectZNapako(1);
        }

        if(empty($upfilter)){
            RedirectZNapako(2);
        }

        if(empty($geslofilter)){
            RedirectZNapako(3);
        }

    }

    function RedirectZNapako($napaka){
        header("location: install.php?napaka=$napaka");
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
                <div class="VzpostavljanjePBNaslov">Vzpostavljanje povezave s podatkovno bazo</div>
                <div class="formdiv">
                    <form method="post" action="">
                        <div class="formvnosi">
                            <div class="formvnosItem">
                                <div class="vnosNaslov">IP Naslov:</div>
                                <input type="text" name="ipPB" class="ipPB">
                            </div>

                            <div class="formvnosItem">
                                <div class="vnosNaslov">Uporabniško ime:</div>
                                <input type="text" name="upPB" class="ipPB">
                            </div>

                            <div class="formvnosItem">
                                <div class="vnosNaslov">Geslo:</div>
                                <input type="text" name="gesloPB" class="ipPB">
                            </div>  
                        </div>                      
                        <?php 
                            if(isset($_GET['napaka'])){
                                switch($_GET['napaka']){
                                    case 1 : echo("<div class='napaka'>Vpišite veljaven IP naslov</div>");
                                        break;
                                    case 2 : echo("<div class='napaka'>Vpišite veljaveno Uporabniško ime</div>");
                                        break;
                                    case 3 : echo("<div class='napaka'>Vpišite veljaveno Geslo</div>");
                                        break;
                                }

                                
                            }
                        
                        ?>
                        <div style="text-align: center;">
                            <input type="submit">
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
                </div>
            </div>
        </div>
    </body>
</html>