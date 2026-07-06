<!DOCTYPE html>
<html lang="no">
<head>

<meta charset="UTF-8">

<title>Sudoku</title>

<style>

body{
    font-family: Arial, sans-serif;
    padding:40px;
}

</style>

</head>

<body>

<h1>Sudoku</h1>

<p>Vanskelighetsgrad:
<strong>{{ $sudoku['difficulty'] }}</strong>
</p>

<p>

{{ $sudoku['puzzle'] }}

</p>

</body>
</html>