// Wishlist Management - Aggiornamento in tempo reale

// Protezione contro dichiarazioni multiple
if (typeof WishlistManager !== 'undefined') {
    console.warn('⚠️ WishlistManager già definito, utilizzo esistente');
} else {

class WishlistManager {
    constructor() {
        this.bindEvents();
        // Disabilita gli handler predefiniti del template
        this.disableDefaultHandlers();
        // Inizializza lo stato dei cuori al caricamento
        this.initializeWishlistState();
    }

    disableDefaultHandlers() {
        // Rimuovi gli event listener predefiniti per evitare conflitti
        $('.js-addwish-b2').off('click');
        $('.js-addwish-detail').off('click');
    }

    bindEvents() {
        // Gestisci click sui bottoni dei preferiti con event delegation
        document.addEventListener('click', (e) => {
            // Cerca il bottone di rimozione dai preferiti (X)
            const removeWishlistBtn = e.target.closest('.btn-remove-wishlist');
            if (removeWishlistBtn) {
                e.preventDefault();
                e.stopPropagation();
                const productId = removeWishlistBtn.dataset.productId || removeWishlistBtn.getAttribute('data-product-id');
                if (productId) {
                    this.removeFromWishlist(productId, removeWishlistBtn);
                }
                return false;
            }
            
            // Cerca il bottone di aggiunta/toggle ai preferiti
            const wishlistBtn = e.target.closest('.js-addwish-detail, .js-addwish-b2, .wishlist-btn');
            if (wishlistBtn) {
                e.preventDefault();
                e.stopPropagation();
                const productId = wishlistBtn.dataset.productId || 
                                wishlistBtn.getAttribute('data-product-id') ||
                                this.getProductIdFromParent(wishlistBtn);
                if (productId) {
                    this.toggleWishlist(productId, wishlistBtn);
                }
                return false;
            }
        });
    }

    getProductIdFromParent(button) {
        // Cerca l'ID prodotto negli elementi parent
        let parent = button.closest('[data-product-id]');
        if (parent) return parent.getAttribute('data-product-id');
        
        // Cerca nell'URL di un link vicino
        const productLink = button.closest('.block2, .product-item')?.querySelector('a[href*="product.php?id="]');
        if (productLink) {
            const match = productLink.href.match(/id=(\d+)/);
            return match ? match[1] : null;
        }
        
        return null;
    }

    // Utility per controllare se siamo nella pagina wishlist
    isWishlistPage() {
        const isWishlist = window.location.pathname.includes('wishlist.php') || 
                          window.location.href.includes('wishlist.php');
        console.log('🔍 Check wishlist page:', isWishlist, window.location.pathname);
        return isWishlist;
    }

    async removeFromWishlist(productId, button) {
        try {
            console.log('Rimozione prodotto dai preferiti:', productId);
            
            // Disabilita il bottone durante la richiesta
            button.disabled = true;
            
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('product_id', productId);

            const response = await fetch('include/ajax/wishlist_handler.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            console.log('Risposta rimozione:', data);
            
            if (data.success) {
                // Rimuovi la riga dalla tabella se siamo nella pagina wishlist
                const row = button.closest('tr.table_row');
                if (row) {
                    row.remove();
                }
                
                // Aggiorna il contatore nell'header
                this.updateWishlistCounter(data.wishlist_count);
                
                // Aggiorna tutti i cuori per questo prodotto
                this.setHeartState(productId, false);
                
                // Trigger sincronizzazione globale
                if (window.globalSyncManager) {
                    window.globalSyncManager.syncProduct(productId, false, data.wishlist_count);
                }
                
                // Mostra messaggio di successo solo se non siamo nella pagina wishlist
                if (!this.isWishlistPage()) {
                    this.showSweetAlert('Prodotto rimosso dai preferiti!', '', 'success');
                }
            } else {
                console.error('Errore rimozione:', data.error);
                
                // Se l'errore è dovuto al fatto che l'utente non è loggato
                if (data.error && data.error.includes('login')) {
                    this.showSweetAlert('Accesso richiesto', 'Devi effettuare il login per gestire i preferiti', 'warning', {
                        confirmButtonText: 'Vai al Login',
                        showCancelButton: true,
                        cancelButtonText: 'Annulla'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'login.php';
                        }
                    });
                } else {
                    this.showSweetAlert('Errore nella rimozione', data.error || 'Si è verificato un errore', 'error');
                }
            }
        } catch (error) {
            console.error('Errore nella richiesta:', error);
            this.showSweetAlert('Errore di connessione', 'Errore di connessione al server', 'error');
        } finally {
            // Riabilita il bottone
            button.disabled = false;
        }
    }

    async toggleWishlist(productId, button) {
        try {
            console.log('Toggle wishlist per prodotto:', productId);
            
            // Disabilita il bottone durante la richiesta
            button.disabled = true;
            
            const formData = new FormData();
            formData.append('action', 'toggle');
            formData.append('product_id', productId);

            const response = await fetch('include/ajax/wishlist_handler.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            console.log('Risposta server:', data);
            
            if (data.success) {
                // Aggiorna l'aspetto del bottone
                this.updateWishlistButton(button, data.action);
                
                // Aggiorna il contatore nell'header
                this.updateWishlistCounter(data.wishlist_count);
                
                // Trigger sincronizzazione globale
                const productId = button.dataset.productId || 
                                 button.getAttribute('data-product-id') ||
                                 this.getProductIdFromParent(button);
                
                if (productId && window.globalSyncManager) {
                    window.globalSyncManager.syncProduct(
                        productId, 
                        data.action === 'added', 
                        data.wishlist_count
                    );
                }
                
                // Mostra il messaggio appropriato basato sull'azione usando SweetAlert
                // Ma solo se non siamo nella pagina wishlist
                if (!this.isWishlistPage()) {
                    const message = data.action === 'added' ? 'è stato aggiunto ai preferiti!' : 'è stato rimosso dai preferiti!';
                    const productName = this.getProductName(button);
                    this.showSweetAlert(productName, message, 'success');
                }
            } else {
                console.error('Errore server:', data.error);
                
                // Se l'errore è dovuto al fatto che l'utente non è loggato
                if (data.error && data.error.includes('login')) {
                    this.showSweetAlert('Accesso richiesto', 'Devi effettuare il login per gestire i preferiti', 'warning', {
                        confirmButtonText: 'Vai al Login',
                        showCancelButton: true,
                        cancelButtonText: 'Annulla'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'login.php';
                        }
                    });
                } else {
                    this.showSweetAlert('Errore nell\'operazione', data.error || 'Si è verificato un errore', 'error');
                }
            }
        } catch (error) {
            console.error('Errore nella richiesta:', error);
            this.showSweetAlert('Errore di connessione', 'Errore di connessione al server', 'error');
        } finally {
            // Riabilita il bottone
            button.disabled = false;
        }
    }

    getProductName(button) {
        // Cerca il nome del prodotto vicino al bottone
        const nameElement = button.closest('.block2').querySelector('.js-name-b2') ||
                           button.closest('.product-detail').querySelector('.js-name-detail') ||
                           button.closest('tr').querySelector('.product-name');
        
        return nameElement ? nameElement.textContent.trim() : 'Prodotto';
    }

    updateWishlistButton(button, action) {
        const isInWishlist = action === 'added';
        
        // Aggiorna il bottone corrente
        this.updateHeartVisual(button, isInWishlist);
        
        // Trova l'ID prodotto per la sincronizzazione globale
        const productId = button.dataset.productId || 
                         button.getAttribute('data-product-id') ||
                         this.getProductIdFromParent(button);
        
        if (productId) {
            console.log(`Sincronizzazione globale per prodotto ${productId} - ${action}`);
            // Aggiorna tutti i cuori per questo prodotto in tutta la pagina
            this.setHeartState(productId, isInWishlist);
        } else {
            console.warn('ID prodotto non trovato per la sincronizzazione globale');
        }
    }

    updateWishlistCounter(count) {
        // Aggiorna il contatore dei preferiti nell'header
        console.log('Aggiornamento contatore preferiti:', count);
        
        // Trova l'icona dei preferiti nell'header (desktop e mobile)
        const wishlistLinks = document.querySelectorAll('a[href="wishlist.php"]');
        
        wishlistLinks.forEach(link => {
            if (link.hasAttribute('data-notify')) {
                link.setAttribute('data-notify', count);
                console.log('Contatore aggiornato:', link);
            }
        });
        
        // Fallback: cerca per l'icona cuore specificamente
        const heartCounters = document.querySelectorAll('[data-notify]');
        heartCounters.forEach(counter => {
            const heartIcon = counter.querySelector('.zmdi-favorite-outline, .zmdi-favorite');
            if (heartIcon) {
                counter.setAttribute('data-notify', count);
                console.log('Contatore cuore aggiornato:', counter);
            }
        });
    }

    showSweetAlert(title, message = '', type = 'success', options = {}) {
        if (typeof swal !== 'undefined') {
            // Usa SweetAlert se disponibile (compatibile con il template)
            if (options && (options.confirmButtonText || options.showCancelButton)) {
                // SweetAlert con opzioni avanzate
                return swal({
                    title: title,
                    text: message,
                    type: type,
                    showCancelButton: options.showCancelButton || false,
                    confirmButtonText: options.confirmButtonText || 'OK',
                    cancelButtonText: options.cancelButtonText || 'Annulla'
                });
            } else {
                // SweetAlert semplice
                if (message) {
                    return swal(title, message, type);
                } else {
                    return swal(title, type);
                }
            }
        } else {
            // Fallback a alert normale
            alert(`${title} ${message}`);
            return Promise.resolve({ isConfirmed: true });
        }
    }

    async initializeWishlistState() {
        try {
            console.log('Inizializzazione stato preferiti...');
            
            // Ottieni la lista degli ID dei prodotti nei preferiti
            const response = await fetch('include/ajax/wishlist_handler.php', {
                method: 'POST',
                body: new URLSearchParams({ action: 'get_user_wishlist' }),
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            });

            const data = await response.json();
            console.log('Preferiti utente:', data);
            
            if (data.success) {
                if (data.user_logged_in === false) {
                    console.log('Utente non loggato - saltando inizializzazione preferiti');
                    return;
                }
                
                if (data.wishlist && data.wishlist.length > 0) {
                    // Per ogni prodotto nei preferiti, imposta il cuore pieno
                    data.wishlist.forEach(productId => {
                        this.setHeartState(productId, true);
                    });
                    
                    console.log(`Inizializzati ${data.wishlist.length} prodotti nei preferiti`);
                } else {
                    console.log('Nessun prodotto nei preferiti');
                }
            }
        } catch (error) {
            console.error('Errore nel caricamento stato preferiti:', error);
        }
    }

    setHeartState(productId, isInWishlist) {
        console.log(`Sincronizzazione globale cuori per prodotto ${productId}: ${isInWishlist ? 'attivo' : 'inattivo'}`);
        
        // Trova tutti i bottoni cuore per questo prodotto con vari selettori
        const selectors = [
            `[data-product-id="${productId}"] .js-addwish-b2`,
            `[data-product-id="${productId}"] .js-addwish-detail`,
            `[data-product-id="${productId}"] .btn-addwish-b2`,
            `[data-product-id="${productId}"] .wishlist-btn`,
            `.js-addwish-b2[data-product-id="${productId}"]`,
            `.js-addwish-detail[data-product-id="${productId}"]`,
            `.btn-addwish-b2[data-product-id="${productId}"]`,
            `.wishlist-btn[data-product-id="${productId}"]`
        ];
        
        let updatedButtons = 0;
        
        selectors.forEach(selector => {
            const buttons = document.querySelectorAll(selector);
            buttons.forEach(button => {
                this.updateHeartVisual(button, isInWishlist);
                updatedButtons++;
            });
        });
        
        // Cerca anche nei prodotti che potrebbero avere l'ID in modi diversi
        const allWishlistButtons = document.querySelectorAll('.js-addwish-b2, .js-addwish-detail, .btn-addwish-b2, .wishlist-btn');
        allWishlistButtons.forEach(button => {
            const buttonProductId = this.getProductIdFromParent(button);
            if (buttonProductId == productId) {
                this.updateHeartVisual(button, isInWishlist);
                updatedButtons++;
            }
        });
        
        // Cerca anche bottoni con href che contengono l'ID
        const linkButtons = document.querySelectorAll(`a[href*="product_id=${productId}"], a[href*="id=${productId}"]`);
        linkButtons.forEach(link => {
            const heartBtn = link.querySelector('.js-addwish-b2, .js-addwish-detail, .btn-addwish-b2');
            if (heartBtn) {
                this.updateHeartVisual(heartBtn, isInWishlist);
                updatedButtons++;
            }
        });
        
        console.log(`Aggiornati ${updatedButtons} bottoni cuore per il prodotto ${productId}`);
        
        // Forza l'aggiornamento dell'intero documento per prodotti in cards, grid, etc.
        this.updateAllProductCards(productId, isInWishlist);
    }

    updateAllProductCards(productId, isInWishlist) {
        // Cerca nelle card prodotto standard
        const productCards = document.querySelectorAll('.block2, .product-item, .product-card, .item-slick2');
        
        productCards.forEach(card => {
            // Controlla se questa card appartiene al prodotto
            let cardProductId = card.getAttribute('data-product-id');
            
            if (!cardProductId) {
                // Cerca nei link della card
                const productLink = card.querySelector('a[href*="product.php?id="], a[href*="product_id="]');
                if (productLink) {
                    const match = productLink.href.match(/(?:id|product_id)=(\d+)/);
                    cardProductId = match ? match[1] : null;
                }
            }
            
            if (cardProductId == productId) {
                // Trova tutti i bottoni cuore in questa card
                const heartButtons = card.querySelectorAll('.js-addwish-b2, .js-addwish-detail, .btn-addwish-b2, .wishlist-btn');
                heartButtons.forEach(button => {
                    this.updateHeartVisual(button, isInWishlist);
                });
            }
        });
    }

    updateHeartVisual(button, isInWishlist) {
        const icon = button.querySelector('i');
        const heart1 = button.querySelector('.icon-heart1');
        const heart2 = button.querySelector('.icon-heart2');
        
        if (isInWishlist) {
            // Cuore pieno - prodotto nei preferiti
            if (icon) {
                // Per icone zmdi
                if (icon.classList.contains('zmdi-favorite-outline')) {
                    icon.classList.remove('zmdi-favorite-outline');
                    icon.classList.add('zmdi-favorite');
                }
                // Per icone fa
                if (icon.classList.contains('fa-heart-o')) {
                    icon.classList.remove('fa-heart-o');
                    icon.classList.add('fa-heart');
                }
                icon.style.color = '#e74c3c';
            }
            
            // Per template con immagini cuore
            if (heart1 && heart2) {
                heart1.style.display = 'none';
                heart2.style.display = 'block';
            }
            
            button.classList.add('wishlist-active');
        } else {
            // Cuore vuoto - prodotto non nei preferiti
            if (icon) {
                // Per icone zmdi
                if (icon.classList.contains('zmdi-favorite')) {
                    icon.classList.remove('zmdi-favorite');
                    icon.classList.add('zmdi-favorite-outline');
                }
                // Per icone fa
                if (icon.classList.contains('fa-heart')) {
                    icon.classList.remove('fa-heart');
                    icon.classList.add('fa-heart-o');
                }
                icon.style.color = '';
            }
            
            // Per template con immagini cuore
            if (heart1 && heart2) {
                heart1.style.display = 'block';
                heart2.style.display = 'none';
            }
            
            button.classList.remove('wishlist-active');
        }
    }
}

// Inizializza quando il DOM è caricato
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Inizializzazione WishlistManager v2.0...');
    
    // Verifica se esiste già un'istanza
    if (window.wishlistManager) {
        console.warn('⚠️ WishlistManager già inizializzato, utilizzo esistente');
        return;
    }
    
    window.wishlistManager = new WishlistManager();
    
    // Debug: mostra tutti i bottoni cuore trovati
    const heartButtons = document.querySelectorAll('.js-addwish-b2, .js-addwish-detail, .btn-addwish-b2, .wishlist-heart');
    console.log(`💝 Trovati ${heartButtons.length} bottoni cuore nella pagina:`, heartButtons);
});

// Funzione globale per compatibilità con template esistenti
function toggleWishlist(productId) {
    if (window.wishlistManager) {
        const button = event.target.closest('.wishlist-btn, .js-addwish-b2, .js-addwish-detail, .btn-addwish-b2');
        if (button) {
            window.wishlistManager.toggleWishlist(productId, button);
        }
    }
}

// Funzione globale per inizializzare lo stato dei cuori su nuove pagine
function initializeWishlistHearts() {
    if (window.wishlistManager) {
        window.wishlistManager.initializeWishlistState();
    }
}

// Funzione globale per sincronizzare un prodotto specifico
function syncWishlistProduct(productId, isInWishlist) {
    if (window.wishlistManager) {
        window.wishlistManager.setHeartState(productId, isInWishlist);
    }
}

// Event listener per nuovi contenuti caricati dinamicamente (AJAX)
document.addEventListener('wishlist:contentLoaded', () => {
    if (window.wishlistManager) {
        window.wishlistManager.initializeWishlistState();
    }
});

// Osservatore per contenuti caricati dinamicamente
if (typeof MutationObserver !== 'undefined') {
    const observer = new MutationObserver((mutations) => {
        let hasNewWishlistButtons = false;
        
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1) { // Element node
                    // Controlla se ci sono nuovi bottoni wishlist
                    const newButtons = node.querySelectorAll && node.querySelectorAll('.js-addwish-b2, .js-addwish-detail, .btn-addwish-b2');
                    if (newButtons && newButtons.length > 0) {
                        hasNewWishlistButtons = true;
                    }
                }
            });
        });
        
        // Se abbiamo trovato nuovi bottoni, reinizializza lo stato
        if (hasNewWishlistButtons && window.wishlistManager) {
            setTimeout(() => {
                window.wishlistManager.initializeWishlistState();
            }, 100);
        }
    });
    
    // Osserva i cambiamenti nel DOM
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

} // Fine protezione WishlistManager
