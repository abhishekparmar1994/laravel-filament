<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('event')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        'restored', 'impersonated' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('description')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('System')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subject_type')
                    ->label('Resource')
                    ->formatStateUsing(fn(?string $state): string => $state ? class_basename($state) : '-')
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'restored' => 'Restored',
                        'impersonated' => 'Impersonated',
                        'stopped_impersonation' => 'Stopped Impersonation',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
