<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionStep extends Model
{
    protected $fillable = ['description', 'sort', 'completed'];

    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }
}

