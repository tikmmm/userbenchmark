<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

// php artisan generate:pc
class GeneratePC extends Command
{
    protected $signature = 'generate:pc {count=1}';
    protected $description = 'Generates a specified number of PCs with random components';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $startTime = microtime(true);
        $count = (int) $this->argument('count');
        $generatedPCs = 0;

        for ($i = 0; $i < $count; $i++) {
            $this->generatePC();
            $generatedPCs++;
        }

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) / 60;

        $this->info('Task completed in ' . number_format($executionTime, 2) . ' minutes.');
        $this->info('Total PCs generated: ' . $generatedPCs);

        Log::info('php artisan generate:pc task completed in ' . number_format($executionTime, 2) . ' minutes. Total PCs generated: ' . $generatedPCs);
    }

    protected function generatePC(): void
    {
        $currentTime = Carbon::now();
        //CPU
        $cpu = DB::table('hardwares')
            ->where('part', 'CPU')
            ->inRandomOrder()
            ->first();
        $cpu_score = $this->getRandomScore($cpu->score);

        //GPU
        $gpuQuery = DB::table('hardwares')
            ->where('part', 'GPU');

        //AMD nem lehet intel
        if ($cpu->brand === 'AMD') {
            $gpuQuery->where('brand', '!=', 'Intel');
        }

        $gpu = $gpuQuery
            ->inRandomOrder()
            ->first();
        $gpu_score = $this->getRandomScore($gpu->score);

        //RAM
        $ram = DB::table('hardwares')
            ->where('part', 'RAM')
            ->inRandomOrder()
            ->first();
        $ram_score = $this->getRandomScore($ram->score);

        //SSD/HDD
        $selectedStorages = DB::table('hardwares')
            ->whereIn('part', ['SSD', 'HDD'])
            ->inRandomOrder()
            ->limit(rand(1, 5))
            ->get();
        $ssds = $selectedStorages->where('part', 'SSD');
        $hdds = $selectedStorages->where('part', 'HDD');

        // Legmagasabb SSD vagy HDD
        $ssd_score = 0;
        if ($ssds->isNotEmpty()) {
            $ssd_score = $ssds->max('score');
        } elseif ($hdds->isNotEmpty()) {
            $ssd_score = $hdds->max('score');
        }

        $gamer_score = ($cpu_score * 0.3) + ($gpu_score * 0.5) + ($ssd_score * 0.1) + ($ram_score * 0.1);
        $workstation_score = ($cpu_score * 0.5) + ($ram_score * 0.2) + ($ssd_score * 0.2) + ($gpu_score * 0.1);
        $desktop_score = ($cpu_score * 0.3) + ($ram_score * 0.3) + ($ssd_score * 0.3) + ($gpu_score * 0.1);

        //Beszúrja az adatokat a pc táblába
        $pc_id = DB::table('pc')->insertGetId([
            'cpu_id' => $cpu->id,
            'gpu_id' => $gpu->id,
            'ram_id' => $ram->id,
            'cpu_score' => $cpu_score,
            'gpu_score' => $gpu_score,
            'ram_score' => $ram_score,
            'gamer_score' => $gamer_score,
            'workstation_score' => $workstation_score,
            'desktop_score' => $desktop_score,
            'created_at' => $currentTime,
            'updated_at' => $currentTime,
        ]);

        //Beszúrja az adatokat a storage táblába
        foreach ($ssds as $ssd) {
            DB::table('storage')->insert([
                'pc_id' => $pc_id,
                'storage_id' => $ssd->id,
                'type' => 'SSD',
                'score' => $this->getRandomScore($ssd->score),
                'created_at' => $currentTime,
                'updated_at' => $currentTime,
            ]);
        }
        foreach ($hdds as $hdd) {
            DB::table('storage')->insert([
                'pc_id' => $pc_id,
                'storage_id' => $hdd->id,
                'type' => 'HDD',
                'score' => $this->getRandomScore($hdd->score),
                'created_at' => $currentTime,
                'updated_at' => $currentTime,
            ]);
        }

        //Kiírja a generált pc azonosítóját
        $this->info('A new PC has been generated with ID: ' . $pc_id);
    }

    //Random pontot generál
    protected function getRandomScore($score): float
    {
        $min = $score * 0.9;
        $max = $score * 1.1;
        return mt_rand($min * 100, $max * 100) / 100;
    }
}
