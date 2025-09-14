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
                @php
                    $folders = collect($files)->filter(fn($f) => $this->getFileTypeFromMimeType($f['mimeType']) === 'folder');
                    $docs    = collect($files)->reject(fn($f) => $this->getFileTypeFromMimeType($f['mimeType']) === 'folder');
                @endphp

                <ul class="divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    {{-- Pastas primeiro --}}
                    @foreach($folders as $file)
                        <li class="flex items-center justify-between px-3 py-2 cursor-pointer"
                            wire:click="openFolder('{{ $file['id'] }}', '{{ addslashes($file['name']) }}')">
                            <div class="flex items-center gap-2">
                                <svg class="h-5 w-5 text-yellow-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z"/>
                                </svg>
                                <span class="font-medium text-gray-900 dark:text-white truncate">{{ $file['name'] }}</span>
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ \Carbon\Carbon::parse($file['modifiedTime'])->tz(config('app.timezone'))->format('d/m/Y') }}
                            </div>
                        </li>
                    @endforeach

                    {{-- Arquivos depois --}}
                    @foreach($docs as $file)
                        @php
                            $viewLink = !empty($file['webViewLink'])
                                ? $file['webViewLink']
                                : 'https://drive.google.com/file/d/' . $file['id'] . '/view';

                            $isSelected = in_array($file['id'], $selectedFiles);

                            // Extensões permitidas
                            $allowedExtensions = [];
                            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                            // Detecta se é Google Sheets (MIME type começa com application/vnd.google-apps.spreadsheet)
                            $isGoogleSheet = str_contains($file['mimeType'], 'application/vnd.google-apps.spreadsheet');

                            // Se não for permitido, desabilita
                            $isDisabled = !$isGoogleSheet && !in_array($extension, $allowedExtensions);
                        @endphp

                        <li class="flex items-center justify-between px-3 py-2 
                            {{ $isDisabled ? 'bg-gray-100 dark:bg-gray-800 opacity-50 cursor-not-allowed' : 'cursor-pointer' }}
                            {{ $isSelected ? 'bg-green-50 dark:bg-green-900/20' : '' }}"
                            @unless($isDisabled)
                                wire:click="selectFile('{{ $file['id'] }}')"
                            @endunless
                        >
                            <div class="flex items-center gap-2">
                                @if($isSelected)
                                    <img src="{{ asset('images/check-green.png') }}" alt="Selecionado" class="h-5 w-5">
                                @else
                                    @if(isset($file['iconLink']) && $file['iconLink'])
                                        <img src="{{ $file['iconLink'] }}" alt="icon" class="h-5 w-5">
                                    @else
                                        <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                        </svg>
                                    @endif
                                @endif

                                <a href="{{ $viewLink }}" target="_blank"
                                    class="truncate {{ $isDisabled ? 'text-gray-400 pointer-events-none' : 'text-blue-600 dark:text-blue-400 hover:underline' }}">
                                    {{ $file['name'] }}
                                </a>
                            </div>

                            <div class="text-xs {{ $isDisabled ? 'text-gray-400' : 'text-gray-500' }}">
                                {{ \Carbon\Carbon::parse($file['modifiedTime'])->tz(config('app.timezone'))->format('d/m/Y') }}
                            </div>
                        </li>
                    @endforeach


                </ul>
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
                <span class="text-sm text-white-700 dark:text-gray-300">
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
