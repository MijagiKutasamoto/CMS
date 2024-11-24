document.addEventListener('DOMContentLoaded', () => {
    // Usuwanie zdjęć
    document.querySelectorAll('.delete-image').forEach((button) => {
        button.addEventListener('click', () => {
            if (confirm('Czy na pewno chcesz usunąć to zdjęcie?')) {
                fetch('delete_gallery.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ image_id: button.dataset.imageId }),
                })
                .then((response) => response.json())
                .then((data) => {
                    if (data.status === 'success') {
                        button.closest('.gallery-item').remove();
                    } else {
                        alert(data.message);
                    }
                });
            }
        });
    });

    // Przełączanie zdjęć w galerii
    const images = document.querySelectorAll('.gallery-item img');
    const modal = document.createElement('div');
    modal.className = 'gallery-modal';
    modal.innerHTML = `
        <button class="prev">←</button>
        <img src="" alt="Podgląd">
        <button class="next">→</button>
        <button class="close">×</button>
    `;
    document.body.appendChild(modal);

    const modalImage = modal.querySelector('img');
    let currentIndex = 0;

    images.forEach((img, index) => {
        img.addEventListener('click', () => {
            currentIndex = index;
            modalImage.src = img.src;
            modal.classList.add('visible');
        });
    });

    modal.querySelector('.prev').addEventListener('click', () => {
        currentIndex = (currentIndex - 1 + images.length) % images.length;
        modalImage.src = images[currentIndex].src;
    });

    modal.querySelector('.next').addEventListener('click', () => {
        currentIndex = (currentIndex + 1) % images.length;
        modalImage.src = images[currentIndex].src;
    });

    modal.querySelector('.close').addEventListener('click', () => {
        modal.classList.remove('visible');
    });
});
