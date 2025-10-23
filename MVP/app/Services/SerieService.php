<?php

namespace App\Services;

use App\Models\Serie;
use App\Models\Turma;
use Filament\Notifications\Notification;

class SerieService {

    public function deletarSerie($id): bool
    {
        
        $turmas = Turma::where('id_serie', $id)->get();

        if ($turmas->count() > 0) {

            Notification::make()
                ->title('Operação cancelada')
                ->body("Não foi possível excluir esta série pois esta vinculada a turmas.")
                ->danger()
                ->send();

            return false;
        }

        $serie = Serie::find($id);

        $serie->delete();

        Notification::make()
            ->title('Série excluída com sucesso')
            ->success()
            ->send();

        return true;
    }

    public function deletarSerieEmMassa($records, $action): bool
    {
        foreach ($records as $record) {
            $this->deletarSerie($record->id);

            $action->halt();
        }
        return true;
    }

}