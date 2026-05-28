<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMDA Road Incident Map</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <link class="jsbin" rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link class="jsbin" rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>

    <style>
        #map { height: 620px; width: 100%; border-radius: 12px; }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased">

    <div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        <header class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Metro Manila Traffic Incidents</h1>
                <p class="text-sm text-slate-500 mt-1">MMDA Road Incident Spatial Mapping Dashboard</p>
            </div>
            
            <div class="flex items-center gap-3 bg-white p-2 rounded-xl shadow-sm border border-slate-200">
                <label for="typeFilter" class="text-sm font-semibold text-slate-700 pl-2">Filter Category:</label>
                <select id="typeFilter" class="text-sm border-0 rounded-lg p-2 bg-slate-50 font-medium text-slate-800 shadow-inner focus:ring-2 focus:ring-indigo-500 focus:outline-none min-w-[220px]">
                    <option value="">All Incidents</option>
                    @foreach($types as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>
        </header>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 flex flex-col">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Incidents</span>
                <span id="stat-total" class="text-2xl font-black text-slate-800 mt-1">0</span>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 flex flex-col border-l-4 border-l-red-500">
                <span class="text-xs font-bold uppercase tracking-wider text-red-500">Accidents</span>
                <span id="stat-accidents" class="text-2xl font-black text-slate-800 mt-1">0</span>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 flex flex-col border-l-4 border-l-amber-500">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-500">Stalled Vehicles</span>
                <span id="stat-stalled" class="text-2xl font-black text-slate-800 mt-1">0</span>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 flex flex-col border-l-4 border-l-blue-500">
                <span class="text-xs font-bold uppercase tracking-wider text-blue-500">Roadworks & Other</span>
                <span id="stat-other" class="text-2xl font-black text-slate-800 mt-1">0</span>
            </div>
        </div>

        <div class="relative bg-white p-2 rounded-2xl shadow-sm border border-slate-200">
            <div id="map" class="z-10"></div>
            
            <div id="loading" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm rounded-2xl z-[1000] flex items-center justify-center">
                <div class="bg-white px-6 py-4 rounded-xl shadow-xl flex items-center gap-3 font-semibold text-slate-700">
                    <svg class="animate-spin h-5 w-5 text-indigo-600" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Parsing map hotspots...</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Maps clean database groups to distinct custom map pin colors
        function getMarkerColor(type) {
            if (!type) return '#64748b';
            const normalized = type.trim();
            if (normalized === 'Vehicular Accident') return '#ef4444'; 
            if (normalized === 'Stalled Vehicle') return '#f59e0b';    
            if (normalized === 'Roadwork & Obstruction') return '#3b82f6'; 
            return '#64748b'; 
        }

        const map = L.map('map').setView([14.5826, 121.0515], 11);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        let markersGroup = L.markerClusterGroup();
        map.addLayer(markersGroup);

        function loadMapData(typeFilterValue = "") {
            document.getElementById('loading').style.display = 'flex';

            let url = '/api/incidents';
            if (typeFilterValue && typeFilterValue.trim() !== "") {
                url += '?type=' + encodeURIComponent(typeFilterValue.trim());
            }

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    renderMarkers(data);
                    document.getElementById('loading').style.display = 'none';
                })
                .catch(err => {
                    console.error("API Data Request Failed:", err);
                    document.getElementById('loading').innerText = "Endpoint Connection Error.";
                });
        }

        // Initialize App Dashboard Load
        loadMapData("");

        function renderMarkers(incidents) {
            markersGroup.clearLayers(); 

            let counts = { 'total': incidents.length, 'accident': 0, 'stalled': 0, 'other': 0 };

            incidents.forEach(incident => {
                const lat = parseFloat(incident.latitude);
                const lng = parseFloat(incident.longitude);
                
                if (isNaN(lat) || isNaN(lng)) return;

                const type = incident.incident_type ? incident.incident_type.trim() : 'Other';
                const color = getMarkerColor(type);
                
                if (type === 'Vehicular Accident') counts.accident++;
                else if (type === 'Stalled Vehicle') counts.stalled++;
                else counts.other++;

                const customIcon = L.divIcon({
                    html: `<div style="background-color: ${color}; width: 14px; height: 14px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.3);"></div>`,
                    className: 'custom-pin',
                    iconSize: [14, 14]
                });

                const marker = L.marker([lat, lng], { icon: customIcon });

                marker.bindPopup(`
                    <div class="font-sans min-w-[180px]">
                        <strong style="color: ${color}">${type}</strong>
                        <p class="text-xs bg-slate-100 p-1.5 my-1 rounded text-slate-700 font-medium">${incident.description || 'No description'}</p>
                        <div class="text-[11px] text-gray-500 space-y-0.5">
                            <div>📍 <b>Location:</b> ${incident.location || 'Unknown'}</div>
                            <div>🕒 <b>Time:</b> ${incident.occurred_at || 'N/A'}</div>
                        </div>
                    </div>
                `);
                
                markersGroup.addLayer(marker);
            });

            document.getElementById('stat-total').innerText = counts.total.toLocaleString();
            document.getElementById('stat-accidents').innerText = counts.accident.toLocaleString();
            document.getElementById('stat-stalled').innerText = counts.stalled.toLocaleString();
            document.getElementById('stat-other').innerText = counts.other.toLocaleString();
        }

        document.getElementById('typeFilter').addEventListener('change', function(e) {
            loadMapData(e.target.value); 
        });
    </script>
</body>
</html>