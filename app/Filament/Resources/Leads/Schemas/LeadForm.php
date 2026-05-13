<?php

namespace App\Filament\Resources\Leads\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use App\Models\Lookup;

class LeadForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Lead Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('Shop_Name')
                                    ->required(),
                                DateTimePicker::make('last_modified'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Select::make('Industry')
                                    ->options(Lookup::whereHas('parent', fn($query) => $query->where('name', 'Lead Industry'))->pluck('name', 'name'))
                                    ->searchable()
                                    ->live()
                                    ->createOptionForm([
                                        TextInput::make('name')->required()->label('New Industry'),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $parent = Lookup::where('name', 'Lead Industry')->first();
                                        if ($parent) {
                                            Lookup::create([
                                                'name' => $data['name'],
                                                'label' => $data['name'],
                                                'parent_id' => $parent->id,
                                                'tenant_id' => auth()->user()->last_active_tenant_id ?? auth()->user()->tenant_id,
                                            ]);
                                        }
                                        return $data['name'];
                                    })
                                    ->default(null),
                                Select::make('Source')
                                    ->options(Lookup::whereHas('parent', fn($query) => $query->where('name', 'Lead Source'))->pluck('name', 'name'))
                                    ->searchable()
                                    ->live()
                                    ->createOptionForm([
                                        TextInput::make('name')->required()->label('New Source'),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $parent = Lookup::where('name', 'Lead Source')->first();
                                        if ($parent) {
                                            Lookup::create([
                                                'name' => $data['name'],
                                                'label' => $data['name'],
                                                'parent_id' => $parent->id,
                                                'tenant_id' => auth()->user()->last_active_tenant_id ?? auth()->user()->tenant_id,
                                            ]);
                                        }
                                        return $data['name'];
                                    })
                                    ->default(null),
                                Select::make('Language')
                                    ->options(Lookup::whereHas('parent', fn($query) => $query->where('name', 'Lead Language'))->pluck('name', 'name'))
                                    ->searchable()
                                    ->live()
                                    ->createOptionForm([
                                        TextInput::make('name')->required()->label('New Language'),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $parent = Lookup::where('name', 'Lead Language')->first();
                                        if ($parent) {
                                            Lookup::create([
                                                'name' => $data['name'],
                                                'label' => $data['name'],
                                                'parent_id' => $parent->id,
                                                'tenant_id' => auth()->user()->last_active_tenant_id ?? auth()->user()->tenant_id,
                                            ]);
                                        }
                                        return $data['name'];
                                    })
                                    ->default(null),
                            ]),
                    ]),

                Section::make('Contact Numbers')
                    ->schema([
                        \Filament\Forms\Components\Repeater::make('phones')
                            ->relationship()
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('name')
                                    ->label('Contact Name')
                                    ->maxLength(255),
                                \Filament\Forms\Components\TextInput::make('position')
                                    ->label('Position / Role')
                                    ->maxLength(255)
                                    ->default(null),
                                \Filament\Forms\Components\TextInput::make('phone_number')
                                    ->label('Phone Number')
                                    ->required()
                                    ->maxLength(255),
                                \Filament\Forms\Components\Toggle::make('is_main')
                                    ->label('Main Contact')
                                    ->default(false),
                                \Filament\Forms\Components\Hidden::make('tenant_id')
                                    ->default(fn () => auth()->user()->last_active_tenant_id ?? auth()->user()->tenant_id),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->addActionLabel('Add Phone Number')
                            ->columnSpanFull(),
                    ]),

                Section::make('Location Details')
                    ->columns(3)
                    ->schema([
                        Select::make('Country')
                            ->options(\App\Models\Country::pluck('name', 'name'))
                            ->searchable()
                            ->live()
                            ->createOptionForm([
                                TextInput::make('name')->required()->label('Country Name'),
                            ])
                            ->createOptionUsing(function (array $data) {
                                return \App\Models\Country::firstOrCreate(['name' => $data['name']])->name;
                            })
                            ->default(null),
                        Select::make('State')
                            ->options(function (Get $get) {
                                $country = \App\Models\Country::where('name', $get('Country'))->first();
                                if ($country) {
                                    return $country->states()->pluck('name', 'name');
                                }
                                return \App\Models\State::pluck('name', 'name');
                            })
                            ->searchable()
                            ->live()
                            ->createOptionForm([
                                TextInput::make('name')->required()->label('State Name'),
                            ])
                            ->createOptionUsing(function (array $data, Get $get) {
                                $country = \App\Models\Country::where('name', $get('Country'))->first();
                                if ($country) {
                                    return $country->states()->firstOrCreate(['name' => $data['name']])->name;
                                }
                                return $data['name']; // Fallback if no country selected
                            })
                            ->afterStateUpdated(function (\Filament\Forms\Set $set, ?string $state) {
                                if (!$state) return;
                                $stateRecord = \App\Models\State::with('country')->where('name', $state)->first();
                                if ($stateRecord && $stateRecord->country) {
                                    $set('Country', $stateRecord->country->name);
                                }
                            })
                            ->default(null),
                        Select::make('City')
                            ->options(function (Get $get) {
                                $state = \App\Models\State::where('name', $get('State'))->first();
                                if ($state) {
                                    return $state->cities()->pluck('name', 'name');
                                }
                                return \App\Models\City::pluck('name', 'name');
                            })
                            ->searchable()
                            ->live()
                            ->createOptionForm([
                                TextInput::make('name')->required()->label('City Name'),
                            ])
                            ->createOptionUsing(function (array $data, Get $get) {
                                $state = \App\Models\State::where('name', $get('State'))->first();
                                if ($state) {
                                    return $state->cities()->firstOrCreate(['name' => $data['name']])->name;
                                }
                                return $data['name']; // Fallback if no state selected
                            })
                            ->afterStateUpdated(function (\Filament\Forms\Set $set, ?string $state) {
                                if (!$state) return;
                                $cityRecord = \App\Models\City::with('state.country')->where('name', $state)->first();
                                if ($cityRecord && $cityRecord->state) {
                                    $set('State', $cityRecord->state->name);
                                    if ($cityRecord->state->country) {
                                        $set('Country', $cityRecord->state->country->name);
                                    }
                                }
                            })
                            ->default(null),
                        Textarea::make('address_1')
                            ->default(null)
                            ->columnSpanFull(),
                        Textarea::make('address_2')
                            ->default(null)
                            ->columnSpanFull(),
                        Textarea::make('address_3')
                            ->default(null)
                            ->columnSpanFull(),
                    ]),

                Section::make('Status & Remarks')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('relevant')
                                    ->live()
                                    ->required(),
                                Select::make('status_id')
                                    ->relationship('status', 'name', fn ($query) => $query->whereHas('parent', fn($q) => $q->where('name', 'Lead Status')))
                                    ->searchable()
                                    ->createOptionForm([
                                        TextInput::make('name')->required()->label('New Status'),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $parent = Lookup::where('name', 'Lead Status')->first();
                                        if ($parent) {
                                            $newStatus = Lookup::create([
                                                'name' => $data['name'],
                                                'label' => $data['name'],
                                                'parent_id' => $parent->id,
                                                'tenant_id' => auth()->user()->last_active_tenant_id ?? auth()->user()->tenant_id,
                                            ]);
                                            return $newStatus->id;
                                        }
                                        return null;
                                    })
                                    ->default(null),
                            ]),
                        Textarea::make('Irrelevant_reason')
                            ->hidden(fn (Get $get): bool => $get('relevant'))
                            ->default(null)
                            ->columnSpanFull(),
                        Textarea::make('remarks')
                            ->default(null)
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('tenant_id')
                            ->default(fn () => auth()->user()->last_active_tenant_id),
                    ]),
            ]);
    }
}







