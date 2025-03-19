document.addEventListener('DOMContentLoaded', function() {
    initializeSearchFunctionality();
});

function initializeSearchFunctionality() {
    const searchInputs = document.querySelectorAll('.search-input');
    
    // Apply live search to all search inputs on the page
    searchInputs.forEach(searchInput => {
        const searchContainer = searchInput.closest('.search-container');
        
        // Create results container if it doesn't exist
        let searchResults = searchContainer.querySelector('.search-results-dropdown');
        if (!searchResults) {
            searchResults = document.createElement('div');
            searchResults.className = 'search-results-dropdown';
            searchResults.style.display = 'none';
            searchContainer.appendChild(searchResults);
        }
        
        // Add event listener for input changes
        let debounceTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimeout);
            const query = this.value.trim();
            
            // Hide results if query is empty or too short
            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }
            
            // Show loading indicator
            searchResults.innerHTML = '<div class="search-loading">Searching...</div>';
            searchResults.style.display = 'block';
            
            // Debounce the API call to avoid too many requests
            debounceTimeout = setTimeout(function() {
                fetchSearchResults(query, searchResults);
            }, 300);
        });
        
        // Handle clicking on search input
        searchInput.addEventListener('focus', function() {
            const query = this.value.trim();
            if (query.length >= 2) {
                fetchSearchResults(query, searchResults);
            }
        });
        
        // Hide search results when clicking outside
        document.addEventListener('click', function(event) {
            if (!searchContainer.contains(event.target)) {
                searchResults.style.display = 'none';
            }
        });
        
        // Prevent form submission on Enter if dropdown is open
        searchInput.closest('form').addEventListener('submit', function(event) {
            if (searchResults.style.display === 'block') {
                // Only prevent if user is selecting an item
                const activeItem = searchResults.querySelector('.search-result-item:hover');
                if (activeItem) {
                    event.preventDefault();
                    activeItem.querySelector('a').click();
                }
            }
        });
    });
}

// Function to fetch search results
function fetchSearchResults(query, resultsContainer) {
    fetch(`live_search.php?q=${encodeURIComponent(query)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.data.length > 0) {
                displaySearchResults(data.data, query, resultsContainer);
            } else {
                resultsContainer.innerHTML = '<div class="no-results-found">No products found</div>';
                resultsContainer.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error fetching search results:', error);
            resultsContainer.innerHTML = '<div class="search-error">Error loading results</div>';
            resultsContainer.style.display = 'block';
        });
}

// Function to display search results
function displaySearchResults(items, query, resultsContainer) {
    resultsContainer.innerHTML = '';
    
    // Create a header
    const headerElement = document.createElement('div');
    headerElement.className = 'search-results-header';
    headerElement.textContent = `Quick results for "${query}"`;
    resultsContainer.appendChild(headerElement);
    
    // Create results container
    const resultsElement = document.createElement('div');
    resultsElement.className = 'search-results-items';
    
    items.forEach(item => {
        const resultItem = document.createElement('div');
        resultItem.className = 'search-result-item';
        
        resultItem.innerHTML = `
            <a href="Traditional.php?id=${item.id}" class="search-result-link">
                <div class="search-result-image">
                    <img src="${item.image}" alt="${item.name}" onerror="this.src='images/placeholder.jpg'">
                </div>
                <div class="search-result-info">
                    <div class="search-result-name">${item.name}</div>
                    <div class="search-result-category">${item.category_name} - ${item.subcategory_name}</div>
                    <div class="search-result-price">₹${item.price}</div>
                </div>
            </a>
        `;
        
        resultsElement.appendChild(resultItem);
    });
    
    resultsContainer.appendChild(resultsElement);
    
    // Add "View All Results" link
    const searchInput = resultsContainer.closest('.search-container').querySelector('.search-input');
    const viewAllLink = document.createElement('div');
    viewAllLink.className = 'view-all-results';
    viewAllLink.innerHTML = `<a href="search_results.php?q=${encodeURIComponent(query)}">View all results (${items.length}+)</a>`;
    resultsContainer.appendChild(viewAllLink);
    
    resultsContainer.style.display = 'block';
}

// Enhance keyboard navigation for search results
function addKeyboardNavigation(resultsContainer, searchInput) {
    searchInput.addEventListener('keydown', function(e) {
        const results = resultsContainer.querySelectorAll('.search-result-item');
        if (results.length === 0 || resultsContainer.style.display === 'none') return;
        
        const active = resultsContainer.querySelector('.search-result-item.active');
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                if (!active) {
                    results[0].classList.add('active');
                } else {
                    active.classList.remove('active');
                    const next = active.nextElementSibling;
                    if (next && next.classList.contains('search-result-item')) {
                        next.classList.add('active');
                    } else {
                        results[0].classList.add('active');
                    }
                }
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                if (!active) {
                    results[results.length - 1].classList.add('active');
                } else {
                    active.classList.remove('active');
                    const prev = active.previousElementSibling;
                    if (prev && prev.classList.contains('search-result-item')) {
                        prev.classList.add('active');
                    } else {
                        results[results.length - 1].classList.add('active');
                    }
                }
                break;
                
            case 'Enter':
                if (active) {
                    e.preventDefault();
                    active.querySelector('a').click();
                }
                break;
                
            case 'Escape':
                resultsContainer.style.display = 'none';
                break;
        }
    });
}