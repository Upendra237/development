class BusinessService {
    constructor() {
        this.businesses = [];
        this.categories = [];
        this.callbacks = {
            onDataUpdate: []
        };
    }

    async initialize() {
        await Promise.all([
            this.loadCategories(),
            this.loadBusinesses()
        ]);
        this.notifyDataUpdate();
    }

    async loadCategories() {
        try {
            const response = await fetch('api/get-categories.php');
            const data = await response.json();
            this.categories = data.categories;
        } catch (error) {
            console.error('Error loading categories:', error);
        }
    }

    async loadBusinesses() {
        try {
            const response = await fetch('api/get-businesses.php');
            const data = await response.json();
            this.businesses = data.businesses;
            this.notifyDataUpdate();
        } catch (error) {
            console.error('Error loading businesses:', error);
        }
    }

    filterBusinesses(query = '', selectedCategories = new Set()) {
        return this.businesses.filter(business => {
            const matchesSearch = !query || 
                business.name.toLowerCase().includes(query.toLowerCase()) ||
                business.description.toLowerCase().includes(query.toLowerCase()) ||
                business.address.toLowerCase().includes(query.toLowerCase());
                
            const matchesCategory = selectedCategories.size === 0 || 
                selectedCategories.has(business.category);

            return matchesSearch && matchesCategory;
        });
    }

    onDataUpdate(callback) {
        this.callbacks.onDataUpdate.push(callback);
    }

    notifyDataUpdate() {
        this.callbacks.onDataUpdate.forEach(callback => callback({
            businesses: this.businesses,
            categories: this.categories
        }));
    }

    getCategory(categoryId) {
        return this.categories.find(category => category.id === categoryId);
    }
}