<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Overview')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email'),
                        TextEntry::make('roles.name')
                            ->label('Roles')
                            ->badge()
                            ->placeholder('No role assigned'),
                        IconEntry::make('is_active')
                            ->label('Active Account')
                            ->boolean(),
                        TextEntry::make('theme')
                            ->label('Selected Theme')
                            ->badge(),
                        TextEntry::make('last_login_at')
                            ->label('Last Login')
                            ->dateTime()
                            ->placeholder('Never'),
                        TextEntry::make('email_verified_at')
                            ->label('Email Verified At')
                            ->dateTime()
                            ->placeholder('Unverified'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ]),
            ]);
    }
}
