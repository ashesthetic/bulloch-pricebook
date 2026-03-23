<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Models\Pricebook\Department;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Pricebook — Inventory';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('department_number')
                ->label('Department Number')
                ->required()
                ->maxLength(6)
                ->disabled(fn ($operation) => $operation === 'edit'),
            Forms\Components\TextInput::make('description')
                ->required()
                ->maxLength(18),
            Forms\Components\TextInput::make('owner')
                ->maxLength(100),
            Forms\Components\Toggle::make('shift_report_flag')
                ->label('Shift Report'),
            Forms\Components\Toggle::make('sales_summary_report')
                ->label('Sales Summary Report'),
            Forms\Components\Toggle::make('bt9000_inventory_control')
                ->label('BT9000 Inventory Control'),
            Forms\Components\TextInput::make('conexxus_product_code')
                ->label('Conexxus Product Code')
                ->maxLength(10),
            Forms\Components\Toggle::make('gift_card_department')
                ->label('Gift Card Department'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('department_number')
                    ->label('Number')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('owner')
                    ->searchable(),
                Tables\Columns\IconColumn::make('shift_report_flag')
                    ->label('Shift Report')
                    ->boolean(),
                Tables\Columns\IconColumn::make('sales_summary_report')
                    ->label('Sales Summary')
                    ->boolean(),
                Tables\Columns\IconColumn::make('gift_card_department')
                    ->label('Gift Card')
                    ->boolean(),
                Tables\Columns\TextColumn::make('skus_count')
                    ->label('SKUs')
                    ->counts('skus')
                    ->sortable(),
            ])
            ->defaultSort('department_number')
            ->searchable()
            ->filters([])
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
            'index'  => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit'   => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}
