<?php

namespace App\Filament\Widgets;

use App\Models\Rota;
use App\Models\ValorRotaMensal;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ComparativoMensalWidget extends BaseWidget implements HasForms
{
    use InteractsWithForms;

    public ?string $mes_ano = null;

    public ?int $mesSelecionado = null;
    public ?int $anoSelecionado = null;

    public function mount(): void
    {
        $previa = now()->copy()->subMonth();
        $this->mesSelecionado = (int) $previa->month;
        $this->anoSelecionado = (int) $previa->year;

        $mesAno = request()->string('mes_ano')->toString();
        if ($mesAno && preg_match('/^(\d{2})\/(\d{4})$/', $mesAno, $mes)) {
            $this->mesSelecionado = (int) $mes[1];
            $this->anoSelecionado = (int) $mes[2];
        }

        $this->mes_ano = sprintf('%02d/%04d', $this->mesSelecionado, $this->anoSelecionado);

        $this->form->fill([
            'mes_ano' => $this->mes_ano,
        ]);
    }

    protected function getStats(): array
    {
        $agora = now();
        $mAtual = (int) $agora->month;
        $aAtual = (int) $agora->year;

        $totalAtual = (float) Rota::query()->sum('valor_total');

        $mSelecionado = $this->mesSelecionado ?: $mAtual;
        $aSelecionado = $this->anoSelecionado ?: $aAtual;

        $comparacao = ValorRotaMensal::where('mes', $mSelecionado)->where('ano', $aSelecionado)->first();
        $totalComparacao = $this->totalDoSnapshot($comparacao);

        $diferenca = $totalAtual - $totalComparacao;
        $porcentage   = $totalComparacao > 0 ? round(($diferenca / $totalComparacao) * 100, 1) : null;

        $labelAtual = sprintf('Atual (%02d/%d)', $mAtual, $aAtual);

        $prev = $agora->copy()->subMonth();
        $isAnterior = ($mSelecionado === (int)$prev->month && $aSelecionado === (int)$prev->year);
        $labelComp  = ($isAnterior ? 'Anterior' : 'Selecionado') . sprintf(' (%02d/%d)', $mSelecionado, $aSelecionado);

        $descricao = $porcentage === null
            ? 'Sem base para comparar'
            : (($diferenca >= 0 ? '+' : '') . 'R$ ' . number_format($diferenca, 2, ',', '.') . " ({$porcentage}%)");

        $color = $porcentage === null ? 'gray' : ($diferenca >= 0 ? 'danger' : 'success');
        $icon  = $porcentage === null ? 'heroicon-o-minus' : ($diferenca >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down');

        return [
            Stat::make($labelAtual, 'R$ ' . number_format($totalAtual, 2, ',', '.'))
                ->description($descricao)
                ->descriptionIcon($icon)
                ->color($color),

            Stat::make($labelComp, 'R$ ' . number_format($totalComparacao, 2, ',', '.'))
                ->color('gray'),

            Stat::make('Filtrar Mês', view('filament.widgets.filtro-competencia-select', [
                'form' => $this->form,
            ]))
                ->icon('heroicon-o-calendar')
                ->color('primary'),
        ];
    }

    /** Form: Select único (MM/YYYY) carregado por DISTINCT */
    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('mes_ano')
                ->label(false)
                ->options($this->opcoesCompetencias())
                ->searchable()
                ->preload()
                ->native(false)
                ->required(),
        ];
    }

    /** Submit do filtro: atualiza seleção + emite evento p/ gráfico */
    public function filtrar(): void
    {
        $valor = $this->mes_ano ?? '';
        if (!preg_match('/^(\d{2})\/(\d{4})$/', $valor, $mes)) {
            return;
        }

        $this->mesSelecionado = (int) $mes[1];
        $this->anoSelecionado = (int) $mes[2];

        $this->dispatch('competencia-atualizada', mes: $this->mesSelecionado, ano: $this->anoSelecionado);
    }

    private function opcoesCompetencias(): array
    {
        $periodos = ValorRotaMensal::query()
            ->select('mes', 'ano')
            ->distinct()
            ->orderBy('ano', 'desc')
            ->orderBy('mes', 'desc')
            ->get();

        $opcoes = [];
        foreach ($periodos as $periodo) {
            $opcao = sprintf('%02d/%04d', (int) $periodo->mes, (int) $periodo->ano);
            $opcoes[$opcao] = $opcao;
        }
        return $opcoes;
    }

    private function totalDoSnapshot(?ValorRotaMensal $print): float
    {
        if (!$print) return 0.0;

        if (!is_null($print->valor_total_mes)) {
            return (float) $print->valor_total_mes;
        }

        $listagem = $print->valor_total_por_rota ?? [];
        $soma = 0.0;
        foreach ($listagem as $item) {
            $soma += (float) ($item['valor_total'] ?? 0);
        }
        return $soma;
    }
}
