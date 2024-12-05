class MapController {
    constructor(mapId, options = {}) {
        const mapElement = document.getElementById(mapId);
        if (!mapElement) {
            console.error('Map container not found');
            return;
        }

        this.map = L.map(mapId, {
            center: [options.lat || DEFAULT_LAT, options.lng || DEFAULT_LNG],
            zoom: options.zoom || DEFAULT_ZOOM,
            zoomControl: true,
            scrollWheelZoom: true
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(this.map);

        this.markers = L.layerGroup().addTo(this.map);
        this.businesses = new Map();

        setTimeout(() => {
            this.map.invalidateSize();
        }, 100);
    }

    addMarker(business, category) {
        if (!business.location || !business.location.lat || !business.location.lng) {
            console.error('Invalid business location:', business);
            return;
        }

        const marker = L.marker([business.location.lat, business.location.lng], {
            icon: this.createCustomIcon(category),
            title: business.name,
            riseOnHover: true
        });

        const popupContent = this.createPopupContent(business);
        marker.bindPopup(popupContent, {
            maxWidth: 300,
            autoPanPadding: [50, 50]
        });

        this.markers.addLayer(marker);
        this.businesses.set(business.id, { business, marker });
    }

    createCustomIcon(category) {
        const iconHtml = `<div style="background-color: ${category.color}">
            <i class="bi ${category.icon}"></i>
        </div>`;
        
        return L.divIcon({
            className: `custom-marker ${category.id}`,
            html: iconHtml,
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -32]
        });
    }

    createPopupContent(business) {
        const hours = this.getTodayHours(business.hours);
        const isOpen = this.isBusinessOpen(business.hours);
        const statusClass = isOpen ? 'text-success' : 'text-danger';
        const statusText = isOpen ? 'Open' : 'Closed';

        return `
            <div class="business-popup">
                <h3>${business.name}</h3>
                <div class="rating">
                    <i class="bi bi-star-fill text-warning"></i> ${business.rating.toFixed(1)} 
                    <small>(${business.reviews_count} reviews)</small>
                </div>
                <div class="address">
                    <i class="bi bi-geo-alt"></i> ${business.address}
                </div>
                <div class="phone">
                    <i class="bi bi-telephone"></i> ${business.phone}
                </div>
                <div class="hours">
                    <i class="bi bi-clock"></i> <strong>Today:</strong> ${hours}
                    <span class="${statusClass}">
                        <i class="bi bi-circle-fill"></i> ${statusText}
                    </span>
                </div>
                ${business.website ? `
                    <div class="website mt-2">
                        <a href="${business.website}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-globe"></i> Visit Website
                        </a>
                    </div>
                ` : ''}
                ${business.features && business.features.length ? `
                    <div class="features mt-2">
                        <strong><i class="bi bi-tags"></i> Features:</strong><br>
                        ${business.features.map(f => `<span class="badge bg-light text-dark me-1">${f}</span>`).join('')}
                    </div>
                ` : ''}
            </div>
        `;
    }

    getTodayHours(hours) {
        if (!hours) return 'Hours not available';
        const days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        const today = days[new Date().getDay()];
        return hours[today] || 'Closed';
    }

    isBusinessOpen(hours) {
        if (!hours) return false;
        
        const now = new Date();
        const days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        const today = days[now.getDay()];
        const currentHours = hours[today];

        if (!currentHours || currentHours.toLowerCase() === 'closed') return false;

        const [openTime, closeTime] = currentHours.split(' - ').map(time => {
            const [hours, minutes] = time.replace(/[AP]M/i, '').trim().split(':');
            const isPM = time.toLowerCase().includes('pm');
            let hour = parseInt(hours);
            if (isPM && hour !== 12) hour += 12;
            if (!isPM && hour === 12) hour = 0;
            return new Date().setHours(hour, parseInt(minutes));
        });

        const currentTime = now.getTime();
        return currentTime >= openTime && currentTime <= closeTime;
    }

    clearMarkers() {
        this.markers.clearLayers();
        this.businesses.clear();
    }

    fitBounds() {
        if (this.businesses.size > 0) {
            const markers = Array.from(this.businesses.values()).map(b => b.marker);
            const group = L.featureGroup(markers);
            this.map.fitBounds(group.getBounds().pad(0.1));
        }
    }

    highlightMarker(businessId) {
        const business = this.businesses.get(businessId);
        if (business) {
            business.marker.openPopup();
            this.map.panTo(business.marker.getLatLng(), {
                animate: true,
                duration: 0.5
            });
        }
    }
}