{{-- resources/views/livewire/drive-file-picker.blade.php --}}
<div class="space-y-4">
    <div class="flex items-center gap-2">
        <x-filament::input.wrapper>
            <x-filament::input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Search files by name…"
            />
        </x-filament::input.wrapper>

        <a
            href="{{ route('socialite.filament.admin.oauth.redirect', ['provider' => 'google']) }}"
            class="fi-btn fi-btn-size-md fi-color-primary"
        >
            Reconnect Google
        </a>
    </div>

    @if($error)
        <x-filament::section>
            <div class="text-danger-600 dark:text-danger-400 text-sm">
                {{ $errorMessage }}
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left">
                        <tr>
                            <th class="py-2 pr-4">Name</th>
                            <th class="py-2 pr-4">Type</th>
                            <th class="py-2 pr-4">Modified</th>
                            <th class="py-2 pr-4">Open</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($files as $f)
                            <tr class="border-t">
                                <td class="py-2 pr-4">
                                    <div class="flex items-center gap-2">
                                        @if($f['iconLink'])
                                            <img src="{{ $f['iconLink'] }}" alt="" class="h-4 w-4">
                                        @endif
                                        <span class="font-medium">{{ $f['name'] }}</span>
                                    </div>
                                </td>
                                <td class="py-2 pr-4">{{ $f['mimeType'] }}</td>
                                <td class="py-2 pr-4">
                                    {{ \Carbon\Carbon::parse($f['modifiedTime'])->tz(config('app.timezone'))->format('d/m/Y H:i') }}
                                </td>
                                <td class="py-2 pr-4">
                                    @if($f['webViewLink'])
                                        <a href="{{ $f['webViewLink'] }}" target="_blank" class="text-primary-600 hover:underline">
                                            Open
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-gray-500">No files found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</div>
