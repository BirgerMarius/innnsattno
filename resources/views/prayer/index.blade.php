@extends('layouts.app')

@section('title', 'Bønnetider - ' . $prison['name'])

@push('styles')
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

        .toolbar {
            margin-bottom: 20px;
        }

        a {
            text-decoration: none;
            margin-right: 15px;
        }

        @media print {
            .toolbar {
                display: none;
            }
        }
    </style>
@endpush

@section('content')

@include('partials.header')

<div class="toolbar">

    <a href="?year={{ $month == 1 ? $year - 1 : $year }}&month={{ $month == 1 ? 12 : $month - 1 }}">
        ⬅ Forrige måned
    </a>

    <strong>{{ $monthName }} {{ $year }}</strong>

    <a href="?year={{ $month == 12 ? $year + 1 : $year }}&month={{ $month == 12 ? 1 : $month + 1 }}">
        Neste måned ➜
    </a>

    <br><br>

    <a href="{{ request()->path() }}/utskrift?year={{ $year }}&month={{ $month }}">
        🖨 Skriv ut denne måneden
    </a>

    |

    <a href="{{ request()->path() }}/utskrift?year={{ $month == 12 ? $year + 1 : $year }}&month={{ $month == 12 ? 1 : $month + 1 }}">
        🖨 Skriv ut neste måned
    </a>

</div>

<h1>Bønnetider {{ $prison['name'] }}</h1>

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

@endsection
