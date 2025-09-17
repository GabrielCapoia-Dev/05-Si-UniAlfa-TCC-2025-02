<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExportModeloController extends Controller
{
    public function handle(Request $request): Response
    {
        $modelClass = $request->get('model');

        if (! $modelClass || ! class_exists($modelClass)) {
            abort(404, 'Model inválida.');
        }

        $model = app($modelClass);

        if (! method_exists($model, 'exportModelo')) {
            abort(500, 'Model não implementa exportModelo.');
        }

        return $model->exportModelo();
    }
}
