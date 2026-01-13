<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Carteirinhas para impressão</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        :root {
            --margem-pagina: 8mm;
            --altura-card: 68mm;
            --cor-primaria: #1e40af;
            --cor-secundaria: #f5d20b;
            --cor-destaque: #ffffff;
            --cor-acento: #ef4444;
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
            background: var(--cor-primaria);
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
            padding: 3mm;
            box-sizing: border-box;
        }

        /* LADO ESQUERDO (FRENTE) */
        .lado-esquerdo {
            display: flex;
            flex-direction: column;
        }

        .conteudo-esquerdo {
            display: grid;
            grid-template-columns: 27mm 1fr;
            column-gap: 2.5mm;
            align-items: start;
        }

        .col-dados .linha-info {
            margin: 0 0 1.5mm 0;
        }

        .logo-topo {
            display: flex;
            align-items: flex-start;
            gap: 1.5mm;
            margin-bottom: 1.5mm;
        }

        .brasao {
            width: 6rem;
            height: 6rem;
            object-fit: contain;
        }

        .carteira-badge {
            background: var(--cor-primaria);
            color: #fff;
            font-size: 7pt;
            font-weight: bold;
            padding: 0.8mm 1.5mm;
            display: inline-block;
            margin-bottom: 1.5mm;
            line-height: 1.2;
            border-radius: 2px;
        }

        .foto-container {
            margin-bottom: 0;
            text-align: center;
        }

        .foto {
            width: 25mm;
            height: 30mm;
            object-fit: cover;
            border: 2px solid var(--cor-primaria);
            border-radius: 3px;
        }

        .linha-info {
            background: var(--cor-secundaria);
            color: #000000;
            padding: 0.8mm 1.5mm;
            margin: 1.5mm 0;
            font-size: 9pt;
            font-weight: bold;
            border-radius: 2px;
        }

        .dados-aluno {
            font-size: 6pt;
            line-height: 1.4;
        }

        .dados-aluno div {
            margin-bottom: 0.8mm;
        }

        .lbl {
            font-weight: bold;
            font-size: 9pt;
        }

        .lbl span {
            color: var(--cor-primaria);
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
            background: var(--cor-primaria);
            color: #fff;
            font-size: 7pt;
            font-weight: bold;
            padding: 0.8mm 1.5mm;
            margin-bottom: 1.5mm;
            line-height: 1.2;
            text-align: right;
            border-radius: 2px;
        }

        .logo-direita {
            display: flex;
            align-items: center;
            gap: 1.5mm;
            margin-bottom: 2mm;
        }

        .logo-direita .brasao {
            width: 16mm;
            height: 16mm;
        }

        .logo-direita .texto-umuarama {
            text-align: right;
        }

        .logo-direita .cidade {
            font-size: 9pt;
        }

        .logo-direita .prefeitura {
            font-size: 5pt;
        }

        .dados-responsavel {
            font-size: 6.5pt;
            line-height: 1.5;
            margin-bottom: 2mm;
            background: #f3f4f6;
            padding: 2mm;
            border-radius: 3px;
            border-left: 3px solid var(--cor-secundaria);
        }

        .dados-responsavel div {
            margin-bottom: 1mm;
        }

        .dados-responsavel .lbl span {
            color: var(--cor-secundaria);
        }

        /* Faixa colorida na parte inferior */
        .faixa {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 5mm;
            display: flex;
        }

        .fx {
            height: 100%;
        }

        .fx.b1 {
            background: var(--cor-primaria);
            width: 35%;
        }

        .fx.b2 {
            background: var(--cor-secundaria);
            width: 15%;
        }

        .fx.b3 {
            background: var(--cor-destaque);
            width: 15%;
        }

        .fx.b4 {
            background: var(--cor-acento);
            width: 10%;
        }

        .fx.b5 {
            background: var(--cor-primaria);
            width: 25%;
        }

        /* Ícone de tesoura */
        .tesoura {
            position: absolute;
            left: -3.5mm;
            bottom: -1.5mm;
            font-size: 8pt;
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

    @foreach ($alunos->chunk(4) as $lote)
        <div class="pagina">
            <div class="cards">
                @foreach ($lote as $aluno)
                    <div class="card">
                        <div class="card-inner">
                            {{-- LADO ESQUERDO (FRENTE) --}}
                            <div class="lado lado-esquerdo">
                                <div class="logo-topo">
                                    <img class="brasao" src="{{ asset('images/brasao-umuarama.png') }}" alt="Brasão">
                                </div>

                                <div class="carteira-badge">
                                    CARTEIRA DE TRANSPORTE ESCOLAR - 2026
                                </div>

                                <div class="conteudo-esquerdo">
                                    <div class="col-foto">
                                        <div class="foto-container">
                                            <img class="foto" src="{{ $aluno->foto_web_url }}" alt="Foto do aluno">
                                        </div>
                                    </div>

                                    <div class="col-dados">
                                        <div class="linha-info">
                                            <span>Linha:</span> {{ $aluno->rota?->nome ?? '-' }}
                                        </div>

                                        <div class="dados-aluno">
                                            <div class="lbl">
                                                <span>Nome:</span> {{ $aluno->nome }}
                                            </div>
                                            <div class="lbl"><span>Responsável:</span>
                                                {{ $aluno->nome_responsavel ?? '-' }}
                                            </div>
                                            <div class="lbl"><span>Telefone:</span>
                                                {{ $aluno->telefone_responsavel ?? '-' }}</div>
                                            <div class="lbl">
                                                <span>Escola:</span> {{ $aluno->turma?->escola?->nome ?? '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tesoura">✂</div>
                            </div>


                            {{-- LADO DIREITO (VERSO) --}}
                            <div class="lado lado-direito">
                                <div class="topo-direito">
                                    <div class="carteira-badge-direita">
                                        CARTEIRA DE TRANSPORTE ESCOLAR - 2026
                                    </div>

                                    <div class="logo-direita">
                                        <img class="brasao" src="{{ asset('images/brasao-umuarama.png') }}"
                                            alt="Brasão">
                                    </div>
                                </div>

                                <div class="dados-responsavel">
                                    <div class="lbl">
                                        <span>Endereço:</span>
                                        {{ $aluno->logradouro ?? $aluno->endereco }},
                                        {{ $aluno->numero ?? 'S/N' }}
                                    </div>
                                    <div class="lbl">
                                        <span>Bairro:</span> {{ $aluno->bairro ?? '-' }}
                                    </div>
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

                {{-- Completa com cards vazios para manter layout --}}
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

        @if (!$loop->last)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach

</body>

</html>