<?php

namespace App\Filament\Resources\Todolists\Pages;

use App\Filament\Resources\Todolists\TodolistResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ArchivedTodolists extends ListRecords
{
    protected static string $resource = TodolistResource::class;

    protected static ?string $title = 'Archived Todolists';

    protected static ?string $breadcrumb = 'Archived';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('tenant_id', auth()->user()->tenant_id)->whereHas('status', fn ($q) => $q->where('name', 'Completed'))->whereNull('parent_id'))
            ->columns([
                Tables\Columns\TextColumn::make('Title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority.name')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Urgent' => 'danger',
                        'High' => 'warning',
                        'Normal' => 'info',
                        'Low' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Completed At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pics.name')
                    ->label('PICs')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\Filter::make('self_only')
                    ->toggle()
                    ->label('Show Self Only')
                    ->query(fn (Builder $query) => $query->whereHas('pics', fn ($q) => $q->where('user_id', auth()->id())))
                    ->default(false)
                    ->visible(fn () => in_array(auth()->user()->role?->role, ['Superadmin', 'Tenant admin', 'Manager'])),
            ])
            ->actions([
                Tables\Actions\Action::make('restore')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $todoStatus = \App\Models\Lookup::whereHas('parent', fn ($q) => $q->where('name', 'Todolist Status'))
                            ->where('name', 'To do')
                            ->first();
                        
                        $record->update([
                            'status_id' => $todoStatus?->id,
                        ]);
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
