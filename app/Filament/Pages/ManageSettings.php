<?php

namespace App\Filament\Pages;

use App\Mail\TestMail;
use App\Models\Setting;
use App\Models\User;
use App\Support\SystemNotifier;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 11;

    protected static ?string $title = 'Application Settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermission('settings.manage') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'site_name' => (string) Setting::getValue('site_name', config('app.name')),
            'site_tagline' => (string) Setting::getValue('site_tagline', 'Startup Admin Boilerplate'),
            'support_email' => (string) Setting::getValue('support_email', 'support@example.com'),
            'maintenance_banner' => (string) Setting::getValue('maintenance_banner', ''),
            'registration_enabled' => Setting::getValue('registration_enabled', '0') === '1',
        ]);
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTestNotification')
                ->label('Send Test Notification')
                ->icon(Heroicon::OutlinedBell)
                ->color('gray')
                ->action(function (): void {
                    $user = auth()->user();

                    if ($user instanceof User) {
                        SystemNotifier::notifyUser(
                            $user,
                            'Test Database Notification',
                            'Your database notifications drawer is configured and working.',
                            'success',
                        );

                        $this->dispatch('databaseNotificationsSent');
                    }

                    Notification::make()
                        ->title('Test notification sent to your Notifications drawer')
                        ->success()
                        ->send();
                }),
            Action::make('sendTestEmail')
                ->label('Send Test Email')
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('gray')
                ->schema([
                    TextInput::make('recipient')
                        ->label('Recipient Email')
                        ->email()
                        ->required()
                        ->default(fn (): string => (string) (auth()->user()?->email ?? '')),
                ])
                ->action(function (array $data): void {
                    $siteName = (string) Setting::getValue('site_name', config('app.name'));
                    Mail::to((string) $data['recipient'])->send(new TestMail($siteName));

                    Notification::make()
                        ->title("Test email dispatched to {$data['recipient']}")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('General')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('site_name')
                            ->label('Application Name')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('support_email')
                            ->label('Support Email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('site_tagline')
                            ->label('Tagline')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
                Section::make('System & Access')
                    ->schema([
                        TextInput::make('maintenance_banner')
                            ->label('Notice Banner')
                            ->placeholder('Optional announcement shown to admins')
                            ->maxLength(255),
                        Toggle::make('registration_enabled')
                            ->label('Allow Self-Registration'),
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
            ->id('settings-form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make([
                    Action::make('save')
                        ->label('Save Settings')
                        ->submit('save')
                        ->keyBindings(['mod+s']),
                ]),
            ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Setting::setValue('site_name', $state['site_name'], 'general', 'string');
        Setting::setValue('site_tagline', $state['site_tagline'] ?? '', 'general', 'string');
        Setting::setValue('support_email', $state['support_email'], 'general', 'string');
        Setting::setValue('maintenance_banner', $state['maintenance_banner'] ?? '', 'system', 'string');
        Setting::setValue('registration_enabled', (bool) ($state['registration_enabled'] ?? false), 'auth', 'boolean');

        $actor = auth()->user();

        SystemNotifier::notifyAdmins(
            'Application Settings Updated',
            'System configuration was updated.',
            'info',
            $actor instanceof User ? $actor : null,
        );

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }
}
