<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MixAndMatchResource\Pages;
use App\Models\Pricebook\MixAndMatch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MixAndMatchResource extends Resource
{
    protected static ?string $model = MixAndMatch::class;
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationGroup = 'Pricebook — Promotions';
    protected static ?string $navigationLabel = 'Mix & Match';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('mix_and_match_identifier')
                ->label('Identifier')
                ->required()
                ->maxLength(13)
                ->disabled(fn ($operation) => $operation === 'edit'),
            Forms\Components\TextInput::make('english_description')
                ->label('English Description')
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
                Tables\Columns\TextColumn::make('mix_and_match_identifier')
                    ->label('Identifier')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('english_description')
                    ->label('Description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('members_count')
                    ->label('Members')
                    ->counts('members'),
            ])
            ->defaultSort('mix_and_match_identifier')
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
            'index'  => Pages\ListMixAndMatches::route('/'),
            'create' => Pages\CreateMixAndMatch::route('/create'),
            'edit'   => Pages\EditMixAndMatch::route('/{record}/edit'),
        ];
    }
}
