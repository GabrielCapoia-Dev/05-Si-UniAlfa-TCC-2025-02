{{-- resources/views/livewire/drive-file-picker.blade.php --}}
<div class="bg-white dark:bg-gray-900 rounded-lg shadow-lg max-w-7xl mx-auto">
    <!-- Header -->
    <div class="border-b border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <svg class="h-8 w-8 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12,1L8,6H16M6,2V8H2V2M22,2V8H18V2M2,10V16H6V10M18,10V16H22V10M8,18V24H16V18M6,18V24H2V18M22,18V24H18V18"/>
                </svg>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Google Drive</h2>
            </div>
            
            <div class="flex items-center gap-2">
                <a href="{{ route('socialite.filament.admin.oauth.redirect', ['provider' => 'google']) }}"
                   class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2M21 9V7L19 5V6.5C17.9 4.8 16 3.9 14 4.1L13 4.4C15.1 6.1 15.9 8.5 15 11.1C14.5 12.2 13.8 12.9 12.9 13.5C11.8 14.2 11.5 15.6 12.2 16.7C12.9 17.8 14.3 18.1 15.4 17.4C16.1 17 16.8 16.3 17.2 15.4L17.6 14.5C19.1 15.7 20.1 17.6 20.1 19.8C20.1 21.4 19.4 22.8 18.2 23.8L20 22V20H21C22.1 20 23 19.1 23 18V11C23 9.9 22.1 9 21 9M1 9V11C1 12.1 1.9 13 3 13V20H1V22H9C7.3 22 6 20.7 6 19V13C7.1 13 8 12.1 8 11V9C8 7.9 7.1 7 6 7H3C1.9 7 1 7.9 1 9Z"/>
                    </svg>
                    Reconectar
                </a>
                
                <button wire:click="refreshFiles" 
                        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="h-5 w-5 {{ $loading ? 'animate-spin' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Search and Controls -->
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" 
                       wire:model.live.debounce.400ms="search"
                       placeholder="Pesquisar arquivos por nome..."
                       class="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
            </div>

            <div class="flex items-center border border-gray-300 dark:border-gray-600 rounded-md overflow-hidden">
                <button wire:click="toggleViewMode" 
                        class="px-3 py-2 text-sm font-medium {{ $viewMode === 'grid' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600' }}">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M3,11H11V3H3M3,21H11V13H3M13,21H21V13H13M13,3V11H21V3"/>
                    </svg>
                </button>
                <button wire:click="toggleViewMode"
                        class="px-3 py-2 text-sm font-medium {{ $viewMode === 'list' ? 'bg-blue-500 text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600' }}">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M3,5H21V3H3M3,13H21V11H3M3,21H21V19H3"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Breadcrumbs -->
    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
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

    <!-- Selected Files Info -->
    @if(count($selectedFiles) > 0)
        <div class="px-4 py-3 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-700">
            <div class="flex items-center justify-between">
                <span class="text-sm text-blue-800 dark:text-blue-200">
                    {{ count($selectedFiles) }} {{ count($selectedFiles) === 1 ? 'arquivo selecionado' : 'arquivos selecionados' }}
                </span>
                <button wire:click="$set('selectedFiles', [])" class="text-blue-600 dark:text-blue-400 text-sm hover:underline">
                    Limpar seleção
                </button>
            </div>
        </div>
    @endif

    <!-- Content Area -->
    <div class="p-4 min-h-96">
        @if($error)
            <div class="text-center py-12">
                <svg class="h-12 w-12 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-red-600 dark:text-red-400 mb-4">{{ $errorMessage }}</p>
                @if(str_contains($errorMessage, 'não está conectada'))
                    <a href="{{ route('socialite.filament.admin.oauth.redirect', ['provider' => 'google']) }}"
                       class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                        Conectar Google Drive
                    </a>
                @endif
            </div>
        @elseif($loading)
            <div class="text-center py-12">
                <div class="animate-spin h-12 w-12 border-4 border-blue-500 border-t-transparent rounded-full mx-auto mb-4"></div>
                <p class="text-gray-500 dark:text-gray-400">Carregando arquivos...</p>
            </div>
        @else
            <!-- Grid View -->
            @if($viewMode === 'grid')
                @if(count($files) > 0)
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                        @foreach($files as $file)
                            <div class="group relative bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer {{ in_array($file['id'], $selectedFiles) ? 'ring-2 ring-blue-500 border-blue-500' : '' }}"
                                 wire:click="selectFile('{{ $file['id'] }}')">
                                
                                <div class="flex justify-center mb-3">
                                    @if(isset($file['iconLink']) && $file['iconLink'])
                                        <img src="{{ $file['iconLink'] }}" alt="" class="h-12 w-12">
                                    @else
                                        <div class="h-12 w-12 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center">
                                            <svg class="h-6 w-6 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                                @if($this->getFileTypeFromMimeType($file['mimeType']) === 'folder')
                                                    <path d="M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z"/>
                                                @else
                                                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                                @endif
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                <h3 class="text-sm font-medium text-gray-900 dark:text-white text-center truncate mb-2" title="{{ $file['name'] }}">
                                    {{ $file['name'] }}
                                </h3>

                                <div class="text-xs text-gray-500 dark:text-gray-400 text-center">
                                    <div>{{ \Carbon\Carbon::parse($file['modifiedTime'])->tz(config('app.timezone'))->format('d/m/Y') }}</div>
                                    @if(isset($file['size']) && $file['size'])
                                        <div>{{ $this->formatFileSize((int)$file['size']) }}</div>
                                    @endif
                                </div>

                                <!-- Actions -->
                                <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <div class="flex items-center gap-1">
                                        @if($this->getFileTypeFromMimeType($file['mimeType']) === 'folder')
                                            <button wire:click.stop="openFolder('{{ $file['id'] }}', '{{ addslashes($file['name']) }}')"
                                                    class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </button>
                                        @else
                                            @if(isset($file['webViewLink']) && $file['webViewLink'])
                                                <a href="{{ $file['webViewLink'] }}" target="_blank"
                                                   class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                    </svg>
                                                </a>
                                            @endif
                                        @endif
                                    </div>
                                </div>

                                <!-- Selection Checkbox -->
                                <div class="absolute top-2 left-2 {{ in_array($file['id'], $selectedFiles) ? 'opacity-100' : 'opacity-0 group-hover:opacity-100' }} transition-opacity">
                                    <div class="h-5 w-5 rounded-full border-2 flex items-center justify-center {{ in_array($file['id'], $selectedFiles) ? 'bg-blue-500 border-blue-500' : 'border-gray-300 bg-white' }}">
                                        @if(in_array($file['id'], $selectedFiles))
                                            <svg class="h-3 w-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                    </div>
                                </div>
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
            @else
                <!-- List View -->
                @if(count($files) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-6 py-3 text-left">
                                        <input type="checkbox" 
                                               wire:click="selectAllFiles"
                                               {{ count($selectedFiles) === count($files) && count($files) > 0 ? 'checked' : '' }}
                                               class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer"
                                        wire:click="sortFiles('name')">
                                        <div class="flex items-center gap-1">
                                            Nome
                                            @if($sortBy === 'name')
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    @if($sortDirection === 'asc')
                                                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                                    @else
                                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                    @endif
                                                </svg>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer"
                                        wire:click="sortFiles('modifiedTime')">
                                        <div class="flex items-center gap-1">
                                            Modificado
                                            @if($sortBy === 'modifiedTime')
                                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                                    @if($sortDirection === 'asc')
                                                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                                    @else
                                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                    @endif
                                                </svg>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tamanho</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($files as $file)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 {{ in_array($file['id'], $selectedFiles) ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" 
                                                   wire:click="selectFile('{{ $file['id'] }}')"
                                                   {{ in_array($file['id'], $selectedFiles) ? 'checked' : '' }}
                                                   class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                @if(isset($file['iconLink']) && $file['iconLink'])
                                                    <img src="{{ $file['iconLink'] }}" alt="" class="h-8 w-8">
                                                @else
                                                    <div class="h-8 w-8 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center">
                                                        <svg class="h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                                            @if($this->getFileTypeFromMimeType($file['mimeType']) === 'folder')
                                                                <path d="M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z"/>
                                                            @else
                                                                <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                                            @endif
                                                        </svg>
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $file['name'] }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                                {{ ucfirst($this->getFileTypeFromMimeType($file['mimeType'])) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ \Carbon\Carbon::parse($file['modifiedTime'])->tz(config('app.timezone'))->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            @if(isset($file['size']) && $file['size'])
                                                {{ $this->formatFileSize((int)$file['size']) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center gap-2">
                                                @if($this->getFileTypeFromMimeType($file['mimeType']) === 'folder')
                                                    <button wire:click="openFolder('{{ $file['id'] }}', '{{ addslashes($file['name']) }}')"
                                                            class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                                        Abrir
                                                    </button>
                                                @else
                                                    @if(isset($file['webViewLink']) && $file['webViewLink'])
                                                        <a href="{{ $file['webViewLink'] }}" target="_blank"
                                                                                                                      class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                                            Visualizar
                                                        </a>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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
        @endif
    </div>

    <!-- Footer / Actions -->
    <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2 bg-gray-50 dark:bg-gray-900">
        <button wire:click="$set('selectedFiles', [])" 
                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
            Cancelar
        </button>
        @if(count($selectedFiles) > 0)
            <button wire:click="confirmSelection"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Selecionar {{ count($selectedFiles) === 1 ? 'arquivo' : 'arquivos' }}
            </button>
        @endif
    </div>
</div>
