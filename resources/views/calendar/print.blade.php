<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Månedskalender - For utskrift</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: #f2f2f2;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
        }

        .calendar-print-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            padding: 18px;
        }

        .calendar-print-actions button,
        .calendar-print-actions a {
            background: #0d6efd;
            border: 1px solid #0d6efd;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
            display: inline-block;
            font-size: 18px;
            padding: 10px 16px;
            text-decoration: none;
        }

        .calendar-print-actions a {
            background: #fff;
            color: #333;
            border-color: #999;
        }

        .calendar-month-page {
            background: #fff;
            display: flex;
            flex-direction: column;
            height: 190mm;
            margin: 0 auto 14px auto;
            padding: 0;
            page-break-after: always;
            width: 277mm;
        }

        .calendar-month-page:last-child {
            page-break-after: auto;
        }

        .calendar-title {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 7mm 0;
            text-align: center;
        }

        .calendar-table {
            border-collapse: collapse;
            flex: 1;
            table-layout: fixed;
            width: 100%;
        }

        .calendar-table th,
        .calendar-table td {
            border: 1px solid #222;
        }

        .calendar-table th {
            font-size: 14px;
            height: 9mm;
            text-align: center;
        }

        .calendar-week-header,
        .calendar-week-number {
            width: 12mm;
        }

        .calendar-week-number {
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            vertical-align: middle;
        }

        .calendar-day {
            height: 23mm;
            padding: 2mm;
            vertical-align: top;
        }

        .calendar-day-number {
            display: block;
            font-size: 18px;
            font-weight: 700;
            line-height: 1;
        }

        .calendar-holiday-name {
            color: #7a1f1f;
            display: block;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.15;
            margin-top: 1.5mm;
        }

        .calendar-empty .calendar-day-number {
            display: none;
        }

        .calendar-weekend {
            background: #ededed;
        }

        .calendar-footer {
            color: #666;
            font-size: 11px;
            margin-top: 4mm;
            text-align: center;
        }

        @media screen and (max-width: 1100px) {
            .calendar-month-page {
                height: auto;
                min-height: 68vw;
                width: calc(100vw - 24px);
            }
        }

        @media print {
            body {
                background: #fff;
            }

            .calendar-print-actions {
                display: none !important;
            }

            .calendar-month-page {
                height: 190mm;
                margin: 0;
                width: 277mm;
            }
        }
    </style>
</head>
<body>
    <div class="calendar-print-actions">
        <button type="button" onclick="window.print()">Skriv ut kalender</button>
        <a href="{{ route('calendar.index') }}">Tilbake til valgsiden</a>
    </div>

    @foreach($months as $month)
        <section class="calendar-month-page">
            <h1 class="calendar-title">{{ $month['title'] }}</h1>

            <table class="calendar-table" aria-label="Kalender for {{ $month['title'] }}">
                <thead>
                    <tr>
                        <th class="calendar-week-header">Uke</th>
                        <th>Mandag</th>
                        <th>Tirsdag</th>
                        <th>Onsdag</th>
                        <th>Torsdag</th>
                        <th>Fredag</th>
                        <th>Lørdag</th>
                        <th>Søndag</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($month['weeks'] as $week)
                        <tr>
                            <td class="calendar-week-number">{{ $week['number'] }}</td>

                            @foreach($week['days'] as $day)
                                <td class="calendar-day {{ $day['isWeekend'] ? 'calendar-weekend' : '' }} {{ $day['number'] ? '' : 'calendar-empty' }}">
                                    <span class="calendar-day-number">{{ $day['number'] }}</span>
                                    @if($day['holidayName'])
                                        <span class="calendar-holiday-name">{{ $day['holidayName'] }}</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="calendar-footer">
                Generert fra Innsatt.no
            </div>
        </section>
    @endforeach
</body>
</html>
