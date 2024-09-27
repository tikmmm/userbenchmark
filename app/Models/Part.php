<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Part extends Model
{
    use HasFactory;

    protected $table = 'parts';

    protected $fillable = [
        'id',
        'brand',
        'model',
        'part',
        'min_score',
        'max_score',
        'avg_score',
        'cpu_id',
        'gpu_id',
        'ram_id',
        'storage_id',
        'updated_at',
    ];

    //PC kapcsolatok (cpu, gpu, ram)
    public function cpuPcs(): HasMany
    {
        return $this->hasMany(PC::class, 'cpu_id', 'cpu_id');
    }
    public function gpuPcs(): HasMany
    {
        return $this->hasMany(PC::class, 'gpu_id', 'gpu_id');
    }
    public function ramPcs(): HasMany
    {
        return $this->hasMany(PC::class, 'ram_id', 'ram_id');
    }

    //Storage kapcsolat (ssd, hdd)
    public function storagePcs(): HasMany
    {
        return $this->hasMany(Storage::class, 'storage_id', 'storage_id');
    }
}
