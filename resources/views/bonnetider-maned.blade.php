<!DOCTYPE html>
<html>
<head>
    <title>Bønnetider Ringerike - Måned</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }

        th {
            background: #eeeeee;
        }

        h1 {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<h1>Bønnetider Ringerike fengsel</h1>

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

</body>
</html>