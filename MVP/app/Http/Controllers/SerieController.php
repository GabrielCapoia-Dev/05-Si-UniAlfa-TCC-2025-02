<?php

namespace App\Http\Controllers;

use App\Services\SerieService;
use Filament\Notifications\Notification;

class SerieController extends Controller
{
    public function criarSerie($data): Notification
    {
        
        $service = new SerieService();
        return $service->criarSerie($data);
    }

    public function editarSerie($record, $data): Notification
    {
        
        $service = new SerieService();
        return $service->editarSerie($record, $data);
    }

    public function deletarSerie($id): Notification
    {
        
        $service = new SerieService();
        return $service->deletarSerie($id);
    }

    public function deletarSerieEmMassa($records, $action): Notification
    {
        
        $service = new SerieService();
        return $service->deletarSerieEmMassa($records, $action);
    }


}