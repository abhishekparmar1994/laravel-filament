<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestUsersTable extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', User::class) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Users')
            ->query(
                User::query()
                    ->with('roles')
                    ->latest()
                    ->limit(5),
            )
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->placeholder('No role'),
                TextColumn::make('email_verified_at')
                    ->label('Verified')
                    ->dateTime()
                    ->placeholder('Unverified'),
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->since(),
            ])
            ->recordActions([
                Action::make('edit')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (User $record): string => UserResource::getUrl('edit', ['record' => $record]))
                    ->visible(fn (User $record): bool => auth()->user()?->can('update', $record) ?? false),
            ]);
    }
}
