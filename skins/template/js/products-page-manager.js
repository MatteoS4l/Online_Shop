class ProductsPageManager {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        console.log('ProductsPageManager inizializzato (versione semplificata)');
    }

    bindEvents() {
        // Gestione modale prodotto
        $(document).on('click', '.js-show-modal1', function(e) {
            e.preventDefault();
            const productId = $(this).data('product-id');
            if (productId && window.ProductModalManager) {
                window.ProductModalManager.showModal(productId);
            }
        });
        
        // Gestione wishlist
        $(document).on('click', '.js-addwish-b2', function(e) {
            e.preventDefault();
            const productId = $(this).data('product-id');
            if (productId && window.WishlistManager) {
                window.WishlistManager.toggleWishlist(productId, $(this));
            }
        });
        
        // Gestione carrello
        $(document).on('click', '.js-addcart-detail', function(e) {
            e.preventDefault();
            const productId = $(this).data('product-id');
            if (productId && window.CartManager) {
                window.CartManager.addToCart(productId, 1);
            }
        });
    }
}

// Inizializza quando il DOM è pronto
$(document).ready(function() {
    window.ProductsPageManager = new ProductsPageManager();
});
