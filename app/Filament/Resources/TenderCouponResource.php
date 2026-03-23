<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderCouponResource\Pages;
use App\Models\Pricebook\TenderCoupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderCouponResource extends Resource
{
    protected static ?string $model = TenderCoupon::class;
    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationGroup = 'Pricebook — Payments';
    protected static ?string $navigationLabel = 'Tenders & Coupons';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('item_number')
                ->label('Item Number')
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
                Tables\Columns\TextColumn::make('item_number')
                    ->label('Item Number')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('english_description')
                    ->label('English Description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('french_description')
                    ->label('French Description')
                    ->searchable(),
            ])
            ->defaultSort('item_number')
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
            'index'  => Pages\ListTenderCoupons::route('/'),
            'create' => Pages\CreateTenderCoupon::route('/create'),
            'edit'   => Pages\EditTenderCoupon::route('/{record}/edit'),
        ];
    }
}
