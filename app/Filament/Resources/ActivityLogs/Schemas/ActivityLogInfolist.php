<?php

namespace App\Filament\Resources\ActivityLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Audit Details')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('event')
                            ->badge(),
                        TextEntry::make('user.name')
                            ->label('Performed By')
                            ->placeholder('System'),
                        TextEntry::make('subject_type')
                            ->label('Model')
                            ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-'),
                        TextEntry::make('subject_id')
                            ->label('Record ID'),
                        TextEntry::make('ip_address')
                            ->label('IP Address')
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('description')
                            ->columnSpanFull(),
                        TextEntry::make('properties')
                            ->label('Changed Data')
                            ->formatStateUsing(fn (mixed $state): string => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : 'No payload')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
