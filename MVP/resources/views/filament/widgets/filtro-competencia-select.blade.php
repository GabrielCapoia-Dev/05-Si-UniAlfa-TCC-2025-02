<div class="mt-2">
    <form wire:submit.prevent="filtrar" class="flex items-center gap-2">
        {{ $form }}
        <x-filament::button type="submit" size="sm">
            Filtrar
        </x-filament::button>
    </form>
</div>
