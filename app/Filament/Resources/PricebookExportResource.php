<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PricebookExportResource\Pages;
use App\Models\Pricebook\PricebookExport;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PricebookExportResource extends Resource
{
    protected static ?string $model = PricebookExport::class;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'admin']) ?? false;
    }

    public static function canCreate(): bool { return static::canViewAny(); }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return static::canViewAny(); }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return static::canViewAny(); }
    public static function canDeleteAny(): bool { return static::canViewAny(); }
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationLabel = 'Export History';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 11;

    public static function table(Table $table): Table
    {
        return $table
            ->poll('3s')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'running',
                        'danger'  => 'failed',
                    ]),
                Tables\Columns\TextColumn::make('records_exported')
                    ->label('SKUs Exported')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('finished_at')
                    ->label('Finished')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->state(fn ($record) => $record->finished_at && $record->started_at
                        ? $record->started_at->diffForHumans($record->finished_at, true)
                        : ($record->status === 'running' ? 'Running…' : '—')),
                Tables\Columns\TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(60)
                    ->placeholder('—')
                    ->visible(fn ($record) => $record?->status === 'failed'),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
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
            'index' => Pages\ListPricebookExports::route('/'),
        ];
    }
}
