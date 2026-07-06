<!DOCTYPE html>
<html lang="no">
<head>

<meta charset="UTF-8">

<title>Sudoku</title>

</head>

<body>

<h1>Sudoku</h1>

<p>Vanskelighetsgrad: {{ $difficulty }}</p>

<p>Antall: {{ $count }}</p>

<p>Fasit:
@if($solution)
Ja
@else
Nei
@endif
</p>

<script>

window.print();

</script>

</body>
</html>