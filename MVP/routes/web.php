<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;


Route::get('/', function () {
    return view('home');
});

Route::get('/test', function () {
    return view('test');
});

Route::get('/oauth/redirect/google', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
Route::get('/oauth/callback/google', [GoogleAuthController::class, 'callback'])->name('google.callback');



Route::get('/mock/carteirinhas', function () {
    // Template mockado (objeto anônimo com ano + urlFundo())
    $template = new class {
        public string $ano = '2025';
        public function urlFundo(): string
        {
            return asset('img/fundo-carteirinha-2025.jpg');
        }
    };

    // Helper pra montar um aluno fake com “relações” simples
    $mkAluno = function (int $i) {
        $escola = (object)['nome' => ['Bento Mossurunga', 'Papa Pio XII', 'Sebastião de Mattos', 'São Francisco'][($i - 1) % 4]];
        $turma  = (object)['escola' => $escola, 'serie' => ($i % 2 ? '2ª Série' : '3ª Série'), 'turno' => ($i % 3 ? 'Integral' : 'Manhã')];
        $rota   = (object)['nome' => sprintf('%02d - Morada do Sol / Arco Íris', ($i % 12) + 1)];
        $resp   = (object)['nome' => 'Responsável ' . $i, 'telefone' => '(44) 9' . str_pad((string)rand(10000000, 99999999), 8, '0', STR_PAD_LEFT)];
        return (object)[
            'nome'                  => 'Aluno ' . Str::padLeft((string)$i, 2, '0'),
            'cgm'                   => (string)rand(1000000000, 1999999999),
            'foto_url'              => asset('images/check-green.png'),
            'endereco'              => 'Rua Exemplo, ' . rand(1, 2999),
            'bairro'                => ['Centro', 'Parque Itália', 'Jardim Lisboa', 'Zona I'][($i - 1) % 4],
            'turma'                 => $turma,
            'rota'                  => $rota,
            'responsavel'           => $resp,
            'telefone_responsavel'  => null,   // fallback no Blade já cobre
            'nome_responsavel'      => null,   // fallback no Blade já cobre
            'turno'                 => null,   // usa turno da turma
        ];
    };

    // Gere 8 alunos mock (2 páginas, 4 por página)
    $alunos = collect(range(1, 8))->map($mkAluno);

    return view('pdf.carteirinhas', compact('alunos', 'template'));
})->name('mock.carteirinhas.html');

Route::get('/mock/carteirinhas.pdf', function () {
    $template = new class {
        public string $ano = '2025';
        public function urlFundo(): string
        {
            return asset('img/fundo-carteirinha-2025.jpg');
        }
    };

    $mkAluno = function (int $i) {
        $escola = (object)['nome' => ['Bento Mossurunga', 'Papa Pio XII', 'Sebastião de Mattos', 'São Francisco'][($i - 1) % 4]];
        $turma  = (object)['escola' => $escola, 'serie' => ($i % 2 ? '2ª Série' : '3ª Série'), 'turno' => ($i % 3 ? 'Integral' : 'Manhã')];
        $rota   = (object)['nome' => sprintf('%02d - Morada do Sol / Arco Íris', ($i % 12) + 1)];
        $resp   = (object)['nome' => 'Responsável ' . $i, 'telefone' => '(44) 9' . str_pad((string)rand(10000000, 99999999), 8, '0', STR_PAD_LEFT)];
        return (object)[
            'nome'     => 'Aluno ' . Str::padLeft((string)$i, 2, '0'),
            'cgm'      => (string)rand(1000000000, 1999999999),
            'foto_url' => asset('images/check-green.png'),
            'endereco' => 'Rua Exemplo, ' . rand(1, 2999),
            'bairro'   => ['Centro', 'Parque Itália', 'Jardim Lisboa', 'Zona I'][($i - 1) % 4],
            'turma'    => $turma,
            'rota'     => $rota,
            'responsavel' => $resp,
        ];
    };

    $alunos = collect(range(1, 8))->map($mkAluno);

    $pdf = Pdf::loadView('pdf.carteirinhas', compact('alunos', 'template'))
        ->setPaper('a4', 'landscape');

    return $pdf->stream('carteirinhas-mock.pdf');
})->name('mock.carteirinhas.pdf');
