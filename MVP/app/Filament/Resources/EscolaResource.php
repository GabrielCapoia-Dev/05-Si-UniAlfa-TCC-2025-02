<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EscolaResource\Pages;
use App\Models\Escola;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;
use Filament\Forms\Components\Grid;
use App\Forms\Components\Mapa;
use App\Services\EscolaService;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;

class EscolaResource extends Resource
{
    protected static ?string $model = Escola::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Escolas';

    protected static ?string $pluralModelLabel = 'Escolas';

    protected static ?string $modelLabel = 'Escola';

    protected static ?string $navigationGroup = 'Gerenciamento Escolar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(12)
                    ->schema([
                        Mapa::make('pontos')
                            ->label('Localização da Escola')
                            ->dehydrated(false)
                            ->rotaAtiva(false)
                            ->afterStateHydrated(function ($set, $get) {
                                $lat = $get('latitude');
                                $lng = $get('longitude');
                                if ($lat && $lng) {
                                    $set('pontos', [[
                                        'latitude' => (float)$lat,
                                        'longitude' => (float)$lng,
                                        'ordem'    => 1,
                                    ]]);
                                } else {
                                    $set('pontos', []);
                                }
                            })
                            ->afterStateUpdated(function ($state, $set) {
                                if (is_array($state) && !empty($state[0])) {
                                    $lat = $state[0]['latitude'] ?? null;
                                    $lng = $state[0]['longitude'] ?? null;

                                    $set('latitude', $lat);
                                    $set('longitude', $lng);

                                    if (!$lat || !$lng) return;

                                    if ($lat && $lng) {
                                        try {
                                            $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&addressdetails=1";
                                            $response = \Illuminate\Support\Facades\Http::withHeaders([
                                                'User-Agent' => 'SME-Umuarama/1.0 (gabriel.capoia@edu.umuarama.pr.gov.br)',
                                            ])->get($url);

                                            if ($response->successful()) {
                                                $data = $response->json();
                                                $address = $data['address'] ?? [];

                                                $cep = $address['postcode'] ?? null;

                                                $set('logradouro', $address['road'] ?? '');
                                                $set('bairro', $address['suburb'] ?? $address['neighbourhood'] ?? '');
                                                $set('cidade', $address['city'] ?? $address['town'] ?? $address['village'] ?? '');
                                                $set('estado', $address['state'] ?? '');
                                                $set('cep', $cep ?? '');

                                                // 🔎 Se o CEP for brasileiro, normaliza pelo ViaCEP
                                                if ($cep && preg_match('/^\d{5}-?\d{3}$/', $cep)) {
                                                    $cepNum = preg_replace('/[^0-9]/', '', $cep);
                                                    $viaCep = \Illuminate\Support\Facades\Http::get("https://viacep.com.br/ws/{$cepNum}/json/");
                                                    if ($viaCep->successful() && !$viaCep->json('erro')) {
                                                        $br = $viaCep->json();
                                                        $set('logradouro', $br['logradouro'] ?? '');
                                                        $set('bairro', $br['bairro'] ?? '');
                                                        $set('cidade', $br['localidade'] ?? '');
                                                        $set('estado', $br['uf'] ?? '');
                                                        $set('cep', $br['cep'] ?? $cep);
                                                    }
                                                }
                                            }
                                        } catch (\Exception $e) {
                                            logger()->error("Erro ao consultar geocoding: {$e->getMessage()}");
                                        }
                                    }
                                }
                            })

                            ->columnSpan(5),


                        Hidden::make('latitude')->dehydrated(true),
                        Hidden::make('longitude')->dehydrated(true),


                        Grid::make(12)
                            ->schema([
                                Forms\Components\TextInput::make('nome')
                                    ->label('Nome:')
                                    ->columnSpan(2)
                                    ->required()
                                    ->minLength(3)
                                    ->maxLength(255)
                                    ->rule('regex:/^\p{L}+(?:\s\p{L}+)*$/u')
                                    ->validationMessages([
                                        'regex' => 'Use apenas letras, sem caracteres especiais.',
                                    ])
                                    ->unique(ignoreRecord: true)
                                    ->columnSpan(7),

                                Forms\Components\Fieldset::make('Informações de Endereço')
                                    ->schema([
                                        Forms\Components\Grid::make(8)
                                            ->schema([
                                                Forms\Components\TextInput::make('logradouro')
                                                    ->label('Logradouro')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->columnSpan(4)
                                                    ->disabled(false),

                                                Forms\Components\TextInput::make('numero')
                                                    ->label('Número')
                                                    ->nullable()
                                                    ->mask('999999')
                                                    ->rule('regex:/^[0-9]{0,6}$/')
                                                    ->maxLength(6)
                                                    ->columnSpan(2),

                                                Forms\Components\TextInput::make('cep')
                                                    ->label('CEP')
                                                    ->required()
                                                    ->mask('99999-999')
                                                    ->rule('regex:/^\d{5}-\d{3}$/')
                                                    ->maxLength(9)
                                                    ->columnSpan(2)
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        $cep = preg_replace('/[^0-9]/', '', $state);
                                                        if (strlen($cep) !== 8) return;

                                                        try {
                                                            $response = Http::get("https://viacep.com.br/ws/{$cep}/json/");
                                                            if ($response->successful() && !$response->json('erro')) {
                                                                $data = $response->json();
                                                                $set('logradouro', $data['logradouro'] ?? '');
                                                                $set('bairro', $data['bairro'] ?? '');
                                                                $set('cidade', $data['localidade'] ?? '');
                                                                $set('estado', $data['uf'] ?? '');
                                                            }
                                                        } catch (\Exception $e) {
                                                            logger()->error("Erro ao consultar CEP: {$e->getMessage()}");
                                                        }
                                                    }),
                                            ]),

                                        Forms\Components\Grid::make(8)
                                            ->schema([
                                                Forms\Components\TextInput::make('bairro')
                                                    ->label('Bairro')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->columnSpan(3)
                                                    ->disabled(false),

                                                Forms\Components\TextInput::make('cidade')
                                                    ->label('Cidade')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->columnSpan(3)
                                                    ->disabled(false),

                                                Forms\Components\TextInput::make('estado')
                                                    ->label('Estado')
                                                    ->required()
                                                    ->placeholder('ex.: PR, SP, RJ.')
                                                    ->maxLength(2)
                                                    ->columnSpan(2)
                                                    ->disabled(false),
                                            ]),

                                        Forms\Components\TextInput::make('complemento')
                                            ->label('Complemento Endereço')
                                            ->nullable()
                                            ->placeholder('Ex.: Próximo ao Supermercado')
                                            ->maxLength(100),
                                    ]),
                                Forms\Components\Select::make('tipo')
                                    ->label('Tipo')
                                    ->required()
                                    ->options([
                                        'Municipal' => 'Municipal',
                                        'Estadual' => 'Estadual',
                                    ])
                                    ->columnSpan(7),
                            ])
                            ->columnSpan(7),

                    ]),
            ]);
    }



    public static function table(Table $table): Table
    {
        $user = Auth::user();
        return app(EscolaService::class)->configurarTabela($table, $user);
    }



    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageEscolas::route('/'),
        ];
    }
}
