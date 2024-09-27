<?php

namespace App\Services;

use App\Models\PC;
use App\Models\Part;
use App\Models\Storage;
use Illuminate\Pagination\LengthAwarePaginator;

class PartService
{
    public function search(string $search, string $sortBy = 'avg_score', string $sortOrder = 'desc', ?string $part = null): LengthAwarePaginator
    {
        $columns = ['id', 'part', 'brand', 'model', 'min_score', 'max_score', 'avg_score'];
        $query = Part::query();

        if (!empty($search)) {
            // Model alapján keresés
            $models = Part::where('model', 'ILIKE', "%$search%")
                ->distinct()
                ->pluck('model');

            if ($models->isNotEmpty()) {
                // Lekérdezi az egyedi partokat
                $parts = Part::whereIn('model', $models)
                    ->distinct()
                    ->pluck('part');

                //Beállítja az elsőt
                $part = $part ?? $parts->first();

                //Keresés a parts táblában
                $query->where('part', $part);
                foreach ($columns as $column) {
                    $query->orWhere($column, 'ILIKE', "%$search%");
                }
            }
        }

        // Rendezés
        $validSortByColumns = ['avg_score', 'id', 'part', 'brand', 'model', 'min_score', 'max_score', 'avg_score'];
        $sortBy = in_array($sortBy, $validSortByColumns) ? $sortBy : 'avg_score';
        $query->orderBy($sortBy, $sortOrder);

        // Lapozás
        return $query->paginate(10);
    }

    //Pontok lekérése
    public function getPartScores($partId): array
    {
        $part = Part::find($partId);

        //cpu, gpu, ram a pc-ből
        $cpuScores = PC::where('cpu_id', $part->cpu_id)
            ->pluck('cpu_score')
            ->map(fn($score) => (float)$score)
            ->toArray();

        $gpuScores = PC::where('gpu_id', $part->gpu_id)
            ->pluck('gpu_score')
            ->map(fn($score) => (float)$score)
            ->toArray();

        $ramScores = PC::where('ram_id', $part->ram_id)
            ->pluck('ram_score')
            ->map(fn($score) => (float)$score)
            ->toArray();

        //ssd hdd a storage táblából
        $storageScores = Storage::where('storage_id', $part->storage_id)
            ->pluck('score')
            ->map(fn($score) => (float)$score)
            ->toArray();

        //Minden pont
        $scores = collect()
            ->merge($cpuScores)
            ->merge($gpuScores)
            ->merge($ramScores)
            ->merge($storageScores)
            ->sort()
            ->values()
            ->toArray();

        if (empty($scores)) {
            return [
                'scores' => [],
                'minScore' => 'N/A',
                'avgScore' => 'N/A',
                'maxScore' => 'N/A',
            ];
        }

        $minScore = min($scores);
        $maxScore = max($scores);
        $avgScore = array_sum($scores) / count($scores);

        return [
            'scores' => $scores,
            'minScore' => $minScore,
            'avgScore' => $avgScore,
            'maxScore' => $maxScore,
        ];
    }
    public function getPartBrand($partId): ?string
    {
        $part = Part::find($partId);
        return $part ? $part->brand : null;
    }
}
