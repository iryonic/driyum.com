// DRIYUM - Cart JavaScript

// Live Search in Cart
let searchTimeout;
document.getElementById('search-input')?.addEventListener('input', function (e) {
    const query = e.target.value.trim();

    clearTimeout(searchTimeout);

    if (query.length >= 2) {
        document.getElementById('popular-searches').classList.add('hidden');
        document.getElementById('categories-quick').classList.add('hidden');
        document.getElementById('search-loading').classList.remove('hidden');
        document.getElementById('search-results-container').classList.add('hidden');
        document.getElementById('search-no-results').classList.add('hidden');

        searchTimeout = setTimeout(() => {
            searchProducts(query);
        }, 300);
    } else {
        document.getElementById('popular-searches').classList.remove('hidden');
        document.getElementById('categories-quick').classList.remove('hidden');
        document.getElementById('search-results-container').classList.add('hidden');
        document.getElementById('search-loading').classList.add('hidden');
        document.getElementById('search-no-results').classList.add('hidden');
    }
});

async function searchProducts(query) {
    try {
        const response = await fetch(`${BASE_URL}api/search.php?q=${encodeURIComponent(query)}`);
        const data = await response.json();

        document.getElementById('search-loading').classList.add('hidden');

        if (data.success && data.products.length > 0) {
            displaySearchResults(data.products);
        } else {
            document.getElementById('search-no-results').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Search error:', error);
        document.getElementById('search-loading').classList.add('hidden');
        showToast('Search failed', 'error');
    }
}

function displaySearchResults(products) {
    const container = document.getElementById('search-products-grid');

    let html = '';
    products.forEach(product => {
        html += `
            <a href="${BASE_URL}product/${product.slug}" class="bg-white rounded-xl overflow-hidden hover:shadow-lg transition" onclick="closeSearch()">
                <img src="${product.image}" alt="${product.name}" class="w-full h-48 object-cover">
                <div class="p-4">
                    <h4 class="font-semibold mb-2 line-clamp-2">${product.name}</h4>
                    <p class="text-primary font-bold">₹${product.price}</p>
                </div>
            </a>
        `;
    });

    container.innerHTML = html;
    document.getElementById('search-results-container').classList.remove('hidden');
}
