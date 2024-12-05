document.addEventListener('DOMContentLoaded', async () => {
    const businessService = new BusinessService();
    const mapController = new MapController('map');
    let selectedCategories = new Set();
    let currentSearchTerm = '';

    function getOpenStatus(business) {
        const isOpen = mapController.isBusinessOpen(business.hours);
        return isOpen ? 
            '<span class="text-success"><i class="bi bi-clock"></i> Open</span>' : 
            '<span class="text-danger"><i class="bi bi-clock"></i> Closed</span>';
    }

    function getRatingStars(rating) {
        return '★'.repeat(Math.floor(rating)) + '☆'.repeat(5 - Math.floor(rating));
    }

    function highlightSearchTerm(text, searchTerm) {
        if (!searchTerm) return text;
        const regex = new RegExp(`(${searchTerm})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }

    function getBusinessColor(business) {
        const category = businessService.getCategory(business.category);
        return category ? category.color : '#777';
    }

    function getBusinessIcon(business) {
        const category = businessService.getCategory(business.category);
        return category ? category.icon : 'bi-building';
    }

    function renderBusinessList(businesses, searchTerm = '') {
        const listElement = document.getElementById('businessList');
        if (!businesses.length) {
            listElement.innerHTML = `<div class="alert alert-info">No businesses found${searchTerm ? ` for "${searchTerm}"` : ''}</div>`;
            return;
        }

        listElement.innerHTML = businesses.map(business => `
            <div class="business-item" data-id="${business.id}">
                <div class="d-flex align-items-start">
                    <div class="business-icon me-2" style="background-color: ${getBusinessColor(business)}">
                        <i class="bi ${getBusinessIcon(business)}"></i>
                    </div>
                    <div class="business-details flex-grow-1">
                        <h5 class="mb-1">${highlightSearchTerm(business.name, searchTerm)}</h5>
                        <div class="rating text-warning mb-1">
                            ${getRatingStars(business.rating)} 
                            <small class="text-muted">(${business.reviews_count})</small>
                        </div>
                        <div class="small text-muted">${highlightSearchTerm(business.address, searchTerm)}</div>
                        <div class="small text-muted">${getOpenStatus(business)}</div>
                    </div>
                </div>
            </div>
        `).join('');

        attachBusinessListEvents();
    }

    function renderCategoryFilters(categories) {
        const categoryList = document.getElementById('categoryList');
        categoryList.innerHTML = `
            <div class="category-grid">
                ${categories.map(category => `
                    <div class="category-filter">
                        <input type="checkbox" 
                               class="btn-check category-checkbox" 
                               id="category-${category.id}" 
                               value="${category.id}" 
                               checked>
                        <label class="btn btn-outline-secondary w-100 text-start" for="category-${category.id}">
                            <span class="category-icon" style="background-color: ${category.color}">
                                <i class="bi ${category.icon}"></i>
                            </span>
                            <span class="category-name">${category.name}</span>
                        </label>
                    </div>
                `).join('')}
            </div>
        `;

        categories.forEach(category => selectedCategories.add(category.id));
        attachCategoryEvents();
        updateFilterButtons();
    }

    function updateFilterButtons() {
        const checkboxes = document.querySelectorAll('.category-checkbox');
        const selectAllBtn = document.getElementById('selectAll');
        const clearAllBtn = document.getElementById('clearAll');
        
        const checkedCount = document.querySelectorAll('.category-checkbox:checked').length;
        const totalCount = checkboxes.length;
        
        selectAllBtn.disabled = checkedCount === totalCount;
        clearAllBtn.disabled = checkedCount === 0;
    }

    function attachCategoryEvents() {
        document.querySelectorAll('.category-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', handleCategoryChange);
        });

        document.getElementById('selectAll').addEventListener('click', selectAllCategories);
        document.getElementById('clearAll').addEventListener('click', clearAllCategories);
    }

    function selectAllCategories() {
        document.querySelectorAll('.category-checkbox').forEach(checkbox => {
            checkbox.checked = true;
            selectedCategories.add(checkbox.value);
        });
        updateFilterButtons();
        updateDisplayedBusinesses();
    }

    function clearAllCategories() {
        selectedCategories.clear(); // Clear the Set first
        document.querySelectorAll('.category-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        updateFilterButtons();
        updateDisplayedBusinesses();
    }

    function handleCategoryChange(e) {
        const checkbox = e.target;
        if (checkbox.checked) {
            selectedCategories.add(checkbox.value);
        } else {
            selectedCategories.delete(checkbox.value);
        }
        updateFilterButtons();
        updateDisplayedBusinesses();
    }

    function updateDisplayedBusinesses() {
        const filteredBusinesses = selectedCategories.size === 0 ? [] : businessService.filterBusinesses(currentSearchTerm, selectedCategories);
        renderBusinessList(filteredBusinesses, currentSearchTerm);
        
        mapController.clearMarkers();
        if (filteredBusinesses.length > 0) {
            filteredBusinesses.forEach(business => {
                const category = businessService.getCategory(business.category);
                mapController.addMarker(business, category);
            });
            mapController.fitBounds();
        }
    }

    function attachBusinessListEvents() {
        document.querySelectorAll('.business-item').forEach(item => {
            item.addEventListener('click', () => {
                const businessId = parseInt(item.dataset.id);
                mapController.highlightMarker(businessId);
                item.classList.add('active');
                setTimeout(() => item.classList.remove('active'), 1000);
            });
        });
    }

    function handleSearch(query) {
        currentSearchTerm = query;
        updateDisplayedBusinesses();
    }

    const searchInput = document.getElementById('searchInput');
    let searchTimeout;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => handleSearch(e.target.value.trim()), 300);
    });

    businessService.onDataUpdate(({ businesses, categories }) => {
        if (categories.length > 0 && !document.querySelector('.category-checkbox')) {
            renderCategoryFilters(categories);
        }
        updateDisplayedBusinesses();
    });

    await businessService.initialize();
});