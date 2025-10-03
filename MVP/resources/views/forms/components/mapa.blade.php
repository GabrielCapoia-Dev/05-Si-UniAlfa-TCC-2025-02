<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
  <div
    x-data="window.MapaRota({
      pontos: @entangle($getStatePath()).live,     // sincroniza em tempo real
      statePath: @js($getStatePath()),
      center: [-23.7666, -53.3121],
      zoom: 13,
      addMode: true,
      rotaAtiva: {{ ($getExtraAttributes()['rota-ativa'] ?? '1') === '1' ? 'true' : 'false' }},
    })"
    x-init="init()"
    class="relative"
    wire:ignore               <!-- 🔑 impede o Livewire de mexer nesse bloco -->
  >
    <div x-ref="mapContainer" class="map-container"></div>
  </div>

  @assets
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
      window.MapaRota = window.MapaRota || function (opts = {}) {
        return {
          pontos: opts.pontos ?? [],
          statePath: opts.statePath ?? null,
          zoom: opts.zoom ?? 13,
          center: opts.center ?? [-23.7666, -53.3121],
          addMode: opts.addMode ?? true,
          rotaAtiva: opts.rotaAtiva ?? true,

          map: null,
          markers: [],
          rotaLayer: null,

          sync() {
            if (this.$wire && this.statePath) {
              this.$wire.set(this.statePath, this.pontos);
            }
          },

          init() {
            if (this.map) return;

            this.map = L.map(this.$refs.mapContainer, { zoomControl: true })
              .setView(this.center, this.zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
              attribution: '&copy; OpenStreetMap contributors'
            }).addTo(this.map);

            // guarde a instância no DOM para reinvalidação pós-Livewire
            this.$refs.mapContainer._leaflet_map = this.map;

            // container pode estar invisível → corrige depois do primeiro paint
            this.$nextTick(() => setTimeout(() => this.map.invalidateSize(), 300));

            this.map.on('click', (e) => {
              if (!this.addMode) return;

              if (!this.rotaAtiva) {
                // modo escola: 1 único ponto
                this.pontos = [{ latitude: e.latlng.lat, longitude: e.latlng.lng, ordem: 1 }];
              } else {
                this.adicionarPonto(e.latlng.lat, e.latlng.lng);
              }
              this.sync(); // dispara afterStateUpdated no backend
            });

            this.$watch('pontos', () => {
              this.renderizarMarcadores();
              if (this.rotaAtiva) this.calcularRota();
            }, { deep: true });

            this.renderizarMarcadores();
            if (this.rotaAtiva) this.calcularRota();
          },

          adicionarPonto(lat, lng) {
            const novo = { latitude: lat, longitude: lng, ordem: (this.pontos?.length ?? 0) + 1 };
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

            if (!this.pontos || this.pontos.length === 0) return;

            this.pontos.forEach((ponto, i) => {
              const marker = L.marker([ponto.latitude, ponto.longitude], { draggable: true }).addTo(this.map);

              marker.on('click', () => this.removerPonto(i));
              marker.on('dragend', (e) => {
                const pos = e.target.getLatLng();
                const arr = [...this.pontos];
                arr[i] = { ...arr[i], latitude: pos.lat, longitude: pos.lng };
                this.pontos = arr;
                this.sync();
              });

              marker.bindPopup(`
                <div style="min-width:150px;">
                  <b>Ponto ${ponto.ordem}</b><br>
                  <small>Lat: ${ponto.latitude.toFixed(6)}<br>Lng: ${ponto.longitude.toFixed(6)}</small><br>
                  <em>(Clique para remover, arraste para mover)</em>
                </div>
              `);

              this.markers.push(marker);
            });

            if (this.pontos.length === 1) {
              this.map.setView([this.pontos[0].latitude, this.pontos[0].longitude], this.zoom);
            }
          },

          async calcularRota() {
            if (!this.pontos || this.pontos.length < 2) {
              if (this.rotaLayer) {
                this.map.removeLayer(this.rotaLayer);
                this.rotaLayer = null;
              }
              return;
            }

            const coords = this.pontos.map(p => `${p.longitude},${p.latitude}`).join(';');

            try {
              const res = await fetch(`https://router.project-osrm.org/route/v1/driving/${coords}?overview=full&geometries=geojson`);
              const data = await res.json();
              if (data.routes && data.routes.length > 0) {
                const route = data.routes[0];
                if (this.rotaLayer) this.map.removeLayer(this.rotaLayer);
                this.rotaLayer = L.geoJSON(route.geometry, { style: { color: '#ff3c00', weight: 5, opacity: 0.7 } }).addTo(this.map);
              }
            } catch (err) {
              console.error('Erro ao calcular rota:', err);
            }
          },
        }
      };

      // Sempre que o Livewire terminar um update, revalide o tamanho do mapa
      document.addEventListener('livewire:load', () => {
        if (window.Livewire?.hook) {
          Livewire.hook('message.processed', () => {
            document.querySelectorAll('.map-container').forEach(el => {
              if (el._leaflet_map) el._leaflet_map.invalidateSize(false);
            });
          });
        }
      });
    </script>

    <style>
      .map-container { height: 500px; border-radius: 8px; border: 1px solid #ccc; position: relative; z-index: 1; }
      .leaflet-container { z-index: 0 !important; }
    </style>
  @endassets
</x-dynamic-component>
