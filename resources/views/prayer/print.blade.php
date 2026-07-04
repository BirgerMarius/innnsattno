<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Bønnetider - {{ $prison['name'] }}</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 15px;
            font-size: 12px;
        }

        h1 {
            text-align: center;
            margin-bottom: 5px;
        }

        h2 {
            text-align: center;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: normal;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
        }

        th {
            background: #eeeeee;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 11px;
            color: #666;
        }

        @media print {
            @page {
                margin: 10mm;
            }
        }
    </style>
</head>
<body onload="window.print()">

<h1>Innsatt.no</h1>

<h2>
    Bønnetider – {{ $prison['name'] }}<br>
    {{ $monthName }} {{ $year }}
</h2>

<table>

<tr>
    <th>Dato</th>
    <th>Fajr</th>
    <th>Dhuhr</th>
    <th>Asr</th>
    <th>Maghrib</th>
    <th>Isha</th>
</tr>

@foreach($days as $day)

<tr>
    <td>{{ $day['date'] }}</td>
    <td>{{ $day['fajr'] }}</td>
    <td>{{ $day['duhr'] }}</td>
    <td>{{ $day['asr'] }}</td>
    <td>{{ $day['maghrib'] }}</td>
    <td>{{ $day['isha'] }}</td>
</tr>

@endforeach

</table>

<div class="footer">
    Automatisk generert fra Bonnetid.no<br>
    https://innsatt.no/bonnetider
</div>

</body>
</html>