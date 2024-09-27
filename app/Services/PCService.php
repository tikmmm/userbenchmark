<?php

namespace App\Services;

use App\Models\PC;
use App\Models\Storage;
use Exception;
use Illuminate\Support\Facades\Log;

class PCService
{
    protected PartService $partService;

    public function __construct(PartService $partService)
    {
        $this->partService = $partService;
    }

    public function addPartToPC(array $pcParts, string $partType, int $partId): array
    {
        // Max 5 storage
        if (in_array($partType, ['ssd', 'hdd']) && count($pcParts['storages']) >= 5) {
            throw new Exception('You can only add up to 5 storages (HDD or SSD).');
        }

        // ADM nem lehet Intel
        if ($partType === 'cpu' || $partType === 'gpu') {
            $newPartBrand = $this->partService->getPartBrand($partId);

            if ($partType === 'cpu' && $pcParts['gpu_id']) {
                $currentGpuBrand = $this->partService->getPartBrand($pcParts['gpu_id']);
                if ($this->admIntel($newPartBrand, $currentGpuBrand)) {
                    throw new Exception('Cannot add AMD with Intel');
                }
            }
            if ($partType === 'gpu' && $pcParts['cpu_id']) {
                $currentCpuBrand = $this->partService->getPartBrand($pcParts['cpu_id']);
                if ($this->admIntel($newPartBrand, $currentCpuBrand)) {
                    throw new Exception('Cannot add AMD with Intel');
                }
            }
        }

        switch ($partType) {
            case 'cpu':
                $pcParts['cpu_id'] = $partId;
                break;
            case 'gpu':
                $pcParts['gpu_id'] = $partId;
                break;
            case 'ram':
                $pcParts['ram_id'] = $partId;
                break;
            case 'ssd':
            case 'hdd':
                $pcParts['storages'][] = [
                    'storage_id' => $partId,
                    'type' => strtoupper($partType),
                ];
                break;
        }

        return $pcParts;
    }

    public function removePartFromPC(array $pcParts, string $partType, int $partId): array
    {
        switch ($partType) {
            case 'cpu':
                if ($pcParts['cpu_id'] === $partId) {
                    $pcParts['cpu_id'] = null;
                }
                break;
            case 'gpu':
                if ($pcParts['gpu_id'] === $partId) {
                    $pcParts['gpu_id'] = null;
                }
                break;
            case 'ram':
                if ($pcParts['ram_id'] === $partId) {
                    $pcParts['ram_id'] = null;
                }
                break;
            case 'ssd':
            case 'hdd':
            foreach ($pcParts['storages'] as $key => $storage) {
                if ($storage['storage_id'] === $partId && $storage['type'] === strtoupper($partType)) {
                    unset($pcParts['storages'][$key]);
                    break;
                }
            }
            $pcParts['storages'] = array_values($pcParts['storages']);
            break;
        }

        return $pcParts;
    }

    public function savePC(array $pcParts): PC
    {
        if (!$pcParts['cpu_id'] || !$pcParts['gpu_id'] || !$pcParts['ram_id'] || empty($pcParts['storages'])) {
            throw new Exception('Missing parts!');
        }

        // PC Mentés
        $pc = PC::create([
            'cpu_id' => $pcParts['cpu_id'],
            'gpu_id' => $pcParts['gpu_id'],
            'ram_id' => $pcParts['ram_id'],
            'cpu_score' => $this->partService->getPartScores($pcParts['cpu_id'])['avgScore'],
            'gpu_score' => $this->partService->getPartScores($pcParts['gpu_id'])['avgScore'],
            'ram_score' => $this->partService->getPartScores($pcParts['ram_id'])['avgScore'],
            'storage_score' => collect($pcParts['storages'])->avg(function ($storage) {
                return $this->partService->getPartScores($storage['storage_id'])['avgScore'];
            }),
            'updated_at' => now(),
        ]);
        Log::info('PC created successfully.', ['pc_id' => $pc->id]);

        $scores = $this->calculateScores($pcParts);
        $pc->gamer_score = $scores['gamer'];
        $pc->workstation_score = $scores['workstation'];
        $pc->desktop_score = $scores['desktop'];
        $pc->save();

        // Storage mentés
        foreach ($pcParts['storages'] as $storage) {
            Storage::create([
                'pc_id' => $pc->id,
                'storage_id' => $storage['storage_id'],
                'type' => $storage['type'],
                'score' => $this->partService->getPartScores($storage['storage_id'])['avgScore'],
                'updated_at' => now(),
            ]);
        }
        Log::info('Storages saved for PC.', ['pc_id' => $pc->id, 'storages' => $pcParts['storages']]);

        return $pc;

    }

    public function calculateScores(array $pcParts): array
    {
        $cpuScore = $this->partService->getPartScores($pcParts['cpu_id'])['avgScore'] ?? 0;
        $gpuScore = $this->partService->getPartScores($pcParts['gpu_id'])['avgScore'] ?? 0;
        $ramScore = $this->partService->getPartScores($pcParts['ram_id'])['avgScore'] ?? 0;

        $storages = $pcParts['storages'] ?? [];
        $ssdScore = collect($storages)
            ->where('type', 'SSD')
            ->pluck('storage_id')
            ->map(fn($id) => $this->partService->getPartScores($id)['avgScore'] ?? 0)
            ->max() ?? 0;

        return [
            'gamer' => ($cpuScore * 0.3) + ($gpuScore * 0.5) + ($ssdScore * 0.1) + ($ramScore * 0.1),
            'workstation' => ($cpuScore * 0.5) + ($ramScore * 0.2) + ($ssdScore * 0.2) + ($gpuScore * 0.1),
            'desktop' => ($cpuScore * 0.3) + ($ramScore * 0.3) + ($ssdScore * 0.3) + ($gpuScore * 0.1),
        ];
    }

    protected function admIntel(string $newPartBrand, string $currentBrand): bool
    {
        return ($newPartBrand === 'AMD' && $currentBrand === 'Intel') || ($newPartBrand === 'Intel' && $currentBrand === 'AMD');
    }
}
