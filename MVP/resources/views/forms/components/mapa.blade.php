<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="window.MapaRota({
        pontos: @entangle($getStatePath()).live,
        statePath: @js($getStatePath()),
        center: [-23.7666, -53.3121],
        zoom: 13,
        addMode: true,
        rotaAtiva: {{ ($getExtraAttributes()['rota-ativa'] ?? '1') === '1' ? 'true' : 'false' }},
        raioEscola: {{ (int) ($getExtraAttributes()['raio-escola'] ?? 2000) }},
        raioPonto: {{ (int) ($getExtraAttributes()['raio-ponto'] ?? 500) }},
    })" x-init="init()" class="relative" wire:ignore data-mapa-ctrl>
        <div x-ref="mapContainer" class="map-container"></div>
    </div>

    @assets
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

        <script>
            window.addEventListener('pontos-updated', () => {
                document.querySelectorAll('[data-mapa-ctrl]').forEach(el => {
                    const ctrl = el._mapa_ctrl;
                    if (!ctrl) return;
                    ctrl.renderizarMarcadores();
                    if (ctrl.rotaAtiva) ctrl.calcularRota();
                    ctrl.map?.invalidateSize(false);
                });
            });

            document.addEventListener('livewire:load', () => {
                if (window.Livewire?.hook) {
                    Livewire.hook('message.processed', () => {
                        document.querySelectorAll('[data-mapa-ctrl]').forEach(el => {
                            const ctrl = el._mapa_ctrl;
                            if (!ctrl) return;
                            ctrl.renderizarMarcadores();
                            if (ctrl.rotaAtiva) ctrl.calcularRota();
                            ctrl.map?.invalidateSize(false);
                        });
                    });
                }
            });
        </script>

        <script>
            window.MapaRota = window.MapaRota || function(opts = {}) {
                return {
                    pontos: opts.pontos ?? [],
                    statePath: opts.statePath ?? null,
                    zoom: opts.zoom ?? 13,
                    center: opts.center ?? [-23.7666, -53.3121],
                    addMode: opts.addMode ?? true,
                    rotaAtiva: opts.rotaAtiva ?? true,
                    raioEscola: opts.raioEscola ?? 2000,
                    raioPonto: opts.raioPonto ?? 500,

                    map: null,
                    markers: [],
                    rotaLayer: null,
                    circlesGroup: null,

                    // controle de requisição
                    _routeReqId: 0,
                    _routeAbort: null,
                    routeGroup: null,

                    sync() {
                        if (this.$wire && this.statePath) {
                            this.$wire.set(this.statePath, this.pontos);
                        }
                    },

                    init() {
                        if (this.map) return;

                        this.$el._mapa_ctrl = this;

                        this.map = L.map(this.$refs.mapContainer, {
                                zoomControl: true
                            })
                            .setView(this.center, this.zoom);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors'
                        }).addTo(this.map);

                        this.$refs.mapContainer._leaflet_map = this.map;
                        this.$nextTick(() => setTimeout(() => this.map.invalidateSize(), 300));

                        this.routeGroup = L.layerGroup().addTo(this.map);
                        this.rotaLayer = null;

                        this.circlesGroup = L.layerGroup().addTo(this.map);

                        // click para adicionar ponto
                        this.map.on('click', (e) => {
                            if (!this.addMode) return;
                            if (!this.rotaAtiva) {
                                this.pontos = [{
                                    latitude: e.latlng.lat,
                                    longitude: e.latlng.lng,
                                    ordem: 1,
                                }];
                            } else {
                                this.adicionarPonto(e.latlng.lat, e.latlng.lng);
                            }
                            this.sync?.();
                        });

                        this.$watch('pontos', () => {
                            this.renderizarMarcadores();
                            if (this.rotaAtiva) this.calcularRota();
                        }, {
                            deep: true
                        });

                        this.renderizarMarcadores();
                        if (this.rotaAtiva) this.calcularRota();
                    },

                    adicionarPonto(lat, lng) {
                        const novo = {
                            latitude: lat,
                            longitude: lng,
                            ordem: (this.pontos?.length ?? 0) + 1
                        };
                        this.pontos = [...(this.pontos ?? []), novo];
                    },

                    removerPonto(index) {
                        const arr = [...(this.pontos ?? [])];
                        arr.splice(index, 1);
                        arr.forEach((p, i) => p.ordem = i + 1);
                        this.pontos = arr;
                        this.sync();
                    },

                    renderizarMarcadores() {
                        this.markers.forEach(m => this.map.removeLayer(m));
                        this.markers = [];

                        this.circlesGroup?.clearLayers();

                        if (!this.pontos || this.pontos.length === 0) return;

                        this.pontos.forEach((ponto, i) => {
                            if (this.rotaAtiva === false) {
                                ponto.tipo = 'escola';
                            }
                            const isEscola = (ponto.tipo === 'escola');
                            const color = isEscola ? '#10b981' : '#1E88E5';
                            const icon = makeNumberedIcon(ponto.ordem ?? (i + 1), {
                                fill: color
                            });

                            const marker = L.marker([ponto.latitude, ponto.longitude], {
                                draggable: !isEscola,
                                icon,
                            }).addTo(this.map);

                            if (!isEscola) {
                                marker.on('click', () => this.removerPonto(i));
                                marker.on('dragend', (e) => {
                                    const pos = e.target.getLatLng();
                                    const arr = [...this.pontos];
                                    arr[i] = {
                                        ...arr[i],
                                        latitude: pos.lat,
                                        longitude: pos.lng
                                    };
                                    this.pontos = arr;
                                    this.sync?.();
                                });
                            }

                            marker.bindPopup(`
          <div style="min-width:150px;">
          <b>${ponto.rotulo ? ponto.rotulo : `Ponto ${ponto.ordem ?? (i + 1)}`}</b><br>
            <small>Lat: ${Number(ponto.latitude).toFixed(6)}<br>Lng: ${Number(ponto.longitude).toFixed(6)}</small>
          </div>
        `);

                            this.markers.push(marker);

                            const raio = Number(ponto.raio ?? (isEscola ? this.raioEscola : this.raioPonto));
                            if (!Number.isNaN(raio) && raio > 0) {
                                const circle = L.circle([ponto.latitude, ponto.longitude], {
                                    radius: raio,
                                    color: color,
                                    fillColor: color,
                                    fillOpacity: isEscola ? 0.12 : 0.15,
                                    weight: 2,
                                });
                                circle.addTo(this.circlesGroup);
                            }
                        });


                        if (this.pontos.length === 1) {
                            this.map.setView([this.pontos[0].latitude, this.pontos[0].longitude], this.zoom);
                        }
                    },

                    async calcularRota() {
                        if (!this.pontos || this.pontos.length < 2) {
                            if (this._routeAbort) {
                                try {
                                    this._routeAbort.abort();
                                } catch {}
                            }
                            this.routeGroup?.clearLayers();
                            this.rotaLayer = null;

                            if (this.$wire && typeof this.$wire.call === 'function') {
                                try {
                                    this.$wire.call('processarRota', {
                                        distance: 0,
                                        duration: 0,
                                        geometry: null,
                                        waypoints: [],
                                        legs: [],
                                    });
                                } catch (e) {
                                    console.error(e);
                                }
                            }
                            return;
                        }

                        const coords = this.pontos.map(p => `${p.longitude},${p.latitude}`).join(';');

                        if (this._routeAbort && typeof this._routeAbort.abort === 'function') {
                            try {
                                this._routeAbort.abort();
                            } catch {}
                        }
                        this._routeAbort = ('AbortController' in window) ? new AbortController() : null;
                        const reqId = ++this._routeReqId;

                        try {
                            const url =
                                `https://router.project-osrm.org/route/v1/driving/${coords}?overview=full&geometries=geojson&overview=full&steps=true`;
                            const res = await fetch(url, this._routeAbort ? {
                                signal: this._routeAbort.signal
                            } : undefined);

                            // Se outra requisição já começou depois desta, ignora a resposta
                            if (reqId !== this._routeReqId) return;

                            const data = await res.json();
                            if (!data.routes || data.routes.length === 0) {
                                this.routeGroup?.clearLayers();
                                this.rotaLayer = null;
                                return;
                            }

                            const route = data.routes[0];

                            // limpa rota anterior
                            this.routeGroup?.clearLayers();

                            // desenha nova rota
                            this.rotaLayer = L.geoJSON(route.geometry, {
                                style: {
                                    color: '#10b981',
                                    weight: 5,
                                    opacity: 0.7
                                },
                            }).addTo(this.routeGroup);

                            // calc bounds (SW/NE)
                            const tmpLayer = L.geoJSON(route.geometry);
                            const bounds = tmpLayer.getBounds();
                            const boundsPayload = {
                                southWest: {
                                    lat: bounds.getSouthWest().lat,
                                    lng: bounds.getSouthWest().lng
                                },
                                northEast: {
                                    lat: bounds.getNorthEast().lat,
                                    lng: bounds.getNorthEast().lng
                                },
                            };

                            // prepare payload completo — contém tudo que você pediu
                            const payload = {
                                distance: route.distance ?? route.summary?.totalDistance ?? null, // metros
                                duration: route.duration ?? route.summary?.totalTime ?? null, // segundos
                                legs: route.legs ?? [],
                                geometry: route.geometry ?? null,
                                weight_name: route.weight_name ?? null,
                                waypoints: (data.waypoints ?? []).map(wp => ({
                                    name: wp.name,
                                    location: wp.location
                                })),
                                bounds: boundsPayload,
                                total_points: (this.pontos?.length ?? 0),
                                original_response: route, // cuidado: pode ser grande, mas foi pedido "tudo"
                                requested_at: (new Date()).toISOString(),
                            };

                            // Envia para o Livewire pai para que o backend processe (AX -> modalidade AY)
                            // O método "processarResumoDaRota" precisa existir no componente Livewire pai.
                            if (this.$wire && typeof this.$wire.call === 'function') {
                                try {
                                    this.$wire.call('processarRota', payload)

                                } catch (err) {
                                    // se falhar, apenas loga — não trava a UI
                                    console.error('Falha ao chamar Livewire.processarResumoDaRota:', err);
                                }
                            }

                        } catch (err) {
                            if (!(err && err.name === 'AbortError')) {
                                console.error('Erro ao calcular rota:', err);
                            }
                        }
                    }

                }
            };
        </script>


        <script>
            // --- helper: cria um pin SVG com número ---
            function makeNumberedIcon(n, {
                fill = '#1E88E5',
                textColor = '#000'
            } = {}) {
                const label = (n ?? '').toString();
                const fontSize =
                    label.length <= 1 ? 14 :
                    label.length === 2 ? 12 : 10;

                const svg = `
      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="48" viewBox="0 0 32 48">
        <!-- gota do pin -->
        <path d="M16 0c-8.837 0-16 7.163-16 16 0 11.046 16 32 16 32s16-20.954 16-32C32 7.163 24.837 0 16 0z"
              fill="${fill}"/>
        <circle cx="16" cy="16" r="10" fill="#fff"/>
        <text x="16" y="20" text-anchor="middle"
              font-family="Inter, Arial, sans-serif"
              font-weight="700"
              font-size="${fontSize}"
              fill="${textColor}">${label}</text>
      </svg>`.trim();

                return L.icon({
                    iconUrl: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
                    iconSize: [32, 48],
                    iconAnchor: [16, 48],
                    popupAnchor: [0, -42],
                });
            }
        </script>


        <style>
            .map-container {
                height: 400px;
                border-radius: 8px;
                border: 1px solid #ccc;
                position: relative;
                z-index: 1;
            }

            .leaflet-container {
                z-index: 0 !important;
            }
        </style>
    @endassets
</x-dynamic-component>
