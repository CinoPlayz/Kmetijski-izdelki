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

?>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Branje Admin</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="BranjeAdmin.css">
        <link rel="stylesheet" type="text/css" href="../DataTables/datatables.min.css"/>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script type="text/javascript" src="../DataTables/datatables.min.js"></script>
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
                <div class="tablediv">
                    <table id="tabela" class="tabela" style="width: 100%">
                        <thead>
                            <tr>
                                <?php 
                                require("../PovezavaZBazo.php");
                                $tabelafilter = filter_input(INPUT_GET, 'tabela', FILTER_SANITIZE_STRING);

                                $tabela = mysqli_real_escape_string($povezava, $tabelafilter);

                                $sql = "SHOW columns FROM $tabela;";

                                $rezultat = mysqli_query($povezava, $sql);

                                $tabele = array();

                                if($rezultat == true && mysqli_num_rows($rezultat) > 0){
                                    while($vrstica = mysqli_fetch_assoc($rezultat)){
                                        echo "<th>". str_replace("_", " ", $vrstica['Field'])."</th>";
                                        array_push($tabele, $vrstica['Field']);
                                    }
                                }                                

                                ?>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <script>
                $(document).ready(function() {
                $('#tabela').DataTable( {
                    "ajax": {
                        "url": '../api/branje.php?tabela=<?php echo $tabela;?>',
                        "dataType": "json",
                        
                        
                        "beforeSend": function (xhr) {
                        xhr.setRequestHeader("Authorization",
                        "Bearer " + "<?php if(isset($_SESSION['Token'])){echo $_SESSION['Token'];} ?>");
                        }
                        
                    },

                    "columns": [
                        <?php 
                            for($i = 0; $i < count($tabele); $i++){
                                echo "{\"data\": \"". $tabele[$i] ."\"},\n";
                            }    
                            
                        ?>
                        
                    ],

                    "columnDefs": [{
                        "defaultContent": "NULL",
                        "targets": "_all"
                    }],
                    "scrollX": "true"
                } );
            } );
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