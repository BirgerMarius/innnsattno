<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Sudoku</title>

    <style>

        @page {
            size: A4;
            margin: 10mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
        }

        .page {
            height: 277mm;
            page-break-after: always;
            display: flex;
            flex-direction: column;
        }

        h2 {
            margin: 0 0 10px 0;
            text-align: center;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            flex: 1;
        }

        table.sudoku {
            border-collapse: collapse;
            margin: auto;
        }

        table.sudoku td {

            width: 22px;
            height: 22px;

            border: 1px solid #999;

            text-align: center;
            font-size: 16px;
            font-weight: bold;
        }

        /* Tykke streker */

        table.sudoku tr:nth-child(3n) td {
            border-bottom: 2px solid #000;
        }

        table.sudoku tr:first-child td {
            border-top: 2px solid #000;
        }

        table.sudoku td:nth-child(3n) {
            border-right: 2px solid #000;
        }

        table.sudoku td:first-child {
            border-left: 2px solid #000;
        }

        .footer{
            margin-top:12px;
            font-size:11px;
            text-align:center;
            color:#666;
        }

        @media print{

            .page:last-child{
                page-break-after:auto;
            }

        }

    </style>

</head>

<body>

@foreach(array_chunk($sudokus,9) as $page)

<div class="page">

    <h2>
        Sudoku – {{ ucfirst($difficulty) }}
    </h2>

    <div class="grid">

        @foreach($page as $sudoku)

            <table class="sudoku">

                @for($row=0;$row<9;$row++)

                    <tr>

                    @for($col=0;$col<9;$col++)

                        @php
                            $value = $sudoku['board'][($row*9)+$col];
                        @endphp

                        <td>

                            @if($value=="0")
                                &nbsp;
                            @else
                                {{ $value }}
                            @endif

                        </td>

                    @endfor

                    </tr>

                @endfor

            </table>

        @endforeach

    </div>

    <div class="footer">

        Generert fra Innsatt.no • {{ now()->format('d.m.Y H:i') }}

    </div>

</div>

@endforeach
@if($showSolution)

@foreach(array_chunk($sudokus, 9) as $page)

<div class="page">

    <h2>Fasit – {{ ucfirst($difficulty) }}</h2>

    <div class="grid">

        @foreach($page as $sudoku)

            <table class="sudoku">

                @for($row = 0; $row < 9; $row++)

                    <tr>

                        @for($col = 0; $col < 9; $col++)

                            @php
                                $value = $sudoku['solution'][($row * 9) + $col] ?? "0";
                            @endphp

                            <td>
                                {{ $value }}
                            </td>

                        @endfor

                    </tr>

                @endfor

            </table>

        @endforeach

    </div>

    <div class="footer">
        Generert fra Innsatt.no • {{ now()->format('d.m.Y H:i') }}
    </div>

</div>

@endforeach

@endif
<script>

window.print();

</script>

</body>
</html>