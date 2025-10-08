{{-- resources/views/pdf/carteirinhas.blade.php --}}
@php
    $fundo = is_object($template) && method_exists($template, 'urlFundo')
        ? $template->urlFundo()
        : public_path('img/fundo-carteirinha-2025.jpg'); // use caminho local p/ DomPDF
@endphp
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
  @page { margin: 8mm; }
  body { margin: 0; font-family: DejaVu Sans, sans-serif; }

  /* container da página */
  .pagina { width: 100%; }

  /* linha que conterá até 2 cards por linha (usando inline-block) */
  .cards { font-size: 0; } /* remove gap do inline-block */

  /* cada carteirinha: 2 na linha, 2 linhas = 4 por página */
  .card {
    display: inline-block;
    width: calc(50% - 6mm);
    height: 90mm;                 /* ajuste fino: altura da carteirinha */
    margin: 3mm;                  /* espaço entre elas */
    border: 1px solid #ddd;
    position: relative;
    vertical-align: top;
    overflow: hidden;
    page-break-inside: avoid;
  }

  /* fundo como IMG absoluta (mais confiável que background CSS no DomPDF) */
  .bg {
    position: absolute; left: 0; top: 0; right: 0; bottom: 0;
    width: 100%; height: 100%;
    object-fit: cover; opacity: .22;
  }

  /* miolo em tabela (compatibilidade DomPDF) */
  .wrap { position: relative; padding: 6mm; height: 100%; }
  .tbl  { width: 100%; border-collapse: collapse; }
  .col-foto { width: 42%; vertical-align: top; padding-right: 4mm; }
  .col-dados{ width: 58%; vertical-align: top; }

  .foto  { width: 100%; height: 58mm; object-fit: cover; border-radius: 4px; border: 1px solid #ccc; }
  .tit   { font-size: 12pt; font-weight: 700; margin: 0 0 2mm; }
  .lbl   { font-weight: 700; }
  .linha { font-size: 10pt; margin: 1mm 0 3mm; }
  .box   { font-size: 9.6pt; line-height: 1.25; }
  .mt4   { margin-top: 4mm; }

  .faixa { position: absolute; left:0; right:0; bottom:0; height: 6mm; display:flex; }
  .fx    { height:100%; }
  .fx.b1 { background:#0a5ab0; width: 30%; }
  .fx.b2 { background:#ffd200; width: 10%; }
  .fx.b3 { background:#ffffff; width: 10%; border-top: 1px solid #eee; border-bottom: 1px solid #eee; box-sizing: border-box; }
  .fx.b4 { background:#e31e24; width: 10%; }
  .fx.b5 { background:#0a5ab0; width: 40%; }
</style>
</head>
<body>
@foreach($alunos->chunk(4) as $lote)
  <div class="pagina">
    <div class="cards">
      @foreach($lote as $aluno)
        <div class="card">
          <img class="bg" src="{{ $fundo }}">
          <div class="wrap">
            <table class="tbl">
              <tr>
                <td class="col-foto">
                  <img class="foto" src="{{ $aluno->foto_url ?? public_path('img/placeholder-foto.jpg') }}">
                </td>
                <td class="col-dados">
                  <p class="tit">Umuarama — Carteira de Transporte Escolar {{ $template->ano }}</p>
                  <p class="linha"><span class="lbl">Linha:</span> {{ $aluno->rota?->nome ?? '-' }}</p>

                  <div class="box">
                    <div><span class="lbl">Nome:</span> {{ $aluno->nome }}</div>
                    <div><span class="lbl">Endereço:</span> {{ $aluno->endereco }}</div>
                    <div><span class="lbl">Bairro:</span> {{ $aluno->bairro }}</div>
                    <div><span class="lbl">Escola:</span> {{ $aluno->turma?->escola?->nome ?? '-' }}</div>
                    <div>
                      <span class="lbl">Série:</span> {{ $aluno->turma?->serie ?? '-' }}
                      &nbsp;&nbsp;&nbsp;
                      <span class="lbl">Turno:</span> {{ $aluno->turma?->turno ?? $aluno->turno ?? '-' }}
                    </div>
                  </div>

                  <div class="box mt4">
                    <div><span class="lbl">Responsável:</span> {{ $aluno->responsavel?->nome ?? $aluno->nome_responsavel ?? '-' }}</div>
                    <div><span class="lbl">Telefone:</span> {{ $aluno->responsavel?->telefone ?? $aluno->telefone_responsavel ?? '-' }}</div>
                    <div><span class="lbl">CGM:</span> {{ $aluno->cgm }}</div>
                  </div>
                </td>
              </tr>
            </table>
          </div>

          <div class="faixa">
            <div class="fx b1"></div><div class="fx b2"></div><div class="fx b3"></div><div class="fx b4"></div><div class="fx b5"></div>
          </div>
        </div>
      @endforeach

      {{-- completa 4 por página --}}
      @for ($i = $lote->count(); $i < 4; $i++)
        <div class="card"></div>
      @endfor
    </div>
  </div>

  @if(! $loop->last)
    <div style="page-break-after: always;"></div>
  @endif
@endforeach
</body>
</html>
