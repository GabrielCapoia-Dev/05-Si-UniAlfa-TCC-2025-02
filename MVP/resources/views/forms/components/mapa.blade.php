<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{
        pontos: @entangle($getStatePath()),
        map: null,
        markers: [],
        rotaLayer: null,
        addMode: true,
    
        init() {
            this.map = L.map(this.$refs.mapContainer, {
                zoomControl: true,
            }).setView([-23.7666, -53.3121], 13);
    
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(this.map);
    
            // clique no mapa → adiciona ponto
            this.map.on('click', (e) => {
                if (this.addMode) {
                    this.adicionarPonto(e.latlng.lat, e.latlng.lng);
                }
            });
    
            this.renderizarMarcadores();
        },
    
        adicionarPonto(lat, lng) {
            const novoPonto = {
                latitude: lat,
                longitude: lng,
                ordem: this.pontos.length + 1
            };
    
            this.pontos.push(novoPonto);
            this.renderizarMarcadores();
            this.calcularRota();
        },
    
        removerPonto(index) {
            this.pontos.splice(index, 1);
            this.pontos.forEach((ponto, i) => ponto.ordem = i + 1);
            this.renderizarMarcadores();
            this.calcularRota();
        },
    
        renderizarMarcadores() {
            // limpa marcadores antigos
            this.markers.forEach(marker => this.map.removeLayer(marker));
            this.markers = [];
    
            if (this.pontos && this.pontos.length > 0) {
                this.pontos.forEach((ponto, index) => {
                    const marker = L.marker([ponto.latitude, ponto.longitude], {
                        draggable: true
                    }).addTo(this.map);
    
                    // clique → remove
                    marker.on('click', () => this.removerPonto(index));
    
                    // arrastar → atualiza coordenadas
                    marker.on('dragend', (e) => {
                        const pos = e.target.getLatLng();
                        this.pontos[index].latitude = pos.lat;
                        this.pontos[index].longitude = pos.lng;
                        this.calcularRota();
                    });
    
                    marker.bindPopup(`
                                <div style='min-width: 150px;'>
                                    <b>Ponto ${ponto.ordem}</b><br>
                                    <small>
                                        Lat: ${ponto.latitude.toFixed(6)}<br>
                                        Lng: ${ponto.longitude.toFixed(6)}
                                    </small><br>
                                    <em>(Clique para remover, arraste para mover)</em>
                                </div>
                            `);
    
                    this.markers.push(marker);
                });
    
                // centraliza no primeiro ponto apenas
                if (this.pontos.length === 1) {
                    this.map.setView([this.pontos[0].latitude, this.pontos[0].longitude], 13);
                }
            }
        },
    
        calcularRota() {
            if (this.pontos.length < 2) {
                if (this.rotaLayer) {
                    this.map.removeLayer(this.rotaLayer);
                    this.rotaLayer = null;
                }
                return;
            }
    
            const coords = this.pontos.map(p => `${p.longitude},${p.latitude}`).join(';');
    
            fetch(`https://router.project-osrm.org/route/v1/driving/${coords}?overview=full&geometries=geojson`)
                .then(res => res.json())
                .then(data => {
                    if (data.routes && data.routes.length > 0) {
                        const route = data.routes[0];
    
                        if (this.rotaLayer) {
                            this.map.removeLayer(this.rotaLayer);
                        }
    
                        this.rotaLayer = L.geoJSON(route.geometry, {
                            style: {
                                color: '#ff3c00',
                                weight: 5,
                                opacity: 0.7
                            }
                        }).addTo(this.map);
    
                        console.log(route);
                        console.log('Distância:', (route.distance / 1000).toFixed(2), 'km');
                        console.log('Tempo:', Math.round(route.duration / 60), 'min');
                    }
                })
                .catch(err => console.error('Erro ao calcular rota:', err));
        }
    }" x-effect="renderizarMarcadores()" class="relative">
        <div x-ref="mapContainer" class="map-container"></div>
    </div>

    @once
        @push('scripts')
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        @endpush
        @push('styles')
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            <style>
                .map-container {
                    height: 500px;
                    border-radius: 8px;
                    border: 1px solid #ccc;
                    position: relative;
                    z-index: 1;
                    /* 🔹 mantém o mapa abaixo de outros componentes */
                }

                .leaflet-container {
                    z-index: 0 !important;
                    /* 🔹 impede que o Leaflet sobrescreva */
                }
            </style>
        @endpush
    @endonce
</x-dynamic-component>
