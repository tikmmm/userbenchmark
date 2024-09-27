<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Storage extends Model
{
    use HasFactory;

    protected $table = 'storage';

    protected $fillable = [
        'pc_id',
        'storage_id',
        'type',
        'score',
        'updated_at',
    ];

    //Kapcsolat a pc-vel
    public function pc(): BelongsTo
    {
        return $this->belongsTo(PC::class, 'pc_id');
    }

    //Kapcsolat a part-al
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class, 'storage_id');
    }
}
