<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PC extends Model
{
    use HasFactory;

    protected $table = 'pc';

    protected $fillable = [
        'cpu_id',
        'gpu_id',
        'ram_id',
        'cpu_score',
        'gpu_score',
        'ram_score',
        'updated_at'
    ];

    // CPU kapcsolat a parts táblával
    public function cpu(): BelongsTo
    {
        return $this->belongsTo(Part::class, 'cpu_id');
    }

    public function gpu(): BelongsTo
    {
        return $this->belongsTo(Part::class, 'gpu_id');
    }

    public function ram(): BelongsTo
    {
        return $this->belongsTo(Part::class, 'ram_id');
    }
    public function storages(): HasMany
    {
        return $this->hasMany(Storage::class, 'pc_id');
    }
}
