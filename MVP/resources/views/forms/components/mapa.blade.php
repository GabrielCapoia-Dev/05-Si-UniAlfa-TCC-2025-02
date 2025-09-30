<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div 
        x-data="{
            pontosDeParada: @entangle($getStatePath()),
            map: null,
            markers: [],
            addMode: false,
            paradaIcon: null,
            
            init() {
                this.map = L.map(this.$refs.mapContainer).setView([-23.7666, -53.3121], 13);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(this.map);
                
                this.paradaIcon = L.icon({
                    iconUrl: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48Y2lyY2xlIGN4PSIxNiIgY3k9IjE2IiByPSIxNCIgZmlsbD0iIzIxOTZGMyIgc3Ryb2tlPSJ3aGl0ZSIgc3Ryb2tlLXdpZHRoPSIyIi8+PHRleHQgeD0iMTYiIHk9IjIxIiBmb250LXNpemU9IjE2IiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC13ZWlnaHQ9ImJvbGQiPlA8L3RleHQ+PC9zdmc+',
                    iconSize: [32, 32],
                    iconAnchor: [16, 32],
                    popupAnchor: [0, -32]
                });
                
                this.carregarPontos();
                
                this.map.on('click', (e) => {
                    if (this.addMode) {
                        this.adicionarPonto(e.latlng.lat, e.latlng.lng);
                    }
                });
            },
            
            toggleAddMode() {
                this.addMode = !this.addMode;
                this.$refs.mapContainer.style.cursor = this.addMode ? 'crosshair' : '';
            },
            
            adicionarPonto(lat, lng) {
                if (!this.pontosDeParada) {
                    this.pontosDeParada = [];
                }
                
                const novoPonto = {
                    latitude: lat,
                    longitude: lng,
                    ordem: this.pontosDeParada.length + 1
                };
                
                this.pontosDeParada.push(novoPonto);
                this.renderizarMarcadores();
            },
            
            removerPonto(index) {
                this.pontosDeParada.splice(index, 1);
                this.pontosDeParada.forEach((ponto, i) => {
                    ponto.ordem = i + 1;
                });
                this.renderizarMarcadores();
            },
            
            carregarPontos() {
                if (this.pontosDeParada && this.pontosDeParada.length > 0) {
                    this.renderizarMarcadores();
                }
            },
            
            renderizarMarcadores() {
                this.markers.forEach(marker => this.map.removeLayer(marker));
                this.markers = [];
                
                if (this.pontosDeParada) {
                    this.pontosDeParada.forEach((ponto, index) => {
                        const marker = L.marker([ponto.latitude, ponto.longitude], {
                            icon: this.paradaIcon
                        }).addTo(this.map);
                        
                        const popupContent = `
                            <div style='min-width: 150px;'>
                                <b>Ponto ${ponto.ordem}</b><br>
                                <small>Lat: ${ponto.latitude.toFixed(6)}<br>
                                Lng: ${ponto.longitude.toFixed(6)}</small><br>
                                <button 
                                    onclick='document.dispatchEvent(new CustomEvent()'
                                    style='margin-top: 8px; padding: 4px 8px; background: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer; width: 100%;'
                                >
                                    Remover
                                </button>
                            </div>
                        `;
                        
                        marker.bindPopup(popupContent);
                        this.markers.push(marker);
                    });
                    
                    if (this.pontosDeParada.length > 0) {
                        const bounds = L.latLngBounds(
                            this.pontosDeParada.map(p => [p.latitude, p.longitude])
                        );
                        this.map.fitBounds(bounds, { padding: [50, 50] });
                    }
                }
            }
        }"
        x-on:remover-ponto.document="removerPonto($event.detail)"
        class="relative"
    >
        <!-- Controles -->
        <div class="mb-4 flex gap-2">
            <button 
                type="button"
                x-on:click="toggleAddMode()"
                x-bind:class="addMode ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'"
                class="px-4 py-2 border border-gray-300 rounded-lg shadow-sm hover:bg-blue-50 transition-colors flex items-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </button>
            
            <button 
                type="button"
                x-on:click="pontosDeParada = []; renderizarMarcadores()"
                x-show="pontosDeParada && pontosDeParada.length > 0"
                class="px-4 py-2 bg-red-600 text-white rounded-lg shadow-sm hover:bg-red-700 transition-colors"
            >
                Limpar Todos
            </button>
        </div>

        <!-- Mapa -->
        <div 
            x-ref="mapContainer"
            style="height: 500px; border-radius: 8px; border: 2px solid #e5e7eb;"
        ></div>

        <!-- Lista de Pontos -->
        <div x-show="pontosDeParada && pontosDeParada.length > 0" class="mt-4">
            <h3 class="text-sm font-medium text-gray-700 mb-2">
                Pontos de Parada (<span x-text="pontosDeParada ? pontosDeParada.length : 0"></span>)
            </h3>
            <div class="space-y-2 max-h-60 overflow-y-auto">
                <template x-for="(ponto, index) in pontosDeParada" :key="index">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex-1">
                            <span class="font-medium text-gray-700">Ponto <span x-text="ponto.ordem"></span></span>
                            <div class="text-xs text-gray-500">
                                Lat: <span x-text="ponto.latitude.toFixed(6)"></span>, 
                                Lng: <span x-text="ponto.longitude.toFixed(6)"></span>
                            </div>
                        </div>
                        <button 
                            type="button"
                            x-on:click="removerPonto(index)"
                            class="px-3 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-600 transition-colors"
                        >
                            Remover
                        </button>
                    </div>
                </template>
            </div>
        </div>

        @once
            @push('scripts')
                <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            @endpush
            @push('styles')
                <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                <style>
                    .leaflet-container {
                        font-family: inherit;
                    }
                </style>
            @endpush
        @endonce
    </div>
</x-dynamic-component>