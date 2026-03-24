<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Keyword extends Model
{
    protected $fillable = ['phrase', 'volume', 'difficulty'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
