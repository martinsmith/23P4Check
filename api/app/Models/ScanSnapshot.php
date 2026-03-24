<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanSnapshot extends Model
{
    protected $fillable = ['site_id', 'passed_count', 'failed_count', 'total_checks'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}

