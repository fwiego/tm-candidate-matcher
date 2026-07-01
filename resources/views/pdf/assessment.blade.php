<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчёт о сверке</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.5;
            padding: 40px;
        }

        .header {
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        .header h1 {
            font-size: 20px;
            font-weight: bold;
            color: #4f46e5;
        }

        .header .meta {
            font-size: 10px;
            color: #6b7280;
            margin-top: 4px;
        }

        .section {
            margin-bottom: 24px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }

        .grid-2 {
            width: 100%;
            border-collapse: collapse;
        }

        .grid-2 td {
            width: 50%;
            padding: 4px 8px 4px 0;
            vertical-align: top;
        }

        .label {
            color: #6b7280;
            font-size: 10px;
        }

        .value {
            color: #111827;
            font-weight: 500;
        }

        .coverage-block {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 16px;
            text-align: center;
            margin-bottom: 24px;
        }

        .coverage-number {
            font-size: 36px;
            font-weight: bold;
            color: {{ $coverageColor }};
        }

        .coverage-label {
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }

        .coverage-bar-wrap {
            background: #e5e7eb;
            height: 8px;
            border-radius: 4px;
            margin: 10px auto;
            width: 60%;
        }

        .coverage-bar-fill {
            background: {{ $coverageColor }};
            height: 8px;
            border-radius: 4px;
            width: {{ $assessment->coverage_percent }}%;
        }

        .coverage-stats {
            font-size: 10px;
            color: #6b7280;
        }

        .req-table {
            width: 100%;
            border-collapse: collapse;
        }

        .req-table th {
            background: #f3f4f6;
            text-align: left;
            padding: 6px 8px;
            font-size: 10px;
            text-transform: uppercase;
            color: #6b7280;
            letter-spacing: 0.05em;
        }

        .req-table td {
            padding: 7px 8px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 9px;
            font-weight: bold;
        }

        .badge-must { background: #fef2f2; color: #b91c1c; }
        .badge-nice { background: #eff6ff; color: #1d4ed8; }

        .status-matched { color: #15803d; font-weight: bold; }
        .status-missing { color: #b91c1c; font-weight: bold; }

        .footer {
            margin-top: 32px;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Отчёт о сверке кандидата</h1>
    <div class="meta">
        Сформирован: {{ now()->format('d.m.Y H:i') }}
        @if($assessment->calculatedBy)
            &nbsp;·&nbsp; Выполнил: {{ $assessment->calculatedBy->name }}
        @endif
    </div>
</div>

<div class="coverage-block">
    <div class="coverage-number">{{ $assessment->coverage_percent }}%</div>
    <div class="coverage-bar-wrap">
        <div class="coverage-bar-fill"></div>
    </div>
    <div class="coverage-label">Покрытие требований</div>
    <div class="coverage-stats">
        Must: {{ $mustMatched }}/{{ $mustTotal }} &nbsp;·&nbsp;
        Nice to have: {{ $niceMatched }}/{{ $niceTotal }}
    </div>
</div>

<div class="section">
    <div class="section-title">Кандидат</div>
    <table class="grid-2">
        <tr>
            <td>
                <div class="label">ФИО</div>
                <div class="value">{{ $assessment->candidate->full_name }}</div>
            </td>
            <td>
                <div class="label">Грейд</div>
                <div class="value">{{ $gradeLabels[$assessment->candidate->grade] ?? '—' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Локация</div>
                <div class="value">{{ $assessment->candidate->location ?: '—' }}</div>
            </td>
            <td></td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">Вакансия</div>
    <table class="grid-2">
        <tr>
            <td>
                <div class="label">Должность</div>
                <div class="value">{{ $assessment->request->position }}</div>
            </td>
            <td>
                <div class="label">Грейд</div>
                <div class="value">{{ $gradeLabels[$assessment->request->grade] ?? '—' }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Локация</div>
                <div class="value">{{ $assessment->request->location ?: '—' }}</div>
            </td>
            <td>
                <div class="label">Гражданство</div>
                <div class="value">{{ $assessment->request->citizenship ?: '—' }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">Детализация по требованиям</div>
    <table class="req-table">
        <thead>
            <tr>
                <th>Технология</th>
                <th>Тип</th>
                <th>Вес</th>
                <th>Результат</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requirements as $req)
            <tr>
                <td>{{ $req['technology'] }}</td>
                <td>
                    <span class="badge {{ $req['type'] === 'must' ? 'badge-must' : 'badge-nice' }}">
                        {{ $req['type'] === 'must' ? 'Must' : 'Nice' }}
                    </span>
                </td>
                <td>{{ $req['weight'] }}</td>
                <td>
                    @if($req['is_matched'])
                        <span class="status-matched">✓ Есть</span>
                    @else
                        <span class="status-missing">✗ Нет</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="footer">
    TM Candidate Matcher &nbsp;·&nbsp; Автоматически сформированный отчёт
</div>

</body>
</html>