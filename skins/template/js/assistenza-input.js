$(document).ready(function() {
    // Mostra input con animazione
    $('#btn-apri-input-assistenza').on('click', function() {
        $('#assistenza-input-wrapper').slideDown(300);
        $('#assistenza-alert').hide().text('');
        $('#form-assistenza')[0].reset();
        $('html, body').animate({scrollTop: $('#assistenza-input-wrapper').offset().top - 80}, 400);
    });

    // Invio richiesta assistenza
    $('#form-assistenza').on('submit', function(e) {
        e.preventDefault();
        var richiesta = $('#testoRichiesta').val().trim();
        if (richiesta.length === 0) {
            $('#assistenza-alert').removeClass().addClass('alert alert-danger').text('Il campo richiesta è obbligatorio.').show();
            return;
        }
        $.ajax({
            url: 'include/ajax/invia_richiesta_assistenza.php',
            method: 'POST',
            data: { richiesta: richiesta },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#assistenza-alert').removeClass().addClass('alert alert-success').text('Richiesta inviata con successo!').show();
                    setTimeout(function() {
                        location.reload();
                    }, 1200);
                } else {
                    $('#assistenza-alert').removeClass().addClass('alert alert-danger').text(response.message || 'Errore nell\'invio.').show();
                }
            },
            error: function() {
                $('#assistenza-alert').removeClass().addClass('alert alert-danger').text('Errore di comunicazione col server.').show();
            }
        });
    });
});
