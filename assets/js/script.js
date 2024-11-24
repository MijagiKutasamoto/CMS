let currentPage = 1;
const postsContainer = document.querySelector('.blog-posts');
const loadMoreBtn = document.querySelector('.load-more');

function loadMorePosts() {
    currentPage++;
    fetch(`?page=${currentPage}`)
        .then(response => response.text())
        .then(data => {
            const parser = new DOMParser();
            const newPosts = parser.parseFromString(data, 'text/html').querySelectorAll('.blog-post');
            newPosts.forEach(post => postsContainer.appendChild(post));

            // Sprawdź, czy osiągnęliśmy koniec
            const totalPages = parseInt(document.querySelector('.pagination').dataset.totalPages);
            if (currentPage >= totalPages) {
                loadMoreBtn.style.display = 'none';
            }
        });
}

if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', loadMorePosts);
}
