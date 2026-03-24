<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorScan extends Model
{
    protected $fillable = ['competitor_id', 'site_id', 'business_name', 'results', 'passed_count', 'failed_count', 'total_checks'];

    protected function casts(): array
    {
        return [
            'results' => 'array',
        ];
    }

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}

