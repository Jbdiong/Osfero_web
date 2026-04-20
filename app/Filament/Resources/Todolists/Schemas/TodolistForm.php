<?php

namespace App\Filament\Resources\Todolists\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;

class TodolistForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Grid::make(4)
                    ->schema([
                        // LEFT COLUMN (Main Content) - Span 2
                        \Filament\Forms\Components\Group::make()
                            ->columnSpan(2)
                            ->schema([
                                \Filament\Forms\Components\Section::make()
                                    ->schema([
                                        TextInput::make('Title')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull()
                                            ->extraInputAttributes(['class' => 'text-2xl font-bold']), // Larger title
                                        Textarea::make('Description')
                                            ->rows(10)
                                            ->columnSpanFull(),
                                    ]),
                                    \Filament\Forms\Components\Section::make('Dates')
                                    ->schema([
                                        DatePicker::make('start_date')
                                            ->native(false)
                                            ->default(today())
                                            ->required(),
                                        DatePicker::make('end_date')
                                            ->native(false)
                                            ->required()
                                            ->afterOrEqual('start_date'),
                                    ]),
                                    \Filament\Forms\Components\Section::make('Subtasks')
                            ->schema([
                                \Filament\Forms\Components\Repeater::make('children')
                                    ->relationship('children')
                                    ->schema([
                                        TextInput::make('Title')
                                            ->required(),
                                        Select::make('status_id')
                                            ->label('Status')
                                            ->relationship('status', 'name', fn ($query) => $query->whereHas('parent', fn ($q) => $q->where('name', 'Todolist Status'))->orderBy('id'))
                                            ->required()
                                            ->default(fn () => \App\Models\Lookup::whereHas('parent', fn ($q) => $q->where('name', 'Todolist Status'))->where('name', 'To do')->first()?->id),
                                        DatePicker::make('start_date')
                                            ->native(false),
                                        DatePicker::make('end_date')
                                            ->native(false)
                                            ->afterOrEqual('start_date'),
                                        \Filament\Forms\Components\Hidden::make('tenant_id')
                                            ->default(fn () => auth()->user()?->tenant_id),
                                    ])
                                    ->columns(4)
                                    ->defaultItems(0)
                                    ->addActionLabel('Add Subtask'),
                            ]),

                            ]),

                        // RIGHT COLUMN (Sidebar) - Span 1
                        \Filament\Forms\Components\Group::make()
                            ->columnSpan(2)
                            ->schema([
                                \Filament\Forms\Components\Section::make('Status & Priority')
                                    ->schema([
                                        Select::make('status_id')
                                            ->relationship('status', 'name', fn ($query) => $query->whereHas('parent', fn ($q) => $q->where('name', 'Todolist Status'))->orderBy('id'))
                                            ->default(request()->query('status_id'))
                                            ->required(),
                                        Select::make('priority_id')
                                            ->relationship('priority', 'name', fn ($query) => $query->whereHas('parent', fn ($q) => $q->where('name', 'Priority'))->orderBy('id'))
                                            ->default(null),
                                    ]),

                                
                                \Filament\Forms\Components\Section::make('Person in Charge (PICs)')
                                    ->schema([
                                        \Filament\Forms\Components\CheckboxList::make('pics')
                                            ->label('Select PICs')
                                            ->relationship('pics', 'name', fn ($query) => $query->whereHas('tenants', fn ($q) => $q->where('tenants.id', auth()->user()->tenant_id)))
                                            ->required()
                                            ->searchable()
                                            ->bulkToggleable()
                                            ->columns(1)
                                            ->saveRelationshipsUsing(function ($record, $state) {
                                                $record->pics()->syncWithPivotValues($state, ['tenant_id' => $record->tenant_id]);
                                            }),
                                    ]),

                                \Filament\Forms\Components\Section::make('Relationships')
                                    ->schema([
                                        Select::make('lead_id')
                                            ->relationship('lead', 'id') // Ideally display name, but 'id' was in original. Consider changing to 'name' or relevant label if Lead model has it.
                                            ->default(null),
                                        Select::make('payment_id')
                                            ->relationship('payment', 'id')
                                            ->default(null),
                                        Select::make('parent_id')
                                            ->label('Parent Task')
                                            ->relationship('parent', 'Title') // Adjusted to show Title instead of ID for better UX
                                            ->default(null),
                                    ]),

                                \Filament\Forms\Components\Hidden::make('position')
                                    ->default(0),
                                
                                \Filament\Forms\Components\Hidden::make('tenant_id')
                                    ->default(fn () => auth()->user()?->tenant_id),
                            ]),

                        // Full width Section for Subtasks
                        
                    ]),
            ]);
    }
}







