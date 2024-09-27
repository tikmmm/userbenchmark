<?php

namespace App\Http\Controllers;

use App\Services\PartService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Part;
use Illuminate\Http\JsonResponse;

class PartController extends Controller
{
    private PartService $partService;

    public function __construct(PartService $partService)
    {
        $this->partService = $partService;
    }

    public function show(Request $request): View
    {
        // Rendezés és keresés
        $sortBy = $request->input('sort_by', 'avg_score');
        $sortOrder = $request->input('sort_order', 'desc');
        $search = $request->input('search', '');

        // Kiválasztott part és brand
        $selectedPart = $request->input('part', 'CPU');
        $selectedBrand = $request->input('brand', '');

        // Egyedi part értékek
        $partsDistinct = Part::distinct()->pluck('part');
        $selectedPart = $selectedPart ?: $partsDistinct->first();

        // Part frissítése a keresés alapján
        if (!empty($search)) {
            $searchResults = Part::where('model', 'ILIKE', "%$search%")
                ->distinct()
                ->pluck('part');

            if ($searchResults->isNotEmpty() && !$searchResults->contains($selectedPart)) {
                $selectedPart = $searchResults->first();
            }
        }

        // Egyedi brand értékek a kiválasztott part alapján
        $brands = Part::where('part', $selectedPart)
            ->distinct()
            ->pluck('brand');

        // Keresés
        $query = Part::where('part', $selectedPart);

        if (!empty($search)) {
            $search = strtolower($search);
            $columns = ['id', 'part', 'brand', 'model', 'min_score', 'max_score', 'avg_score'];
            $query->where(function ($q) use ($search, $columns) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'ILIKE', "%$search%");
                }
            });
        }

        // Brand szűrő
        if ($selectedBrand) {
            $query->where('brand', $selectedBrand);
        }

        // Eredmény
        $partsData = $query
            ->orderBy($sortBy, $sortOrder)
            ->paginate(10);

        return view('part.show', [
            'partsData' => $partsData,
            'search' => $search,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
            'brands' => $brands,
            'selectedBrand' => $selectedBrand,
            'partsDistinct' => $partsDistinct,
            'selectedPart' => $selectedPart
        ]);
    }

    public function showModel(string $model): View
    {
        $part = Part::where('model', $model)
            ->with(['cpuPcs', 'gpuPcs', 'ramPcs', 'storagePcs'])
            ->firstOrFail();

        $partScores = $this->partService->getPartScores($part->id);
        $minScore = $partScores['minScore'] ?? 0.0;
        $avgScore = $partScores['avgScore'] ?? 0.0;
        $maxScore = $partScores['maxScore'] ?? 0.0;

        return view('part.model', [
            'part' => $part,
            'scores' => $partScores['scores'] ?? [],
            'minScore' => floatval($minScore),
            'avgScore' => floatval($avgScore),
            'maxScore' => floatval($maxScore),
        ]);
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $query = $request->get('query', '');

        if (empty($query)) {
            return response()->json([]);
        }

        $models = Part::where('model', 'ILIKE', "%$query%")
            ->limit(10)
            ->pluck('model');

        return response()->json($models);
    }
}
