<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExportModeloController extends Controller
{
    public function handle(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Admin')) {
            abort(403, 'Você não tem permissão para exportar o modelo.');
        }

        $modelClass = $request->query('model');

        if (! class_exists($modelClass) || ! method_exists($modelClass, 'exportModelo')) {
            abort(400, 'Modelo inválido.');
        }

        return app($modelClass)::exportModelo();
    }
}
