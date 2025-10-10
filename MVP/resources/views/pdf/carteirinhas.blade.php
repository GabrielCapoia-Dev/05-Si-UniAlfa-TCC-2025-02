<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>Carteirinhas para impressão</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
    :root {
      --margem-pagina: 8mm;
      --altura-card: 90mm;
    }

    @page {
      size: A4 portrait;
      margin: var(--margem-pagina);
    }

    @media print {
      .no-print {
        display: none !important;
      }

      body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
    }

    body {
      margin: 0;
      font-family: Arial, Helvetica, sans-serif;
      color: #000;
    }

    .toolbar {
      position: sticky;
      top: 0;
      display: flex;
      gap: .5rem;
      align-items: center;
      justify-content: space-between;
      padding: .75rem 1rem;
      background: #f9fafb;
      border-bottom: 1px solid #e5e7eb;
    }

    .btn {
      appearance: none;
      border: 1px solid #d1d5db;
      background: #111827;
      color: #fff;
      border-radius: .5rem;
      padding: .5rem .9rem;
      font-weight: 600;
      cursor: pointer;
    }

    .btn:hover {
      filter: brightness(.95);
    }

    .pagina {
      width: 100%;
      position: relative;
    }

    .cards {
      font-size: 0;
    }

    .card {
      display: block;
      width: 100%;
      height: var(--altura-card);
      border: none;
      position: relative;
      overflow: visible;
      page-break-inside: avoid;
      background: #fff;
      box-sizing: border-box;
      margin-bottom: 0;
    }

    /* Linha de corte horizontal (pontilhada) */
    .card::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 1px;
      background: repeating-linear-gradient(to right,
          #666 0px,
          #666 3px,
          transparent 3px,
          transparent 7px);
    }

    /* Container interno da carteirinha */
    .card-inner {
      position: relative;
      height: 100%;
      display: flex;
    }

    /* Linha de dobra vertical (pontilhada) */
    .card-inner::after {
      content: '';
      position: absolute;
      top: 0;
      bottom: 0;
      left: 50%;
      width: 1px;
      background: repeating-linear-gradient(to bottom,
          #666 0px,
          #666 3px,
          transparent 3px,
          transparent 7px);
      transform: translateX(-50%);
      z-index: 10;
    }

    .lado {
      width: 50%;
      height: 100%;
      position: relative;
      padding: 5mm;
      box-sizing: border-box;
    }

    /* LADO ESQUERDO (FRENTE) */
    .lado-esquerdo {
      display: flex;
      flex-direction: column;
    }

    .logo-topo {
      display: flex;
      align-items: flex-start;
      gap: 2mm;
      margin-bottom: 2mm;
    }

    .brasao {
      width: 7rem;
      height: 7rem;
      object-fit: contain;
    }

    .texto-umuarama {
      flex: 1;
    }

    .cidade {
      font-size: 14pt;
      font-weight: bold;
      color: #0a5ab0;
      margin: 0;
      line-height: 1;
    }

    .prefeitura {
      font-size: 6.5pt;
      margin: 0;
      line-height: 1.2;
      margin-top: 0.5mm;
    }

    .carteira-badge {
      background: #ffd200;
      color: #000;
      font-size: 6.5pt;
      font-weight: bold;
      padding: 1mm 2mm;
      display: inline-block;
      margin-bottom: 2mm;
      line-height: 1.2;
    }

    .foto-container {
      margin-bottom: 2mm;
    }

    .foto {
      width: 100%;
      max-width: 30mm;
      height: 40mm;
      object-fit: cover;
      border: 1px solid #999;
    }

    .linha-info {
      background: #ffd200;
      padding: 1mm 2mm;
      margin: 2mm 0;
      font-size: 7.5pt;
      font-weight: bold;
    }

    .dados-aluno {
      font-size: 7.5pt;
      line-height: 1.35;
    }

    .dados-aluno div {
      margin-bottom: 1mm;
    }

    .lbl {
      font-weight: bold;
    }

    /* LADO DIREITO (VERSO) */
    .lado-direito {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .topo-direito {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
    }

    .carteira-badge-direita {
      background: #ffd200;
      color: #000;
      font-size: 6.5pt;
      font-weight: bold;
      padding: 1mm 2.5mm;
      margin-bottom: 2mm;
      line-height: 1.2;
      text-align: right;
    }

    .logo-direita {
      display: flex;
      align-items: center;
      gap: 2mm;
      margin-bottom: 3mm;
    }

    .logo-direita .brasao {
      width: 9rem;
      height: 9rem;
    }

    .logo-direita .cidade {
      font-size: 12pt;
    }

    .logo-direita .prefeitura {
      font-size: 6pt;
    }

    .dados-responsavel {
      font-size: 8pt;
      line-height: 1.45;
    }

    .dados-responsavel div {
      margin-bottom: 2mm;
    }

    /* Faixa colorida na parte inferior */
    .faixa {
      position: absolute;
      left: 0;
      right: 0;
      bottom: 0;
      height: 6mm;
      display: flex;
    }

    .fx {
      height: 100%;
    }

    .fx.b1 {
      background: #0a5ab0;
      width: 30%;
    }

    .fx.b2 {
      background: #ffd200;
      width: 10%;
    }

    .fx.b3 {
      background: #ffffff;
      width: 10%;
      border-top: 1px solid #ccc;
    }

    .fx.b4 {
      background: #e31e24;
      width: 10%;
    }

    .fx.b5 {
      background: #0a5ab0;
      width: 40%;
    }

    /* Ícone de tesoura */
    .tesoura {
      position: absolute;
      left: -4mm;
      bottom: -1.5mm;
      font-size: 9pt;
      color: #666;
    }
  </style>
</head>

<body>

  <div class="toolbar no-print">
    <div><strong>Carteirinhas de Transporte Escolar</strong> — {{ now()->format('d/m/Y') }}</div>
    <div>
      <button class="btn" onclick="window.print()">Imprimir</button>
    </div>
  </div>

  @foreach($alunos->chunk(4) as $lote)
  <div class="pagina">
    <div class="cards">
      @foreach($lote as $aluno)
      <div class="card">
        <div class="card-inner">
          {{-- LADO ESQUERDO (FRENTE) --}}
          <div class="lado lado-esquerdo">
            <div class="logo-topo">
              <img class="brasao" src="{{ asset('storage/img/brasao-umuarama.png') }}" alt="Brasão Umuarama">

            </div>

            <div class="carteira-badge">CARTEIRA DE TRANSPORTE ESCOLAR - {{ $template->ano ?? now()->year }}</div>

            <div class="foto-container">
              <img class="foto" src="{{ $aluno->foto_web_url }}" alt="Foto do aluno">
            </div>

            <div class="linha-info">
              <span class="lbl">Linha:</span> {{ $aluno->rota?->nome ?? '-' }}
            </div>

            <div class="dados-aluno">
              <div><span class="lbl">Nome:</span> {{ $aluno->nome }}</div>
              <div><span class="lbl">Endereço:</span> {{ $aluno->endereco }}</div>
              <div><span class="lbl">Bairro:</span> {{ $aluno->bairro ?? '-' }}</div>
              <div><span class="lbl">Escola:</span> {{ $aluno->turma?->escola?->nome ?? '-' }}</div>
              <div>
                <span class="lbl">Série:</span> {{ $aluno->turma?->serie?->nome ?? '-' }}
                &nbsp;&nbsp;
                <span class="lbl">Turno:</span> {{ $aluno->turma?->turno ?? '-' }}
              </div>
            </div>

            <div class="tesoura">✂</div>
          </div>

          {{-- LADO DIREITO (VERSO) --}}
          <div class="lado lado-direito">
            <div class="topo-direito">
              <div class="carteira-badge-direita">
                CARTEIRA DE TRANSPORTE ESCOLAR - {{ $template->ano ?? now()->year }}
              </div>

              <div class="logo-direita">
                <img class="brasao" src="{{ asset('storage/img/brasao-umuarama.png') }}" alt="Brasão Umuarama">

              </div>
            </div>

            <div class="dados-responsavel">
              <div><span class="lbl">Responsável:</span> {{ $aluno->nome_responsavel ?? '-' }}</div>
              <div><span class="lbl">Telefone:</span> {{ $aluno->telefone_responsavel ?? '-' }}</div>
              <div><span class="lbl">CGM:</span> {{ $aluno->cgm }}</div>
            </div>

            <div class="faixa">
              <div class="fx b1"></div>
              <div class="fx b2"></div>
              <div class="fx b3"></div>
              <div class="fx b4"></div>
              <div class="fx b5"></div>
            </div>
          </div>
        </div>
      </div>
      @endforeach

      {{-- Completa com cards vazios para manter layout 2x2 --}}
      @for ($i = $lote->count(); $i < 4; $i++)
        <div class="card">
        <div class="card-inner">
          <div class="lado lado-esquerdo"></div>
          <div class="lado lado-direito"></div>
        </div>
    </div>
    @endfor
  </div>
  </div>

  @if(! $loop->last)
  <div style="page-break-after: always;"></div>
  @endif
  @endforeach

</body>

</html>