// DRIYUM - Search JavaScript
// This file handles search-specific functionality

// Search history management
const MAX_SEARCH_HISTORY = 10;

function saveSearchHistory(query) {
    let history = JSON.parse(localStorage.getItem('searchHistory') || '[]');

    // Remove if already exists
    history = history.filter(item => item !== query);

    // Add to beginning
    history.unshift(query);

    // Limit size
    if (history.length > MAX_SEARCH_HISTORY) {
        history = history.slice(0, MAX_SEARCH_HISTORY);
    }

    localStorage.setItem('searchHistory', JSON.stringify(history));
}

function loadSearchHistory() {
    return JSON.parse(localStorage.getItem('searchHistory') || '[]');
}

function clearSearchHistory() {
    localStorage.removeItem('searchHistory');
    showToast('Search history cleared', 'success');
}

// Display search history if available
document.getElementById('search-input')?.addEventListener('focus', function () {
    const history = loadSearchHistory();
    if (history.length > 0) {
        // Could display recent searches here
    }
});
