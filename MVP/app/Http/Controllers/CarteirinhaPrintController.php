<?php

namespace App\Http\Controllers;

use App\Models\Aluno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            ->get()
            ->map(function ($aluno) {
                // Busca a foto do aluno baseada no CGM
                $aluno->foto_web_url = $this->buscarFotoAluno($aluno->cgm);
                return $aluno;
            });

        abort_if($alunos->isEmpty(), 404, 'Nenhum aluno com carteirinha encontrado.');

        // objeto simples só pra passar ano e fundo
        $template = (object) [
            'ano' => now()->year,
            'urlFundo' => public_path('img/fundo-carteirinha-2025.jpg'),
        ];

        return view('pdf.carteirinhas', compact('alunos', 'template'));
    }

    /**
     * Busca a foto do aluno por CGM
     * Procura por extensões: jpeg, jpg, png
     */
    private function buscarFotoAluno(?string $cgm): string
    {
        if (empty($cgm)) {
            return asset('images/avatar-placeholder.png'); // foto padrão
        }

        $extensoes = ['jpeg', 'jpg', 'png'];
        
        foreach ($extensoes as $ext) {
            $caminho = "alunos/{$cgm}.{$ext}";
            
            if (Storage::disk('public')->exists($caminho)) {
                return asset("storage/{$caminho}");
            }
        }

        // Se não encontrar, retorna placeholder
        return asset('images/avatar-placeholder.png');
    }
}