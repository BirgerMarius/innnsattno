<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Ordjakt</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body{
            background:#f8f9fa;
        }

        .word-grid{
            border-collapse:collapse;
            margin:auto;
        }

        .word-grid td{
            width:34px;
            height:34px;
            border:1px solid #999;
            text-align:center;
            vertical-align:middle;
            font-size:22px;
            font-weight:bold;
            font-family:monospace;
            background:#fff;
        }

        .word-list{
            columns:2;
            margin-top:25px;
        }

        .word-list li{
            margin-bottom:6px;
            font-size:18px;
        }

        @media(max-width:768px){

            .word-grid td{
                width:26px;
                height:26px;
                font-size:16px;
            }

            .word-list{
                columns:1;
            }
        }
    </style>

</head>
<body>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h1>🧩 Ordjakt</h1>

        href="{{ url('/ordjakt/utskrift') }}"
            🖨️ Utskriftsversjon
        </a>

    </div>

    <div class="card shadow-sm">

        <div class="card-body text-center">

            <table class="word-grid">

                @foreach($grid as $row)

                    <tr>

                        @foreach($row as $letter)

                            <td>{{ $letter }}</td>

                        @endforeach

                    </tr>

                @endforeach

            </table>

        </div>

    </div>

    <div class="card mt-4 shadow-sm">

        <div class="card-header">

            <strong>Finn disse ordene</strong>

        </div>

        <div class="card-body">

            <ul class="word-list">

                @foreach($words as $word)

                    <li>{{ $word }}</li>

                @endforeach

            </ul>

        </div>

    </div>

</div>

</body>
</html>