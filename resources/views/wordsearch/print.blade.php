<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Ordjakt</title>

<style>

@page{
    size:A4 portrait;
    margin:10mm;
}

body{
    font-family:Arial, Helvetica, sans-serif;
    margin:0;
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
    border:1px solid #444;
    text-align:center;
    font-size:20px;
    font-weight:bold;
    font-family:monospace;
}

.words{
    columns:2;
    width:70%;
    margin:0 auto;
    font-size:18px;
}

.words li{
    margin-bottom:6px;
}

.no-print{
    text-align:center;
    margin:20px;
}

@media print{

    .no-print{
        display:none;
    }

}

</style>

</head>

<body>

<div class="no-print">

    <button onclick="window.print();">
        Skriv ut
    </button>

</div>

<h1>ORDJAKT - {{ $categoryName }}</h1>

<table>

@foreach($grid as $row)

<tr>

@foreach($row as $letter)

<td>{{ $letter }}</td>

@endforeach

</tr>

@endforeach

</table>

<h2 style="text-align:center;">Finn disse ordene</h2>

<ul class="words">

@foreach($words as $word)

<li>
    {{ $word['display'] }}
    @if($word['display'] !== $word['word'])
        ({{ $word['word'] }})
    @endif
</li>

@endforeach

</ul>

<script>
window.onload = function () {
    window.print();
};
</script>

</body>
</html>
