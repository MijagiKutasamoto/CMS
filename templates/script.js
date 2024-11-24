let carousel = document.querySelector('.carousel'),
    list = document.querySelector('.list'),
    items = document.querySelectorAll('.item'),
    runningTime = document.querySelector('.carousel .timeRunning');

let timeRunning = 3000; // Czas animacji
let timeAutoNext = 7000; // Czas automatycznego przesunięcia

// Funkcja resetująca animację czasu
function resetTimeAnimation() {
    runningTime.style.animation = 'none';
    runningTime.offsetHeight; /* Wymuszenie reflow */
    runningTime.style.animation = 'runningTime 7s linear 1 forwards';
}

// Funkcja przesuwająca slider
function showSlider(type) {
    let sliderItemsDom = list.querySelectorAll('.item'); // Aktualizuj listę elementów
    if (sliderItemsDom.length > 1) { // Sprawdź, czy jest więcej niż jeden element
        if (type === 'next') {
            list.appendChild(sliderItemsDom[0]); // Przenieś pierwszy element na koniec
            carousel.classList.add('next');
        } else {
            list.prepend(sliderItemsDom[sliderItemsDom.length - 1]); // Przenieś ostatni element na początek
            carousel.classList.add('prev');
        }

        setTimeout(() => {
            carousel.classList.remove('next');
            carousel.classList.remove('prev');
        }, timeRunning);

        resetTimeAnimation(); // Reset animacji czasu
    }
}

// Automatyczne przesuwanie slajdów
setInterval(() => {
    showSlider('next');
}, timeAutoNext);

// Uruchomienie początkowej animacji
resetTimeAnimation();
