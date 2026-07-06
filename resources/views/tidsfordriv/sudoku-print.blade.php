<!DOCTYPE html>
<html lang="no">
<head>

<meta charset="UTF-8">

<title>Sudoku</title>

<style>

body{
 <h1>Sudoku</h1>

<p>
Vanskelighetsgrad:
<strong>{{ $difficulty }}</strong>
</p>

<table border="1" cellspacing="0" cellpadding="8">

@for($row = 0; $row < 9; $row++)

<tr>

@for($col = 0; $col < 9; $col++)

@php
$value = $board[$row * 9 + $col];
@endphp

<td align="center" width="35" height="35">

@if($value == "0")

&nbsp;

@else

{{ $value }}

@endif

</td>

@endfor

</tr>

@endfor

</table>
</body>
</html>