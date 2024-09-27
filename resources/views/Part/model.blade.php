<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> {{ $part->model }} - {{ $part->part }}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<h1 class="header">{{ $part->brand }} {{ $part->model }} - {{ $part->part }}</h1>

<div class="chart-container">
    <canvas id="scoresChart" width="800" height="300"></canvas>
    <div class="scores-summary">
        <div class="score-item">Min Score: {{ round($minScore, 2) }}</div>
        <div class="score-item"><b>Avg Score: {{ round($avgScore, 2) }}</b></div>
        <div class="score-item">Max Score: {{ round($maxScore, 2) }}</div>
    </div>
</div>

<script>
    const scores = @json($scores);
    const avgScore = {{ $avgScore }};

    if (Array.isArray(scores)) {
        const ctx = document.getElementById('scoresChart').getContext('2d');

        const averageLinePlugin = {
            id: 'averageLine',
            beforeDraw: (chart) => {
                const ctx = chart.ctx;
                const yScale = chart.scales.y;
                const avgLine = yScale.getPixelForValue(avgScore);

                ctx.save();
                ctx.strokeStyle = '#d3d3d3';
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.moveTo(chart.chartArea.left, avgLine);
                ctx.lineTo(chart.chartArea.right, avgLine);
                ctx.stroke();
                ctx.restore();
            }
        };

        const minScore = Math.min(...scores);
        const maxScore = Math.max(...scores);

        const scoresChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: scores.map((_, index) => `Score ${index + 1}`),
                datasets: [{
                    label: '',
                    data: scores.map(score => parseFloat(score).toFixed(2)),
                    backgroundColor: '#4E6F937F',
                    borderColor: '#2f4660',
                    borderWidth: 1,
                    fill: false,
                    pointRadius: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    x: {
                        display: false,
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        display: false,
                        beginAtZero: false,
                        min: minScore,
                        max: maxScore,
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            },
            plugins: [averageLinePlugin]
        });
    } else {
        console.error('Scores is not an array:', scores);
    }
</script>

<div class="button-container">
    <a href="{{ route('part.show', ['part' => $part->part]) }}" title="Back to parts" class="back-button">&#8592;</a>
    <form action="{{ route('part.addToPC') }}" method="POST" style="display: inline;">
        @csrf
        <input type="hidden" name="part_type" value="{{ strtolower($part->part) }}">
        <input type="hidden" name="part_id" value="{{ $part->id }}">
        <button type="submit" class="add-button" title="Add to pc">&#43</button>
    </form>
</div>

@if($errors->any())
    <div class="alert alert-danger" style="color: red">
        @foreach ($errors->all() as $error)
            {{ $error }}
        @endforeach
    </div>
@endif

</body>
</html>
