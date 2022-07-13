<?php 

if(!defined('LahkoSporocila')) {
    http_response_code(403);
    exit;
}

function NapakaSporocilo($tabela, $stolpec){
    $vrni = false;
    switch($tabela){
        case "Izdelek":
    
            switch ($stolpec){
    
                case "Izdelek": echo "<div class='napaka'>Vpišite veljavni izdelek</div>";                
                break;
    
                case "Merska_enota": echo "<div class='napaka'>Izberite veljavno mersko enoto</div>";
                break;
    
                case "Cena": echo "<div class='napaka'>Vpišite veljavno ceno</div>";
                break;
    
                case "Ekolosko": echo "<div class='napaka'>Izberite možnost ali je izdelek ekološki</div>";
                break;

                default: $vrni = true;
                break;
    
            }    
        break;

        case "Prodaja":
    
            switch ($stolpec){
    
                case "Datum_Prodaje": echo "<div class='napaka'>Izberite veljavni datum</div>";                
                break;
    
                case "Datum_Vpisa": echo "<div class='napaka'>Izberite veljavni datum vpisa</div>";
                break;
    
                case "Koliko": echo "<div class='napaka'>Vpišite veljavno količino</div>";
                break;
    
                case "id_stranke": echo "<div class='napaka'>Izberite ustrezno stranko</div>";
                break;

                case "Izdelek": echo "<div class='napaka'>Izberite ustrezni izdelek</div>";
                break;
                

                default: $vrni = true;
                break;
    
            }    
        break;

        case "Stranka":
    
            switch ($stolpec){
    
                case "Ime": echo "<div class='napaka'>Vpišite veljavno ime</div>";                
                break;
    
                case "Priimek": echo "<div class='napaka'>Vpišite veljavni priimek</div>";
                break;
    
                case "Naslov": echo "<div class='napaka'>Vpišite veljavni naslov</div>";
                break;
    
                case "Posta": echo "<div class='napaka'>Izberite/Vpišite veljavno pošto</div>";
                break;

                case "Izdelek": echo "<div class='napaka'>Izberite ustrezni izdelek</div>";
                break;
                

                default: $vrni = true;
                break;
    
            }    
        break;

        case "Nacrtovani_Prevzemi":
    
            switch ($stolpec){
    
                case "Kolicina": echo "<div class='napaka'>Vpišite veljavno količino</div>";                
                break;
    
                case "Dan": echo "<div class='napaka'>Izberite ustrezni dan</div>";
                break;
    
                case "Cas": echo "<div class='napaka'>Izberite ustrezni čas</div>";
                break;

                case "Izdelek": echo "<div class='napaka'>Izberite ustrezni izdelek</div>";
                break;
                
                case "id_stranke": echo "<div class='napaka'>Izberite ustrezno stranko</div>";
                break;
                
                case "Cas_Enkrat": echo "<div class='napaka'>Izberite veljavni čas enkrat</div>";
                break;
                

                default: $vrni = true;
                break;
    
            }    
        break;
    
    }

    return $vrni;
}
