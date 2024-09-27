<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('js/autocomplete.js') }}"></script>
    <script>
        var autocompleteUrl = "{{ route('part.autocomplete') }}";
    </script>
    <title>Parts</title>
</head>
<body>

<!-- Part gombsor -->
<div class="tab-selector">
    @foreach ($partsDistinct as $partValue)
        <button
            class="tab-button {{ $selectedPart === $partValue ? 'active' : '' }}"
            onclick="location.href='{{ route('part.show', ['part' => $partValue]) }}'">
            {{ $partValue }}
        </button>
    @endforeach
</div>
<br>

<!-- Kereső -->
<form method="GET" action="{{ route('part.show') }}">
    <input type="hidden" name="sort_by" value="avg_score">
    <input type="hidden" name="sort_order" value="desc">
    <input type="hidden" name="part" value="{{ request('part') }}">
    <input type="text" name="search" id="search" placeholder="Search..." autocomplete="off" value="{{ request('search') }}">
    <button type="submit" class="button">Search</button>
</form>

<div class="autocomplete-suggestions" id="autocomplete-suggestions"></div>

<!-- Táblázat -->
<div class="table-container">
    <table border="1">
        <thead>
        <tr>
            <th>Brand</th>
            <th>Model</th>
            <th>Min Score</th>
            <th>Max Score</th>
            <th>Avg Score</th>
            <th>Add to pc</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($partsData as $part)
            <tr>
                <td>{{ $part->brand }}</td>
                <td><a href="{{ route('part.model', ['model' => $part->model]) }}" class="model-link">{{ $part->model }}</a></td>
                <td>{{ $part->min_score }}</td>
                <td>{{ $part->max_score }}</td>
                <td>{{ $part->avg_score }}</td>
                <td class="center-cell">
                    <form method="POST" action="{{ route('part.addToPC') }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="part_type" value="{{ strtolower($selectedPart) }}">
                        <input type="hidden" name="part_id" value="{{ $part->id }}">
                        <input type="submit" value="+" style="border: none; background: none; cursor: pointer; font-size: 24px;">
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@if ($partsData)
    <p>Results: {{ $partsData->total() }}</p>
@endif

<!-- Lapozás -->
<div class="pagination-container">
    <ul class="pagination">
        @if ($partsData->onFirstPage())
            <li><span class="first" style="color: #ddd;">«</span></li>
        @else
            <li><a href="{{ $partsData->appends(request()->except('page'))->url(1) }}" class="first" aria-label="Legelső oldal">«</a></li>
        @endif

        @if ($partsData->previousPageUrl())
            <li><a href="{{ $partsData->appends(request()->except('page'))->previousPageUrl() }}" class="previous" aria-label="Előző oldal">←</a></li>
        @else
            <li><span class="previous" style="color: #ddd;">←</span></li>
        @endif

        @for ($i = max(1, $partsData->currentPage() - 1); $i <= min($partsData->lastPage(), $partsData->currentPage() + 1); $i++)
            @if ($i == $partsData->currentPage())
                <li><span class="current">{{ $i }}</span></li>
            @else
                <li><a href="{{ $partsData->appends(request()->except('page'))->url($i) }}">{{ $i }}</a></li>
            @endif
        @endfor

        @if ($partsData->nextPageUrl())
            <li><a href="{{ $partsData->appends(request()->except('page'))->nextPageUrl() }}" class="next" aria-label="Következő oldal">→</a></li>
        @else
            <li><span class="next" style="color: #ddd;">→</span></li>
        @endif

        @if ($partsData->hasMorePages())
            <li><a href="{{ $partsData->appends(request()->except('page'))->url($partsData->lastPage()) }}" class="last" aria-label="Legutolsó oldal">»</a></li>
        @else
            <li><span class="last" style="color: #ddd;">»</span></li>
        @endif
    </ul>
</div>
<br>

<!-- Brand szűrő -->
<form method="GET" action="{{ route('part.show') }}">
    <input type="hidden" name="sort_by" value="avg_score">
    <input type="hidden" name="sort_order" value="desc">
    <input type="hidden" name="search" value="{{ request('search') }}">
    <input type="hidden" name="part" value="{{ $selectedPart }}">
    <div class="brand-filter">
        <label for="brand">Brand:</label>
        <select name="brand" id="brand" onchange="this.form.submit()">
            <option value="">all</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand }}" {{ $selectedBrand === $brand ? 'selected' : '' }}>{{ $brand }}</option>
            @endforeach
        </select>
    </div>
</form>
<br>

<!-- PC összerakás -->
<h2>Build PC</h2>
<ul style="list-style-type: none; padding-left: 0;">
    @foreach (['cpu', 'gpu', 'ram'] as $partType)
        <li>
            <b>{{ strtoupper($partType) }}:</b>
            @if (session("pc_parts.{$partType}_id"))
                @php
                    $part = \App\Models\Part::find(session("pc_parts.{$partType}_id"));
                @endphp
                <form action="{{ route('removePartFromPC') }}" method="POST" style="display:inline; cursor: pointer;">
                    @csrf
                    <input type="hidden" name="part_type" value="{{ $partType }}">
                    <input type="hidden" name="part_id" value="{{ session("pc_parts.{$partType}_id") }}">
                    <span onclick="this.parentNode.submit();" style="display:inline">
                        {{ $part->brand }} {{ $part->model }} <b>Score:</b> {{ $part->avg_score }}
                        <span style="cursor: pointer;"><b> -</b></span>
                    </span>
                </form>
            @else
                -
            @endif
        </li>
    @endforeach

    @foreach (['SSD', 'HDD'] as $storageType)
        <li>
            <b>{{ $storageType }}:</b>
            @php
                $storages = session('pc_parts.storages', []);
            @endphp
            @if (!empty($storages))
                <ul style="list-style-type: none;">
                @foreach ($storages as $storage)
                    @if ($storage['type'] === $storageType)
                        <form action="{{ route('removePartFromPC') }}" method="POST" style="display:inline; cursor: pointer;">
                            @csrf
                            <input type="hidden" name="part_type" value="{{ strtolower($storageType) }}">
                            <input type="hidden" name="part_id" value="{{ $storage['storage_id'] }}">
                            <span onclick="this.parentNode.submit();" style="display:inline">
                                {{ \App\Models\Part::find($storage['storage_id'])->brand }}
                                {{ \App\Models\Part::find($storage['storage_id'])->model }}
                                <b>Score:</b> {{ \App\Models\Part::find($storage['storage_id'])->avg_score }}
                                <span style="cursor: pointer;"><b> -</b></span>
                            </span>
                        </form>
                        <br>
                    @endif
                @endforeach
                </ul>
            @else
                -
            @endif
        </li>
    @endforeach
</ul>

<form method="POST" action="{{ route('pc.save') }}">
    @csrf
    <button type="submit">Save</button>
</form>

@if(session('success'))
    <div class="alert alert-success" style="color: green">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger" style="color: red">
            @foreach ($errors->all() as $error)
             {{ $error }}
            @endforeach
    </div>
@endif

</body>
</html>
