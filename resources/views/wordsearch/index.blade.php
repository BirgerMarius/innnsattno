<!doctype html>
<html lang="no">
<head>

<meta charset="utf-8">

<title>Ordjakt</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

table{
    border-collapse:collapse;
    margin:auto;
}

td{
    width:34px;
    height:34px;
    border:1px solid #ccc;
    text-align:center;
    font-size:22px;
    font-weight:bold;
}

</style>

</head>

<body>

<div class="container mt-4">

<h1>🧩 Ordjakt</h1>

<p>Finn disse ordene:</p>

<ul>

@foreach($words as $word)
<li>{{ $word }}</li>
@endforeach

</ul>

<table>

@foreach($grid as $row)

<tr>

@foreach($row as $letter)

<td>{{ $letter }}</td>

@endforeach

</tr>

@endforeach

</table>

</div>

</body>
</html>