<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mission extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'title', 'description', 'category', 'type', 'priority', 'status',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(MissionStep::class)->orderBy('sort');
    }
}

