(() => {
  const API_BASE = window.location.origin.includes('localhost') ? 'http://localhost:3002' : '';


  const els = {
    searchForm: document.getElementById('searchForm'),
    citySearch: document.getElementById('citySearch'),
    searchBtn: document.getElementById('searchBtn'),
    locationBtn: document.getElementById('locationBtn'),
    status: document.getElementById('status'),
    error: document.getElementById('error'),
    loading: document.getElementById('loading'),
    emptyState: document.getElementById('emptyState'),
    shops: document.getElementById('shops'),
    resultCount: document.getElementById('resultCount'),
    mapEl: document.getElementById('map'),
  };

  const state = {
    map: null,
    shopMarkers: [],
    userCircle: null,
    userMarker: null,
    requestSeq: 0,
  };

  function setError(message) {
    els.error.textContent = message || '';
  }

  function setStatus(message) {
    els.status.textContent = message || '';
  }

  function setLoading(isLoading, message) {
    els.loading.hidden = !isLoading;
    els.searchBtn.disabled = isLoading;
    els.locationBtn.disabled = isLoading;
    if (message) setStatus(message);
  }

  function kmFromMeters(meters) {
    const km = (meters || 0) / 1000;
    return km;
  }

  function distanceBadgeClass(km) {
    if (km > 5) return 'badge-danger';
    if (km > 2) return 'badge-warning';
    return 'badge-success';
  }

  function getCategoryIcon(categoriesText) {
    const text = (categoriesText || '').toLowerCase();
    if (text.includes('emergency')) return 'fa-first-aid';
    if (text.includes('pharmacy')) return 'fa-pills';
    if (text.includes('pet') || text.includes('animal') || text.includes('clinic')) return 'fa-paw';
    return 'fa-clinic-medical';
  }

  function createMarkerIcon(number, isHighlighted) {
    const size = isHighlighted ? 36 : 30;
    const anchor = isHighlighted ? 18 : 15;
    return L.divIcon({
      className: 'custom-marker-icon',
      html: String(number),
      iconSize: [size, size],
      iconAnchor: [anchor, anchor],
    });
  }

  function createCustomPopup(shop) {
    const name = shop?.properties?.name || 'Unnamed Veterinary Shop';
    const address1 = shop?.properties?.address_line1 || '';
    const address2 = shop?.properties?.address_line2 || '';
    const km = kmFromMeters(shop?.properties?.distance);
    const categories = shop?.properties?.categories || '';

    const categoriesHtml = categories
      ? `
        <div class="mt-2" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;">
          ${categories
            .split(',')
            .map((c) => c.trim())
            .filter(Boolean)
            .slice(0, 8)
            .map((category) => `<span class="chip" style="background:rgba(245,243,255,1);padding:6px 9px;border-radius:999px;border:1px solid #ddd6fe;color:#7c3aed;font-weight:800;font-size:12px;">${category}</span>`)
            .join('')}
        </div>
      `
      : '';

    const address2Html = address2 ? `<div style="margin-top:6px;color:#6b5d85;font-weight:650;">${address2}</div>` : '';

    return `
      <div>
        <div class="custom-popup-header">${name}</div>
        <div class="custom-popup-body">
          <div style="display:flex;gap:10px;align-items:flex-start;">
            <div style="color:#7c3aed;"><i class="fas fa-location-dot"></i></div>
            <div>
              <div style="font-weight:800;">${address1 || 'Address not available'}</div>
              ${address2Html}
            </div>
          </div>
          <div style="margin-top:10px;color:#6b5d85;font-weight:800;">
            <i class="fas fa-route" style="color:#7c3aed;margin-right:8px;"></i>
            ${(km || 0).toFixed(2)} km away
          </div>
          ${categoriesHtml}
        </div>
      </div>
    `;
  }

  function initMapIfNeeded(latitude, longitude) {
    if (!state.map) {
      state.map = L.map('map').setView([latitude, longitude], 13);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
      }).addTo(state.map);

      // User marker/circle will be updated when we know the coordinates
      state.userMarker = L.marker([latitude, longitude], {
        icon: L.divIcon({
          className: '',
          html: '<div style="background:#3b82f6;width:16px;height:16px;border-radius:50%;border:3px solid #fff;box-shadow:0 0 0 2px rgba(59,130,246,0.45);"></div>',
          iconSize: [16, 16],
          iconAnchor: [8, 8],
        }),
      }).addTo(state.map).bindPopup('<div style="padding:6px 10px;font-weight:900;">Your location</div>');

      state.userCircle = L.circle([latitude, longitude], {
        color: '#3b82f6',
        fillColor: '#3b82f6',
        fillOpacity: 0.1,
        radius: 500,
        weight: 1,
      }).addTo(state.map);

      return;
    }

    state.map.setView([latitude, longitude], 13);

    if (state.userMarker) {
      state.userMarker.setLatLng([latitude, longitude]);
    } else {
      state.userMarker = L.marker([latitude, longitude]).addTo(state.map);
    }

    if (state.userCircle) {
      state.userCircle.setLatLng([latitude, longitude]);
    } else {
      state.userCircle = L.circle([latitude, longitude], {
        color: '#3b82f6',
        fillColor: '#3b82f6',
        fillOpacity: 0.1,
        radius: 500,
        weight: 1,
      }).addTo(state.map);
    }
  }

  function clearShopMarkers() {
    state.shopMarkers.forEach((m) => state.map.removeLayer(m));
    state.shopMarkers = [];
  }

  function addMarkersToMap(features) {
    if (!state.map) return;
    clearShopMarkers();

    features.forEach((shop, index) => {
      const marker = L.marker([shop.properties.lat, shop.properties.lon], {
        icon: createMarkerIcon(index + 1, false),
      })
        .bindPopup(createCustomPopup(shop))
        .addTo(state.map);

      marker.on('mouseover', () => marker.setIcon(createMarkerIcon(index + 1, true)));
      marker.on('mouseout', () => marker.setIcon(createMarkerIcon(index + 1, false)));

      state.shopMarkers.push(marker);
    });
  }

  function focusShopOnMap(index) {
    const marker = state.shopMarkers[index];
    if (!marker || !state.map) return;
    state.map.setView(marker.getLatLng(), 15, { animate: true });
    marker.openPopup();
  }

  function renderShopCards(features) {
    els.shops.innerHTML = '';
    const count = features?.length || 0;
    els.resultCount.textContent = String(count);

    if (!count) {
      els.emptyState.hidden = false;
      return;
    }

    els.emptyState.hidden = true;

    features.forEach((shop, index) => {
      const name = shop?.properties?.name || 'Unnamed Veterinary Shop';
      const address1 = shop?.properties?.address_line1 || 'Address not available';
      const address2 = shop?.properties?.address_line2 || '';
      const categoriesText = shop?.properties?.categories || '';
      const km = kmFromMeters(shop?.properties?.distance);
      const badgeClass = distanceBadgeClass(km);
      const categoryIcon = getCategoryIcon(categoriesText);

      const categories = categoriesText
        ? categoriesText
            .split(',')
            .map((c) => c.trim())
            .filter(Boolean)
            .slice(0, 6)
        : [];

      const card = document.createElement('div');
      card.className = 'shop-card';
      card.setAttribute('role', 'listitem');
      card.innerHTML = `
        <div class="shop-row">
          <div class="shop-avatar" aria-hidden="true">
            <i class="fas ${categoryIcon}"></i>
          </div>
          <div style="flex:1;">
            <h3 class="shop-title">${name}</h3>
            <div class="shop-address">
              <div><i class="fas fa-location-dot" style="color:#7c3aed;margin-right:8px;"></i>${address1}</div>
              ${address2 ? `<div style="margin-top:6px;">${address2}</div>` : ''}
            </div>

            ${categories.length ? `
              <div class="chips">
                ${categories.map((c) => `<span class="chip">${c}</span>`).join('')}
              </div>
            ` : ''}

            <div class="shop-actions">
              <span class="badge ${badgeClass}">
                <i class="fas fa-route"></i>
                ${km.toFixed(2)} km
              </span>

              <button class="link-btn" type="button" data-index="${index}" aria-label="Show ${name} on map">
                <i class="fas fa-map-pin"></i>
                Show on map
              </button>
            </div>
          </div>
        </div>
      `;

      // Card hover -> highlight corresponding map marker
      card.addEventListener('mouseenter', () => {
        const marker = state.shopMarkers[index];
        if (marker) marker.setIcon(createMarkerIcon(index + 1, true));
      });

      card.addEventListener('mouseleave', () => {
        const marker = state.shopMarkers[index];
        if (marker) marker.setIcon(createMarkerIcon(index + 1, false));
      });

      const btn = card.querySelector('button[data-index]');
      btn.addEventListener('click', () => focusShopOnMap(index));

      els.shops.appendChild(card);
    });
  }

  async function getGeocodeForCity(city) {
    const res = await fetch(`${API_BASE}/api/geocode?city=${encodeURIComponent(city)}`);
    if (!res.ok) {
      let details = 'Could not find that city.';
      try {
        const data = await res.json();
        details = data?.error || data?.details || details;
      } catch {
        // ignore json parsing issues
      }
      throw new Error(details);
    }
    return res.json();
  }

  async function getVetShops(latitude, longitude) {
    const res = await fetch(`${API_BASE}/api/vetshops?latitude=${latitude}&longitude=${longitude}`);
    if (!res.ok) {
      let details = 'Failed to fetch veterinary shops.';
      try {
        const data = await res.json();
        details = data?.details || data?.error || details;
      } catch {
        // ignore json parsing issues
      }
      throw new Error(details);
    }
    return res.json();
  }

  async function handleSearchCity(city) {
    const trimmed = (city || '').trim();
    if (!trimmed) {
      setError('Please enter a city name.');
      return;
    }

    setError('');
    setStatus(`Searching for shops near “${trimmed}”…`);
    setLoading(true);

    state.requestSeq += 1;
    const requestId = state.requestSeq;

    try {
      const geo = await getGeocodeForCity(trimmed);
      if (requestId !== state.requestSeq) return; // ignore stale results

      const { latitude, longitude } = geo;
      setStatus(`Location found: ${trimmed} (${latitude.toFixed(5)}, ${longitude.toFixed(5)})`);

      initMapIfNeeded(latitude, longitude);

      const shopsData = await getVetShops(latitude, longitude);
      if (requestId !== state.requestSeq) return; // ignore stale results

      const features = shopsData?.features || [];
      renderShopCards(features);
      addMarkersToMap(features);
    } catch (err) {
      setError(err?.message || 'Something went wrong. Please try again.');
      els.resultCount.textContent = '0';
      els.shops.innerHTML = '';
      els.emptyState.hidden = false;
    } finally {
      if (requestId === state.requestSeq) setLoading(false);
    }
  }

  async function handleUseLocation() {
    setError('');
    setStatus('Getting your location…');
    setLoading(true);

    state.requestSeq += 1;
    const requestId = state.requestSeq;

    if (!navigator.geolocation) {
      setError('Geolocation is not supported by your browser.');
      setLoading(false);
      return;
    }

    navigator.geolocation.getCurrentPosition(
      async (position) => {
        if (requestId !== state.requestSeq) return;
        const { latitude, longitude } = position.coords;
        setStatus(`Your location: (${latitude.toFixed(5)}, ${longitude.toFixed(5)})`);

        initMapIfNeeded(latitude, longitude);

        try {
          const shopsData = await getVetShops(latitude, longitude);
          if (requestId !== state.requestSeq) return;

          const features = shopsData?.features || [];
          renderShopCards(features);
          addMarkersToMap(features);
        } catch (err) {
          setError(err?.message || 'Failed to load nearby shops. Please try again.');
          els.resultCount.textContent = '0';
          els.shops.innerHTML = '';
          els.emptyState.hidden = false;
        } finally {
          if (requestId === state.requestSeq) setLoading(false);
        }
      },
      (err) => {
        if (requestId !== state.requestSeq) return;
        const message =
          err?.code === 1
            ? 'Location permission denied. Please allow location access and try again.'
            : 'Failed to access location. Please try again.';
        setError(message);
        setLoading(false);
      },
      { enableHighAccuracy: true, timeout: 12000, maximumAge: 15000 }
    );
  }

  function initDefaultMapView() {
    // Default world view until the user searches/chooses location.
    if (state.map) return;
    state.map = L.map('map').setView([20, 0], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(state.map);
  }

  // Events
  els.searchForm.addEventListener('submit', (e) => {
    e.preventDefault();
    handleSearchCity(els.citySearch.value);
  });

  els.locationBtn.addEventListener('click', () => handleUseLocation());

  // Init
  document.addEventListener('DOMContentLoaded', () => {
    initDefaultMapView();
    setStatus('Please search for a city or use your current location.');
    setError('');
    els.resultCount.textContent = '0';
    els.emptyState.hidden = false;
  });
})();

