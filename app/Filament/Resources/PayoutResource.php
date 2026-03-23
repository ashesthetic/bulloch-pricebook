<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayoutResource\Pages;
use App\Models\Pricebook\Payout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Pricebook — Payments';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('payout_number')
                ->label('Payout Number')
                ->required()
                ->maxLength(13)
                ->disabled(fn ($operation) => $operation === 'edit'),
            Forms\Components\TextInput::make('english_description')
                ->label('English Description')
                ->required()
                ->maxLength(18),
            Forms\Components\TextInput::make('french_description')
                ->label('French Description')
                ->maxLength(18),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('payout_number')
                    ->label('Number')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('english_description')
                    ->label('English Description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('french_description')
                    ->label('French Description')
                    ->searchable(),
            ])
            ->defaultSort('payout_number')
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
            'index'  => Pages\ListPayouts::route('/'),
            'create' => Pages\CreatePayout::route('/create'),
            'edit'   => Pages\EditPayout::route('/{record}/edit'),
        ];
    }
}
