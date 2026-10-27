$(document).ready(function() {
    // Apri la modal
    $('#btn-apri-modal-assistenza').on('click', function() {
        $('#modalAssistenza').modal('show');
        $('#assistenza-alert').hide().text('');
        $('#form-assistenza')[0].reset();
    });

    // Invio richiesta assistenza
    $('#form-assistenza').on('submit', function(e) {
        e.preventDefault();
        var richiesta = $('#testoRichiesta').val().trim();
        // Controllo max lato client se input ha attributo max
        var input = $('#testoRichiesta')[0];
        if (input && input.hasAttribute('max')) {
            var max = parseInt(input.getAttribute('max'));
            if (!isNaN(max) && richiesta.length > max) {
                richiesta = richiesta.substring(0, max);
                $('#testoRichiesta').val(richiesta);
            }
        }
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
                        $('#modalAssistenza').modal('hide');
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
