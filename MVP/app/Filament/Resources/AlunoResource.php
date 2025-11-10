<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AlunoResource\Pages;
use App\Models\Aluno;
use App\Services\AlunoService;
use App\Services\UserService;
use App\Services\EscolaService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
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
                            ->maxLength(100)
                            ->rule('regex:/^[\p{L}\p{N}]+(?: [\p{L}\p{N}]+)*$/u')
                            ->validationMessages([
                                'regex' => 'Use apenas letras, sem caracteres especiais.',
                            ]),

                        Forms\Components\DatePicker::make('data_nascimento')
                            ->label('Data de Nascimento:')
                            ->required()
                            ->maxDate(Carbon::today()->subYears(4))
                            ->rule(fn() => 'before_or_equal:' . Carbon::today()->subYears(4)->toDateString())
                            ->validationMessages([
                                'before_or_equal' => 'A criança precisa ter pelo menos 4 anos.',
                            ]),

                        Forms\Components\TextInput::make('cgm')
                            ->label('CGM:')
                            ->required()
                            ->minLength(6)
                            ->rules(['regex:/^\d+$/'])
                            ->validationMessages([
                                'regex' => 'Apenas numeros',
                                'min' => 'O CGM deve ter no mínimo 6 dígitos.',
                            ])
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
                                            ->preload()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                $set('tem_carteirinha', filled($state));
                                            }),

                                        Forms\Components\Toggle::make('tem_carteirinha')
                                            ->label('Usa o Transporte?')
                                            ->default(false)
                                            ->columnSpanFull()
                                            ->visible(fn() => app(UserService::class)->ehAdmin())
                                            ->inline(false)
                                            ->onColor('success')
                                            ->offColor('danger')
                                            ->onIcon('heroicon-s-check')
                                            ->offIcon('heroicon-s-x-mark')
                                            ->disabled()
                                            ->dehydrated(true),
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
                                            ->maxLength(100)
                                            ->minLength(3)
                                            ->rule('regex:/^[\p{L}\p{N}]+(?: [\p{L}\p{N}]+)*$/u')
                                            ->validationMessages([
                                                'regex' => 'Use apenas letras, sem caracteres especiais.',
                                            ]),

                                        Forms\Components\TextInput::make('telefone_responsavel')
                                            ->label('Telefone do Responsável:')
                                            ->required()
                                            ->mask('(99)99999-9999')
                                            ->rules(['regex:/^\(\d{2}\)\d{5}-\d{4}$/'])
                                            ->validationMessages([
                                                'regex' => 'O telefone deve estar no formato (99)99999-9999',
                                            ])
                                            ->maxLength(14),

                                        Forms\Components\TextInput::make('telefone_aluno')
                                            ->label('Telefone do Aluno:')
                                            ->nullable()
                                            ->mask('(99)99999-9999')
                                            ->rules(['regex:/^\(\d{2}\)\d{5}-\d{4}$/'])
                                            ->validationMessages([
                                                'regex' => 'O telefone deve estar no formato (99)99999-9999',
                                            ])
                                            ->maxLength(14),

                                        Forms\Components\TextInput::make('telefone_alternativo')
                                            ->label('Telefone Alternativo:')
                                            ->nullable()
                                            ->mask('(99)99999-9999')
                                            ->rules(['regex:/^\(\d{2}\)\d{5}-\d{4}$/'])
                                            ->validationMessages([
                                                'regex' => 'O telefone deve estar no formato (99)99999-9999',
                                            ])
                                            ->maxLength(14),
                                    ]),

                                // ======= ENDEREÇO =======
                                Forms\Components\Fieldset::make('Endereço')
                                    ->schema([
                                        Forms\Components\Grid::make(9)
                                            ->schema([
                                                Forms\Components\TextInput::make('logradouro')
                                                    ->label('Logradouro')
                                                    ->maxLength(100)
                                                    ->columnSpan(5)
                                                    ->disabled(fn(Forms\Get $get) => blank($get('cep')))
                                                    ->required()
                                                    ->minLength(3)
                                                    ->rule('regex:/^\p{L}+(?:\s\p{L}+)*$/u')
                                                    ->validationMessages([
                                                        'regex' => 'Use apenas letras e um espaço simples entre palavras.',
                                                    ]),

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
                                                    ->maxLength(100)
                                                    ->minLength(2)
                                                    ->rule('regex:/^\p{L}+(?:\s\p{L}+)*$/u')
                                                    ->validationMessages([
                                                        'regex' => 'Use apenas letras e um espaço simples entre palavras.',
                                                    ])
                                                    ->required(),

                                                Forms\Components\TextInput::make('cidade')
                                                    ->label('Cidade')
                                                    ->maxLength(100)
                                                    ->required()
                                                    ->minLength(3)
                                                    ->rule('regex:/^\p{L}+(?:\s\p{L}+)*$/u')
                                                    ->validationMessages([
                                                        'regex' => 'Use apenas letras e um espaço simples entre palavras.',
                                                    ]),

                                                Forms\Components\TextInput::make('estado')
                                                    ->label('UF')
                                                    ->maxLength(2)
                                                    ->placeholder('PR, SP, RJ...')
                                                    ->required()
                                                    ->rule('regex:/^\p{L}+(?:\s\p{L}+)*$/u')
                                                    ->validationMessages([
                                                        'regex' => 'Use apenas letras e um espaço simples entre palavras.',
                                                    ]),

                                                Forms\Components\TextInput::make('complemento')
                                                    ->label('Complemento')
                                                    ->maxLength(100)
                                                    ->nullable()
                                                    ->placeholder('Ex.: Próximo ao Supermercado')
                                                    ->minLength(3)
                                                    ->rule('regex:/^\p{L}+(?:\s\p{L}+)*$/u')
                                                    ->validationMessages([
                                                        'regex' => 'Use apenas letras e um espaço simples entre palavras.',
                                                    ]),
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
        $turmaId  = request()->integer('turma');
        return app(AlunoService::class)->configurarTabela($table, $user, $turmaId);
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
