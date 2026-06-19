<h1>Bønnetider – Ringerike fengsel</h1>

<p>Dato: {{ $times['date'] }}</p>

<table border="1" cellpadding="8">
    <tr>
        <th>Bønn</th>
        <th>Tid</th>
    </tr>
    <tr>
        <td>Fajr</td>
        <td>{{ $times['fajr'] }}</td>
    </tr>
    <tr>
        <td>Dhuhr</td>
        <td>{{ $times['duhr'] }}</td>
    </tr>
    <tr>
        <td>Asr</td>
        <td>{{ $times['asr'] }}</td>
    </tr>
    <tr>
        <td>Maghrib</td>
        <td>{{ $times['maghrib'] }}</td>
    </tr>
    <tr>
        <td>Isha</td>
        <td>{{ $times['isha'] }}</td>
    </tr>
</table>

<p>
Hijri-dato: {{ $times['hijri_date'] }}
</p>