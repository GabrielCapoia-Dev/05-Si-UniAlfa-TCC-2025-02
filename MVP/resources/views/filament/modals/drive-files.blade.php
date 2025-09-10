{{-- resources/views/filament/modals/drive-files.blade.php --}}
<div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

    <!-- Modal -->
    <div class="relative w-full max-w-6xl bg-white dark:bg-gray-900 rounded-xl shadow-xl overflow-hidden transform transition-all
                flex flex-col" style="width: 95vw; max-width: 1200px; height: 85vh;">
        
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex-shrink-0">
            <div class="flex items-center gap-3">
                <svg class="h-6 w-6 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12,1L8,6H16M6,2V8H2V2M22,2V8H18V2M2,10V16H6V10M18,10V16H22V10M8,18V24H16V18M6,18V24H2V18M22,18V24H18V18"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Seletor de Planilhas - Google Drive
                </h3>
            </div>
            <button @click="Livewire.emit('closeModal')" 
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700">
                <span class="sr-only">Fechar</span>
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Body com barra de rolagem interna -->
        <div class="flex-1 p-6 overflow-hidden">
            <livewire:drive-file-picker />
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex justify-between items-center flex-shrink-0">
            <a href="{{ route('socialite.filament.admin.oauth.redirect', ['provider' => 'google']) }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2M21 9V7L19 5V6.5C17.9 4.8 16 3.9 14 4.1L13 4.4C15.1 6.1 15.9 8.5 15 11.1C14.5 12.2 13.8 12.9 12.9 13.5C11.8 14.2 11.5 15.6 12.2 16.7C12.9 17.8 14.3 18.1 15.4 17.4C16.1 17 16.8 16.3 17.2 15.4L17.6 14.5C19.1 15.7 20.1 17.6 20.1 19.8C20.1 21.4 19.4 22.8 18.2 23.8L20 22V20H21C22.1 20 23 19.1 23 18V11C23 9.9 22.1 9 21 9M1 9V11C1 12.1 1.9 13 3 13V20H1V22H9C7.3 22 6 20.7 6 19V13C7.1 13 8 12.1 8 11V9C8 7.9 7.1 7 6 7H3C1.9 7 1 7.9 1 9Z"/>
                </svg>
                Reconectar Google Drive
            </a>
            
            <button type="button" onclick="Livewire.emit('closeModal')"
                    class="px-4 py-2 bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-white rounded-md hover:bg-gray-400 dark:hover:bg-gray-600 focus:outline-none transition">
                Fechar
            </button>
        </div>
    </div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Cards de arquivo */
.file-card {
    transition: all 0.2s ease-in-out;
    padding: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    background-color: #fff;
}
.file-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}
.dark .file-card {
    border-color: #374151;
    background-color: #1f2937;
}
.dark .file-card:hover {
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
}
</style>
