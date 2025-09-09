<x-filament-forms::field-wrapper :id="$getId()" :label="$getLabel()" :label-sr-only="$isLabelHidden()"
    :helper-text="$getHelperText()" :required="$isRequired()" :state-path="$getStatePath()">
    <div x-data="leafletMapComponent({
            defaultLat: {{ $getDefaultLat() }},
            defaultLng: {{ $getDefaultLng() }},
            zoom: {{ $getZoom() }},
            radius: {{ $getDefaultRadius() }}
        })" x-init="initMap()" x-cloak class="w-full rounded-lg overflow-hidden {{ $getMapHeight() }}"
        x-load-css='@json(\App\Forms\Components\LeafletMap::defaultCss())'
        x-load-js='@json(\App\Forms\Components\LeafletMap::defaultJs())'>
        <div id="{{ $getStatePath() }}-map" wire:ignore class="w-full h-full" style="height: 350px;"></div>
    </div>
</x-filament-forms::field-wrapper>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('leafletMapComponent', ({
        defaultLat,
        defaultLng,
        zoom,
        radius
    }) => ({
        map: null,
        marker: null,
        circle: null,
        currentRadius: radius || 500,

        async initMap() {
            while (!window.L) await new Promise(r => setTimeout(r, 50));

            this.map = L.map('{{ $getStatePath() }}-map').setView([defaultLat, defaultLng],
                zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: ''
            }).addTo(this.map);

            this.marker = L.marker([defaultLat, defaultLng], {
                draggable: true
            }).addTo(this.map);

            this.circle = L.circle([defaultLat, defaultLng], {
                radius: this.currentRadius
            }).addTo(this.map);

            const updateCircle = (lat, lng) => {
                this.marker.setLatLng([lat, lng]);
                this.circle.setLatLng([lat, lng]);
                this.circle.setRadius(this.currentRadius);
            };

            this.map.on('click', (e) => {
                const {
                    lat,
                    lng
                } = e.latlng;
                updateCircle(lat, lng);
                this.$wire.mapClicked({
                    lat,
                    lng
                });
            });

            this.marker.on('dragend', (e) => {
                const {
                    lat,
                    lng
                } = e.target.getLatLng();
                updateCircle(lat, lng);
                this.$wire.mapClicked({
                    lat,
                    lng
                });
            });

            // OBS: Livewire escuta mudanças no campo raio
            Livewire.hook('message.processed', (message, component) => {
                const newRadius = parseInt(component.get('data.raio')) || 2000;
                if (newRadius !== this.currentRadius) {
                    this.currentRadius = newRadius;
                    this.circle.setRadius(this.currentRadius);
                }
            });

            Livewire.on('raioUpdated', ({
                radius
            }) => {
                if (this.circle) {
                    this.currentRadius = parseInt(radius);
                    this.circle.setRadius(this.currentRadius);
                }
            });



            setTimeout(() => this.map.invalidateSize(), 200);
        },
    }));


});
</script>

@push('styles')
<style>
.leaflet-container {
    z-index: 0 !important;
}

.leaflet-map-pane,
.leaflet-tile-pane,
.leaflet-overlay-pane,
.leaflet-shadow-pane,
.leaflet-marker-pane,
.leaflet-tooltip-pane,
.leaflet-popup-pane {
    z-index: 0 !important;
}
</style>
@endpush