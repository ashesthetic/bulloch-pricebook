<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PricebookImportResource\Pages;
use App\Jobs\ExportPricebookJob;
use App\Jobs\ImportPricebookJob;
use App\Models\Pricebook\PricebookImport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PricebookImportResource extends Resource
{
    protected static ?string $model = PricebookImport::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationLabel = 'Import History';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('file_path')
                ->label('File Path')
                ->required()
                ->columnSpanFull(),
            Forms\Components\Select::make('status')
                ->options([
                    'running'   => 'Running',
                    'completed' => 'Completed',
                    'failed'    => 'Failed',
                ])
                ->required(),
            Forms\Components\Textarea::make('error_message')
                ->label('Error Message')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('3s')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('station_id')
                    ->label('Station ID'),
                Tables\Columns\TextColumn::make('bt9000_version')
                    ->label('Version'),
                Tables\Columns\TextColumn::make('file_created_at')
                    ->label('File Date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'running',
                        'danger'  => 'failed',
                    ]),
                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->formatStateUsing(fn ($state, $record) => $record->status === 'running'
                        ? "{$state}% — {$record->current_section}"
                        : ($record->status === 'completed' ? '100%' : '—')),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('finished_at')
                    ->label('Finished')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->headerActions([
                Tables\Actions\Action::make('start_import')
                    ->label('Start Import')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Start Pricebook Import')
                    ->modalDescription('This will truncate all pricebook tables and re-import from the XML file. Continue?')
                    ->action(function () {
                        $filePath = env('PRICEBOOK_PATH');
                        if (empty($filePath)) {
                            Notification::make()
                                ->title('PRICEBOOK_PATH is not set in .env')
                                ->danger()
                                ->send();
                            return;
                        }

                        if (! str_starts_with($filePath, '/')) {
                            $filePath = base_path($filePath);
                        }

                        if (! file_exists($filePath)) {
                            Notification::make()
                                ->title("File not found: {$filePath}")
                                ->danger()
                                ->send();
                            return;
                        }

                        $import = PricebookImport::create([
                            'file_path'  => $filePath,
                            'started_at' => now(),
                            'status'     => 'running',
                        ]);

                        dispatch(new ImportPricebookJob($filePath, $import->id));

                        Notification::make()
                            ->title('Import queued successfully')
                            ->body("Import #{$import->id} is running in the background.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('export')
                    ->label('Export to File')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Export Pricebook to XML')
                    ->modalDescription('This will overwrite the existing XML file with current database data. Continue?')
                    ->action(function () {
                        $filePath = env('PRICEBOOK_PATH');
                        if (empty($filePath)) {
                            Notification::make()
                                ->title('PRICEBOOK_PATH is not set in .env')
                                ->danger()
                                ->send();
                            return;
                        }

                        if (! str_starts_with($filePath, '/')) {
                            $filePath = base_path($filePath);
                        }

                        dispatch(new ExportPricebookJob($filePath));

                        Notification::make()
                            ->title('Export queued successfully')
                            ->body('The XML file will be updated in the background.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPricebookImports::route('/'),
            'view'  => Pages\ViewPricebookImport::route('/{record}'),
        ];
    }
}
