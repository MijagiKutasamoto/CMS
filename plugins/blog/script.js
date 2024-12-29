document.addEventListener('DOMContentLoaded', () => {
    const cartButtons = document.querySelectorAll('.add-to-cart');

    cartButtons.forEach(button => {
        button.addEventListener('click', () => {
            const productId = button.dataset.productId;

            fetch('/plugins/shop/cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: productId }),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(err => {
                    console.error('Błąd:', err);
                });
        });
    });
});