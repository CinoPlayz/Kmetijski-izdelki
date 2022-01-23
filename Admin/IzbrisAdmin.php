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

//echo $_POST['izbris'];


?>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Izbris Admin</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="IzbrisAdmin.css">
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
                <div class="prepricani">Ali res želite izbrisati ta vnos?</div>
                <div>
                    <form action="IzbrisAdmin.php" method="POST">
                        <input type="submit" value="DA" name="izbris">
                        <span style="padding-right:  3vw;"></span>
                        <input type="submit" value="NE" name="izbris">
                    </form>
                </div>

                <div class="prepricani" style="color: red;">To bo izbrisalo naslednje podatke:</div>

                <?php 
                    require("../PovezavaZBazo.php");

                    $tabelafilter = filter_input(INPUT_GET, 'tabela', FILTER_SANITIZE_STRING);

                    $tabela = mysqli_real_escape_string($povezava, $tabelafilter);

                    $stolpec = "";

                    $sql = "SHOW columns FROM $tabela;";

                    $rezultat = mysqli_query($povezava, $sql);

                    if($rezultat == true && mysqli_num_rows($rezultat) > 0){
                        while($vrstica = mysqli_fetch_assoc($rezultat)){
                            if($vrstica['Key'] == "PRI"){
                                $stolpec = $vrstica['Field'];
                            }
                        }

                        if(!empty($stolpec)){
                            $podatekfilter = filter_input(INPUT_GET, $stolpec , FILTER_SANITIZE_STRING);

                            $podatek = mysqli_real_escape_string($povezava, $podatekfilter);

                            if(!empty($podatek)){
                                

                                echo "<div class='tablediv'>";
                                echo "<div class='tabela'>". ucfirst($tabela) .":</div>";
                                echo "<table>";

                                $sql = "SHOW columns FROM $tabela;";

                                $rezultatStolpci = mysqli_query($povezava, $sql);

                                while($vrstica = mysqli_fetch_array($rezultatStolpci)){
                                    echo "<th>" . str_replace("_", " ", $vrstica['Field'])  . "</th>";
                                }

                                if(is_numeric($podatek)){
                                    $sql = "SELECT * FROM $tabela WHERE $stolpec=$podatek;";
                                }
                                else{
                                    $sql = "SELECT * FROM $tabela WHERE $stolpec='$podatek';";
                                }


                                $rezultat = mysqli_query($povezava, $sql);

                                while($vrstica = mysqli_fetch_row($rezultat)){
                                    echo "<tr>";

                                    for($p = 0; $p < count($vrstica); $p++){
                                        echo"<td>" . $vrstica[$p] . "</td>";
                                    }
                                    echo "</tr>";
                                }

                                echo "</table>";

                                echo "</div>";



                                $sql = "SELECT 
                                    TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
                                FROM
                                    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                                WHERE
                                    REFERENCED_TABLE_SCHEMA = '$podatkovnabaza' AND
                                    REFERENCED_TABLE_NAME = '$tabela' AND
                                    REFERENCED_COLUMN_NAME = '$stolpec'";

                                $ForeignKeyTabeleAtribut = array();

                                $rezultat = mysqli_query($povezava, $sql);

                                if(mysqli_num_rows($rezultat) > 0){
                                    while($vrstica = mysqli_fetch_assoc($rezultat)){
                                        array_push($ForeignKeyTabeleAtribut, array("TABLE_NAME" => $vrstica['TABLE_NAME'], "COLUMN_NAME" => $vrstica['COLUMN_NAME']));
                                    }

                                    $sql = "";

                                    $tabelainQuery = array();
                                    for($i = 0; $i < count($ForeignKeyTabeleAtribut); $i++){

                                        if(is_numeric($podatek)){
                                            $sql = "SELECT * FROM ". $ForeignKeyTabeleAtribut[$i]['TABLE_NAME'] . " WHERE " . $ForeignKeyTabeleAtribut[$i]['COLUMN_NAME'] . "=$podatek;";
                                        }
                                        else{
                                            $sql = "SELECT * FROM ". $ForeignKeyTabeleAtribut[$i]['TABLE_NAME'] . " WHERE " . $ForeignKeyTabeleAtribut[$i]['COLUMN_NAME'] . "='$podatek';";
                                        }

                                        array_push($tabelainQuery, array($ForeignKeyTabeleAtribut[$i]['TABLE_NAME'], $sql));
                                        
                                    }

                                    for($i = 0; $i < count($tabelainQuery); $i++){
                                        $tabelaIzpis = str_replace("_", " ", $tabelainQuery[$i][0]);
                                        echo "<div class='tablediv'>";
                                        echo "<div class='tabela'>". ucfirst($tabelaIzpis) .":</div>";
                                        echo "<table>";

                                        $sql = "SHOW columns FROM " . $tabelainQuery[$i][0] . ";";
                                        $rezultatStolpci = mysqli_query($povezava, $sql);

                                        while($vrstica = mysqli_fetch_array($rezultatStolpci)){
                                            echo "<th>" . str_replace("_", " ", $vrstica['Field'])  . "</th>";
                                        }

                                        $rezultat = mysqli_query($povezava, $tabelainQuery[$i][1]);

                                        while($vrstica = mysqli_fetch_row($rezultat)){
                                            echo "<tr>";

                                            for($p = 0; $p < count($vrstica); $p++){
                                                echo"<td>" . $vrstica[$p] . "</td>";
                                            }
                                            echo "</tr>";
                                        }

                                        echo "</table>";

                                        echo "</div>";
                                    }


                                }

                                

                            }
                            else{
                                header("location: DomovAdmin.php");
                                exit;  
                            }
                        }
                        else{
                            header("location: DomovAdmin.php");
                            exit;  
                        }
                    }
                    else{
                        header("location: DomovAdmin.php");
                        exit;
                    }

                
                ?>


            </div>

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