<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competitor extends Model
{
    protected $fillable = ['domain'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function competitorScans(): HasMany
    {
        return $this->hasMany(CompetitorScan::class);
    }
}

