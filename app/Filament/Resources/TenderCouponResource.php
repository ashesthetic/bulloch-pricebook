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
            Forms\Components\Tabs::make('Tender/Coupon Details')
                ->tabs([
                    Forms\Components\Tabs\Tab::make('Basic Info')
                        ->schema([
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
                            Forms\Components\Select::make('tender_type')
                                ->label('Tender Type')
                                ->options([0 => 'Standard (0)', 1 => 'Coupon (1)'])
                                ->nullable(),
                            Forms\Components\TextInput::make('amount')
                                ->label('Amount')
                                ->numeric()
                                ->prefix('$')
                                ->minValue(0.01)
                                ->maxValue(999.99),
                            Forms\Components\Toggle::make('prompt_for_amount')
                                ->label('Prompt For Amount'),
                            Forms\Components\Toggle::make('coupon_not_active')
                                ->label('Coupon Not Active'),
                            Forms\Components\TextInput::make('max_per_customer')
                                ->label('Max Per Customer')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(999),
                            Forms\Components\DatePicker::make('start_date')
                                ->label('Start Date'),
                            Forms\Components\DatePicker::make('end_date')
                                ->label('End Date'),
                            Forms\Components\TextInput::make('coupon_accounting_implications')
                                ->label('Coupon Accounting Implications')
                                ->maxLength(6),
                        ])->columns(2),

                    Forms\Components\Tabs\Tab::make('Restrictions')
                        ->schema([
                            Forms\Components\Toggle::make('available_at_pump_only')
                                ->label('Available At Pump Only'),
                            Forms\Components\Toggle::make('available_in_kiosk_only')
                                ->label('Available In Kiosk Only'),
                            Forms\Components\TextInput::make('type_of_restrictions')
                                ->label('Type Of Restrictions')
                                ->numeric(),
                            Forms\Components\TextInput::make('restriction_identifier')
                                ->label('Restriction Identifier')
                                ->maxLength(13),
                        ])->columns(2),

                    Forms\Components\Tabs\Tab::make('Loyalty Card')
                        ->schema([
                            Forms\Components\TextInput::make('loyalty_card_description')
                                ->label('Loyalty Card Description')
                                ->maxLength(18)
                                ->helperText('Leave blank if no loyalty card is required.'),
                            Forms\Components\Toggle::make('loyalty_card_restriction')
                                ->label('Card Restriction'),
                            Forms\Components\TextInput::make('loyalty_card_swipe_type')
                                ->label('Card Swipe Type')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(99),
                        ])->columns(2),

                    Forms\Components\Tabs\Tab::make('UPCs')
                        ->schema([
                            Forms\Components\Repeater::make('upcs')
                                ->label('')
                                ->relationship('upcs')
                                ->schema([
                                    Forms\Components\TextInput::make('upc')
                                        ->label('UPC')
                                        ->required()
                                        ->maxLength(13)
                                        ->columnSpanFull(),
                                ])
                                ->addActionLabel('Add UPC')
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('tender_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        0 => 'Standard',
                        1 => 'Coupon',
                        default => '—',
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('CAD')
                    ->sortable(),
                Tables\Columns\IconColumn::make('coupon_not_active')
                    ->label('Not Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),
                Tables\Columns\TextColumn::make('loyalty_card_description')
                    ->label('Loyalty Card')
                    ->searchable(),
            ])
            ->defaultSort('item_number')
            ->searchable()
            ->filters([
                Tables\Filters\TernaryFilter::make('coupon_not_active')
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
            'index'  => Pages\ListTenderCoupons::route('/'),
            'create' => Pages\CreateTenderCoupon::route('/create'),
            'edit'   => Pages\EditTenderCoupon::route('/{record}/edit'),
        ];
    }
}
