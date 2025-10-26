<x-filament::page>
    <div class="grid grid-cols-1 gap-4 items-start {{ $rotaSelecionada ? 'lg:grid-60-40' : 'lg:grid-cols-1' }}">
        {{-- Tabela (100% sem mapa | 30% com mapa) --}}
        <div class="order-1 {{ $rotaSelecionada ? '' : 'col-span-full' }}">
            {{ $this->table }}
        </div>

        {{-- Painel lateral (70% / mapa à direita) --}}
        @if ($rotaSelecionada)
            <div class="order-2">
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">

                    {{-- Botão X --}}
                    <button wire:click="fecharDetalhesRota"
                        class="absolute top-3 right-3 z-10 inline-flex items-center justify-center rounded-full p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>

                    {{-- Header --}}
                    <div
                        class="bg-gradient-to-r from-emerald-500 to-emerald-600 p-4 text-white text-center rounded-t-lg">
                        <h2 class="text-lg font-semibold">{{ $rotaSelecionada->nome }}</h2>
                    </div>

                    {{-- Conteúdo --}}
                    <div class="p-6 space-y-4">
                        {{-- MAPA NO PAINEL (DESKTOP) --}}
                        <div wire:key="mapa-desktop-{{ $rotaSelecionada->id }}" x-data="window.MapaRotaVisor({
                            pontos: @js($rotaPontos),
                            center: @js(!empty($rotaPontos) ? [$rotaPontos[0]['latitude'], $rotaPontos[0]['longitude']] : [-23.7666, -53.3121]),
                            zoom: 13,
                            rotaAtiva: true,
                            raioEscola: 2000,
                            raioPonto: 500
                        })"
                            x-init="init()" class="relative" wire:ignore data-rota-visor>
                            <div x-ref="mapContainer" class="map-container"></div>
                        </div>

                        {{-- RESUMO DA ROTA – GRID 3 COLUNAS --}}
                        <div class="mt-6 border-t pt-5">
                            @php
                                $dist = $rotaSelecionada->distancia_total;
                                $tempoMin = (int) ($rotaSelecionada->tempo_estimado ?? 0);
                                $hh = intdiv($tempoMin, 60);
                                $mm = $tempoMin % 60;
                                $tempoFmt = $tempoMin ? ($hh ? "{$hh}h {$mm}min" : "{$mm}min") : '—';
                                $valorKm = $rotaSelecionada->valor_por_km;
                                $valorTotal = $rotaSelecionada->valor_total;
                                $qtdPontos = is_array($rotaPontos ?? null)
                                    ? count($rotaPontos)
                                    : $rotaSelecionada->pontosDeParada->count() ?? null;
                            @endphp

                            <h3
                                class="text-sm font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide mb-4">
                                Resumo da rota
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

                                {{-- Coluna 1 --}}
                                <div class="rounded-lg border p-4 bg-white/50 dark:bg-gray-900/40">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Turno</div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $rotaSelecionada->turno ?? '—' }}
                                    </div>
                                </div>

                                <div class="rounded-lg border p-4 bg-white/50 dark:bg-gray-900/40">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Distância total</div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ is_null($dist) ? '—' : number_format($dist, 2, ',', '.') . ' km' }}
                                    </div>
                                </div>

                                <div class="rounded-lg border p-4 bg-white/50 dark:bg-gray-900/40">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Tempo estimado</div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $tempoFmt }}
                                    </div>
                                </div>

                                {{-- Coluna 2 --}}
                                <div class="rounded-lg border p-4 bg-white/50 dark:bg-gray-900/40">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Valor por km</div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ is_null($valorKm) ? '—' : 'R$ ' . number_format($valorKm, 2, ',', '.') }}
                                    </div>
                                </div>

                                <div class="rounded-lg border p-4 bg-white/50 dark:bg-gray-900/40">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Valor total</div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ is_null($valorTotal) ? '—' : 'R$ ' . number_format($valorTotal, 2, ',', '.') }}
                                    </div>
                                </div>

                                <div class="rounded-lg border p-4 bg-white/50 dark:bg-gray-900/40">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Nº de pontos</div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $qtdPontos ?? '—' }}
                                    </div>
                                </div>


                            </div>

                            {{-- Escolas vinculadas (card normal) --}}
                            <div style="margin-top:1rem" class="rounded-lg border p-4 bg-white/50 dark:bg-gray-900/40">
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">Escolas vinculadas</div>
                                <div class="flex flex-wrap gap-2">
                                    @forelse(($rotaSelecionada->escolas ?? collect()) as $escola)
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                                   bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
                                            {{ $escola->nome }}
                                        </span>
                                    @empty
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Nenhuma escola
                                            vinculada.</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        @endif

        {{-- Modal (MOBILE/TABLET) --}}
        @if ($rotaSelecionada)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 lg:hidden" wire:ignore.self>
                {{-- Backdrop --}}
                <div class="fixed inset-0 bg-black/50 transition-opacity" wire:click="fecharDetalhesRota"></div>

                {{-- Modal --}}
                <div class="relative w-full max-w-md bg-white dark:bg-gray-900 rounded-xl shadow-xl overflow-hidden transform transition-all flex flex-col"
                    style="width: 95vw; max-width: 480px; max-height: 85vh;">

                    {{-- Header --}}
                    <div
                        class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex-shrink-0">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Detalhes da Rota</h3>
                        <button wire:click="fecharDetalhesRota"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <span class="sr-only">Fechar</span>
                            <x-heroicon-o-x-mark class="h-6 w-6" />
                        </button>
                    </div>

                    {{-- Body --}}
                    <div class="flex-1 p-4 overflow-y-auto space-y-4">
                        {{-- Nome da rota --}}
                        <div class="text-center">
                            <h4 class="text-base font-bold text-gray-900 dark:text-white">{{ $rotaSelecionada->nome }}
                            </h4>
                        </div>

                        {{-- MAPA (MOBILE) --}}
                        <div wire:key="mapa-mobile-{{ $rotaSelecionada->id }}" x-data="window.MapaRotaVisor({
                            pontos: @js($rotaPontos),
                            center: @js(!empty($rotaPontos) ? [$rotaPontos[0]['latitude'], $rotaPontos[0]['longitude']] : [-23.7666, -53.3121]),
                            zoom: 13,
                            rotaAtiva: true,
                            raioEscola: 2000,
                            raioPonto: 500
                        })"
                            x-init="init()" class="relative" wire:ignore data-rota-visor>
                            <div x-ref="mapContainer" class="map-container" style="height: 300px"></div>
                        </div>

                        {{-- Resumo da rota (mesmo conteúdo do desktop) --}}
                        @php
                            $dist = $rotaSelecionada->distancia_total;
                            $tempoMin = (int) ($rotaSelecionada->tempo_estimado ?? 0);
                            $hh = intdiv($tempoMin, 60);
                            $mm = $tempoMin % 60;
                            $tempoFmt = $tempoMin ? ($hh ? "{$hh}h {$mm}min" : "{$mm}min") : '—';
                            $valorKm = $rotaSelecionada->valor_por_km;
                            $valorTotal = $rotaSelecionada->valor_total;
                            $qtdPontos = is_array($rotaPontos ?? null)
                                ? count($rotaPontos)
                                : $rotaSelecionada->pontosDeParada->count() ?? null;
                        @endphp

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

                            {{-- Coluna 1 --}}
                            <div class="rounded-lg border p-4 bg-white/50 dark:bg-gray-900/40">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Turno</div>
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $rotaSelecionada->turno ?? '—' }}
                                </div>
                            </div>

                            <div class="rounded-lg border p-4 bg-white/50 dark:bg-gray-900/40">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Distância total</div>
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ is_null($dist) ? '—' : number_format($dist, 2, ',', '.') . ' km' }}
                                </div>
                            </div>

                            <div class="rounded-lg border p-4 bg-white/50 dark:bg-gray-900/40">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Tempo estimado</div>
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $tempoFmt }}
                                </div>
                            </div>

                            {{-- Coluna 2 --}}
                            <div class="rounded-lg border p-4 bg-white/50 dark:bg-gray-900/40">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Valor por km</div>
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ is_null($valorKm) ? '—' : 'R$ ' . number_format($valorKm, 2, ',', '.') }}
                                </div>
                            </div>

                            <div class="rounded-lg border p-4 bg-white/50 dark:bg-gray-900/40">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Valor total</div>
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ is_null($valorTotal) ? '—' : 'R$ ' . number_format($valorTotal, 2, ',', '.') }}
                                </div>
                            </div>

                            <div class="rounded-lg border p-4 bg-white/50 dark:bg-gray-900/40">
                                <div class="text-xs text-gray-500 dark:text-gray-400">Nº de pontos</div>
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $qtdPontos ?? '—' }}
                                </div>
                            </div>


                        </div>

                        {{-- Escolas vinculadas (card normal) --}}
                        <div style="margin-top:1rem" class="rounded-lg border p-4 bg-white/50 dark:bg-gray-900/40">
                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">Escolas vinculadas</div>
                            <div class="flex flex-wrap gap-2">
                                @forelse(($rotaSelecionada->escolas ?? collect()) as $escola)
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                                   bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
                                        {{ $escola->nome }}
                                    </span>
                                @empty
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Nenhuma escola
                                        vinculada.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div
                        class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex justify-end flex-shrink-0">
                        <button wire:click="fecharDetalhesRota"
                            class="inline-flex items-center justify-center gap-x-1 rounded-lg border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-900 shadow-sm transition-colors hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        @endif

    </div>



    @assets
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

        <script>
            window.MapaRotaVisor = window.MapaRotaVisor || function(opts = {}) {
                return {
                    pontos: opts.pontos ?? [],
                    center: opts.center ?? [-23.7666, -53.3121],
                    zoom: opts.zoom ?? 13,
                    rotaAtiva: opts.rotaAtiva ?? true,

                    raioEscola: opts.raioEscola ?? 2000,
                    raioPonto: opts.raioPonto ?? 500,

                    map: null,
                    markers: [],
                    routeGroup: null,
                    circlesGroup: null,

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

                        this.routeGroup = L.layerGroup().addTo(this.map);
                        this.circlesGroup = L.layerGroup().addTo(this.map);

                        this.$refs.mapContainer._leaflet_map = this.map;

                        this.renderizarMarcadores();
                        if (this.rotaAtiva) this.calcularRota();

                        this.$nextTick(() => setTimeout(() => this.map.invalidateSize(false), 200));
                    },

                    renderizarMarcadores() {

                        if (this.markers?.length) this.markers.forEach(m => this.map.removeLayer(m));
                        this.markers = [];


                        this.circlesGroup?.clearLayers();

                        if (!this.pontos?.length) return;

                        const bounds = [];

                        this.pontos.forEach((p, i) => {
                            const isEscola = p.tipo === 'escola';
                            const color = isEscola ? '#10b981' : '#1E88E5';
                            const icon = makeNumberedIcon(p.ordem ?? (i + 1), {
                                fill: color
                            });

                            const latlng = [p.latitude, p.longitude];

                            const marker = L.marker(latlng, {
                                icon
                            }).addTo(this.map);
                            marker.bindPopup(`
                        <div style="min-width:150px;">
                            <b>${p.rotulo ?? (isEscola ? 'Escola' : 'Ponto') + ' ' + (p.ordem ?? (i+1))}</b><br>
                            <small>Lat: ${Number(p.latitude).toFixed(6)}<br>Lng: ${Number(p.longitude).toFixed(6)}</small>
                        </div>
                        `);

                            this.markers.push(marker);
                            bounds.push(latlng);

                            const raio = Number(p.raio ?? (isEscola ? this.raioEscola : this.raioPonto));
                            if (!Number.isNaN(raio) && raio > 0) {
                                L.circle(latlng, {
                                    radius: raio,
                                    color,
                                    fillColor: color,
                                    fillOpacity: isEscola ? 0.12 : 0.15,
                                    weight: 2,
                                }).addTo(this.circlesGroup);
                            }
                        });

                        if (bounds.length >= 2) {
                            this.map.fitBounds(bounds, {
                                padding: [24, 24]
                            });
                        } else if (bounds.length === 1) {
                            this.map.setView(bounds[0], this.zoom);
                        }
                    },

                    async calcularRota() {
                        if (!this.pontos || this.pontos.length < 2) {
                            this.routeGroup?.clearLayers();
                            return;
                        }
                        const coords = this.pontos.map(p => `${p.longitude},${p.latitude}`).join(';');
                        try {
                            const url =
                                `https://router.project-osrm.org/route/v1/driving/${coords}?overview=full&geometries=geojson`;
                            const res = await fetch(url);
                            const data = await res.json();
                            if (!data?.routes?.length) return;

                            this.routeGroup?.clearLayers();
                            L.geoJSON(data.routes[0].geometry, {
                                style: {
                                    color: '#10b981',
                                    weight: 4,
                                    opacity: 0.8
                                }
                            }).addTo(this.routeGroup);
                        } catch (e) {
                            console.warn('OSRM falhou:', e);
                        }
                    },
                };
            };


            function makeNumberedIcon(n, {
                fill = '#1E88E5',
                textColor = '#000'
            } = {}) {
                const label = (n ?? '').toString();
                const fontSize = label.length <= 1 ? 14 : (label.length === 2 ? 12 : 10);
                const svg = `
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="48" viewBox="0 0 32 48">
                <path d="M16 0c-8.837 0-16 7.163-16 16 0 11.046 16 32 16 32s16-20.954 16-32C32 7.163 24.837 0 16 0z" fill="${fill}"/>
                <circle cx="16" cy="16" r="10" fill="#fff"/>
                <text x="16" y="20" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-weight="700" font-size="${fontSize}" fill="${textColor}">${label}</text>
            </svg>`.trim();

                return L.icon({
                    iconUrl: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
                    iconSize: [32, 48],
                    iconAnchor: [16, 48],
                    popupAnchor: [0, -42],
                });
            }

            document.addEventListener('livewire:load', () => {
                if (window.Livewire?.hook) {
                    Livewire.hook('message.processed', () => {
                        document.querySelectorAll('[data-rota-visor]').forEach(el => {
                            const ctrl = el._mapa_ctrl;
                            if (!ctrl?.map) return;
                            ctrl.renderizarMarcadores();
                            if (ctrl.rotaAtiva) ctrl.calcularRota();
                            ctrl.map.invalidateSize(false);
                        });
                    });
                }
            });
        </script>

        <style>
            @media (min-width: 1024px) {
                .lg\:grid-60-40 {
                    display: grid;
                    grid-template-columns: 60% 40% !important;
                }
            }

            .map-container {
                height: 380px;
                width: 100%;
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



</x-filament::page>
