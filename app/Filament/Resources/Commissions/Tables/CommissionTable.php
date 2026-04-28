<?php

namespace App\Filament\Resources\Commissions\Tables;

use App\Filament\Resources\Commissions\CommissionResource;
use App\Models\CommissionEntry;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CommissionTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('assigned_staff')
                    ->label('Staff / PICs')
                    ->getStateUsing(function (CommissionEntry $record) {
                        return $record->users->count() > 0 
                            ? $record->users->pluck('name') 
                            : ($record->user ? [$record->user->name] : []);
                    })
                    ->badge()
                    ->color('gray')
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('users', fn($q) => $q->where('users.name', 'like', "%{$search}%"))
                                     ->orWhereHas('user', fn($q) => $q->where('users.name', 'like', "%{$search}%"));
                    })
                    ->hidden(fn () => CommissionResource::isStaffOnly()),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'design'         => 'primary',
                        'video'          => 'info',
                        'ads_management' => 'success',
                        'sales'          => 'warning',
                        default          => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'design'         => '🎨 Design',
                        'video'          => '🎬 Video',
                        'ads_management' => '📢 Ads Management',
                        'sales'          => '💼 Sales',
                        default          => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('name')
                    ->label('Project / Client')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('entry_date')
                    ->label('Entry Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('package_value')
                    ->label('Package (RM)')
                    ->money('MYR')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('month')
                    ->label('Month')
                    ->formatStateUsing(fn (int $state): string => \Carbon\Carbon::create()->month($state)->format('F'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('year')
                    ->label('Year')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_approved')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->sortable(),

                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Approved By')
                    ->placeholder('Pending')
                    ->hidden(fn () => CommissionResource::isStaffOnly())
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Logged At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('is_approved', 'asc'))
            ->defaultSort('created_at', 'desc')
            ->groups([
                Tables\Grouping\Group::make('month')
                    ->label('Month')
                    ->getTitleFromRecordUsing(
                        fn (CommissionEntry $r) => \Carbon\Carbon::create()->month($r->month)->format('F') . ' ' . $r->year
                    ),
            ])
            ->filters([

                Tables\Filters\SelectFilter::make('month')
                    ->options([
                        1 => 'January',   2 => 'February',  3 => 'March',
                        4 => 'April',     5 => 'May',        6 => 'June',
                        7 => 'July',      8 => 'August',     9 => 'September',
                        10 => 'October', 11 => 'November',  12 => 'December',
                    ]),

                Tables\Filters\SelectFilter::make('year')
                    ->options(fn () => array_combine(
                        range(now()->year - 2, now()->year + 1),
                        range(now()->year - 2, now()->year + 1)
                    )),


                Tables\Filters\SelectFilter::make('staff')
                    ->label('Staff / PIC')
                    ->searchable()
                    ->options(fn () => \Filament\Facades\Filament::getTenant() 
                        ? \Filament\Facades\Filament::getTenant()->users->pluck('name', 'id') 
                        : User::pluck('name', 'id')
                    )
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->where(function ($q) use ($data) {
                            $q->whereHas('users', fn($inner) => $inner->where('users.id', $data['value']))
                              ->orWhere('user_id', $data['value']);
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (CommissionEntry $r) => ! CommissionResource::isStaffOnly() && ! $r->is_approved)
                    ->action(fn (CommissionEntry $r) => $r->update([
                        'is_approved' => true,
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                    ]))
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('unapprove')
                    ->label('Recall')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->visible(fn (CommissionEntry $r) => ! CommissionResource::isStaffOnly() && $r->is_approved)
                    ->action(fn (CommissionEntry $r) => $r->update([
                        'is_approved' => false,
                        'approved_by' => null,
                        'approved_at' => null,
                    ]))
                    ->requiresConfirmation(),

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
