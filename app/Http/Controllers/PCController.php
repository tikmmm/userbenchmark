<?php

namespace App\Http\Controllers;

use App\Services\PCService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Exception;
use App\Models\PC;

class PCController extends Controller
{
    protected PCService $pcService;

    public function __construct(PCService $pcService)
    {
        $this->pcService = $pcService;
    }

    public function index($id): View
    {
        $pc = PC::with(['cpu', 'gpu', 'ram', 'storages.part'])->find($id);

        $scores = $this->pcService->calculateScores([
            'cpu_id' => $pc->cpu_id,
            'gpu_id' => $pc->gpu_id,
            'ram_id' => $pc->ram_id,
            'storages' => $pc->storages,
        ]);

        return view('pc.index', [
            'pc' => $pc,
            'gamerScore' => $scores['gamer'],
            'workstationScore' => $scores['workstation'],
            'desktopScore' => $scores['desktop'],
        ]);
    }

    public function addPartToPC(Request $request): RedirectResponse
    {
        try {
            if (!session()->isStarted()) {
                session()->start();
            }

            $partType = $request->input('part_type');
            $partId = $request->input('part_id');

            $pcParts = session()->get('pc_parts', [
                'cpu_id' => null,
                'gpu_id' => null,
                'ram_id' => null,
                'storages' => [],
            ]);

            $pcParts = $this->pcService->addPartToPC($pcParts, $partType, $partId);

            session()->put('pc_parts', $pcParts);

            return redirect()->route('part.show', ['part' => strtoupper($partType)])
                ->with('success', 'Part added successfully!');
        } catch (Exception $e) {
            Log::error('Error adding part to PC: ' . $e->getMessage());

            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function removePartFromPC(Request $request): RedirectResponse
    {
        try {
            if (!session()->isStarted()) {
                session()->start();
            }

            $partType = $request->input('part_type');
            $partId = $request->input('part_id');

            $pcParts = session()->get('pc_parts', [
                'cpu_id' => null,
                'gpu_id' => null,
                'ram_id' => null,
                'storages' => [],
            ]);

            $pcParts = $this->pcService->removePartFromPC($pcParts, $partType, $partId);

            session()->put('pc_parts', $pcParts);

            return redirect()->back()->with('success', 'Part removed successfully!');
        } catch (Exception $e) {
            Log::error('Error removing part from PC: ' . $e->getMessage());

            return redirect()->back()->withErrors($e->getMessage());
        }
    }

    public function save(): RedirectResponse
    {
        try {
            $pcParts = session('pc_parts', [
                'cpu_id' => null,
                'gpu_id' => null,
                'ram_id' => null,
                'storages' => [],
            ]);

            $pc = $this->pcService->savePC($pcParts);

            session()->forget('pc_parts');

            return redirect()->route('pc.index', ['id' => $pc->id])->with('success', 'PC saved successfully!');
        } catch (Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }
    }
}
