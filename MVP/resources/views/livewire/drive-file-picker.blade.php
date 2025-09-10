{{-- resources/views/livewire/drive-file-picker.blade.php --}}
<div class="h-full flex flex-col">

    @if(count($breadcrumbs) > 1)
        <div class="mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
            <nav class="flex items-center space-x-2 text-sm">
                @foreach($breadcrumbs as $index => $breadcrumb)
                    @if($loop->last)
                        <span class="text-gray-900 dark:text-white font-medium">{{ $breadcrumb['name'] }}</span>
                    @else
                        <button wire:click="navigateToBreadcrumb({{ $index }})" 
                                class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                            {{ $breadcrumb['name'] }}
                        </button>
                        <svg class="h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    @endif
                @endforeach
            </nav>
        </div>
    @endif

    <!-- Content Area -->
    <div class="flex-1 overflow-y-auto">
        @if($error)
            <div class="text-center py-12">
                <svg class="h-12 w-12 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-red-600 dark:text-red-400 mb-4">{{ $errorMessage }}</p>
            </div>
        @elseif($loading)
            <div class="text-center py-12">
                <div class="animate-spin h-12 w-12 border-4 border-blue-500 border-t-transparent rounded-full mx-auto mb-4"></div>
                <p class="text-gray-500 dark:text-gray-400">Carregando arquivos...</p>
            </div>
        @else
            @if(count($files) > 0)
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-7 gap-3">
                    @foreach($files as $file)
                        <div class="group relative bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3 hover:shadow-md hover:border-blue-300 dark:hover:border-blue-600 transition-all cursor-pointer {{ in_array($file['id'], $selectedFiles) ? 'ring-2 ring-blue-500 border-blue-500 bg-blue-50 dark:bg-blue-900/20' : '' }}"
                             wire:click="selectFile('{{ $file['id'] }}')">
                            
                            <!-- File Icon -->
                            <div class="flex justify-center mb-3">
                                @php
                                    $isFolder = $this->getFileTypeFromMimeType($file['mimeType']) === 'folder';
                                    $viewLink = !empty($file['webViewLink'])
                                        ? $file['webViewLink']
                                        : 'https://drive.google.com/file/d/' . $file['id'] . '/view';
                                @endphp

                                @if($isFolder)
                                    {{-- Pasta: usa botão que chama openFolder --}}
                                    <button
                                        type="button"
                                        onclick="event.stopPropagation()"
                                        wire:click="openFolder('{{ $file['id'] }}', '{{ addslashes($file['name']) }}')"
                                        class="rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        title="Abrir pasta"
                                        aria-label="Abrir pasta: {{ $file['name'] }}"
                                    >
                                        @if(isset($file['iconLink']) && $file['iconLink'])
                                            <img src="{{ $file['iconLink'] }}" alt="" class="h-12 w-12">
                                        @else
                                            <div class="h-12 w-12 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center">
                                                <svg class="h-6 w-6 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </button>
                                @else
                                    {{-- Arquivo: ícone vira link para visualizar no Drive --}}
                                    <a
                                        href="{{ $viewLink }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        onclick="event.stopPropagation()"
                                        class="rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        title="Abrir no Google Drive"
                                        aria-label="Abrir no Google Drive: {{ $file['name'] }}"
                                    >
                                        @if(isset($file['iconLink']) && $file['iconLink'])
                                            <img src="{{ $file['iconLink'] }}" alt="" class="h-12 w-12">
                                        @else
                                            <div class="h-12 w-12 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center">
                                                <svg class="h-6 w-6 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </a>
                                @endif
                            </div>

                            {{-- File Info --}}
                            <div class="text-center">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2" title="{{ $file['name'] }}">

                                        <span class="truncate">{{ $file['name'] }}</span>
                                </h3>

                                <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                                    <div>Modificado em: {{ \Carbon\Carbon::parse($file['modifiedTime'])->tz(config('app.timezone'))->format('d/m/Y') }}</div>
                                    @if(isset($file['size']) && $file['size'])
                                        <div>Tamanho: {{ $this->formatFileSize((int)$file['size']) }}</div>
                                    @endif
                                </div>
                            </div>

                            <!-- Actions (top right) -->
                            <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                @if($this->getFileTypeFromMimeType($file['mimeType']) === 'folder')
                                    <button wire:click.stop="openFolder('{{ $file['id'] }}', '{{ addslashes($file['name']) }}')"
                                            class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700"
                                            title="Abrir pasta">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                @else
                                    @if(isset($file['webViewLink']) && $file['webViewLink'])
                                        <a href="{{ $file['webViewLink'] }}" target="_blank"
                                           class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700"
                                           title="Visualizar arquivo">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                        </a>
                                    @endif
                                @endif
                            </div>

                            <!-- Selection Indicator -->
                            @if(in_array($file['id'], $selectedFiles))
                                <div class="absolute top-2 left-2">
                                    <div class="h-5 w-5 rounded-full bg-blue-500 border-2 border-white flex items-center justify-center">
                                        <svg class="h-3 w-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">Nenhum arquivo encontrado</p>
                </div>
            @endif
        @endif
    </div>

    <!-- Selection Info -->
    @if(count($selectedFiles) > 0)
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-700 dark:text-gray-300">
                    {{ count($selectedFiles) }} {{ count($selectedFiles) === 1 ? 'arquivo selecionado' : 'arquivos selecionados' }}
                </span>
                <div class="flex gap-2">
                    <button wire:click="$set('selectedFiles', [])" 
                            class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        Limpar
                    </button>
                    <button wire:click="confirmSelection"
                            class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>