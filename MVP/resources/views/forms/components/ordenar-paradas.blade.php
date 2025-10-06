<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            itens: @entangle($getStatePath()).live,

            up(i){ if(i<=0) return; const a=this.itens[i-1], b=this.itens[i]; this.itens.splice(i-1,2,b,a); this.fix(); this.$dispatch('pontos-updated',{pontos:this.itens}) },
            down(i){ if(i>=this.itens.length-1) return; const a=this.itens[i], b=this.itens[i+1]; this.itens.splice(i,2,b,a); this.fix(); this.$dispatch('pontos-updated',{pontos:this.itens}) },
            remove(i){ this.itens.splice(i,1); this.fix(); this.$dispatch('pontos-updated',{pontos:this.itens}) },

            fix(){ this.itens = this.itens.map((p,idx)=>({ ...p, ordem: idx+1 })) },
            label(p){ return (p.tipo ?? '') === 'escola' ? (p.rotulo ?? `Escola ${p.nome ?? ''}`.trim()) : `Ponto ${p.ordem ?? ''}`.trim() },
        }"
        class="fi-card rounded-lg border p-3 space-y-2">
        <div class="text-sm font-semibold">Sequência de Paradas</div>

        <template x-if="!itens || itens.length === 0">
            <div class="text-xs text-gray-500">Adicione pontos no mapa ou selecione escolas.</div>
        </template>

        <!-- 🔒 Travar altura a partir de 5 e scroll interno só se > 5 -->
        <div
            class="space-y-2"
            x-show="itens?.length"
            :style="(itens?.length ?? 0) >= 5 ? 'max-height: 220px' : ''"
            :class="{'overflow-y-auto': (itens?.length ?? 0) > 5}">
            <template x-for="(p, i) in itens" :key="i">
                <div class="flex items-center justify-between border rounded px-2 py-1">
                    <!-- evita quebra de linha para manter altura estável -->
                    <div class="text-sm truncate" x-text="label(p)"></div>

                    <div class="flex items-center gap-1 shrink-0">
                        <button type="button" class="fi-btn fi-btn-icon" @click="up(i)" title="Para cima">▲</button>
                        <button type="button" class="fi-btn fi-btn-icon" @click="down(i)" title="Para baixo">▼</button>
                        <button type="button" class="fi-btn fi-btn-icon text-red-600 hover:text-red-700" title="Remover" aria-label="Remover parada" @click="remove(i)">✕</button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</x-dynamic-component>