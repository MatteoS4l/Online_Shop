// JS per gestione popup recensione prodotto

document.addEventListener('DOMContentLoaded', function () {
    // Delegato per tutti i pulsanti recensisci
    document.body.addEventListener('click', function (e) {
        if (e.target.classList.contains('btn-recensisci')) {
            var idProdotto = e.target.getAttribute('data-id-prodotto');
            // Controllo AJAX se già recensito
            fetch('include/php-utils/check_recensione_lasciata.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id_prodotto=' + encodeURIComponent(idProdotto)
            })
            .then(r => r.json())
            .then(data => {
                if (!data.canReview) {
                    alert(data.msg || 'Hai già recensito questo prodotto');
                    return;
                }
                document.getElementById('recensione_id_prodotto').value = idProdotto;
                resetRecensionePopup();
                try {
                    // Bootstrap 5
                    var modal = new bootstrap.Modal(document.getElementById('recensioneModal'));
                    modal.show();
                } catch (err) {
                    // Bootstrap 4 fallback
                    if (window.jQuery) {
                        $('#recensioneModal').modal('show');
                    }
                }
            })
            .catch(() => alert('Errore di rete nel controllo recensione'));
        }
    });

    // Stelle rating
    var stelleContainer = document.getElementById('stelleContainer');
    if (stelleContainer) {
        stelleContainer.innerHTML = generaStelleHTML(0);
        stelleContainer.addEventListener('mouseover', function (e) {
            if (e.target.classList.contains('star')) {
                var val = parseInt(e.target.getAttribute('data-value'));
                stelleContainer.innerHTML = generaStelleHTML(val);
            }
        });
        stelleContainer.addEventListener('mouseout', function () {
            var selected = parseInt(document.getElementById('stelleVoto').value) || 0;
            stelleContainer.innerHTML = generaStelleHTML(selected);
        });
        stelleContainer.addEventListener('click', function (e) {
            if (e.target.classList.contains('star')) {
                var val = parseInt(e.target.getAttribute('data-value'));
                document.getElementById('stelleVoto').value = val;
                stelleContainer.innerHTML = generaStelleHTML(val);
            }
        });
    }

    // Submit recensione
    var form = document.getElementById('recensioneForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var formData = new FormData(form);
            fetch('include/php-utils/invia_recensione.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                // Chiudi subito la modale
                try {
                    var modal = bootstrap.Modal.getInstance(document.getElementById('recensioneModal'));
                    if (modal) modal.hide();
                } catch (err) {
                    if (window.jQuery) {
                        $('#recensioneModal').modal('hide');
                    }
                }
                // Mostra un solo alert
                setTimeout(function() { alert(data.msg); }, 300);
            })
            .catch(() => alert('Errore di rete'));
        });
    }
});

function generaStelleHTML(selected) {
    let html = '';
    for (let i = 1; i <= 5; i++) {
        html += `<span class="star" data-value="${i}" style="font-size:2em; color:${i <= selected ? '#FFD700' : '#ccc'}; cursor:pointer;">&#9733;</span>`;
    }
    html += `<input type="hidden" id="stelleVoto" name="stelle" value="${selected}">`;
    return html;
}

function resetRecensionePopup() {
    document.getElementById('recensione_testo').value = '';
    document.getElementById('stelleVoto').value = 0;
    document.getElementById('stelleContainer').innerHTML = generaStelleHTML(0);
}
