<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SkuResource\Pages;
use App\Models\Pricebook\Department;
use App\Models\Pricebook\PriceGroup;
use App\Models\Pricebook\Sku;
use Illuminate\Support\Facades\DB;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SkuResource extends Resource
{
    protected static ?string $model = Sku::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Pricebook — Inventory';
    protected static ?string $navigationLabel = 'SKUs';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('SKU Details')
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
                            Forms\Components\TextInput::make('price')
                                ->numeric()
                                ->prefix('$')
                                ->required(),
                            Forms\Components\Select::make('department_number')
                                ->label('Department')
                                ->options(fn () => Department::orderBy('description')->pluck('description', 'department_number'))
                                ->searchable()
                                ->required(),
                            Forms\Components\Select::make('price_group_number')
                                ->label('Price Group')
                                ->options(fn () => PriceGroup::orderBy('english_description')->pluck('english_description', 'price_group_number'))
                                ->searchable()
                                ->nullable(),
                            Forms\Components\TextInput::make('owner')
                                ->maxLength(100),
                            Forms\Components\TextInput::make('promo_code')
                                ->label('Promo Code')
                                ->maxLength(12),
                            Forms\Components\TextInput::make('host_product_code')
                                ->label('Host Product Code')
                                ->maxLength(50),
                            Forms\Components\TextInput::make('conexxus_product_code')
                                ->label('Conexxus Product Code')
                                ->maxLength(10),
                            Forms\Components\TextInput::make('tax_strategy_id_from_nacs')
                                ->label('Tax Strategy ID (NACS)')
                                ->maxLength(20),
                        ])->columns(2),

                    Forms\Components\Tabs\Tab::make('Pricing & Deposits')
                        ->schema([
                            Forms\Components\TextInput::make('item_deposit')
                                ->label('Item Deposit')
                                ->numeric()
                                ->prefix('$'),
                            Forms\Components\TextInput::make('delivery_channel_price')
                                ->label('Delivery Channel Price')
                                ->numeric()
                                ->prefix('$'),
                            Forms\Components\Toggle::make('prompt_for_price')
                                ->label('Prompt for Price'),
                            Forms\Components\Toggle::make('tax_included_price')
                                ->label('Tax Included in Price'),
                            Forms\Components\Toggle::make('redemption_only')
                                ->label('Redemption Only'),
                            Forms\Components\Toggle::make('loyalty_card_eligible')
                                ->label('Loyalty Card Eligible'),
                        ])->columns(2),

                    Forms\Components\Tabs\Tab::make('Tax Flags')
                        ->schema([
                            Forms\Components\Toggle::make('tax1')->label('TAX1 (GST)'),
                            Forms\Components\Toggle::make('tax2')->label('TAX2 (PST)'),
                            Forms\Components\Toggle::make('tax3')->label('TAX3'),
                            Forms\Components\Toggle::make('tax4')->label('TAX4'),
                            Forms\Components\Toggle::make('tax5')->label('TAX5'),
                            Forms\Components\Toggle::make('tax6')->label('TAX6'),
                            Forms\Components\Toggle::make('tax7')->label('TAX7'),
                            Forms\Components\Toggle::make('tax8')->label('TAX8'),
                            Forms\Components\Toggle::make('ontario_rst_tax_off')->label('Ontario RST Tax Off'),
                            Forms\Components\Toggle::make('ontario_rst_tax_on')->label('Ontario RST Tax On'),
                            Forms\Components\Toggle::make('federal_baked_good_item')->label('Federal Baked Good Item'),
                        ])->columns(3),

                    Forms\Components\Tabs\Tab::make('Car Wash')
                        ->schema([
                            Forms\Components\TextInput::make('wash_type')
                                ->label('Wash Type')
                                ->maxLength(1)
                                ->helperText('Values: 1, 3, or 5'),
                            Forms\Components\TextInput::make('car_wash_controller_code')
                                ->label('Controller Code')
                                ->numeric(),
                            Forms\Components\TextInput::make('car_wash_expiry_in_days')
                                ->label('Expiry (Days)')
                                ->numeric(),
                            Forms\Components\TextInput::make('afd_car_wash_position')
                                ->label('AFD Position on Screen')
                                ->numeric(),
                            Forms\Components\TextInput::make('upsell_qty_car_wash')
                                ->label('Upsell Quantity at Pump')
                                ->numeric(),
                        ])->columns(2),

                    Forms\Components\Tabs\Tab::make('Flags')
                        ->schema([
                            Forms\Components\Toggle::make('item_not_active')->label('Item Not Active'),
                            Forms\Components\Toggle::make('item_desc_not_on_2nd_monitor')->label('Description Not on 2nd Monitor'),
                            Forms\Components\Toggle::make('prevent_bt9000_inventory_control')->label('Prevent BT9000 Inventory Control'),
                            Forms\Components\TextInput::make('petro_canada_pass_code')
                                ->label('Petro Canada PASS Code')
                                ->numeric(),
                            Forms\Components\TextInput::make('age_requirements')
                                ->label('Age Requirements')
                                ->numeric(),
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

                    Forms\Components\Tabs\Tab::make('Linked SKUs')
                        ->schema([
                            Forms\Components\Repeater::make('linkedSkus')
                                ->label('')
                                ->relationship('linkedSkus')
                                ->schema([
                                    Forms\Components\Select::make('linked_item_number')
                                        ->label('SKU')
                                        ->searchable()
                                        ->getSearchResultsUsing(fn (string $search) => DB::table('pb_skus')
                                            ->where('item_number', 'like', "%{$search}%")
                                            ->orWhere('english_description', 'like', "%{$search}%")
                                            ->orderBy('english_description')
                                            ->limit(50)
                                            ->pluck('english_description', 'item_number')
                                            ->map(fn ($desc, $num) => trim($desc) . " [{$num}]")
                                            ->toArray())
                                        ->getOptionLabelUsing(fn ($value) => DB::table('pb_skus')
                                            ->where('item_number', $value)
                                            ->value('english_description')
                                            ? trim(DB::table('pb_skus')->where('item_number', $value)->value('english_description')) . " [{$value}]"
                                            : $value)
                                        ->nullable()
                                        ->columnSpan(2),
                                    Forms\Components\Toggle::make('mandatory')
                                        ->label('Mandatory')
                                        ->columnSpan(1),
                                ])
                                ->columns(3)
                                ->addActionLabel('Add linked SKU')
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
                    ->label('Item #')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('english_description')
                    ->label('Description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('CAD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('department.description')
                    ->label('Department')
                    ->searchable(),
                Tables\Columns\IconColumn::make('loyalty_card_eligible')
                    ->label('Loyalty')
                    ->boolean(),
                Tables\Columns\BadgeColumn::make('item_not_active')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => $state ? 'Inactive' : 'Active')
                    ->colors([
                        'danger'  => true,
                        'success' => false,
                    ]),
            ])
            ->defaultSort('english_description')
            ->searchable()
            ->filters([
                Tables\Filters\SelectFilter::make('department_number')
                    ->label('Department')
                    ->options(fn () => Department::orderBy('description')->pluck('description', 'department_number')),
                Tables\Filters\TernaryFilter::make('item_not_active')
                    ->label('Active Status')
                    ->trueLabel('Inactive only')
                    ->falseLabel('Active only'),
                Tables\Filters\TernaryFilter::make('loyalty_card_eligible')
                    ->label('Loyalty Eligible'),
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
            'index'  => Pages\ListSkus::route('/'),
            'create' => Pages\CreateSku::route('/create'),
            'edit'   => Pages\EditSku::route('/{record}/edit'),
        ];
    }
}
