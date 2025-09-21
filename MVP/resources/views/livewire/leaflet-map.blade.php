<div x-data="{
    lat: {{ $get('lat', -23.7637) }},
    lng: {{ $get('lng', -53.2967) }},
    radius: {{ $get('radius', $getDefaultRadius()) }},
    map: null,
    marker: null,
    circle: null,

    initMap() {
        this.map = L.map($el).setView([this.lat, this.lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '',
        }).addTo(this.map);

        this.marker = L.marker([this.lat, this.lng], { draggable: true }).addTo(this.map);
        this.circle = L.circle([this.lat, this.lng], { radius: this.radius }).addTo(this.map);

        this.marker.on('dragend', (e) => {
            const pos = e.target.getLatLng();
            this.lat = pos.lat;
            this.lng = pos.lng;
            this.circle.setLatLng(pos);

            $wire.set('latitude', this.lat);
            $wire.set('longitude', this.lng);
        });

        this.map.on('click', (e) => {
            this.lat = e.latlng.lat;
            this.lng = e.latlng.lng;

            this.marker.setLatLng(e.latlng);
            this.circle.setLatLng(e.latlng);

            $wire.set('latitude', this.lat);
            $wire.set('longitude', this.lng);
        });

    },
}" x-init="initMap()" style="height: 400px;"></div>

@once
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endonce
