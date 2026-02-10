<div id="search-modal" class="search-modal">
    <div class="search-header">
        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
        <input type="text" 
               id="search-input" 
               class="search-input" 
               placeholder="Search for products..."
               autocomplete="off">
        <button onclick="closeSearch()" class="p-2 hover:bg-gray-100 rounded-full transition" aria-label="Close Search">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    
    <div id="search-results" class="search-results">
        <!-- Popular Searches -->
        <div id="popular-searches" class="mb-8">
            <h3 class="text-lg font-bold mb-4">Popular Searches</h3>
            <div class="flex flex-wrap gap-2">
                <a href="/shop.php?search=apple" class="bg-gray-100 hover:bg-primary hover:text-white px-4 py-2 rounded-full transition" onclick="closeSearch()">
                    Dehydrated Apple
                </a>
                <a href="/shop.php?search=kiwi" class="bg-gray-100 hover:bg-primary hover:text-white px-4 py-2 rounded-full transition" onclick="closeSearch()">
                    Kiwi Slices
                </a>
                <a href="/shop.php?search=alle+hachi" class="bg-gray-100 hover:bg-primary hover:text-white px-4 py-2 rounded-full transition" onclick="closeSearch()">
                    Alle Hachi
                </a>
                <a href="/shop.php?search=combo" class="bg-gray-100 hover:bg-primary hover:text-white px-4 py-2 rounded-full transition" onclick="closeSearch()">
                    Combos
                </a>
                <a href="/shop.php?search=hamper" class="bg-gray-100 hover:bg-primary hover:text-white px-4 py-2 rounded-full transition" onclick="closeSearch()">
                    Gift Hampers
                </a>
            </div>
        </div>
        
        <!-- Categories -->
        <div id="categories-quick" class="mb-8">
            <h3 class="text-lg font-bold mb-4">Categories</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="/shop.php?category=fruits" class="bg-white rounded-xl p-4 hover:shadow-lg transition text-center" onclick="closeSearch()">
                    <div class="text-4xl mb-2">🍎</div>
                    <h4 class="font-semibold">Premium Fruits</h4>
                </a>
                <a href="/shop.php?category=hokh-suin" class="bg-white rounded-xl p-4 hover:shadow-lg transition text-center" onclick="closeSearch()">
                    <div class="text-4xl mb-2">🥬</div>
                    <h4 class="font-semibold">Hokh Suin</h4>
                </a>
                <a href="/shop.php?type=combo" class="bg-white rounded-xl p-4 hover:shadow-lg transition text-center" onclick="closeSearch()">
                    <div class="text-4xl mb-2">📦</div>
                    <h4 class="font-semibold">Combos</h4>
                </a>
                <a href="/shop.php?type=hamper" class="bg-white rounded-xl p-4 hover:shadow-lg transition text-center" onclick="closeSearch()">
                    <div class="text-4xl mb-2">🎁</div>
                    <h4 class="font-semibold">Hampers</h4>
                </a>
            </div>
        </div>
        
        <!-- Search Results Container -->
        <div id="search-results-container" class="hidden">
            <h3 class="text-lg font-bold mb-4">Search Results</h3>
            <div id="search-products-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <!-- Results will be loaded here -->
            </div>
        </div>
        
        <!-- Loading State -->
        <div id="search-loading" class="hidden text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary"></div>
            <p class="mt-4 text-gray-600">Searching...</p>
        </div>
        
        <!-- No Results -->
        <div id="search-no-results" class="hidden text-center py-12">
            <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <p class="text-gray-500 text-lg">No products found</p>
            <p class="text-gray-400 mt-2">Try searching with different keywords</p>
        </div>
    </div>
</div>


