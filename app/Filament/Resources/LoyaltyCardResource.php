<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoyaltyCardResource\Pages;
use App\Models\Pricebook\LoyaltyCard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LoyaltyCardResource extends Resource
{
    protected static ?string $model = LoyaltyCard::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Pricebook — Payments';
    protected static ?string $navigationLabel = 'Loyalty Cards';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('card_name')
                ->label('Card Name')
                ->required()
                ->maxLength(100)
                ->columnSpanFull(),
            Forms\Components\Repeater::make('bins')
                ->relationship()
                ->schema([
                    Forms\Components\TextInput::make('start_iso_bin')
                        ->label('Start ISO BIN')
                        ->required()
                        ->maxLength(20),
                    Forms\Components\TextInput::make('end_iso_bin')
                        ->label('End ISO BIN')
                        ->required()
                        ->maxLength(20),
                    Forms\Components\TextInput::make('min_length')
                        ->label('Min Length')
                        ->numeric()
                        ->required(),
                    Forms\Components\TextInput::make('max_length')
                        ->label('Max Length')
                        ->numeric()
                        ->required(),
                    Forms\Components\Select::make('check_digit')
                        ->label('Check Digit')
                        ->options([0 => 'No (0)', 1 => 'Yes (1)'])
                        ->required(),
                ])
                ->columns(5)
                ->columnSpanFull()
                ->label('BIN Ranges'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('card_name')
                    ->label('Card Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('bins_count')
                    ->label('BIN Ranges')
                    ->counts('bins'),
            ])
            ->defaultSort('card_name')
            ->searchable()
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index'  => Pages\ListLoyaltyCards::route('/'),
            'create' => Pages\CreateLoyaltyCard::route('/create'),
            'edit'   => Pages\EditLoyaltyCard::route('/{record}/edit'),
        ];
    }
}
