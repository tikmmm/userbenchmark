<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hardware extends Model
{
    protected $table = 'hardwares';

    protected $fillable = [
        'id',
        'part',
        'brand',
        'model',
        'score',
    ];
    public function pcCpu(): HasMany
    {
        return $this->hasMany(PC::class, 'cpu_id');
    }

    public function pcGpu(): HasMany
    {
        return $this->hasMany(PC::class, 'gpu_id');
    }

    public function pcRam(): HasMany
    {
        return $this->hasMany(PC::class, 'ram_id');
    }

    public function pcStorage(): HasMany
    {
        return $this->hasMany(Storage::class, 'storage_id');
    }
}
