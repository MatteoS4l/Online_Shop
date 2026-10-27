// Cart Management - Aggiornamento in tempo reale
class CartManager {
    constructor() {
        this.bindEvents();
    }

    bindEvents() {
        // Gestisci click sui bottoni del carrello
        document.addEventListener('click', (e) => {
            // Cerca il bottone più vicino con classe cart-btn
            const cartBtn = e.target.closest('.cart-btn, .js-addcart-detail, .btn-addcart-product');
            if (cartBtn) {
                e.preventDefault();
                const productId = cartBtn.dataset.productId || cartBtn.getAttribute('data-product-id');
                const quantity = cartBtn.dataset.quantity || cartBtn.getAttribute('data-quantity') || 1;
                const size = cartBtn.dataset.size || cartBtn.getAttribute('data-size') || 'M';
                
                if (productId) {
                    this.addToCart(productId, quantity, size, cartBtn);
                }
            }

            // Gestisci click sui bottoni di rimozione nella preview carrello
            const removeBtn = e.target.closest('.btn-remove-cart-item');
            if (removeBtn) {
                e.preventDefault();
                const productId = removeBtn.dataset.productId || removeBtn.getAttribute('data-product-id');
                const size = removeBtn.dataset.size || removeBtn.getAttribute('data-size') || 'M';
                
                if (productId) {
                    this.removeFromCart(productId, size, removeBtn);
                }
            }
        });
    }

    async addToCart(productId, quantity = 1, size = 'M', button) {
        try {
            // Disabilita il bottone durante la richiesta
            if (button) button.disabled = true;

            // Controllo max lato client
            let max = 99;
            if (button) {
                const input = button.closest('.cart-item')?.querySelector('input.num-product');
                if (input && input.hasAttribute('max')) {
                    max = parseInt(input.getAttribute('max')) || max;
                }
            }
            if (quantity > max) quantity = max;

            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            formData.append('size', size);

            const response = await fetch('include/ajax/cart_handler.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Aggiorna il contatore del carrello nell'header
                this.updateCartCounter(data.cart_count);

                // Animazione del bottone
                this.animateCartButton(button);

                // Mostra messaggio di successo
                this.showMessage(data.message, 'success');
            } else {
                this.showMessage(data.error || 'Errore nell\'operazione', 'error');
            }
        } catch (error) {
            console.error('Errore nella richiesta:', error);
            this.showMessage('Errore di connessione', 'error');
        } finally {
            // Riabilita il bottone
            if (button) button.disabled = false;
        }
    }

    async removeFromCart(productId, size = 'M') {
        try {
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('product_id', productId);
            formData.append('size', size);

            const response = await fetch('include/ajax/cart_handler.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                this.updateCartCounter(data.cart_count);
                
                this.showMessage(data.message, 'success');
            } else {
                this.showMessage(data.error || 'Errore nell\'operazione', 'error');
            }
        } catch (error) {
            console.error('Errore nella richiesta:', error);
            this.showMessage('Errore di connessione', 'error');
        }
    }

    async updateCartQuantity(productId, quantity, size = 'M') {
        try {
            // Controllo max lato client
            let max = 99;
            const input = document.querySelector('.cart-item input.num-product[data-product-id="' + productId + '"][data-size="' + size + '"]');
            if (input && input.hasAttribute('max')) {
                max = parseInt(input.getAttribute('max')) || max;
            }
            if (quantity > max) quantity = max;

            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            formData.append('size', size);

            const response = await fetch('include/ajax/cart_handler.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.updateCartCounter(data.cart_count);
                this.showMessage(data.message, 'success');
                return data; // Restituisci i dati completi per aggiornamenti istantanei
            } else {
                this.showMessage(data.error || 'Errore nell\'operazione', 'error');
                return data;
            }
        } catch (error) {
            console.error('Errore nella richiesta:', error);
            this.showMessage('Errore di connessione', 'error');
            return { success: false, error: 'Errore di connessione' };
        }
    }

    updateCartCounter(count) {
        // Aggiorna tutti i contatori del carrello
        const counters = document.querySelectorAll('[data-notify]');
        counters.forEach(counter => {
            const notify = counter.getAttribute('data-notify');
            if (notify !== null) {
                // Questo è il contatore del carrello
                counter.setAttribute('data-notify', count);
            }
        });
    }

    animateCartButton(button) {
        if (!button) return;
        
        // Aggiungi classe di animazione
        button.classList.add('cart-added');
        button.style.transform = 'scale(1.1)';
        button.style.transition = 'transform 0.2s ease';
        
        setTimeout(() => {
            button.style.transform = 'scale(1)';
            button.classList.remove('cart-added');
        }, 200);
    }

    showMessage(message, type = 'info') {
        // Crea un toast/alert temporaneo
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show`;
        alertDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
        `;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="close" onclick="this.parentElement.remove()">
                <span>&times;</span>
            </button>
        `;
        
        document.body.appendChild(alertDiv);
        
        // Rimuovi automaticamente dopo 3 secondi
        setTimeout(() => {
            if (alertDiv.parentElement) {
                alertDiv.remove();
            }
        }, 3000);
    }
}

// Inizializza quando il DOM è caricato
document.addEventListener('DOMContentLoaded', () => {
    new CartManager();
});

// Funzioni globali per compatibilità con template esistenti
function addToCart(productId, quantity = 1, size = 'M') {
    if (window.cartManager) {
        const button = event.target.closest('.cart-btn, .js-addcart-detail, .btn-addcart-product');
        window.cartManager.addToCart(productId, quantity, size, button);
    }
}

function removeFromCart(productId, size = 'M') {
    if (window.cartManager) {
        window.cartManager.removeFromCart(productId, size);
    }
}

function updateCartQuantity(productId, quantity, size = 'M') {
    if (window.cartManager) {
        window.cartManager.updateCartQuantity(productId, quantity, size);
    }
}

// Esponi l'istanza globalmente
window.addEventListener('load', () => {
    window.cartManager = new CartManager();
});
