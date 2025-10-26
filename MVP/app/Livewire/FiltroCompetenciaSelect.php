<?php

namespace App\Livewire;

use App\Models\ValorRotaMensal;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Livewire\Component;
use Filament\Facades\Filament;

class FiltroCompetenciaSelect extends Component implements HasForms
{
    use InteractsWithForms;

    public ?string $mes_ano = null;

    public function mount(): void
    {
        $this->mes_ano = $this->valorInicial();
        $this->form->fill(['mes_ano' => $this->mes_ano]);
    }



    /** Um único campo Select (MM/YYYY) populado via banco (distinct mes/ano) */
    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('mes_ano')
                ->label('Filtrar Mês')
                ->options($this->opcoesCompetencias())
                ->searchable()
                ->preload()
                ->native(false)
                ->required(),
        ];
    }

    public function filtrar(): void
    {
        $valor = $this->mes_ano ?: '';

        if (!preg_match('/^\d{2}\/\d{4}$/', $valor)) {
            return;
        }
        $base = Filament::getCurrentPanel()->getUrl();

        $this->redirect($base . '?' . http_build_query(['mes_ano' => $valor]), navigate: true);
    }

    private function opcoesCompetencias(): array
    {
        return ValorRotaMensal::query()
            ->select('mes', 'ano')
            ->distinct()
            ->orderByDesc('ano')
            ->orderByDesc('mes')
            ->get()
            ->mapWithKeys(function ($periodo) {
                $opcao = sprintf('%02d/%04d', (int) $periodo->mes, (int) $periodo->ano);
                return [$opcao => $opcao];
            })
            ->all();
    }

    private function valorInicial(): string
    {
        $mesAno = request()->string('mes_ano')->toString();
        if ($mesAno && preg_match('/^\d{2}\/\d{4}$/', $mesAno)) {
            return $mesAno;
        }
        $mes = request()->integer('mes') ?: (int) now()->month;
        $ano = request()->integer('ano') ?: (int) now()->year;
        return sprintf('%02d/%04d', $mes, $ano);
    }

    public function render()
    {
        return view('livewire.filtro-competencia-select');
    }
}
