<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Competitor extends Model
{
    protected $fillable = ['domain'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}

