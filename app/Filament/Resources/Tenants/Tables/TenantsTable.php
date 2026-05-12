<?php

namespace App\Filament\Resources\Tenants\Tables;

use App\Models\Tenant;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('code_expiring')
                    ->label('Code Expires')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => ! Auth::user()->tenant_id),
                Tables\Actions\Action::make('generate_code')
                    ->label('Generate Code')
                    ->icon('heroicon-o-key')
                    ->action(function (Tenant $record) {
                        $record->generateInvitationCode();
                        Notification::make()
                            ->title('Invitation code generated')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('copy_invite_link')
                    ->label('Copy Invite Link')
                    ->icon('heroicon-o-link')
                    ->color('info')
                    ->visible(fn (Tenant $record) => !empty($record->code))
                    ->action(function (Tenant $record, \Livewire\Component $livewire) {
                        $url = url('/invite/' . $record->code);
                        $escaped = addslashes($url);

                        $livewire->js("
                            const text = '{$escaped}';
                            if (navigator.clipboard && window.isSecureContext) {
                                navigator.clipboard.writeText(text);
                            } else {
                                const el = document.createElement('textarea');
                                el.value = text;
                                el.style.position = 'fixed';
                                el.style.left = '-9999px';
                                document.body.appendChild(el);
                                el.focus();
                                el.select();
                                document.execCommand('copy');
                                document.body.removeChild(el);
                            }
                        ");

                        Notification::make()
                            ->title('Invite link copied!')
                            ->body($url)
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}







