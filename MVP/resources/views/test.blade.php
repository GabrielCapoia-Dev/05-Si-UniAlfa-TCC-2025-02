<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Rota Escolar em Umuarama</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
    <style>
        #map {
            height: 600px;
        }
    </style>
</head>

<body>
    <h1>Rota Escolar em Umuarama (via OSRM)</h1>
    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
    <script>
        const map = L.map('map').setView([-23.7666, -53.3121], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Ícones customizados
        const icons = {
            parada: L.icon({
                iconUrl: '/images/parada.png',
                iconSize: [32, 32],
                iconAnchor: [16, 32],
                popupAnchor: [0, -32]
            }),
            escola: L.icon({
                iconUrl: '/images/escola.png',
                iconSize: [32, 32],
                iconAnchor: [16, 32],
                popupAnchor: [0, -32]
            }),
            aluno: L.icon({
                iconUrl: '/images/aluno.png',
                iconSize: [24, 24],
                iconAnchor: [12, 24],
                popupAnchor: [0, -24]
            })
        };

        // Pontos principais: 6 paradas + 2 escolas
        const pontosPrincipais = [{
                lat: -23.764870,
                lng: -53.313674,
                type: 'parada',
                label: 'Ponto A'
            },
            {
                lat: -23.765900,
                lng: -53.309500,
                type: 'parada',
                label: 'Ponto B'
            },
            {
                lat: -23.769200,
                lng: -53.310800,
                type: 'parada',
                label: 'Ponto C'
            },
            {
                lat: -23.770500,
                lng: -53.314000,
                type: 'parada',
                label: 'Ponto D'
            },
            {
                lat: -23.767800,
                lng: -53.318000,
                type: 'parada',
                label: 'Ponto E'
            },
            {
                lat: -23.764200,
                lng: -53.317000,
                type: 'parada',
                label: 'Ponto F'
            },

            {
                lat: -23.766900,
                lng: -53.307500,
                type: 'escola',
                label: 'Escola Central'
            },
            {
                lat: -23.764000,
                lng: -53.318200,
                type: 'escola',
                label: 'Escola Municipal'
            }
        ];

        // Gera alunos próximos de cada ponto de parada (~100m)
        const alunos = [];
        pontosPrincipais.forEach(p => {
            if (p.type === 'parada') {
                alunos.push({
                    lat: p.lat + 0.001,
                    lng: p.lng + 0.0005,
                    type: 'aluno',
                    label: `Aluno próximo de ${p.label}`
                }, {
                    lat: p.lat - 0.001,
                    lng: p.lng - 0.0004,
                    type: 'aluno',
                    label: `Aluno próximo de ${p.label}`
                }, {
                    lat: p.lat + 0.0006,
                    lng: p.lng - 0.0007,
                    type: 'aluno',
                    label: `Aluno próximo de ${p.label}`
                });
            }
        });

        const stops = [...pontosPrincipais, ...alunos];

        // Adiciona marcadores
        stops.forEach(stop => {
            L.marker([stop.lat, stop.lng], {
                    icon: icons[stop.type]
                })
                .addTo(map)
                .bindPopup(stop.label);
        });

        // Define a ordem da rota: pontos + escolas no meio
        const waypoints = [
            L.latLng(pontosPrincipais[0].lat, pontosPrincipais[0].lng), // Ponto A
            L.latLng(pontosPrincipais[1].lat, pontosPrincipais[1].lng), // Ponto B
            L.latLng(pontosPrincipais[6].lat, pontosPrincipais[6].lng), // Escola Central
            L.latLng(pontosPrincipais[2].lat, pontosPrincipais[2].lng), // Ponto C
            L.latLng(pontosPrincipais[3].lat, pontosPrincipais[3].lng), // Ponto D
            L.latLng(pontosPrincipais[4].lat, pontosPrincipais[4].lng), // Ponto E
            L.latLng(pontosPrincipais[5].lat, pontosPrincipais[5].lng), // Ponto F
            L.latLng(pontosPrincipais[7].lat, pontosPrincipais[7].lng) // Escola Municipal
        ];

        // Rota OSRM
        L.Routing.control({
            waypoints: waypoints,
            router: L.Routing.osrmv1({
                serviceUrl: 'https://router.project-osrm.org/route/v1'
            }),
            lineOptions: {
                styles: [{
                    color: 'blue',
                    opacity: 0.7,
                    weight: 5
                }]
            },
            show: false,
            addWaypoints: false,
            draggableWaypoints: false,
            fitSelectedRoutes: true,
            createMarker: function() {
                return null;
            }
        }).addTo(map);
    </script>
</body>

</html>