<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <title>PC {{ $pc->id }}</title>
</head>
<body>
<h2>PC parts:</h2>
<ul style="list-style-type: none; padding-left: 0;">
    <li><strong>CPU:</strong>
        @if ($pc->cpu && $pc->cpu->part)
            {{ $pc->cpu->brand }} {{ $pc->cpu->model }} –
            <strong>Score: {{ $pc->cpu_score }}</strong>
        @endif
    </li>
    <li><strong>GPU:</strong>
        @if ($pc->gpu && $pc->gpu->part)
            {{ $pc->gpu->brand }} {{ $pc->gpu->model }} –
            <strong>Score: {{ $pc->gpu_score }}</strong>
        @endif
    </li>
    <li><strong>RAM:</strong>
        @if ($pc->ram && $pc->ram->part)
            {{ $pc->ram->brand }} {{ $pc->ram->model }} –
            <strong>Score: {{ $pc->ram_score }}</strong>
        @endif
    </li>
    @php
        $ssds = $pc->storages->where('type', 'SSD');
        $hdds = $pc->storages->where('type', 'HDD');
    @endphp
    @if ($ssds->isNotEmpty())
        <li><strong>SSD:</strong></li>
        <ul style="list-style-type: none">
            @foreach ($ssds as $ssd)
                <li>
                    @if ($ssd->part)
                        {{ $ssd->part->brand }} {{ $ssd->part->model }} –
                        <strong>Score: {{ number_format($ssd->score, 2) }}</strong>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if ($hdds->isNotEmpty())
        <li><strong>HDD:</strong></li>
        <ul style="list-style-type: none">
            @foreach ($hdds as $hdd)
                <li>
                    @if ($hdd->part)
                        {{ $hdd->part->brand }} {{ $hdd->part->model }} –
                        <strong>Score: {{ number_format($hdd->score, 2) }}</strong>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</ul>

<div style="margin-top: 20px;">
    <h2>Scores:</h2>
    <ul style="list-style-type: none; padding-left: 0;">
        <li><strong>Gamer:</strong> {{ number_format($gamerScore, 2) }}</li>
        <li><strong>Workstation:</strong> {{ number_format($workstationScore, 2) }}</li>
        <li><strong>Desktop:</strong> {{ number_format($desktopScore, 2) }}</li>
    </ul>
</div>
<div style="margin-top: 20px;">
    <a href="{{ route('part.show') }}" title="Back to parts" class="back-button">&#8592;</a>
</div>
</body>
</html>
