<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\PC;
use App\Models\Storage;
use Illuminate\Support\Facades\Log;

// php artisan update:parts
class UpdatePartsTable extends Command
{
    protected $signature = 'update:parts';
    protected $description = 'Updates the parts table';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $startTime = microtime(true);
        $updatedCount = 0;
        $insertedCount = 0;
        $lastPartsUpdate = DB::table('parts')->max('updated_at');

        $batchSize = 5000;

        // PC tábla vizsgálata
        $pcPartsQuery = DB::table('pc');

        // Megnézi, hogy van-e a pc táblában új rekord a parts táblához képest
        if ($lastPartsUpdate) {
            $pcPartsQuery->where('updated_at', '>', $lastPartsUpdate);
        }

        $pcPartsQuery->orderBy('id');

        $pcPartsQuery->chunk($batchSize, function ($pcParts) use (&$updatedCount, &$insertedCount) {
            foreach ($pcParts as $pcPart) {

                // CPU frissítése vagy beszúrása
                if ($pcPart->cpu_id) {
                    if ($this->partExists($pcPart->cpu_id)) {
                        $this->info("Updating CPU part with ID: $pcPart->cpu_id");
                        $this->updatePartScores($pcPart->cpu_id);
                        $updatedCount++;
                    } else {
                        $this->info("Inserting new CPU part with ID: $pcPart->cpu_id");
                        $this->insertNewPart($pcPart->cpu_id, 'cpu');
                        $insertedCount++;
                    }
                }

                // GPU frissítése vagy beszúrása
                if ($pcPart->gpu_id) {
                    if ($this->partExists($pcPart->gpu_id)) {
                        $this->info("Updating GPU part with ID: $pcPart->gpu_id");
                        $this->updatePartScores($pcPart->gpu_id);
                        $updatedCount++;
                    } else {
                        $this->info("Inserting new GPU part with ID: $pcPart->gpu_id");
                        $this->insertNewPart($pcPart->gpu_id, 'gpu');
                        $insertedCount++;
                    }
                }

                // RAM frissítése vagy beszúrása
                if ($pcPart->ram_id) {
                    if ($this->partExists($pcPart->ram_id)) {
                        $this->info("Updating RAM part with ID: $pcPart->ram_id");
                        $this->updatePartScores($pcPart->ram_id);
                        $updatedCount++;
                    } else {
                        $this->info("Inserting new RAM part with ID: $pcPart->ram_id");
                        $this->insertNewPart($pcPart->ram_id, 'ram');
                        $insertedCount++;
                    }
                }
            }
        });

        // Storage tábla vizsgálata
        $storagePartsQuery = DB::table('storage');

        if ($lastPartsUpdate) {
            $storagePartsQuery->where('updated_at', '>', $lastPartsUpdate);
        }

        $storagePartsQuery->orderBy('id');

        $storagePartsQuery->chunk($batchSize, function ($storageParts) use (&$updatedCount, &$insertedCount) {
            foreach ($storageParts as $storagePart) {
                $partId = $storagePart->storage_id;

                // Storage frissítése vagy beszúrása
                if ($partId) {
                    if ($this->partExists($partId)) {
                        $this->info("Updating storage part with ID: $partId");
                        $this->updatePartScores($partId);
                        $updatedCount++;
                    } else {
                        $this->info("Inserting new storage part with ID: $partId");
                        $this->insertNewPart($partId, 'storage');
                        $insertedCount++;
                    }
                }
            }
        });

        $this->info('Total updates: ' . $updatedCount);
        $this->info('Total inserts: ' . $insertedCount);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) / 60;

        $this->info('Task completed, execution time: ' . number_format($executionTime, 2) . ' minutes.');

        Log::info('php artisan update:parts task completed in ' . number_format($executionTime, 2) . ' minutes. Total updates: ' . $updatedCount. '. Total inserts: ' . $insertedCount);
    }

    private function partExists($partId): bool
    {
        // Ellenőrzi, hogy az adott part már létezik-e a parts táblában
        return DB::table('parts')
            ->where('brand', function ($query) use ($partId) {
                $query->select('brand')
                    ->from('hardwares')
                    ->where('id', $partId);
            })
            ->where('model', function ($query) use ($partId) {
                $query->select('model')
                    ->from('hardwares')
                    ->where('id', $partId);
            })
            ->where('part', function ($query) use ($partId) {
                $query->select('part')
                    ->from('hardwares')
                    ->where('id', $partId);
            })
            ->exists();
    }

    private function updatePartScores($partId): void
    {
        $scores = $this->getPartScores($partId);

        // Frissíti a parts táblában a pontokat
        DB::table('parts')
            ->where('cpu_id', $partId)
            ->orWhere('gpu_id', $partId)
            ->orWhere('ram_id', $partId)
            ->orWhere('storage_id', $partId)
            ->update([
                'min_score' => $scores['minScore'],
                'avg_score' => $scores['avgScore'],
                'max_score' => $scores['maxScore'],
                'updated_at' => now(),
            ]);
    }

    private function insertNewPart($partId, $partType): void
    {
        // Lekéri a hardware adatokat a brand, model és part adatok beszúrásához
        $hardware = DB::table('hardwares')->where('id', $partId)->first();
        // Pontok
        $scores = $this->getPartScores($partId);

        // Beszúrja a parts táblába, ha nem létezik
        if (!$this->partExists($partId)) {
            DB::table('parts')->insert([
                'brand' => $hardware->brand,
                'model' => $hardware->model,
                'part' => $hardware->part,
                'cpu_id' => $partType === 'cpu' ? $partId : null, // CPU id, ha az alkatrész CPU egyébként null
                'gpu_id' => $partType === 'gpu' ? $partId : null, // GPU id, ha az alkatrész GPU egyébként null
                'ram_id' => $partType === 'ram' ? $partId : null, // Ram id, ha az alkatrész Ram egyébként null
                'storage_id' => $partType === 'storage' ? $partId : null, // Storage id, ha az alkatrész Storage egyébként null
                'min_score' => $scores['minScore'],
                'avg_score' => $scores['avgScore'],
                'max_score' => $scores['maxScore'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
    private function getPartScores($partId): array
    {
        // Kinyeri az a pontokat a PC táblából
        $cpuScores = PC::where('cpu_id', $partId)
            ->pluck('cpu_score')
            ->map(fn($score) => (float)$score)
            ->toArray();
        $gpuScores = PC::where('gpu_id', $partId)
            ->pluck('gpu_score')
            ->map(fn($score) => (float)$score)
            ->toArray();
        $ramScores = PC::where('ram_id', $partId)
            ->pluck('ram_score')
            ->map(fn($score) => (float)$score)
            ->toArray();

        // Kinyeri az a pontokat a Storage táblából
        $storageScores = Storage::where('storage_id', $partId)
            ->pluck('score')
            ->map(fn($score) => (float)$score)
            ->toArray();

        // Kiszámolja a pontokat
        $scores = collect()
            ->merge($cpuScores)
            ->merge($gpuScores)
            ->merge($ramScores)
            ->merge($storageScores)
            ->sort()
            ->values()
            ->toArray();

        $minScore = min($scores);
        $maxScore = max($scores);
        $avgScore = array_sum($scores) / count($scores);

        return [
            'minScore' => number_format($minScore, 2),
            'avgScore' => number_format($avgScore, 2),
            'maxScore' => number_format($maxScore, 2),
        ];
    }
}
