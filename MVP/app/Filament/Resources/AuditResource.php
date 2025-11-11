<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditResource\Pages;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Rmsramos\Activitylog\Resources\ActivitylogResource;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity as ActivityModel;
use Rmsramos\Activitylog\Models\Activity;

class AuditResource extends ActivitylogResource
{
    protected static function resolveSubjectName(ActivityModel $record): ?string
    {
        // 1) subject já carregado
        $subject = $record->subject; // morphTo
        if ($subject) {
            foreach (['getDisplayName', 'displayName'] as $method) {
                if (method_exists($subject, $method)) {
                    try {
                        $val = $subject->{$method}();
                        if ($val) return (string) $val;
                    } catch (\Throwable) {
                    }
                }
            }
            foreach (['nome', 'name', 'titulo', 'descricao'] as $attr) {
                $val = $subject->{$attr} ?? null;
                if (is_string($val) && $val !== '') return $val;
            }
        }

        // 2) carregar do banco (útil em deleted/restore)
        $class = $record->subject_type;
        $id    = $record->subject_id;
        if ($class && $id) {
            try {
                $query = method_exists($class, 'withTrashed') ? $class::withTrashed() : $class::query();
                $model = $query->find($id);
                if ($model) {
                    foreach (['getDisplayName', 'displayName'] as $method) {
                        if (method_exists($model, $method)) {
                            try {
                                $val = $model->{$method}();
                                if ($val) return (string) $val;
                            } catch (\Throwable) {
                            }
                        }
                    }
                    foreach (['nome', 'name', 'titulo', 'descricao'] as $attr) {
                        $val = $model->{$attr} ?? null;
                        if (is_string($val) && $val !== '') return $val;
                    }
                }
            } catch (\Throwable) {
            }
        }

        // 3) subject_labels gravados pela trait
        $labels = (array) data_get($record->properties, 'subject_labels', []);
        if ($labels) {
            $typeKey = $class ? \Illuminate\Support\Str::of($class)->afterLast('\\')->lower()->value() : null;
            if ($typeKey && !empty($labels[$typeKey])) return (string) $labels[$typeKey];
            foreach (['aluno', 'turma', 'escola'] as $k) {
                if (!empty($labels[$k])) return (string) $labels[$k];
            }
            $first = reset($labels);
            if (is_string($first) && $first !== '') return $first;
        }

        // 4) diff (attributes / old)
        foreach (['attributes', 'old'] as $bag) {
            $arr = (array) data_get($record->properties, $bag, []);
            foreach (['nome', 'name', 'titulo', 'descricao'] as $key) {
                $val = $arr[$key] ?? null;
                if (is_string($val) && $val !== '') return $val;
            }
        }

        return null;
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ===================== RESUMO (topo) =====================
                Split::make([
                    Section::make('Resumo')
                        ->schema([
                            Placeholder::make('causer_id')
                                ->label('Usuário')
                                ->content(function (?Model $record): string {

                                    return $record?->causer?->name ?? 'Sistema';
                                }),

                            Placeholder::make('subject_type')
                                ->label('Alvo')
                                ->content(function (?Model $record): string {

                                    if (! $record) return '-';

                                    // tenta extrair nome amigável do alvo
                                    $name = static::resolveSubjectName($record);


                                    // por fim, monta "Nome (Tipo #ID)" ou só o nome se não houver ID/Tipo
                                    $type = $record->subject_type ? \Illuminate\Support\Str::of($record->subject_type)->afterLast('\\')->headline() : null;
                                    $id   = $record->subject_id;

                                    if ($name && $type && $id)   return "{$name}";
                                    if ($name && $type && !$id)  return "{$name} ({$type})";
                                    if ($name)                   return $name;


                                    // último fallback (quase nunca cai aqui)
                                    return $type && $id ? "{$type} #{$id}" : ($type ?: '-');
                                }),


                            Textarea::make('description')
                                ->label('Descrição')
                                ->rows(2)
                                ->disabled(true)
                                ->columnSpanFull(),
                        ]),

                    Section::make('Evento')
                        ->schema([
                            Placeholder::make('log_name')
                                ->label('Log')
                                ->content(fn(?Model $record) => $record?->log_name ? ucwords($record->log_name) : '-'),

                            Placeholder::make('event')
                                ->label('Ação')
                                ->content(function (?Model $record): string {

                                    return $record?->event ? ucfirst($record->event) : '-';
                                }),

                            Placeholder::make('created_at')
                                ->label('Quando')
                                ->content(function (?Model $record): string {

                                    return $record?->created_at
                                        ? $record->created_at->format(config('filament-activitylog.datetime_format', 'd/m/Y H:i:s'))
                                        : '-';
                                }),
                        ])->grow(false),
                ])->from('md'),

                Section::make('Alterações')
                    ->columns()
                    ->visible(fn($record) => $record->properties?->count() > 0)
                    ->schema(function (?Model $record) {
                        /** @var Activity&ActivityModel $record */
                        $properties = $record->properties->except(['attributes', 'old']);

                        $schema = [];

                        if ($old = $record->properties->get('old')) {
                            $schema[] = KeyValue::make('old')
                                ->formatStateUsing(fn() => self::formatDateValues($old))
                                ->label(__('activitylog::forms.fields.old.label'));
                        }

                        if ($attributes = $record->properties->get('attributes')) {
                            $schema[] = KeyValue::make('attributes')
                                ->formatStateUsing(fn() => self::formatDateValues($attributes))
                                ->label(__('activitylog::forms.fields.attributes.label'));
                        }

                        return $schema;
                    }),
            ])->columns(1);
    }

    /**
     * Helper opcional — formata datas encontrado valores DateTime/Carbon em arrays/strings.
     * Se você já possui esse helper na classe, pode remover este aqui.
     */
    protected static function formatDateValues($data)
    {
        if (is_array($data)) {
            return collect($data)->map(function ($v) {
                if ($v instanceof \DateTimeInterface) {
                    return $v->format('d/m/Y H:i:s');
                }
                return is_array($v) ? self::formatDateValues($v) : $v;
            })->toArray();
        }
        if ($data instanceof \DateTimeInterface) {
            return $data->format('d/m/Y H:i:s');
        }
        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Quando')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                TextColumn::make('causer_display')
                    ->label('Usuário')
                    ->getStateUsing(fn($record) => $record->causer?->name ?: 'Sistema')
                    ->searchable(query: function (Builder $query, string $search) {
                        return $query
                            ->when(
                                strcasecmp($search, 'Sistema') === 0,
                                fn($q) => $q->orWhereNull('causer_id'),
                                fn($q) => $q->orWhereHas('causer', fn($cq) => $cq->where('name', 'like', "%{$search}%"))
                            );
                    })
                    ->sortable(),

                TextColumn::make('acao')
                    ->label('Ação')
                    ->getStateUsing(function (ActivityModel $record) {
                        $props = $record->properties;

                        // properties pode ser Collection, array ou string JSON — normalizamos:
                        if (is_string($props)) {
                            try {
                                $props = json_decode($props, true, 512, JSON_THROW_ON_ERROR);
                            } catch (\Throwable) {
                                $props = [];
                            }
                        } elseif ($props instanceof \Illuminate\Support\Collection) {
                            $props = $props->toArray();
                        } elseif (! is_array($props)) {
                            $props = (array) $props;
                        }

                        $perm = data_get($props, 'policy_permission');

                        return $perm ?: (ucfirst((string) $record->event) ?: '-');
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subject_display')
                    ->label('Alvo')
                    ->wrap()
                    ->state(function (ActivityModel $record) {
                        $name = static::resolveSubjectName($record);
                        $type = $record->subject_type ? \Illuminate\Support\Str::of($record->subject_type)->afterLast('\\')->headline() : null;
                        $id   = $record->subject_id;
                        $suffix = $type ? ($id ? " ({$type} #{$id})" : " ({$type})") : '';
                        return $name ? "{$name}{$suffix}" : ($type && $id ? "{$type} #{$id}" : ($type ?: '-'));
                    })
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(120)
                    ->wrap()
                    ->tooltip(fn($state) => $state)
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([

                SelectFilter::make('model')
                    ->label('Modelo')
                    ->options(fn() => static::modelFilterOptions())
                    ->multiple()
                    ->searchable()
                    ->placeholder('Todos')
                    ->query(function (Builder $query, array $data) {
                        $vals = $data['values'] ?? [];
                        if (empty($vals)) return $query;

                        $includeNull = in_array('__null__', $vals, true);
                        $types = array_values(array_filter($vals, fn($v) => $v !== '__null__'));

                        return $query->where(function ($q) use ($types, $includeNull) {
                            if (!empty($types)) {
                                $q->whereIn('subject_type', $types);
                            }
                            if ($includeNull) {
                                $q->orWhereNull('subject_type');
                            }
                        });
                    }),

                SelectFilter::make('event')
                    ->label('Ação')
                    ->options([
                        'created'  => 'Created',
                        'updated'  => 'Updated',
                        'deleted'  => 'Deleted',
                    ])
                    ->multiple(),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    protected static function modelFilterOptions(): array
    {
        // Coleta os tipos distintos presentes nos logs
        $rows = ActivityModel::query()
            ->select('subject_type')
            ->distinct()
            ->pluck('subject_type')
            ->filter(); // remove null/empty

        // Mapeia FQCN -> Label (basename ‘bonito’)
        $options = [];
        foreach ($rows as $fqcn) {
            $label = \Illuminate\Support\Str::of(class_basename($fqcn))
                ->headline() // “Turma”, “Escola”, “Dominio Email”, etc.
                ->value();
            $options[$fqcn] = $label;
        }

        // Ordena alfabeticamente
        asort($options);

        // Acrescenta uma opção para logs sem subject (ex.: eventos do sistema)
        // chave especial "__null__"
        $options = ['__null__' => 'Sem alvo'] + $options;

        return $options;
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAudits::route('/'),
            'view' => Pages\ViewAudit::route('/{record}/view'),
        ];
    }
}
