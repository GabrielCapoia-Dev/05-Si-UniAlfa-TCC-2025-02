<x-filament-forms::field-wrapper :id="$getId()" :label="$getLabel()" :label-sr-only="$isLabelHidden()"
    :helper-text="$getHelperText() ?? ''" :hint="$getHint() ?? ''" :required="$isRequired()"
    :state-path="$getStatePath()">

    {{-- Carrega CSS apenas uma vez --}}
    @once
    @foreach(\App\Forms\Components\LeafletCirclePicker::defaultCss() as $css)
    <link rel="stylesheet" href="{{ $css }}">
    @endforeach
    @endonce

    <div x-data="leafletCirclePicker($wire, {{ json_encode($getState() ?? \App\Forms\Components\LeafletCirclePicker::defaultState()) }})"
        x-init="async () => {
             // espera o container e o Leaflet estarem prontos
             do {
                 await new Promise(r => setTimeout(r, 50));
             } while (!$refs.map || !window.L);
             init($refs.map);
         }" wire:ignore>
        <div x-ref="map" class="w-full h-96 rounded-lg overflow-hidden"></div>
        <input type="text" id="{{ $getId() }}_fmrest" style="display:none" />
    </div>

    {{-- Carrega JS apenas uma vez --}}
    @once
    @foreach(\App\Forms\Components\LeafletCirclePicker::defaultJs() as $js)
    <script src="{{ $js }}"></script>
    @endforeach
    @endonce

</x-filament-forms::field-wrapper>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('leafletCirclePicker', (wire, initialState) => ({
        state: initialState || {
            lat: -23.7637,
            lng: -53.2967,
            radius: 2000
        },
        map: null,
        marker: null,
        circle: null,

        init(mapEl) {
            console.log('Leaflet init started', this.state);

            if (!window.L) {
                console.error('Leaflet não carregou!');
                return;
            }

            const {
                lat,
                lng,
                radius
            } = this.state;

            this.map = L.map(mapEl).setView([lat, lng], 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: ''
            }).addTo(this.map);

            this.marker = L.marker([lat, lng], {
                draggable: true
            }).addTo(this.map);
            this.circle = L.circle([lat, lng], {
                radius
            }).addTo(this.map);

            const update = (latlng) => {
                this.marker.setLatLng(latlng);
                this.circle.setLatLng(latlng);
                this.state = {
                    lat: latlng.lat,
                    lng: latlng.lng,
                    radius
                };
                wire.set('{{ $getStatePath() }}', this.state);
            };

            this.map.on('click', e => update(e.latlng));
            this.marker.on('dragend', e => update(e.target.getLatLng()));

            setTimeout(() => this.map.invalidateSize(), 200);
        }
    }));
});
</script>