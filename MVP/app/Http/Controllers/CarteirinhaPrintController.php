<?php

namespace App\Http\Controllers;

use App\Models\Aluno;
use Illuminate\Http\Request;

class CarteirinhaPrintController extends Controller
{
    public function __invoke(Request $request)
    {
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->filter()->map(fn($v) => (int) $v)->all();

        abort_if(empty($ids), 404, 'Nenhum aluno informado.');

        // só com carteirinha = true
        $alunos = Aluno::with(['turma.escola', 'turma.serie', 'rota'])
            ->whereIn('id', $ids)
            ->where('tem_carteirinha', true)
            ->orderBy('nome')
            ->get();

        abort_if($alunos->isEmpty(), 404, 'Nenhum aluno com carteirinha encontrado.');

        // objeto simples só pra passar ano e fundo
        $template = (object) [
            'ano' => now()->year,
            'urlFundo' => public_path('img/fundo-carteirinha-2025.jpg'),
        ];

        return view('pdf.carteirinhas', compact('alunos', 'template'));
    }
}
