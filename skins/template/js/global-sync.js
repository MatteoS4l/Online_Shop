// Global Synchronization Manager for Wishlist
// Gestisce la sincronizzazione globale dei cuori tra diverse pagine

class GlobalSyncManager {
    constructor() {
        this.init();
    }

    init() {
        // Ascolta eventi di storage per sincronizzazione tra tab
        window.addEventListener('storage', (e) => {
            if (e.key === 'wishlist_update') {
                const data = JSON.parse(e.newValue);
                this.handleWishlistUpdate(data);
            }
        });

        // Ascolta eventi personalizzati per aggiornamenti locali
        document.addEventListener('wishlist:update', (e) => {
            this.broadcastUpdate(e.detail);
        });
    }

    // Propaga un aggiornamento ai preferiti
    broadcastUpdate(updateData) {
        // Salva in localStorage per sincronizzazione tra tab
        localStorage.setItem('wishlist_update', JSON.stringify({
            ...updateData,
            timestamp: Date.now()
        }));

        // Applica localmente
        this.handleWishlistUpdate(updateData);
    }

    // Gestisce un aggiornamento ricevuto
    handleWishlistUpdate(data) {
        if (window.wishlistManager && data.productId) {
            console.log('Sincronizzazione globale ricevuta:', data);
            window.wishlistManager.setHeartState(data.productId, data.isInWishlist);
            
            if (data.wishlistCount !== undefined) {
                window.wishlistManager.updateWishlistCounter(data.wishlistCount);
            }
        }
    }

    // Sincronizza lo stato di un prodotto specifico
    syncProduct(productId, isInWishlist, wishlistCount = null) {
        const updateData = {
            productId: productId,
            isInWishlist: isInWishlist,
            wishlistCount: wishlistCount
        };

        // Dispatch evento locale
        document.dispatchEvent(new CustomEvent('wishlist:update', {
            detail: updateData
        }));
    }
}

// Inizializza il manager globale
document.addEventListener('DOMContentLoaded', () => {
    window.globalSyncManager = new GlobalSyncManager();
});

// Utility function per trigger manuale
function triggerWishlistSync(productId, isInWishlist, wishlistCount = null) {
    if (window.globalSyncManager) {
        window.globalSyncManager.syncProduct(productId, isInWishlist, wishlistCount);
    }
}
