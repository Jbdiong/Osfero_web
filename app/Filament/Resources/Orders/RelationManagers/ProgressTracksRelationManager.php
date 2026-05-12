<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderProgressMail;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class ProgressTracksRelationManager extends RelationManager
{
    protected static string $relationship = 'progressTracks';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('status_id')
                            ->relationship('status', 'name', fn ($query) => $query->whereHas('parent', fn ($q) => $q->where('name', 'Order Progress'))->orderBy('id'))
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')->required()->label('New Status'),
                            ])
                            ->createOptionUsing(function (array $data) {
                                $parent = \App\Models\Lookup::where('name', 'Order Progress')->first();
                                if ($parent) {
                                    $newStatus = \App\Models\Lookup::create([
                                        'name' => $data['name'],
                                        'label' => $data['name'],
                                        'parent_id' => $parent->id,
                                        'tenant_id' => auth()->user()->last_active_tenant_id ?? auth()->user()->tenant_id,
                                    ]);
                                    return $newStatus->id;
                                }
                                return null;
                            })
                            ->default(request()->query('status_id'))
                            ->required(),
                        Forms\Components\DatePicker::make('completed_at')
                            ->label('Complete Date')
                            ->native(false),
                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('attachments')
                            ->relationship('attachments')
                            ->schema([
                                Forms\Components\TextInput::make('file_name')
                                    ->required(),
                                Forms\Components\FileUpload::make('file_path')
                                    ->label('File')
                                    ->disk('public')
                                    ->directory('order-progress-attachments')
                                    ->required()
                                    ->downloadable()
                                    ->openable()
                                    ->live()
                                    ->hintAction(
                                        Forms\Components\Actions\Action::make('full_preview')
                                            ->label('Full Preview')
                                            ->icon('heroicon-m-magnifying-glass-plus')
                                            ->modalHeading('Document Preview')
                                            ->modalWidth('7xl')
                                            ->modalSubmitAction(false)
                                            ->modalContent(function ($get) {
                                                $file = $get('file_path');
                                                if (!$file) return null;
                                                
                                                $url = null;
                                                $ext = null;

                                                if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                                    $ext = strtolower($file->getClientOriginalExtension());
                                                    try {
                                                        $url = $file->temporaryUrl();
                                                    } catch (\Exception $e) {
                                                        return new HtmlString('<p class="p-4 text-center text-gray-500">Full preview not available for temporary ' . $ext . ' files. Please save the record first.</p>');
                                                    }
                                                } elseif (is_array($file)) {
                                                    $file = array_values($file)[0] ?? null;
                                                }

                                                if (is_string($file)) {
                                                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                                    $url = Storage::disk('public')->url($file);
                                                }

                                                if (!$url) {
                                                    return new HtmlString('<p class="p-4 text-center text-gray-500">Unable to generate preview URL. Please ensure the file is uploaded and saved.</p>');
                                                }
                                                
                                                if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'])) {
                                                    return new HtmlString("<img src='{$url}' class='w-full h-auto max-h-[80vh] object-contain mx-auto' />");
                                                }
                                                
                                                if ($ext === 'pdf') {
                                                    return new HtmlString("
                                                        <div style='height: 600px;'>
                                                            <iframe src='{$url}' class='w-full h-full' style='border: none;'></iframe>
                                                        </div>
                                                    ");
                                                }

                                                if (in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'])) {
                                                     $googleUrl = "https://docs.google.com/viewer?url=" . urlencode($url) . "&embedded=true";
                                                     return new HtmlString("
                                                        <div class='flex flex-col' style='height: 800px;'>
                                                            <iframe src='{$googleUrl}' class='flex-grow w-full border-none'></iframe>
                                                            <div class='p-4 text-center text-sm text-gray-500 bg-gray-50 border-t'>
                                                                <p><strong>Note:</strong> Office document previews via Google Viewer require a publicly accessible URL.</p>
                                                                <p class='text-xs mt-1 text-gray-400'>If you are on a local environment, please download the file to view it.</p>
                                                                <a href='{$url}' target='_blank' class='mt-2 inline-flex items-center text-primary-600 hover:underline font-medium'>
                                                                    <svg class='w-4 h-4 mr-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4'></path></svg>
                                                                    Download File
                                                                </a>
                                                            </div>
                                                        </div>
                                                     ");
                                                }

                                                return new HtmlString("<p class='p-4 text-center text-gray-500'>Preview not supported for .{$ext} files.</p>");
                                            })
                                    ),
                                Forms\Components\Placeholder::make('file_preview')
                                    ->label('Quick Preview')
                                    ->visible(fn ($get) => $get('file_path'))
                                    ->content(function ($get) {
                                        $file = $get('file_path');
                                        if (!$file) return null;

                                        $url = null;
                                        $ext = null;

                                        if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                            $ext = strtolower($file->getClientOriginalExtension());
                                            try {
                                                $url = $file->temporaryUrl();
                                            } catch (\Exception $e) {
                                                $url = null;
                                            }
                                        } elseif (is_array($file)) {
                                            $file = array_values($file)[0] ?? null;
                                        }

                                        if (is_string($file)) {
                                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                            $url = Storage::disk('public')->url($file);
                                        }

                                        if (!$url) return null;

                                        if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'])) {
                                            return new HtmlString("
                                                <div class='mt-2 rounded-xl overflow-hidden border shadow-sm bg-gray-50 transition-all hover:scale-[1.01] cursor-pointer' onclick='window.open(\"{$url}\", \"_blank\")'>
                                                    <img src='{$url}' class='w-full h-auto max-h-48 object-cover' />
                                                </div>
                                            ");
                                        }

                                        if ($ext === 'pdf') {
                                            return new HtmlString("
                                                <div class='mt-2 rounded-xl overflow-hidden border shadow-sm bg-gray-50'>
                                                    <iframe src='{$url}#toolbar=0' class='w-full h-[800px] border-none'></iframe>
                                                    <div class='p-2 text-center text-[10px] text-gray-500 bg-white border-t'>
                                                        <a href='{$url}' target='_blank' class='text-primary-600 hover:underline font-medium'>Open PDF in New Tab</a>
                                                    </div>
                                                </div>
                                            ");
                                        }

                                        if (in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'])) {
                                             return new HtmlString("
                                                <div class='mt-2 rounded-xl overflow-hidden border shadow-sm bg-gray-100 flex items-center justify-center h-48 flex-col gap-2'>
                                                    <svg class='w-12 h-12 text-gray-400' xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor'>
                                                        <path stroke-linecap='round' stroke-linejoin='round' d='M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z' />
                                                    </svg>
                                                    <p class='text-[10px] text-gray-500 px-4 text-center'>Office Document detected. Use 'Full Preview' for better view.</p>
                                                    <a href='{$url}' target='_blank' class='text-primary-600 hover:underline text-[10px] font-medium'>Download File</a>
                                                </div>
                                            ");
                                        }

                                        return new HtmlString("
                                            <div class='mt-2 p-4 rounded-xl border border-dashed text-center bg-gray-50'>
                                                <p class='text-[10px] text-gray-400'>No quick preview for .{$ext}</p>
                                            </div>
                                        ");
                                    })
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull()
                            ->grid(2)
                            ->itemLabel(fn (array $state): ?string => $state['file_name'] ?? null),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'gray',
                        'In Progress' => 'warning',
                        'Completed' => 'success',
                        'Failed' => 'danger',
                        default => 'primary',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Complete Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('send_email')
                    ->label('Send Email')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->modalHeading('Send Progress Update')
                    ->modalSubmitActionLabel('Send Email Now')
                    ->form([
                        Forms\Components\TextInput::make('email')
                            ->label('Customer Email')
                            ->email()
                            ->default(fn ($record) => $record->order?->customer?->email)
                            ->required(),
                        Forms\Components\Textarea::make('email_content')
                            ->label('Message Content')
                            ->default(fn ($record) => "Hi, we have an update on your order progress: {$record->title}\n\nStatus: " . ($record->status?->name ?? 'Update') . "\nNotes: {$record->notes}")
                            ->rows(6)
                            ->required(),
                        Forms\Components\Placeholder::make('attachments_count')
                            ->label('Attachments')
                            ->content(fn ($record) => $record->attachments->count() . ' file(s) will be attached.'),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            Mail::to($data['email'])->send(new OrderProgressMail($record->order, $record, $data['email_content']));
                            
                            Notification::make()
                                ->title('Email Sent Successfully')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to Send Email')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }
}
