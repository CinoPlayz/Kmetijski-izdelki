function PokaziKlikPuscica(element){
    $('#' + element)
    .on('click', function(event){

        let elementkopija = $(this);
        //Preveri če je kliknjeno na puščico
        if((elementkopija.width() - (event.clientX - elementkopija.offset().left)) < 14){
            
            //Preveri če je input prazen, če ni shrani vrednost v placeholder
            if(elementkopija.val() !== ""){
                elementkopija.attr('placeholder', elementkopija.val());
                elementkopija.val('');
            }
        }
        else{
            VrniVrednost(element)
        }
    })
    //Ko gre miška izven inputa vrne vrednost
    .on('mouseleave', function(){
        VrniVrednost(this.id)
    })
    //Ko gre v input miška shrani vrednost v prej-placeholder
    .on('mouseenter', function(){
        if(!$(this).is("[prej-placeholder]")){
            $(this).attr('prej-placeholder', $(this).attr('placeholder'));
        }
    })
}

function VrniVrednost(element){
    let elementkopija = $('#' + element);

    //Preveri če je vrednost inputa nič
    if(elementkopija.val() === ''){
        //Preveri če si razlikujeta prej-placeholder in placeholder, če se nastavi vrednost na placeholder
        if(elementkopija.attr('prej-placeholder') !== elementkopija.attr('placeholder')){
            elementkopija.val(elementkopija.attr('placeholder'));
        }
        
        elementkopija.attr('placeholder', '');
        //Preveri če je vrednost prazna, če je spremeni placeholder v prej-placeholder
        if(elementkopija.val() === ''){
            elementkopija.attr('placeholder', elementkopija.attr('prej-placeholder'));
        }
    }
}