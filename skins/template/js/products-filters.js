// JS per sidebar filtri: gestione dropdown brand con ricerca e selezione, senza AJAX prodotti
$(document).ready(function() {
    // Dropdown brand toggle
    $(document).on('click', '#brandDropdownBtn', function(e) {
        e.stopPropagation();
        $('#brandDropdownMenu').toggle();
        $('#brandSearchInput').val('');
        $('#brandListUl .brand-item').show();
    });
    // Chiudi dropdown se clic fuori
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#brandDropdownMenu').length && !$(e.target).is('#brandDropdownBtn')) {
            $('#brandDropdownMenu').hide();
        }
    });
    // Ricerca brand live
    $(document).on('input', '#brandSearchInput', function() {
        var val = $(this).val().toLowerCase();
        $('#brandListUl .brand-item').each(function() {
            var brand = $(this).data('brand').toLowerCase();
            $(this).toggle(brand.indexOf(val) !== -1);
        });
    });
    // Selezione brand: aggiorna URL mantenendo altri filtri
    $(document).on('click', '#brandListUl .brand-item a', function(e) {
        e.preventDefault();
        var brand = $(this).closest('.brand-item').data('brand');
        var url = new URL(window.location.href);
        url.searchParams.set('brand', brand);
        // Mantieni anche altri filtri (genere, cat, page...)
        window.location.href = url.toString();
    });
});
