<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste do Leaflet</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <style>
    #map {
        height: 400px;
        width: 400px;
    }

    /* altura obrigatória */
    </style>
</head>

<body>
    <h1>Teste do mapa</h1>
    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
    // Inicializa o mapa
    const map = L.map('map').setView([-23.7637, -53.2967], 14);

    // Adiciona tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: ''
    }).addTo(map);

    // Adiciona marcador
    L.marker([-23.7637, -53.2967]).addTo(map)
        .bindPopup('Ponto de parada')
        .openPopup();
    </script>
</body>

</html>