<?php 
session_start();
if(!isset($_SESSION['UprIme']) && !isset($_SESSION['Pravila'])){
    header("location: Prijava.php");
    exit;
}

if($_SESSION['Pravila'] == "Admin"){
    header("location: Domov.php");
    exit;
}

unset($_SESSION['temp']);

?>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Branje</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="Branje.css">
        <link rel="stylesheet" type="text/css" href="DataTables/datatables.min.css"/>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script type="text/javascript" src="DataTables/datatables.min.js"></script>
        <script type="text/javascript" src="DataTables/datetime.js"></script>
        <script type="text/javascript" src="DataTables/moment.js"></script>
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
                <div class="uspesnododano">
                    <?php 
                        if(isset($_GET['uspeh']) && $_GET['uspeh'] == "dodano"){
                            echo "<div class='uspesno'><img src='Slike/tick-green.svg' width='17px' height='17px' style='padding-right: 4px; padding-left: 4px;'>Uspešno Dodano</div>";
                        }

                        if(isset($_GET['uspeh']) && $_GET['uspeh'] == "spremenjeno"){
                            echo "<div class='uspesno'><img src='Slike/tick-green.svg' width='17px' height='17px' style='padding-right: 4px; padding-left: 4px;'>Uspešno Spremenjeno</div>";
                        }

                        if(isset($_GET['uspeh']) && $_GET['uspeh'] == "izbrisano"){
                            echo "<div class='uspesno'><img src='Slike/tick-green.svg' width='17px' height='17px' style='padding-right: 4px; padding-left: 4px;'>Uspešno Izbrisano</div>";
                        }
                    
                    ?>
                    
                </div>
                <div class="tablediv">
                    <table id="tabela" class="tabela" style="width: 100%">
                        <thead>
                            <tr>
                                <?php 
                                define('LahkoPovezava', TRUE);
                                require("PovezavaZBazo.php");
                                $tabelafilter = filter_input(INPUT_GET, 'tabela', FILTER_SANITIZE_STRING);

                                $tabela = mysqli_real_escape_string($povezava, $tabelafilter);

                                if($tabela == "Uporabnik" || $tabela == "Prenosi" || $tabela == "Posta"){
                                    mysqli_close($povezava);
                                    header("location: Domov.php");
                                    exit;
                                }

                                $sql = "SHOW columns FROM $tabela;";

                                $rezultat = mysqli_query($povezava, $sql);

                                $tabele = array();

                                if($rezultat == true && mysqli_num_rows($rezultat) > 0){

                                    //Preveri če je tabela Prodaja oz. Načrtovani_Prevzemi, Stranka in Izdelki če je zapiše drugačni zapored stolpcev
                                    if($tabela == "Prodaja"){
                                        echo "<th>ID Prodaje</th>";
                                        array_push($tabele, array("id_prodaje", "PRI"));

                                        echo "<th>Datum Prodaje</th>";
                                        array_push($tabele, array("Datum_Prodaje", ""));

                                        echo "<th>ID Stranke</th>";
                                        array_push($tabele, array("id_stranke", "MOL"));

                                        echo "<th>Priimek</th>";
                                        array_push($tabele, array("Priimek", "MOL"));

                                        echo "<th>Ime</th>";
                                        array_push($tabele, array("Ime", "MOL"));     
                                                                            
                                        echo "<th>Izdelek</th>";
                                        array_push($tabele, array("Izdelek", "MOL"));

                                        echo "<th>Količina</th>";
                                        array_push($tabele, array("Koliko", "")); 

                                        echo "<th>Merska Enota</th>";
                                        array_push($tabele, array("Merska_enota", "")); 

                                        echo "<th>Datum Vpisa</th>";
                                        array_push($tabele, array("Datum_Vpisa", ""));                                        

                                        echo "<th>Vpisal</th>";
                                        array_push($tabele, array("Uporabnisko_ime", "MOL"));
                                    }
                                    else if($tabela == "Nacrtovani_Prevzemi"){
                                        echo "<th>ID Nacrtovanega Prevzema</th>";
                                        array_push($tabele, array("id_nacrtovani_prevzem", "PRI"));

                                        echo "<th>Izdelek</th>";
                                        array_push($tabele, array("Izdelek", "MOL"));

                                        echo "<th>Količina</th>";
                                        array_push($tabele, array("Kolicina", ""));

                                        echo "<th>Merska Enota</th>";
                                        array_push($tabele, array("Merska_enota", ""));

                                        echo "<th>Dan</th>";
                                        array_push($tabele, array("Dan", ""));

                                        echo "<th>Čas</th>";
                                        array_push($tabele, array("Cas", ""));

                                        echo "<th>ID Stranke</th>";
                                        array_push($tabele, array("id_stranke", "MOL"));

                                        echo "<th>Priimek</th>";
                                        array_push($tabele, array("Priimek", "MOL"));

                                        echo "<th>Ime</th>";
                                        array_push($tabele, array("Ime", "MOL"));     

                                        echo "<th>Datum Enkratnega prevzema</th>";
                                        array_push($tabele, array("Cas_Enkrat", ""));      
                                    }
                                    else if($tabela == "Stranka"){
                                        echo "<th>ID Stranke</th>";
                                        array_push($tabele, array("id_stranke", "PRI"));

                                        echo "<th>Priimek</th>";
                                        array_push($tabele, array("Priimek", ""));

                                        echo "<th>Ime</th>";
                                        array_push($tabele, array("Ime", ""));

                                        echo "<th>Naslov</th>";
                                        array_push($tabele, array("Naslov", ""));

                                        echo "<th>Pošta</th>";
                                        array_push($tabele, array("Posta", ""));

                                        echo "<th>Kraj</th>";
                                        array_push($tabele, array("Kraj", ""));

                                    }    
                                    else if($tabela == "Izdelek"){
                                        echo "<th>Izdelek</th>";
                                        array_push($tabele, array("Izdelek", "PRI"));

                                        echo "<th>Merska enota</th>";
                                        array_push($tabele, array("Merska_enota", ""));

                                        echo "<th>Cena</th>";
                                        array_push($tabele, array("Cena", ""));

                                        echo "<th>Ekološko</th>";
                                        array_push($tabele, array("Ekolosko", ""));
                                    }                                    
                                    else{
                                        while($vrstica = mysqli_fetch_assoc($rezultat)){
                                            echo "<th>". str_replace("_", " ", $vrstica['Field'])."</th>";
                                            array_push($tabele, array($vrstica['Field'], $vrstica['Key']));
                                                                                    
                                        }
                                    }
                                    
                                }
                                
                                

                                ?>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div class="dodajanjediv"><a href="Dodajanje.php?tabela=<?php echo $tabela;?>" class="dodajanje">+</a></div>
            </div>

            <script>
                $(document).ready(function() {

                    
                $('#tabela').DataTable( {
                    "ajax": {
                        "url": 'api/branje.php?tabela=<?php echo $tabela;?>',
                        "dataType": "json",
                        
                        
                        "beforeSend": function (xhr) {
                        xhr.setRequestHeader("Authorization",
                        "Bearer " + "<?php if(isset($_SESSION['Token'])){echo $_SESSION['Token'];} ?>");
                        }
                        
                    },

                    "columns": [
                        <?php 
                            for($i = 0; $i < count($tabele); $i++){
                                echo "{\"data\": \"". $tabele[$i][0] ."\"},\n";

                                if($tabele[$i][1] == "PRI"){
                                    $primaryatribut = $tabele[$i][0];
                                }
                            } 
                            
                        ?>
                        {"data": "<?php echo $tabele[0][0]?>",
                        "orderable": false,
                        "render": function(data, type, row){
                             return '<span style="display:flex; justify-content: right;"><a href="Spreminjanje.php?tabela=<?php echo $tabela . "&"; echo $primaryatribut; ?>=' + row['<?php echo $primaryatribut; ?>'] + '" style="padding-right:10px;"><img src="Slike/pencil.svg" width="20px" height="20px"></a> <a href="Izbris.php?tabela=<?php echo $tabela . "&"; echo $primaryatribut; ?>=' + row['<?php echo $primaryatribut; ?>'] + '"><img src="Slike/trash-can.svg" width="20px" height="20px"></a></span>'
                            }
                        }
                    ],

                    "columnDefs": [
                        <?php 
                        if($tabela == "Prodaja"){
                            echo "{ className: \"levo_table_border\", targets: 1 },
                            { className: \"levo_table_border\", targets: 2 },
                            {  className: \"levo_table_border\", targets: 5 },
                            { className: \"levo_table_border\", targets: 8 },
                            {targets: 1, render: $.fn.dataTable.render.moment( 'YYYY-MM-DD HH:mm:ss', 'DD.MM.YYYY HH:mm:ss' )},
                            {targets: 8, render: $.fn.dataTable.render.moment( 'YYYY-MM-DD HH:mm:ss', 'DD.MM.YYYY HH:mm:ss' )}";
                            
                        }  
                        else if($tabela == "Nacrtovani_Prevzemi"){
                            echo "{ className: \"levo_table_border\", targets: 1 },
                            { className: \"levo_table_border\", targets: 4 },
                            {  className: \"levo_table_border\", targets: 6 },
                            { className: \"levo_table_border\", targets: 9 },
                            { \"type\": \"Dan\", targets: 4 },
                            { \"type\": \"Cas\", targets: 5 },
                            {targets: 9, render: $.fn.dataTable.render.moment( 'YYYY-MM-DD HH:mm:ss', 'DD.MM.YYYY HH:mm:ss' )}";
                            
                        } 
                        else if($tabela == "Stranka"){
                            echo "{ className: \"levo_table_border\", targets: 3}";
                        } 
                        else{
                            echo "{\"defaultContent\": \"\",";
                            echo "\"targets\": \"_all\"}";
                        }
                            
                        ?>
                        
                        
                    ],
                    "scrollX": "true"
                } );

                //Dan sortiranje za Nacrtovani_Prevzemi
                function STDan(dan) {                
                    var stevilka;
                    
                    if (dan == "Ponedeljek"){
                        stevilka = 1;
                    } else if (dan == "Torek"){
                        stevilka = 2;
                    } else if (dan == "Sreda") {
                        stevilka = 3;
                    } else if (dan == "Četrtek") {
                        stevilka = 4;
                    } else if (dan == "Petek") {
                        stevilka = 5;
                    } else if(dan == "Sobota"){
                        stevilka = 6;
                    } else if(dan == "Nedelja"){
                        stevilka = 7;
                    } else {
                        stevilka = 0;
                    }
                    
                    return stevilka;
                }


                $.fn.dataTableExt.oSort["Dan-desc"] = function (x, y) {
                    if(STDan(x) < STDan(y)){
                        return 1;
                    }

                    return -1;
                };

                $.fn.dataTableExt.oSort["Dan-asc"] = function (x, y) {
                    if(STDan(x) > STDan(y)){
                        return 1;
                    }

                    return -1;
                }


                //Cas sortiranje za Nacrtovani_Prevzemi
                function STCas(cas) {                
                    var stevilka;
                    
                    if (cas == "Cel"){
                        stevilka = 1;
                    } else if (cas == "Zjutraj"){
                        stevilka = 2;
                    } else if (cas == "Sredi") {
                        stevilka = 3;
                    } else if (cas == "Zvečer") {
                        stevilka = 4;
                    } else {
                        stevilka = 0;
                    }
                    
                    return stevilka;
                }


                $.fn.dataTableExt.oSort["Cas-desc"] = function (x, y) {
                    if(STCas(x) < STCas(y)){
                        return 1;
                    }

                    return -1;
                };

                $.fn.dataTableExt.oSort["Cas-asc"] = function (x, y) {
                    if(STCas(x) > STCas(y)){
                        return 1;
                    }

                    return -1;
                }

            } );
            </script>
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