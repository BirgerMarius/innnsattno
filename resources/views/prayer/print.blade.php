<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Bønnetider - {{ $prison['name'] }}</title>

    <style>
    @page {
    size: A4 portrait;
    margin: 8mm;
}

body {
    font-family: Arial, sans-serif;
    font-size: 11px;
    margin: 0;
}

h1 {
    margin: 0;
    font-size: 22px;
    text-align: center;
}

h2 {
    margin: 8px 0 12px 0;
    font-size: 16px;
    text-align: center;
    font-weight: normal;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    border: 1px solid #000;
    padding: 3px 5px;
    text-align: center;
    line-height: 1.2;
}

th {
    background: #eeeeee;
}

.footer {
    margin-top: 8px;
    text-align: center;
    font-size: 9px;
    color: #666;
}
    </style>
</head>
<body onload="window.print()">

<div style="text-align:center; margin-bottom:8px;">
    <img src="{{ asset('img/innsatt-logo-v2.png') }}"
         alt="Innsatt.no"
         style="height:60px;">
</div>

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
    Kilde: Bonnetid.no • innsatt.no/bonnetider
</div>

</body>
</html>