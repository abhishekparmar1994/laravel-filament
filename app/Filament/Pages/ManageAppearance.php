<?php

namespace App\Filament\Pages;

use App\Http\Middleware\ApplyUserLocale;
use App\Models\User;
use App\Support\ThemeManager;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageAppearance extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static string|UnitEnum|null $navigationGroup = 'Preferences';

    protected static ?string $navigationLabel = 'Appearance';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Theme & Language';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $user = auth()->user();

        $this->form->fill([
            'theme' => ThemeManager::resolveForUser($user instanceof User ? $user : null),
            'locale' => ($user instanceof User && in_array($user->locale, ApplyUserLocale::SUPPORTED_LOCALES, true))
                ? $user->locale
                : 'en',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Choose Your Theme')
                    ->description('Each user can customize their own admin panel look.')
                    ->schema([
                        Radio::make('theme')
                            ->label('Theme Preset')
                            ->options(ThemeManager::options())
                            ->descriptions(ThemeManager::descriptions())
                            ->required(),
                    ]),
                Section::make('Language & Locale')
                    ->schema([
                        Select::make('locale')
                            ->label('Interface Language')
                            ->options([
                                'en' => 'English',
                                'es' => 'Español',
                                'fr' => 'Français',
                                'hi' => 'हिन्दी',
                            ])
                            ->required()
                            ->native(false),
                    ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('appearance-form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make([
                    Action::make('save')
                        ->label('Apply Preferences')
                        ->submit('save'),
                ]),
            ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $theme = (string) ($state['theme'] ?? ThemeManager::DEFAULT_THEME);
        $locale = in_array($state['locale'] ?? 'en', ApplyUserLocale::SUPPORTED_LOCALES, true)
            ? (string) $state['locale']
            : 'en';

        $user = auth()->user();

        if ($user instanceof User) {
            $user->forceFill([
                'theme' => $theme,
                'locale' => $locale,
            ])->save();
        }

        app()->setLocale($locale);
        FilamentColor::register(ThemeManager::colorsFor($theme));

        Notification::make()
            ->title('Preferences updated')
            ->success()
            ->send();

        $this->redirect(static::getUrl(), navigate: true);
    }
}
