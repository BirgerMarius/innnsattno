<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Ordjakt - Utskrift</title>

    <style>

        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        body{
            font-family: Arial, Helvetica, sans-serif;
            color:#000;
            margin:0;
            padding:0;
        }

        h1{
            text-align:center;
            margin-bottom:20px;
        }

        table{
            border-collapse:collapse;
            margin:0 auto 25px auto;
        }

        td{
            width:30px;
            height:30px;
            border:1px solid #000;
            text-align:center;
            vertical-align:middle;
            font-size:20px;
            font-weight:bold;
            font-family:monospace;
        }

        .words{
            columns:2;
            column-gap:50px;
            width:80%;
            margin:0 auto;
        }

        .words li{
            font-size:16px;
            margin-bottom:5px;
        }

        @media print{

            .no-print{
                display:none;
            }

        }

    </style>

</head>

<body>

<div class="no-print" style="text-align:center;margin:20px;">

    <button onclick="window.print();">
        Skriv ut
    </button>

    <button onclick="window.close();">
        Lukk
    </button>

</div>

<h1>ORDJAKT</h1>

<table>

@foreach($grid as $row)

<tr>

@foreach($row as $letter)

<td>{{ $letter }}</td>

@endforeach

</tr>

@endforeach

</table>

<h3 style="text-align:center;">Finn disse ordene</h3>

<ul class="words">

@foreach($words as $word)

<li>{{ $word }}</li>

@endforeach

</ul>

<script>

window.onload = function () {
    window.print();
};

</script>

</body>
</html>