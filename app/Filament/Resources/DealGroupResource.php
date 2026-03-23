<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DealGroupResource\Pages;
use App\Models\Pricebook\DealGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DealGroupResource extends Resource
{
    protected static ?string $model = DealGroup::class;
    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationGroup = 'Pricebook — Promotions';
    protected static ?string $navigationLabel = 'Deal Groups';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('deal_group_number')
                ->label('Deal Group Number')
                ->required()
                ->maxLength(13),
            Forms\Components\Select::make('type')
                ->options([
                    'site'         => 'Site',
                    'head_office'  => 'Head Office',
                    'home_office'  => 'Home Office',
                ])
                ->required(),
            Forms\Components\TextInput::make('english_description')
                ->label('English Description')
                ->maxLength(18),
            Forms\Components\TextInput::make('french_description')
                ->label('French Description')
                ->maxLength(18),
            Forms\Components\DatePicker::make('start_date')
                ->label('Start Date'),
            Forms\Components\DatePicker::make('end_date')
                ->label('End Date'),
            Forms\Components\Toggle::make('deal_not_active')
                ->label('Deal Not Active'),
            Forms\Components\Toggle::make('fuel_mix_and_match_check')
                ->label('Fuel Mix & Match Check'),
            Forms\Components\Toggle::make('dont_calculate_deal')
                ->label('Don\'t Calculate Deal'),
            Forms\Components\Toggle::make('available_in_kiosk_only')
                ->label('Available in Kiosk Only'),
            Forms\Components\Toggle::make('cpl_stacking_cpn')
                ->label('CPL Stacking CPN'),
            Forms\Components\Toggle::make('available_at_pump_only')
                ->label('Available at Pump Only'),
            Forms\Components\TextInput::make('reason_code_for_deal')
                ->label('Reason Code')
                ->numeric(),
            Forms\Components\TextInput::make('station_id_for_deal')
                ->label('Station ID')
                ->maxLength(7),
            Forms\Components\TextInput::make('fixed_dollar_off')
                ->label('Fixed Dollar Off')
                ->numeric()
                ->prefix('$'),
            Forms\Components\TextInput::make('max_per_customer')
                ->label('Max Per Customer')
                ->numeric(),
            Forms\Components\Section::make('Fuel Requirement')
                ->schema([
                    Forms\Components\TextInput::make('req_fuel_pos_grade')
                        ->label('POS Grade'),
                    Forms\Components\TextInput::make('req_fuel_litres')
                        ->label('Required Litres')
                        ->numeric(),
                ])->columns(2),
            Forms\Components\Section::make('Loyalty Card Requirement')
                ->schema([
                    Forms\Components\TextInput::make('loyalty_card_description')
                        ->label('Card Description')
                        ->maxLength(18),
                    Forms\Components\Toggle::make('loyalty_card_restriction')
                        ->label('Card Restriction'),
                    Forms\Components\TextInput::make('loyalty_card_swipe_type')
                        ->label('Swipe Type')
                        ->numeric(),
                ])->columns(2),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('deal_group_number')
                    ->label('Number')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'site',
                        'info'    => 'head_office',
                        'warning' => 'home_office',
                    ]),
                Tables\Columns\TextColumn::make('english_description')
                    ->label('Description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('deal_not_active')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => $state ? 'Inactive' : 'Active')
                    ->colors([
                        'danger'  => true,
                        'success' => false,
                    ]),
                Tables\Columns\TextColumn::make('components_count')
                    ->label('Components')
                    ->counts('components'),
            ])
            ->defaultSort('deal_group_number')
            ->searchable()
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'site'        => 'Site',
                        'head_office' => 'Head Office',
                        'home_office' => 'Home Office',
                    ]),
                Tables\Filters\TernaryFilter::make('deal_not_active')
                    ->label('Active Status')
                    ->trueLabel('Inactive only')
                    ->falseLabel('Active only'),
            ])
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
            'index'  => Pages\ListDealGroups::route('/'),
            'create' => Pages\CreateDealGroup::route('/create'),
            'edit'   => Pages\EditDealGroup::route('/{record}/edit'),
        ];
    }
}
