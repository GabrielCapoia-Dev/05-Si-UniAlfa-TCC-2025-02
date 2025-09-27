<x-filament::page>
    <div class="flex flex-col md:flex-row gap-4 h-full">
        {{-- Tabela de alunos - um pouco menor --}}
        <div class="md:flex-[0.45] w-full md:w-auto order-1 md:order-none">
            {{ $this->table }}
        </div>

        {{-- Painel lateral com detalhes - mais largo --}}
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden card-detalhes-aluno">
            <div class="md:sticky md:top-6">
                @if ($alunoSelecionado)
                    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                        {{-- Botão X (sempre no canto superior direito do card) --}}
                        <button wire:click="fecharDetalhesAluno"
                            class="absolute top-2 right-2 z-10 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
                            <x-heroicon-o-x-mark class="w-5 h-5" />
                        </button>

                        {{-- Header do Card --}}
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-4 text-white text-center">
                            <h2 class="text-lg font-semibold">Informações do Aluno</h2>
                        </div>

                        {{-- Avatar e Info Principal --}}
                        <div class="p-6 text-center border-b border-gray-200 dark:border-gray-700">
                            <div class="mb-4">
                                <img src="{{ $alunoSelecionado->foto ? asset('storage/' . $alunoSelecionado->foto) : 'https://ui-avatars.com/api/?name=' . urlencode($alunoSelecionado->nome) . '&size=120&background=e5e7eb&color=374151' }}"
                                    class="w-24 h-24 rounded-full mx-auto object-cover border-4 border-white shadow-lg">
                            </div>

                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ $alunoSelecionado->nome }}
                            </h3>

                            <div class="mt-2 space-y-1">
                                <div
                                    class="inline-flex items-center px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs rounded-full">
                                    <span>CGM: {{ $alunoSelecionado->cgm ?? 'N/A' }}</span>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-500">
                                    {{ $alunoSelecionado->sexo }} •
                                    {{ \Carbon\Carbon::parse($alunoSelecionado->data_nascimento)->format('d/m/Y') }}
                                </div>
                            </div>

                            @if ($alunoSelecionado->turma)
                                <div class="mt-2 space-y-1">
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $alunoSelecionado->turma->escola->nome ?? '' }}
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $alunoSelecionado->turma->serie->nome ?? '' }} -
                                        {{ $alunoSelecionado->turma->turma ?? '' }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Informações de Contato --}}
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center">
                                <x-heroicon-o-phone class="w-4 h-4 mr-2" />
                                Contato
                            </h4>
                            <div class="space-y-2">
                                <p><span class="font-medium">Responsável:</span>
                                    {{ $alunoSelecionado->nome_responsavel ?? 'N/A' }}</p>
                                <p><span class="font-medium">Telefone:</span>
                                    {{ $alunoSelecionado->telefone_responsavel ?? 'N/A' }}</p>
                                @if ($alunoSelecionado->telefone_aluno)
                                    <p><span class="font-medium">Telefone do Aluno:</span>
                                        {{ $alunoSelecionado->telefone_aluno }}</p>
                                @endif
                            </div>
                        </div>

                        {{-- Informações de Endereço --}}
                        <div class="p-4">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center">
                                <x-heroicon-o-map-pin class="w-4 h-4 mr-2" />
                                Endereço
                            </h4>
                            <p>{{ $alunoSelecionado->logradouro ?? 'N/A' }}, {{ $alunoSelecionado->numero ?? '' }}</p>
                            <p>{{ $alunoSelecionado->bairro ?? '' }} -
                                {{ $alunoSelecionado->cidade ?? '' }}/{{ $alunoSelecionado->estado ?? '' }}</p>
                            <p>CEP: {{ $alunoSelecionado->cep ?? 'N/A' }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Script para melhorar a UX --}}
    @script
        <script>
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && @js($alunoSelecionado !== null)) {
                    $wire.fecharDetalhesAluno();
                }
            });

            window.addEventListener('error', function(e) {
                if (e.message && e.message.includes('404')) {
                    $wire.fecharDetalhesAluno();
                }
            });

            window.addEventListener('unhandledrejection', function(event) {
                if (event.reason && event.reason.status === 404) {
                    $wire.fecharDetalhesAluno();
                    event.preventDefault();
                }
            });

            // 📌 Fechar ao clicar fora do card
            document.addEventListener('click', function(event) {
                const card = document.querySelector('.card-detalhes-aluno'); // classe que vamos atribuir
                if (card && !card.contains(event.target) && @js($alunoSelecionado !== null)) {
                    $wire.fecharDetalhesAluno();
                }
            });
        </script>
    @endscript

</x-filament::page>
