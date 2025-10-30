<?php

namespace App\Services;

use App\Models\Serie;
use App\Models\Turma;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class SerieService {

    public function deletarSerie($id): Notification
    {
        
        $turmas = Turma::where('id_serie', $id)->get();

        if ($turmas->count() > 0) {

            return Notification::make()
                ->title('Operação cancelada')
                ->body("Não foi possível excluir esta série pois esta vinculada a turmas.")
                ->danger()
                ->send();
        }

        $serie = Serie::find($id);

        $serie->delete();

        return Notification::make()
            ->title('Série excluída com sucesso')
            ->success()
            ->send();
    }

    public function deletarSerieEmMassa($records, $action): Notification
    {
        foreach ($records as $record) {

            $turmas = Turma::where('id_serie', $record->id)->get();

            if ($turmas->count() > 0) {

                return Notification::make()
                    ->title('Operação cancelada')
                    ->body("Não foi possível excluir esta série pois esta vinculada a turmas.")
                    ->danger()
                    ->send();
            }

            $serie = Serie::find($record->id);

            $serie->delete();
        }
        return Notification::make()
            ->title('Séries excluídas com sucesso')
            ->success()
            ->send();
    }


    public function criarSerie($data): Notification
    {
        if(!$data) {
            return Notification::make()
                ->title('Operação cancelada')
                ->body("Nenhuma série foi criada.")
                ->danger()
                ->send();
        }

        $validade = Validator::make($data, [
            'nome' => 'required',
        ], [
            'required' => 'O campo :attribute deve ser preenchido.',
        ]);

        if($validade->fails()) {
            return Notification::make()
                ->title('Operação cancelada')
                ->body("Nenhuma série foi criada. " . $validade->errors()->first())
                ->danger()
                ->send();
        }

        Serie::create($data);

        $notfy = Notification::make()
            ->title('Série criada com sucesso')
            ->success()
            ->send();

        return $notfy;
    }

    public function editarSerie($record, $data): Notification
    {
        if(!$data || !$record) {
            return Notification::make()
                ->title('Operação cancelada')
                ->body("Nenhuma série foi editada.")
                ->danger()
                ->send();
        }

        $validade = Validator::make($data, [
            'nome' => 'required',
        ], [
            'required' => 'O campo :attribute deve ser preenchido.',
        ]);

        if($validade->fails()) {
            return Notification::make()
                ->title('Operação cancelada')
                ->body("Nenhuma série foi editada. " . $validade->errors()->first())
                ->danger()
                ->send();
        }

        $serie = Serie::find($record['id']);

        $serie->update($data);

        return Notification::make()
            ->title('Série editada com sucesso')
            ->success()
            ->send();
    }
}