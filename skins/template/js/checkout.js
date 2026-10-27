// JS per la pagina checkout avanzata
$(document).ready(function() {
    // Selezione corriere
    $(document).on('change', 'input[name="corriere"]', function() {
        $('input[name="corriere"]').prop('checked', false);
        $(this).prop('checked', true);
    });
    // Selezione pagamento
    $(document).on('change', 'input[name="pagamento"]', function() {
        $('input[name="pagamento"]').prop('checked', false);
        $(this).prop('checked', true);
    });
    // Aggiungi indirizzo (placeholder, da implementare modal)
    $('#aggiungi-indirizzo').on('click', function() {
        
    });
    // ...nessuna gestione coupon lato JS, ora è gestito dal form classico...
    // Disabilita bottone conferma ordine finché non sono selezionati tutti i campi obbligatori
    function checkOrderReady() {
        var indirizzo = $('#indirizzo').val();
        var corriere = $('input[name="corriere"]:checked').val();
        var pagamento = $('input[name="pagamento"]:checked').val();
        var ready = indirizzo && corriere && pagamento;
        $('#conferma-ordine').prop('disabled', !ready);
    }
    $(document).on('change', '#indirizzo, input[name="corriere"], input[name="pagamento"]', checkOrderReady);
    $(document).ready(checkOrderReady);
});
