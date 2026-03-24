<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SerpResult extends Model
{
    protected $fillable = [
        'keyword', 'position', 'result_url', 'snippet', 'total_results',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}

