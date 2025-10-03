<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            itens: @entangle($getStatePath()).live,
            up(i) {
                if (i <= 0) return
                const a = this.itens[i - 1], b = this.itens[i]
                this.itens.splice(i - 1, 2, b, a)
                this.fix()
            },
            down(i) {
                if (i >= this.itens.length - 1) return
                const a = this.itens[i], b = this.itens[i + 1]
                this.itens.splice(i, 2, b, a)
                this.fix()
            },
            fix() {
                this.itens = this.itens.map((p, idx) => ({ ...p, ordem: idx + 1 }))
            },
            label(p) {
                if ((p.tipo ?? '') === 'escola') {
                    // rotulo já vem como 'Escola Nome', senão monta
                    return p.rotulo ?? `Escola ${p.nome ?? ''}`.trim()
                }
                return `Ponto ${p.ordem ?? ''}`.trim()
            }
        }"
        class="fi-card rounded-lg border p-3 space-y-2"
    >
        <div class="text-sm font-semibold">Sequência de Paradas</div>

        <template x-if="!itens || itens.length === 0">
            <div class="text-xs text-gray-500">Adicione pontos no mapa ou selecione escolas.</div>
        </template>

        <div class="space-y-2" x-show="itens?.length">
            <template x-for="(p, i) in itens" :key="i">
                <div class="flex items-center justify-between border rounded px-2 py-1">
                    <div class="text-sm" x-text="label(p)"></div>
                    <div class="flex gap-1">
                        <button type="button" class="fi-btn fi-btn-icon" @click="up(i)" title="Para cima">▲</button>
                        <button type="button" class="fi-btn fi-btn-icon" @click="down(i)" title="Para baixo">▼</button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</x-dynamic-component>
