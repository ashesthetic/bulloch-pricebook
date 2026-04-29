<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 20;
    protected static ?string $navigationLabel = 'Audit Log';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('action')
                    ->colors([
                        'success' => 'created',
                        'warning' => 'updated',
                        'danger'  => static fn (string $state): bool => in_array($state, ['deleted', 'logout']),
                        'primary' => 'login',
                    ]),
                Tables\Columns\TextColumn::make('user_name')
                    ->label('User')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('user_role')
                    ->label('Role')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'super_admin' => 'Super Admin',
                        'admin'       => 'Admin',
                        'staff'       => 'Staff',
                        default       => $state ?? '—',
                    })
                    ->colors([
                        'danger'  => 'super_admin',
                        'warning' => 'admin',
                        'success' => 'staff',
                    ]),
                Tables\Columns\TextColumn::make('model_label')
                    ->label('Model'),
                Tables\Columns\TextColumn::make('auditable_id')
                    ->label('Record ID'),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'login'   => 'Login',
                        'logout'  => 'Logout',
                    ]),

                Tables\Filters\SelectFilter::make('user_role')
                    ->label('Role')
                    ->options([
                        'super_admin' => 'Super Admin',
                        'admin'       => 'Admin',
                        'staff'       => 'Staff',
                    ]),

                Tables\Filters\Filter::make('user_name')
                    ->form([
                        Forms\Components\TextInput::make('user_name')->label('User Name'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['user_name'],
                        fn (Builder $q, string $v): Builder => $q->where('user_name', 'like', "%{$v}%")
                    )),

                Tables\Filters\SelectFilter::make('model_label')
                    ->label('Model')
                    ->options([
                        'Department'   => 'Department',
                        'Sku'          => 'SKU',
                        'PriceGroup'   => 'Price Group',
                        'DealGroup'    => 'Deal Group',
                        'MixAndMatch'  => 'Mix & Match',
                        'LoyaltyCard'  => 'Loyalty Card',
                        'TenderCoupon' => 'Tender Coupon',
                        'Payout'       => 'Payout',
                    ]),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('From'),
                        Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'], fn (Builder $q, string $v): Builder => $q->whereDate('created_at', '>=', $v))
                        ->when($data['until'], fn (Builder $q, string $v): Builder => $q->whereDate('created_at', '<=', $v))
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Event Details')->schema([
                Infolists\Components\TextEntry::make('created_at')->label('When')->dateTime(),
                Infolists\Components\TextEntry::make('action')->badge(),
                Infolists\Components\TextEntry::make('user_name')->label('User'),
                Infolists\Components\TextEntry::make('user_role')->label('Role'),
                Infolists\Components\TextEntry::make('model_label')->label('Model'),
                Infolists\Components\TextEntry::make('auditable_id')->label('Record ID'),
                Infolists\Components\TextEntry::make('ip_address')->label('IP Address'),
                Infolists\Components\TextEntry::make('user_agent')->label('User Agent')->columnSpanFull(),
            ])->columns(2),

            Infolists\Components\Section::make('Changes (Human Readable)')
                ->schema([
                    Infolists\Components\ViewEntry::make('diff')
                        ->view('filament.infolists.audit-diff')
                        ->columnSpanFull(),
                ])
                ->visible(fn (AuditLog $record): bool => $record->old_values !== null || $record->new_values !== null),

            Infolists\Components\Section::make('Raw JSON')
                ->schema([
                    Infolists\Components\TextEntry::make('old_values_raw')
                        ->label('Before')
                        ->getStateUsing(fn (AuditLog $record): string =>
                            $record->old_values ? json_encode($record->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '—'
                        )
                        ->fontFamily('mono')
                        ->columnSpan(1),
                    Infolists\Components\TextEntry::make('new_values_raw')
                        ->label('After')
                        ->getStateUsing(fn (AuditLog $record): string =>
                            $record->new_values ? json_encode($record->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '—'
                        )
                        ->fontFamily('mono')
                        ->columnSpan(1),
                ])
                ->columns(2)
                ->collapsed()
                ->visible(fn (AuditLog $record): bool => $record->old_values !== null || $record->new_values !== null),
        ]);
    }

    public static function formatValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $str = (string) $value;

        // Detect ISO 8601 (2026-04-28T04:50:20.000000Z) or MySQL datetime (2026-04-29 00:46:11)
        if (preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}/', $str)) {
            try {
                return Carbon::parse($str)->setTimezone('America/Edmonton')->format('M j, Y g:i A');
            } catch (InvalidFormatException) {
                // fall through
            }
        }

        return $str;
    }

    private static function buildHumanReadableDiff(AuditLog $record): string
    {
        $old = $record->old_values ?? [];
        $new = $record->new_values ?? [];
        $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));

        if (empty($allKeys)) {
            return '—';
        }

        $rows = '';

        if ($record->action === 'updated') {
            $header = '<tr class="border-b border-gray-200 dark:border-gray-700">'
                . '<th class="py-2 pr-6 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Field</th>'
                . '<th class="py-2 pr-6 text-left text-xs font-semibold uppercase tracking-wide text-red-500">Before</th>'
                . '<th class="py-2 text-left text-xs font-semibold uppercase tracking-wide text-green-500">After</th>'
                . '</tr>';

            foreach ($allKeys as $key) {
                $before = $old[$key] ?? null;
                $after  = $new[$key] ?? null;
                if ((string) $before === (string) $after) {
                    continue;
                }
                $label      = ucwords(str_replace('_', ' ', $key));
                $beforeHtml = $before !== null && $before !== ''
                    ? '<span class="text-red-600 line-through">' . e(static::formatValue($before)) . '</span>'
                    : '<em class="text-gray-400">—</em>';
                $afterHtml  = $after !== null && $after !== ''
                    ? '<span class="text-green-600">' . e(static::formatValue($after)) . '</span>'
                    : '<em class="text-gray-400">—</em>';
                $rows .= '<tr class="border-b border-gray-100 dark:border-gray-800">'
                    . "<td class='py-1.5 pr-6 font-medium text-gray-700 dark:text-gray-300 w-1/4'>{$label}</td>"
                    . "<td class='py-1.5 pr-6'>{$beforeHtml}</td>"
                    . "<td class='py-1.5'>{$afterHtml}</td>"
                    . '</tr>';
            }
        } elseif ($record->action === 'created') {
            $header = '<tr class="border-b border-gray-200 dark:border-gray-700">'
                . '<th class="py-2 pr-6 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Field</th>'
                . '<th class="py-2 text-left text-xs font-semibold uppercase tracking-wide text-green-500">Value</th>'
                . '</tr>';

            foreach ($allKeys as $key) {
                $val = $new[$key] ?? null;
                if ($val === null || $val === '') {
                    continue;
                }
                $label = ucwords(str_replace('_', ' ', $key));
                $rows .= '<tr class="border-b border-gray-100 dark:border-gray-800">'
                    . "<td class='py-1.5 pr-6 font-medium text-gray-700 dark:text-gray-300'>{$label}</td>"
                    . "<td class='py-1.5 text-green-600'>" . e(static::formatValue($val)) . '</td>'
                    . '</tr>';
            }
        } else { // deleted
            $header = '<tr class="border-b border-gray-200 dark:border-gray-700">'
                . '<th class="py-2 pr-6 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Field</th>'
                . '<th class="py-2 text-left text-xs font-semibold uppercase tracking-wide text-red-500">Value</th>'
                . '</tr>';

            foreach ($allKeys as $key) {
                $val = $old[$key] ?? null;
                if ($val === null || $val === '') {
                    continue;
                }
                $label = ucwords(str_replace('_', ' ', $key));
                $rows .= '<tr class="border-b border-gray-100 dark:border-gray-800">'
                    . "<td class='py-1.5 pr-6 font-medium text-gray-700 dark:text-gray-300'>{$label}</td>"
                    . "<td class='py-1.5 text-red-600'>" . e(static::formatValue($val)) . '</td>'
                    . '</tr>';
            }
        }

        if ($rows === '') {
            return '<p class="text-gray-400 text-sm italic">No field changes detected.</p>';
        }

        return '<table class="w-full text-sm table-fixed"><thead>' . $header . '</thead><tbody>' . $rows . '</tbody></table>';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}
