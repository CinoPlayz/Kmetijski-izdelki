<?php 
session_start();
if(!isset($_SESSION['UprIme']) && !isset($_SESSION['Pravila'])){
    header("location: ../Prijava.php");
    exit;
}

if($_SESSION['Pravila'] == "Admin"){
    header("location: ../Admin/DomovAdmin.php");    
    exit;
}

define('LahkoPovezava', TRUE);
require("../PovezavaZBazo.php");

?>

<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Prikaz podatkov za račune</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="../JS/datalistVsiElemnti.js"></script>
        <script type="text/javascript" src="../DataTables/moment.js"></script>
        <script type="text/javascript" src="../JS/sorttable.js"></script>
        <link rel="stylesheet" href="Racuni.css">
    </head>
    <body>
        <div class="vse">
            <div class="glava">
                <div>
                    <a href="../Domov.php"><img src="../Slike/nutrition.svg" width="40px" height="40px"></a>
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
                <div class="racuninaslov">Prikaz podatkov za račune</div>
                <div class="formdiv">
                    <form>
                        <div class="divdatuma">
                            <div class="divdatum">
                                <div>Od:</div>
                                <div><input type="date" name="DatumOd" id="DatumOd"></div>
                            </div>

                            <div class="divdatum">
                                <div>Do:</div>
                                <div><input type="date" name="DatumDo" id="DatumDo"></div>
                            </div>
                        </div>

                        <div class="strankadiv">
                            <?php
                                $sql = "SELECT Priimek, Ime, id_stranke FROM Stranka";

                                $rezultatStranka = mysqli_query($povezava, $sql);

                                if(mysqli_num_rows($rezultatStranka) > 0){
                                echo "<div>Stranka:</div>";
                                echo "<input list='Stranke' id='Stranka' name='Stranka'/>";
                                echo "<datalist id='Stranke'>";

                                while($vrsticaStranka = mysqli_fetch_assoc($rezultatStranka)){
                                    
                                    echo "<option value='" . $vrsticaStranka['Priimek'] . " " . $vrsticaStranka['Ime'] . " - " . $vrsticaStranka['id_stranke'] . "'>";                                                            
                                    
                                }
                                echo "</datalist>";
                                }
                            ?>
                        </div>

                        <div class="izdelekdiv">
                            <?php
                                $sql = "SELECT Izdelek, Merska_enota FROM Izdelek";

                                $rezultatIzdelek = mysqli_query($povezava, $sql);

                                if(mysqli_num_rows($rezultatIzdelek) > 0){
                                    echo "<div>Izdelek:</div>";
                                    echo "<select name='Izdelek' id='Izdelek'>";
                                    echo "<option value='*'>Vsi</option>";

                                    while($vrsticaIzdelek = mysqli_fetch_assoc($rezultatIzdelek)){
                                        
                                    echo "<option value='" . $vrsticaIzdelek['Izdelek'] . "'>". $vrsticaIzdelek['Izdelek'] ."</option>";                                                            
                                        
                                    }
                                    echo "</select>";
                                }

                                mysqli_close($povezava);
                            ?>    
                        </div>

                        <div class="napaka">Neka Napaka</div>
                                 
                        <div class="submitdiv">
                            <input type="submit">
                        </div>

                        <div class="prazno">Ni podatkov</div>

                        <div class="izdelki">

                        </div>

                        <div>
                            <table class="Podatki_table sortable">
                                <thead>
                                    <tr>
                                        <th>ID Prodaje</th> 
                                        <th>Datum Prodaje</th>
                                        <th>Izdelek</th>  
                                        <th>Količina</th>
                                        <th>Merska Enota</th>
                                    </tr>
                                </thead>
                                <tbody class="Podatki_table_body">

                                </tbody>
                            </table>
                        </div>
                        
                    </form>
                </div>
            </div>

            <script>
                PokaziKlikPuscica('Stranka');
                $(document).ready(function () {

                    var datumzacetek = moment().subtract(1, 'months').startOf('month').format('YYYY-MM-DD');
                    var datumkonec = moment().subtract(1, 'months').endOf('month').format('YYYY-MM-DD');

                    $("#DatumOd").attr("value", datumzacetek);
                    $("#DatumDo").attr("value", datumkonec);

                    $("form").submit(function (event) {

                        $(".prazno").css("display", "none");
                        $(".Podatki_table").css("display", "none");
                        $(".izdelki").css("display", "none");

                        //Dobi vse podatke za poslat
                        let DatumOd = $("#DatumOd").val();
                        let DatumDo = $("#DatumDo").val();
                        let Stranka = $("#Stranka").val();
                        let Izdelek = $("#Izdelek").val();

                        var naprej = true;

                        //Preveri da podatki niso prazni
                        if(typeof(DatumOd) == "undefined" || DatumOd === null || DatumOd == ""){
                            naprej = false;
                            $(".napaka").css("display", "block");
                            $(".napaka").text("Izberite ustrezni začetni datum");
                        }  
                        
                        if((typeof(DatumDo) == "undefined" || DatumDo === null || DatumDo == "") && naprej == true){
                            naprej = false;
                            $(".napaka").css("display", "block");
                            $(".napaka").text("Izberite ustrezni končni datum");
                        } 

                        if((typeof(Stranka) == "undefined" || Stranka === null || Stranka == "") && naprej == true){
                            naprej = false;
                            $(".napaka").css("display", "block");
                            $(".napaka").text("Izberite ustrezno stranko");
                        } 

                        if((typeof(Izdelek) == "undefined" || Izdelek === null || Izdelek == "") && naprej == true){
                            naprej = false;   
                            $(".napaka").css("display", "block");
                            $(".napaka").text("Izberite ustrezni izdelek");                         
                        } 

                        //Datum žačetni mora biti pozneje kot končni datum
                        if(naprej === true){
                            let DatumOdObjekt = new Date(DatumOd);
                            let DatumDoObjekt = new Date(DatumDo);

                            if(DatumOdObjekt > DatumDoObjekt){
                                naprej = false;   
                                $(".napaka").css("display", "block");
                                $(".napaka").text("Začetni datum je pozneje kot končni datum");
                            }
                        }

                        //Pošiljanje podatkov
                        if(naprej === true){

                            $(".napaka").css("display", "none");
                            $(".napaka").text("Napaka"); 
                            $(".Podatki_table_body").empty();       
                            $(".izdelki").empty();                        

                            var formData = {
                            DatumOd: $("#DatumOd").val(),
                            DatumDo: $("#DatumDo").val(),
                            Stranka: $("#Stranka").val(),
                            Izdelek: $("#Izdelek").val(),
                            tabela: "Prodaja",
                            };

                            $.ajax({
                                type: "GET",
                                url: "../api/branje.php",
                                headers: {
                                    'Authorization':'Bearer <?php if(isset($_SESSION['Token'])){echo $_SESSION['Token'];} ?>',
                                },
                                data: formData,
                                dataType: "json",
                                encode: true,
                                success: function(output, status, xhr) { 

                                    $(".Podatki_table").css("display", "block");
                                    $(".izdelki").css("display", "flex");

                                    //var izdelki = Array.from(Array(2), () => new Array(2));
                                    var koliko_izdelkov = 0;
                                    var izdelki = [];
                                    var kolicina_izdelek = [];
                                    var merske_enote = [];

                                    $.each(output.data, function(index, vrednost){
                                        var datum = moment(vrednost.Datum_Prodaje, 'YYYY-MM-DD HH:mm:ss').format("DD.MM.YYYY HH:mm:ss");
                                        var datumsort = moment(vrednost.Datum_Prodaje, 'YYYY-MM-DD HH:mm:ss').format("YYYYMMDDHHmmss");

                                        $(".Podatki_table_body").append("<tr><td>" + vrednost.id_prodaje + "</td><td sorttable_customkey='" + datumsort + "'>" + datum + "</td><td>" + vrednost.Izdelek + "</td><td>" + vrednost.Koliko + "</td><td>" + vrednost.Merska_enota +  "</td></tr>");

                                        //Pregleda če je izdelek že v arrayu, če ni ga da in da v array kolicina_izdelek novo vrednost za tisto količino
                                        //če je samo prišteje količino že prej količini
                                        var izdelekVarrayu = $.inArray(vrednost.Izdelek, izdelki);
                                        if(izdelekVarrayu !== -1){    
                                            kolicina_izdelek[izdelekVarrayu] += parseInt(vrednost.Koliko);                                      
                                        }
                                        else{
                                            izdelki.push(vrednost.Izdelek);
                                            kolicina_izdelek.push(parseInt(vrednost.Koliko));
                                            merske_enote.push(vrednost.Merska_enota);
                                        }
                                    });

                                    //Izpiše količine za vsak izdelek
                                    $.each(izdelki, function(index, vrednost){
                                        $(".izdelki").append("<div class='izdelek'><div class='izdelek_ime'>" + vrednost + "</div><div>" + kolicina_izdelek[index] + "<span class='niselectable'> </span>" + merske_enote[index] + "</div></div>");
                                    });

                                    

                                },
                                error: function(jqXHR, exception) {
                                    if(jqXHR.responseText = "{\"sporocilo\":\"Ni najdena tabela oz. tabela je prazna\"}"){
                                        $(".prazno").css("display", "block");
                                    }
                                    else{
                                        $(".prazno").css("display", "block");
                                        $(".prazno").text("Napaka: " + jqXHR.responseText);
                                    }
                                }
                            });

                            
                        }

                        event.preventDefault();
                        
                    });
                });
            </script>

            <div class="noga">
                <div>
                    <img src="../Slike/nutrition.svg" width="80px" height="80px">
                </div>

                <div class="nogaMenu">
                    <div class="nogaMenuItem"><a href="../Domov.php" class="nogaMenuItemA">Domov</a></div>
                </div>
            </div>
        </div>
    </body>
</html>