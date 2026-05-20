<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- This server is static (no PHP execution), so visiting `/index.php` should still show the real frontend. -->
    <meta http-equiv="refresh" content="0; url=./index.html" />
    <title>Veterinary Shops Locator - PetCare</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            200: '#ddd6fe',
                            300: '#c4b5fd',
                            400: '#a78bfa',
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9',
                            800: '#5b21b6',
                            900: '#4c1d95',
                        },
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                },
            },
        }
    </script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            color: #334155;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
        }

        #map {
            height: 600px;
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            z-index: 10;
        }

        .card {
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .search-container {
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #8b5cf6;
            pointer-events: none;
        }

        #citySearch {
            padding-left: 40px;
        }

        .pulse-dot {
            position: relative;
        }

        .pulse-dot::before {
            content: '';
            position: absolute;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #8b5cf6;
            left: -20px;
            top: 50%;
            transform: translateY(-50%);
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        /* Custom Leaflet Marker Styles */
        .custom-marker-icon {
            background-color: #8b5cf6;
            border-radius: 50%;
            border: 2px solid white;
            text-align: center;
            color: white;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        /* Custom Leaflet Popup Styles */
        .leaflet-popup-content-wrapper {
            border-radius: 8px;
            padding: 0;
            overflow: hidden;
        }

        .leaflet-popup-content {
            margin: 0;
            padding: 0;
        }

        .custom-popup {
            padding: 15px;
        }

        .custom-popup-header {
            background-color: #8b5cf6;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            font-size: 16px;
            border-radius: 8px 8px 0 0;
        }

        .custom-popup-content {
            padding: 15px;
        }

        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 50px;
            height: 50px;
            border: 3px solid rgba(139, 92, 246, 0.3);
            border-radius: 50%;
            border-top-color: #8b5cf6;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Scrollbar Styling */
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #c4b5fd;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #a78bfa;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
            
            #map {
                height: 400px;
            }
            
            .shops-container {
                max-width: 100%;
            }
        }
    </style>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body class="min-h-screen">
    <header class="gradient-bg text-white shadow-lg">
        <div class="container mx-auto py-4 px-6 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <i class="fas fa-paw text-2xl"></i>
                <h1 class="text-2xl font-bold">PetCare Veterinary Locator</h1>
            </div>
            <div class="hidden md:block">
                <p class="text-primary-100 text-sm">Find the nearest veterinary shops for your pet</p>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 flex items-center">
                <i class="fas fa-search-location mr-2 text-primary-500"></i>
                Find Veterinary Shops
            </h2>
            
            <div class="grid md:grid-cols-2 gap-6">
                <div class="search-container">
                    <i class="fas fa-city search-icon"></i>
                    <input type="text" id="citySearch" class="w-full p-3 border-2 border-primary-200 rounded-lg text-base outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition-all" placeholder="Enter city name..." />
                </div>
                
                <div class="flex space-x-3">
                    <button class="flex-1 bg-primary-600 text-white px-5 py-3 rounded-lg font-medium hover:bg-primary-500 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors duration-200 flex items-center justify-center" id="searchButton" onclick="searchCity()">
                        <i class="fas fa-search mr-2"></i>
                        Search
                    </button>
                    
                    <button class="flex-1 bg-primary-100 text-primary-700 px-5 py-3 rounded-lg font-medium hover:bg-primary-200 disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed transition-colors duration-200 flex items-center justify-center" id="refreshButton" onclick="getLocation()">
                        <i class="fas fa-location-arrow mr-2"></i>
                        Use My Location
                    </button>
                </div>
            </div>
            
            <div class="mt-4">
                <p id="location" class="text-gray-600 font-medium"></p>
                <p id="error" class="text-red-500 font-medium"></p>
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-6">
            <div class="w-full md:w-2/3">
                <div class="bg-white rounded-xl shadow-md p-6 h-full">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800 flex items-center">
                        <i class="fas fa-map-marked-alt mr-2 text-primary-500"></i>
                        Map View
                    </h2>
                    <div id="map" class="rounded-lg"></div>
                </div>
            </div>
            
            <div class="w-full md:w-1/3">
                <div class="bg-white rounded-xl shadow-md p-6 h-full">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800 flex items-center">
                        <i class="fas fa-clinic-medical mr-2 text-primary-500"></i>
                        Nearby Veterinary Shops
                    </h2>
                    
                    <div id="loading" class="flex flex-col items-center justify-center py-10" style="display: none;">
                        <div class="loading-spinner mb-4"></div>
                        <p class="text-gray-600 font-medium pulse-dot">Searching for veterinary shops...</p>
                    </div>
                    
                    <div id="shops" class="max-h-[500px] overflow-y-auto custom-scrollbar pr-2"></div>
                </div>
            </div>
        </div>
    </main>

    <footer class="gradient-bg text-white mt-12 py-6">
        <div class="container mx-auto px-6">
            <div class="text-center">
                <p>&copy; 2023 PetCare Veterinary Locator. All rights reserved.</p>
                <p class="text-primary-200 text-sm mt-2">Helping pet owners find quality veterinary care since 2023</p>
            </div>
        </div>
    </footer>

    <script>
        const apiBase = window.location.origin.includes('localhost') ? 'http://localhost:3002' : '';
        let map;
        const shopsMarkers = [];

        function setLoading(isLoading) {
            const loadingElement = document.getElementById('loading');
            const refreshButton = document.getElementById('refreshButton');
            const searchButton = document.getElementById('searchButton');
            loadingElement.style.display = isLoading ? 'flex' : 'none';
            refreshButton.disabled = isLoading;
            searchButton.disabled = isLoading;
        }

        async function searchCity() {
            const cityInput = document.getElementById('citySearch');
            const city = cityInput.value.trim();
            const errorElement = document.getElementById('error');
            const locationElement = document.getElementById('location');

            if (!city) {
                errorElement.textContent = 'Please enter a city name';
                return;
            }

            errorElement.textContent = '';
            locationElement.innerHTML = `<i class="fas fa-search mr-2"></i> Searching for: <span class="font-semibold">${city}</span>`;
            setLoading(true);

            try {
                // Get coordinates for the city
                const geocodeResponse = await fetch(`${apiBase}/api/geocode?city=${encodeURIComponent(city)}`);
                if (!geocodeResponse.ok) {
                    const errorData = await geocodeResponse.json();
                    throw new Error(errorData.error || `Failed to find location: ${city}`);
                }

                const { latitude, longitude } = await geocodeResponse.json();
                locationElement.innerHTML = `<i class="fas fa-map-marker-alt mr-2 text-primary-500"></i> Location: <span class="font-semibold">${city}</span> <span class="text-gray-500">(${latitude.toFixed(5)}, ${longitude.toFixed(5)})</span>`;

                // Update map and fetch vet shops
                initializeMap(latitude, longitude);
                await fetchAndDisplayVetShops(latitude, longitude);
            } catch (err) {
                errorElement.innerHTML = `<i class="fas fa-exclamation-circle mr-2"></i> ${err.message}`;
                console.error('Error:', err);
            } finally {
                setLoading(false);
            }
        }

        async function fetchAndDisplayVetShops(latitude, longitude) {
            const errorElement = document.getElementById('error');
            const shopsElement = document.getElementById('shops');
            shopsElement.innerHTML = '';

            try {
                const data = await getVetShops(latitude, longitude);

                if (data.features && data.features.length > 0) {
                    data.features.forEach((shop, index) => {
                        const card = document.createElement('div');
                        card.className = 'card bg-white border border-gray-100 rounded-lg p-4 mb-4 shadow-sm hover:shadow-md transition-all';
                        
                        // Determine shop category icon
                        let categoryIcon = 'fa-clinic-medical';
                        if (shop.properties.categories) {
                            if (shop.properties.categories.toLowerCase().includes('emergency')) {
                                categoryIcon = 'fa-first-aid';
                            } else if (shop.properties.categories.toLowerCase().includes('pet')) {
                                categoryIcon = 'fa-paw';
                            } else if (shop.properties.categories.toLowerCase().includes('pharmacy')) {
                                categoryIcon = 'fa-pills';
                            }
                        }
                        
                        // Calculate distance class based on proximity
                        let distanceClass = 'text-green-600';
                        const distanceKm = shop.properties.distance / 1000;
                        if (distanceKm > 5) {
                            distanceClass = 'text-red-500';
                        } else if (distanceKm > 2) {
                            distanceClass = 'text-yellow-500';
                        }
                        
                        card.innerHTML = `
                            <div class="flex items-start">
                                <div class="bg-primary-100 text-primary-700 rounded-full w-10 h-10 flex items-center justify-center mr-3 flex-shrink-0">
                                    <i class="fas ${categoryIcon}"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-lg text-gray-800 mb-1">${shop.properties.name || 'Unnamed Veterinary Shop'}</h3>
                                    <div class="text-sm text-gray-600 mb-2">
                                        <p class="flex items-center"><i class="fas fa-map-marker-alt mr-2 text-primary-400"></i>${shop.properties.address_line1 || 'Address not available'}</p>
                                        ${shop.properties.address_line2 ? `<p class="ml-6 text-gray-500">${shop.properties.address_line2}</p>` : ''}
                                    </div>
                                    <div class="flex items-center justify-between mt-2">
                                        <span class="flex items-center ${distanceClass} font-medium">
                                            <i class="fas fa-route mr-1"></i>
                                            ${distanceKm.toFixed(2)} km
                                        </span>
                                        <button class="text-primary-600 hover:text-primary-800 text-sm font-medium" 
                                                onclick="focusShopOnMap(${index})">
                                            <i class="fas fa-map-pin mr-1"></i> Show on map
                                        </button>
                                    </div>
                                    ${shop.properties.categories ? 
                                        `<div class="mt-2 flex flex-wrap gap-1">
                                            ${shop.properties.categories.split(',').map(category => 
                                                `<span class="bg-primary-50 text-primary-700 text-xs px-2 py-1 rounded-full">${category.trim()}</span>`
                                            ).join('')}
                                        </div>` : 
                                        ''}
                                </div>
                            </div>
                        `;
                        
                        // Add event listener to highlight corresponding marker when hovering over card
                        card.addEventListener('mouseenter', () => {
                            if (shopsMarkers[index]) {
                                shopsMarkers[index].setIcon(createMarkerIcon(index + 1, true));
                            }
                        });
                        
                        card.addEventListener('mouseleave', () => {
                            if (shopsMarkers[index]) {
                                shopsMarkers[index].setIcon(createMarkerIcon(index + 1, false));
                            }
                        });
                        
                        shopsElement.appendChild(card);
                    });
                    addMarkersToMap(data.features);
                } else {
                    shopsElement.innerHTML = `
                        <div class="text-center py-10">
                            <i class="fas fa-search text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-600">No veterinary shops found nearby.</p>
                            <p class="text-gray-500 text-sm mt-2">Try searching in a different location or increasing the search radius.</p>
                        </div>
                    `;
                }
            } catch (err) {
                errorElement.innerHTML = `<i class="fas fa-exclamation-circle mr-2"></i> ${err.message}`;
                console.error('Error:', err);
            }
        }

        async function getVetShops(latitude, longitude) {
            try {
                const response = await fetch(`${apiBase}/api/vetshops?latitude=${latitude}&longitude=${longitude}`);
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.details || `Server returned ${response.status}: ${response.statusText}`);
                }
                return await response.json();
            } catch (err) {
                console.error('Error fetching vet shops:', err);
                throw new Error(err.message || 'Failed to fetch veterinary shops. Please try again later.');
            }
        }

        function createMarkerIcon(number, isHighlighted) {
            return L.divIcon({
                className: 'custom-marker-icon',
                html: number,
                iconSize: isHighlighted ? [36, 36] : [30, 30],
                iconAnchor: isHighlighted ? [18, 18] : [15, 15]
            });
        }

        function createCustomPopup(shop) {
            return `
                <div>
                    <div class="custom-popup-header">${shop.properties.name || 'Unnamed Veterinary Shop'}</div>
                    <div class="custom-popup-content">
                        <p><i class="fas fa-map-marker-alt mr-2 text-primary-500"></i> ${shop.properties.address_line1 || ''}</p>
                        ${shop.properties.address_line2 ? `<p class="ml-6 text-gray-500">${shop.properties.address_line2}</p>` : ''}
                        <p class="mt-2"><i class="fas fa-route mr-2 text-primary-500"></i> ${(shop.properties.distance / 1000).toFixed(2)} km</p>
                        ${shop.properties.categories ? 
                            `<div class="mt-2">
                                <p><i class="fas fa-tag mr-2 text-primary-500"></i> Services:</p>
                                <div class="ml-6 mt-1 flex flex-wrap gap-1">
                                    ${shop.properties.categories.split(',').map(category => 
                                        `<span class="bg-primary-50 text-primary-700 text-xs px-2 py-1 rounded-full">${category.trim()}</span>`
                                    ).join('')}
                                </div>
                            </div>` : 
                            ''}
                    </div>
                </div>
            `;
        }

        function initializeMap(latitude, longitude) {
            if (!map) {
                map = L.map('map').setView([latitude, longitude], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);
                
                // Add user location marker
                L.marker([latitude, longitude], {
                    icon: L.divIcon({
                        className: 'custom-user-location',
                        html: '<div style="background-color: #3b82f6; width: 16px; height: 16px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);"></div>',
                        iconSize: [16, 16],
                        iconAnchor: [8, 8]
                    })
                }).addTo(map)
                .bindPopup('<div class="p-2"><strong>Your Location</strong></div>')
                .openPopup();
                
                // Add pulsing circle around user location
                L.circle([latitude, longitude], {
                    color: '#3b82f6',
                    fillColor: '#3b82f6',
                    fillOpacity: 0.1,
                    radius: 500,
                    weight: 1
                }).addTo(map);
            } else {
                map.setView([latitude, longitude], 13);
                
                // Clear existing circles and add new one
                map.eachLayer(layer => {
                    if (layer instanceof L.Circle) {
                        map.removeLayer(layer);
                    }
                });
                
                L.circle([latitude, longitude], {
                    color: '#3b82f6',
                    fillColor: '#3b82f6',
                    fillOpacity: 0.1,
                    radius: 500,
                    weight: 1
                }).addTo(map);
            }
        }

        function addMarkersToMap(shops) {
            shopsMarkers.forEach(marker => map.removeLayer(marker));
            shopsMarkers.length = 0;

            shops.forEach((shop, index) => {
                const marker = L.marker([shop.properties.lat, shop.properties.lon], {
                    icon: createMarkerIcon(index + 1, false)
                })
                .bindPopup(createCustomPopup(shop))
                .addTo(map);
                
                marker.on('mouseover', function() {
                    this.setIcon(createMarkerIcon(index + 1, true));
                });
                
                marker.on('mouseout', function() {
                    this.setIcon(createMarkerIcon(index + 1, false));
                });

                shopsMarkers.push(marker);
            });
        }
        
        function focusShopOnMap(index) {
            if (shopsMarkers[index]) {
                const marker = shopsMarkers[index];
                map.setView(marker.getLatLng(), 15);
                marker.openPopup();
            }
        }

        function getLocation() {
            const errorElement = document.getElementById('error');
            const locationElement = document.getElementById('location');
            const shopsElement = document.getElementById('shops');

            errorElement.textContent = '';
            locationElement.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Getting your location...';
            shopsElement.innerHTML = '';
            setLoading(true);

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    async (position) => {
                        const { latitude, longitude } = position.coords;
                        locationElement.innerHTML = `<i class="fas fa-map-marker-alt mr-2 text-primary-500"></i> Your Location: <span class="font-semibold">${latitude.toFixed(5)}, ${longitude.toFixed(5)}</span>`;

                        initializeMap(latitude, longitude);
                        await fetchAndDisplayVetShops(latitude, longitude);
                        setLoading(false);
                    },
                    (err) => {
                        errorElement.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i> Failed to access location. Please allow location access in your browser settings.';
                        locationElement.textContent = '';
                        setLoading(false);
                    },
                    { enableHighAccuracy: true }
                );
            } else {
                errorElement.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i> Geolocation is not supported by your browser. Please use a modern browser.';
                locationElement.textContent = '';
                setLoading(false);
            }
        }

        // Add event listener for Enter key in search input
        document.getElementById('citySearch').addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchCity();
            }
        });

        // Initialize with a default view if no location is provided
        window.addEventListener('DOMContentLoaded', function() {
            // Default to a generic location (you can change these coordinates)
            initializeMap(40.7128, -74.0060);
            document.getElementById('location').innerHTML = '<i class="fas fa-info-circle mr-2 text-primary-500"></i> Please search for a location or use your current location';
        });
    </script>
</body>
</html>