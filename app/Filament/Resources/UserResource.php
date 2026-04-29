<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (! auth()->user()?->isSuperAdmin()) {
            $query->whereDoesntHave('roles', fn (Builder $q) => $q->where('name', 'super_admin'));
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'admin']) ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return $record->id !== $user->id || true; // super_admin can edit anyone including self
        }
        // admin cannot edit super_admin accounts
        if ($record->hasRole('super_admin')) {
            return false;
        }

        return $user->hasRole('admin');
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        // No self-delete
        if ($record->id === $user->id) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        // admin cannot delete super_admin
        if ($record->hasRole('super_admin')) {
            return false;
        }

        return $user->hasRole('admin');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'admin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Account Details')->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(table: User::class, column: 'email', ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrateStateUsing(fn (?string $state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->label(fn (string $operation) => $operation === 'create' ? 'Password' : 'New Password (leave blank to keep current)'),

                Forms\Components\Select::make('role')
                    ->label('Role')
                    ->options(function (): array {
                        $opts = ['admin' => 'Admin', 'staff' => 'Staff'];
                        if (auth()->user()?->isSuperAdmin()) {
                            $opts = ['super_admin' => 'Super Admin'] + $opts;
                        }

                        return $opts;
                    })
                    ->required()
                    ->live()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Forms\Components\Select $component, ?Model $record): void {
                        if ($record) {
                            $component->state($record->roles->first()?->name);
                        }
                    }),
            ])->columns(2),

            Forms\Components\Section::make('Staff Permissions')
                ->description('Grant specific access to this staff member. No role can delete records.')
                ->visible(fn (Forms\Get $get): bool => $get('role') === 'staff')
                ->schema(static::buildPermissionToggles()),
        ]);
    }

    public static function buildPermissionToggles(): array
    {
        $modules = [
            'departments'    => 'Departments',
            'skus'           => 'SKUs',
            'price_groups'   => 'Price Groups',
            'deal_groups'    => 'Deal Groups',
            'mix_and_matches' => 'Mix & Match',
            'loyalty_cards'  => 'Loyalty Cards',
            'tender_coupons' => 'Tenders & Coupons',
            'payouts'        => 'Payouts',
        ];

        $sections = [];
        foreach ($modules as $key => $label) {
            $sections[] = Forms\Components\Section::make($label)
                ->schema([
                    Forms\Components\Toggle::make("perm_view_{$key}")->label('View')->inline(false),
                    Forms\Components\Toggle::make("perm_create_{$key}")->label('Create')->inline(false),
                    Forms\Components\Toggle::make("perm_edit_{$key}")->label('Edit')->inline(false),
                ])
                ->columns(3)
                ->compact();
        }

        $sections[] = Forms\Components\Section::make('Find Products Page')
            ->schema([
                Forms\Components\Toggle::make('perm_view_find_products')->label('View')->inline(false),
            ])
            ->compact();

        return $sections;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'admin'       => 'warning',
                        'staff'       => 'success',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'super_admin' => 'Super Admin',
                        'admin'       => 'Admin',
                        'staff'       => 'Staff',
                        default       => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
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
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
