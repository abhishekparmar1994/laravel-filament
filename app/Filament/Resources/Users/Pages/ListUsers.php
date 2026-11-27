<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\SystemNotifier;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    /**
     * @return array<int, Action|CreateAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Import CSV')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->can('create', User::class) ?? false)
                ->schema([
                    FileUpload::make('csv_file')
                        ->label('Users CSV File')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])
                        ->helperText('Columns: name,email,password (header row optional; password defaults to random if omitted).')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $disk = Storage::disk('local');
                    $path = (string) ($data['csv_file'] ?? '');

                    if ($path === '' || ! $disk->exists($path)) {
                        Notification::make()->title('CSV file could not be read')->danger()->send();

                        return;
                    }

                    $result = static::importFromCsvContent((string) $disk->get($path));
                    $disk->delete($path);

                    SystemNotifier::notifyAdmins(
                        'Users CSV Import Completed',
                        "Imported {$result['imported']} user(s), skipped {$result['skipped']}.",
                        'success',
                    );

                    Notification::make()
                        ->title("Imported {$result['imported']} user(s), skipped {$result['skipped']}")
                        ->success()
                        ->send();
                }),
            Action::make('export')
                ->label('Export CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn (): StreamedResponse => static::exportCsv()),
            CreateAction::make(),
        ];
    }

    /**
     * Import users from raw CSV content with duplicate-email and basic format validation.
     *
     * @return array{imported: int, skipped: int}
     */
    public static function importFromCsvContent(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($content)) ?: [];
        $imported = 0;
        $skipped = 0;

        foreach ($lines as $index => $line) {
            if (trim($line) === '') {
                continue;
            }

            $columns = str_getcsv($line);
            $name = trim((string) ($columns[0] ?? ''));
            $email = strtolower(trim((string) ($columns[1] ?? '')));
            $password = trim((string) ($columns[2] ?? ''));

            if ($index === 0 && str_contains($email, 'email') && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;

                continue;
            }

            if (User::withTrashed()->where('email', $email)->exists()) {
                $skipped++;

                continue;
            }

            User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password !== '' ? $password : Str::random(16),
                'is_active' => true,
            ]);

            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    public static function exportCsv(): StreamedResponse
    {
        $filename = 'users-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['ID', 'Name', 'Email', 'Roles', 'Active', 'Verified At', 'Last Login', 'Created At']);

            User::query()
                ->with('roles')
                ->orderBy('id')
                ->chunk(200, function ($users) use ($handle): void {
                    foreach ($users as $user) {
                        fputcsv($handle, [
                            $user->id,
                            $user->name,
                            $user->email,
                            $user->roles->pluck('name')->implode(', '),
                            $user->is_active ? 'Yes' : 'No',
                            $user->email_verified_at?->toDateTimeString() ?? '',
                            $user->last_login_at?->toDateTimeString() ?? '',
                            $user->created_at?->toDateTimeString() ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
