<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'event', 'subject_type', 'subject_id', 'description', 'properties', 'ip_address'])]
class ActivityLog extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    /**
     * The user who performed the recorded action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
