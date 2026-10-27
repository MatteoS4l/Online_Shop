// Product Modal Manager - Gestione modal dinamico per prodotti
class ProductModalManager {
    constructor() {
        this.currentProductId = null;
        this.modalElement = null;
        this.selectedSize = null;
        this.stockPerSize = {};
        this.bindEvents(); // Bind events in the constructor
        this.bindQuantityEvents(); // Bind quantity events in the constructor
    }

    bindEvents() {
        // Event delegation per i bottoni Quick View
        $(document).on('click', '.js-show-modal1', (e) => {
            e.preventDefault();
            const productId = $(e.target).closest('[data-product-id]').data('product-id');
            if (productId) {
                this.loadProductModal(productId);
            }
        });

        // Chiudi modal
        $(document).on('click', '.js-hide-modal1', (e) => {
            e.preventDefault();
            this.hideModal();
        });

        // Gestione quantità solo per i bottoni del modal
        $(document).on('click.productModal', '.btn-modal-qty-up', function(e) {
            e.preventDefault();
            const input = $(this).siblings('input.modal-num-product');
            const currentVal = parseInt(input.val()) || 1;
            const max = parseInt(input.attr('max')) || 1;
            if (currentVal < max) {
                input.val(currentVal + 1);
            }
        });

        $(document).on('click.productModal', '.btn-modal-qty-down', function(e) {
            e.preventDefault();
            const input = $(this).siblings('input.modal-num-product');
            const currentVal = parseInt(input.val()) || 1;
            if (currentVal > 1) {
                input.val(currentVal - 1);
            }
        });
    }

    async loadProductModal(productId) {
        try {
            console.log(`Loading modal for product ID: ${productId}`);
            
            // Mostra loading
            this.showLoadingModal();
            
            // Fetch dati prodotto
            const response = await fetch(`include/ajax/product_modal_data.php?id=${productId}`);
            const data = await response.json();
            
            if (data.success) {
                this.populateModal(data.product);
                this.showModal();
            } else {
                console.error('Errore nel caricamento prodotto:', data.error);
                this.showErrorModal(data.error);
            }
        } catch (error) {
            console.error('Errore nella richiesta modal:', error);
            this.showErrorModal('Errore di connessione');
        }
    }

    populateModal(product) {
        console.log('Populating modal with:', product);
        
        // Aggiorna contenuti
        $('#modal-product-name').text(product.name);
        $('#modal-product-price').text('€' + product.price);
        $('#modal-product-description').text(product.description || 'Nessuna descrizione disponibile');
        
        // Aggiorna immagini
        this.updateModalImages(product.images || []);
        
        // Aggiorna opzioni
        this.selectedSize = null;
        this.stockPerSize = product.stock_per_size || {};
        this.updateModalSizes(product.sizes || []);
        
        // Aggiorna attributi prodotto sui bottoni
        $('#modal-add-cart').attr('data-product-id', product.id);
        $('#modal-add-wishlist').attr('data-product-id', product.id);
        
        this.currentProductId = product.id;
    }

    updateModalImages(images) {
        const container = $('#modal-images-container');
        // Destroy previous slick instance if exists
        if (container.hasClass('slick-initialized')) {
            container.slick('unslick');
        }
        container.empty();
        if (images.length === 0) {
            images = ['skins/template/images/product-placeholder.jpg'];
        }
        images.forEach((imageSrc, index) => {
            const imageItem = `
                <div class="item-slick3" data-thumb="${imageSrc}">
                    <div class="wrap-pic-w pos-relative">
                        <img src="${imageSrc}" alt="IMG-PRODUCT">
                        <!-- Opzione ingrandimento rimossa -->
                    </div>
                </div>
            `;
            container.append(imageItem);
        });
        // Inizializza Slick slider con frecce custom senza testo
        container.slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            arrows: true,
            dots: true,
            fade: false,
            infinite: images.length > 1,
            adaptiveHeight: true,
            appendDots: $('.wrap-slick3-dots'),
            customPaging: function(slider, i) {
                return '<span class="slick-dot"></span>';
            },
            prevArrow: '<button type="button" class="slick-prev" style="background:none;border:none;font-size:2rem;position:absolute;left:0;top:50%;transform:translateY(-50%);z-index:2;">&#8592;</button>',
            nextArrow: '<button type="button" class="slick-next" style="background:none;border:none;font-size:2rem;position:absolute;right:0;top:50%;transform:translateY(-50%);z-index:2;">&#8594;</button>',
        });
    }

    updateModalSizes(sizes) {
        const container = $('#modal-size-buttons');
        container.empty();
        sizes.forEach(size => {
            const stock = this.stockPerSize[size] !== undefined ? this.stockPerSize[size] : 0;
            const disabled = stock === 0 ? 'disabled' : '';
            container.append(`
                <button type="button" class="btn btn-outline-primary m-1 modal-size-btn" data-size="${size}" data-stock="${stock}" ${disabled}>${size}</button>
            `);
        });
        // Gestione selezione
        container.find('.modal-size-btn').on('click', (e) => {
            container.find('.modal-size-btn').removeClass('active');
            $(e.target).addClass('active');
            this.selectedSize = $(e.target).data('size');
            const stock = $(e.target).data('stock');
            $('#modal-quantity').val(1);
            $('#modal-quantity').attr('max', stock);

            // Rimuovi e ri-applica handler quantità per evitare duplicati
            $('.btn-modal-qty-up').off('click.productModal').on('click.productModal', function(e) {
                e.preventDefault();
                const input = $(this).siblings('input.modal-num-product');
                const currentVal = parseInt(input.val()) || 1;
                const max = parseInt(input.attr('max')) || 1;
                if (currentVal < max) {
                    input.val(currentVal + 1);
                }
            });
            $('.btn-modal-qty-down').off('click.productModal').on('click.productModal', function(e) {
                e.preventDefault();
                const input = $(this).siblings('input.modal-num-product');
                const currentVal = parseInt(input.val()) || 1;
                if (currentVal > 1) {
                    input.val(currentVal - 1);
                }
            });
        });
    }


    showLoadingModal() {
        // Crea o mostra modal con loading
        if (!$('.js-modal1').length) {
            $('body').append('<div class="js-modal1" style="display: flex; align-items: center; justify-content: center;"><div class="loading">Caricamento...</div></div>');
        }
        $('.js-modal1').show().addClass('show-modal1');
    }

    showModal() {
        $('.js-modal1').show().addClass('show-modal1');
        $('body').addClass('modal-open');
    }

    hideModal() {
        $('.js-modal1').hide().removeClass('show-modal1');
        $('body').removeClass('modal-open');
        this.currentProductId = null;
    }

    showErrorModal(message) {
        alert('Errore: ' + message);
        this.hideModal();
    }
}

// Inizializza il modal manager quando il documento è pronto
$(document).ready(function() {
    if (typeof ProductModalManager !== 'undefined') {
        window.productModalManager = new ProductModalManager();
        console.log('ProductModalManager inizializzato');
    }
});
