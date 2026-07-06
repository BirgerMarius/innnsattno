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

        table{
            border-collapse: collapse;
        }

        td{
            text-align:center;
            width:35px;
            height:35px;
            font-size:22px;
            border:1px solid #000;
        }

    </style>

</head>

<body>

<h1>Sudoku</h1>

<p>
Vanskelighetsgrad:
<strong>{{ $difficulty }}</strong>
</p>

<table>

@for($row = 0; $row < 9; $row++)
<tr>

    @for($col = 0; $col < 9; $col++)

        @php
            $value = $board[$row * 9 + $col];
        @endphp

        <td>

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