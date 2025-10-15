<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AlunoResource\Pages;
use App\Filament\Resources\AlunoResource\Pages\ListAlunos;
use App\Models\Aluno;
use App\Models\Turma;
use App\Models\Rota;
use App\Services\AlunoService;
use App\Services\UserService;
use App\Models\Serie;
use App\Models\Escola;
use App\Services\EscolaService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AlunoResource extends Resource
{
    protected static ?string $model = Aluno::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Alunos';
    protected static ?string $pluralModelLabel = 'Alunos';
    protected static ?string $modelLabel = 'Aluno';
    protected static ?string $navigationGroup = 'Gerenciamento Escolar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ======= DADOS PRINCIPAIS =======
                Forms\Components\Grid::make(5)
                    ->schema([
                        Forms\Components\TextInput::make('nome')
                            ->label('Nome:')
                            ->columnSpan(2)
                            ->required()
                            ->minLength(3)
                            ->maxLength(255)
                            ->rule('regex:/^[\p{L}]+$/u')
                            ->validationMessages([
                                'regex' => 'Use apenas letras, sem caracteres especiais.',
                            ]),

                        Forms\Components\DatePicker::make('data_nascimento')
                            ->label('Data de Nascimento:')
                            ->required(),

                        Forms\Components\TextInput::make('cgm')
                            ->label('CGM:')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20),

                        Forms\Components\Select::make('sexo')
                            ->label('Sexo:')
                            ->placeholder('Selecione')
                            ->required()
                            ->options([
                                'Masculino' => 'Masculino',
                                'Feminino' => 'Feminino',
                            ]),
                    ]),

                // ======= FOTO E ROTAS =======
                Forms\Components\Grid::make(12)
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\FileUpload::make('foto')
                                    ->label('Enviar Foto:')
                                    ->image()
                                    ->directory('alunos')
                                    ->disk('public')
                                    ->visibility('public')
                                    ->panelAspectRatio('1:1')
                                    ->columnSpan(3)
                                    ->getUploadedFileNameForStorageUsing(function ($file, $get) {
                                        return app(AlunoService::class)->salvarFotoComNomeComoCGM($file, $get);
                                    }),

                                Forms\Components\Fieldset::make('Rotas')
                                    ->schema([
                                        Forms\Components\Select::make('id_rota')
                                            ->label('Rota de transporte')
                                            ->options(function (Forms\Get $get) {
                                                return app(AlunoService::class)->opcoesDeRotasParaTurma($get('id_turma'));
                                            })
                                            ->disabled(fn(Forms\Get $get) => blank($get('id_turma')))
                                            ->visible(fn() => app(UserService::class)->ehAdmin())
                                            ->helperText('Lista apenas rotas vinculadas à escola da turma selecionada.')
                                            ->searchable()
                                            ->columnSpanFull()
                                            ->preload(),

                                        Forms\Components\Toggle::make('tem_carteirinha')
                                            ->label('Usa o Transporte?')
                                            ->default(false)
                                            ->columnSpanFull()
                                            ->visible(function () {
                                                $user = Auth::user();
                                                return (app(AlunoService::class)->podeVerToggleCarteirinha($user));
                                            })
                                            ->inline(false)
                                            ->onColor('success')
                                            ->offColor('danger')
                                            ->onIcon('heroicon-s-check')
                                            ->offIcon('heroicon-s-x-mark'),
                                    ])
                                    ->visible(fn() => app(UserService::class)->ehAdmin()),
                            ])
                            ->columnSpan(3),

                        // ======= CONTATO, ENDEREÇO E ESCOLA =======
                        Forms\Components\Grid::make(9)
                            ->schema([

                                // ======= CONTATOS =======
                                Forms\Components\Fieldset::make('Informações para Contato')
                                    ->schema([
                                        Forms\Components\TextInput::make('nome_responsavel')
                                            ->label('Nome do Responsável:')
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('telefone_responsavel')
                                            ->label('Telefone do Responsável:')
                                            ->required()
                                            ->numeric()
                                            ->maxLength(11),

                                        Forms\Components\TextInput::make('telefone_aluno')
                                            ->label('Telefone do Aluno:')
                                            ->nullable()
                                            ->numeric()
                                            ->maxLength(11),

                                        Forms\Components\TextInput::make('telefone_alternativo')
                                            ->label('Telefone Alternativo:')
                                            ->nullable()
                                            ->numeric()
                                            ->maxLength(11),
                                    ]),

                                // ======= ENDEREÇO =======
                                Forms\Components\Fieldset::make('Endereço')
                                    ->schema([
                                        Forms\Components\Grid::make(9)
                                            ->schema([
                                                Forms\Components\TextInput::make('logradouro')
                                                    ->label('Logradouro')
                                                    ->maxLength(255)
                                                    ->columnSpan(5)
                                                    ->disabled(fn(Forms\Get $get) => blank($get('cep')))
                                                    ->required(),

                                                Forms\Components\TextInput::make('numero')
                                                    ->label('Número')
                                                    ->maxLength(6)
                                                    ->nullable()
                                                    ->mask('999999'),

                                                Forms\Components\TextInput::make('cep')
                                                    ->label('CEP')
                                                    ->mask('99999-999')
                                                    ->rules(['regex:/^\d{5}-\d{3}$/'])
                                                    ->validationMessages([
                                                        'regex' => 'O CEP deve estar no formato 00000-000',
                                                    ])
                                                    ->required()
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        $cep = preg_replace('/[^0-9]/', '', $state);
                                                        if (strlen($cep) !== 8) return;

                                                        try {
                                                            $response = Http::timeout(5)->get("https://viacep.com.br/ws/{$cep}/json/");
                                                            if ($response->successful() && !$response->json('erro')) {
                                                                $data = $response->json();
                                                                $set('logradouro', $data['logradouro'] ?? '');
                                                                $set('bairro', $data['bairro'] ?? '');
                                                                $set('cidade', $data['localidade'] ?? '');
                                                                $set('estado', $data['uf'] ?? '');
                                                            }
                                                        } catch (\Exception $e) {
                                                            Log::error("Erro ao consultar CEP: {$e->getMessage()}");
                                                        }
                                                    })
                                                    ->columnSpan(2),
                                            ]),

                                        Forms\Components\Grid::make(8)
                                            ->schema([
                                                Forms\Components\TextInput::make('bairro')
                                                    ->label('Bairro')
                                                    ->maxLength(255)
                                                    ->required(),

                                                Forms\Components\TextInput::make('cidade')
                                                    ->label('Cidade')
                                                    ->maxLength(255)
                                                    ->required(),

                                                Forms\Components\TextInput::make('estado')
                                                    ->label('UF')
                                                    ->maxLength(2)
                                                    ->placeholder('PR, SP, RJ...')
                                                    ->required(),

                                                Forms\Components\TextInput::make('complemento')
                                                    ->label('Complemento')
                                                    ->maxLength(100)
                                                    ->nullable()
                                                    ->placeholder('Ex.: Próximo ao Supermercado'),
                                            ]),
                                    ]),

                                Forms\Components\Fieldset::make('Informações da Escola')
                                    ->schema([
                                        Forms\Components\Select::make('id_escola')
                                            ->label('Escola')
                                            ->options(fn() => app(EscolaService::class)->opcoesDeEscolasParaUsuario(Auth::user()))
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->default(fn($record) => app(AlunoService::class)->escolaInicialParaForm($record, Auth::user()))
                                            ->afterStateHydrated(function ($state, callable $set, $record) {
                                                $set('id_escola', app(AlunoService::class)->escolaInicialParaForm($record, Auth::user()));
                                            })
                                            ->dehydrated(false)
                                            ->disabled(fn() => app(AlunoService::class)->deveTravarCampoEscola(Auth::user()))
                                            ->reactive()
                                            ->afterStateUpdated(fn($state, callable $set) => $set('id_turma', null)),

                                        Forms\Components\Select::make('id_turma')
                                            ->label('Turma')
                                            ->options(function (Forms\Get $get, $record) {
                                                $idEscola = $get('id_escola') ?? $record?->turma?->id_escola;
                                                return app(AlunoService::class)->opcoesDeTurmasParaEscola($idEscola);
                                            })
                                            ->searchable()
                                            ->required()
                                            ->disabled(function (Forms\Get $get, $record) {
                                                $idEscola = $get('id_escola') ?? $record?->turma?->id_escola;
                                                return app(AlunoService::class)->desabilitarSelectTurma($idEscola);
                                            })
                                            ->reactive()
                                            ->placeholder('Selecione a escola primeiro'),
                                    ]),

                            ])
                            ->columnSpan(9),
                    ]),
            ]);
    }

    // ================== TABELA ==================
    public static function table(Table $table): Table
    {
        $user = Auth::user();
        return app(AlunoService::class)->configurarTabela($table, $user);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAlunos::route('/'),
            'create' => Pages\CreateAluno::route('/create'),
            'edit' => Pages\EditAluno::route('/{record}/edit'),
        ];
    }
}
