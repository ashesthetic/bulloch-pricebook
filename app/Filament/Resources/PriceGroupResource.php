<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PriceGroupResource\Pages;
use App\Models\Pricebook\PriceGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PriceGroupResource extends Resource
{
    protected static ?string $model = PriceGroup::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Pricebook — Inventory';
    protected static ?string $navigationLabel = 'Price Groups';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('price_group_number')
                ->label('Price Group Number')
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
            Forms\Components\TextInput::make('price')
                ->numeric()
                ->prefix('$')
                ->required(),
            Forms\Components\Repeater::make('quantityPricing')
                ->label('Quantity Pricing')
                ->relationship('quantityPricing')
                ->schema([
                    Forms\Components\TextInput::make('quantity')
                        ->label('Quantity')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->required()
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('price')
                        ->label('Price')
                        ->numeric()
                        ->prefix('$')
                        ->required()
                        ->columnSpan(1),
                ])
                ->columns(2)
                ->orderColumn('quantity')
                ->addActionLabel('Add tier')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('price_group_number')
                    ->label('Number')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('english_description')
                    ->label('Description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('CAD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_pricing_count')
                    ->label('Qty Pricing')
                    ->counts('quantityPricing'),
                Tables\Columns\TextColumn::make('skus_count')
                    ->label('SKUs')
                    ->counts('skus'),
            ])
            ->defaultSort('price_group_number')
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
            'index'  => Pages\ListPriceGroups::route('/'),
            'create' => Pages\CreatePriceGroup::route('/create'),
            'edit'   => Pages\EditPriceGroup::route('/{record}/edit'),
        ];
    }
}
