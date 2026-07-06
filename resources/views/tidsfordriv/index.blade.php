<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Tidsfordriv</title>

    <style>
        body{
            font-family: Arial, sans-serif;
            max-width:700px;
            margin:40px auto;
        }

        fieldset{
            padding:20px;
        }

        button{
            margin-top:20px;
            padding:12px 25px;
            font-size:18px;
            cursor:pointer;
        }

        input[type=number]{
            width:70px;
        }
    </style>

</head>
<body>

<h1>🧩 Tidsfordriv</h1>

<form method="POST" action="/tidsfordriv/sudoku/print">

    @csrf

    <fieldset>

        <legend><strong>Sudoku</strong></legend>

        <p>

            <label>
                <input type="radio" name="difficulty" value="easy" checked>
                Lett
            </label>

            <br>

            <label>
                <input type="radio" name="difficulty" value="medium">
                Middels
            </label>

            <br>

            <label>
                <input type="radio" name="difficulty" value="hard">
                Vanskelig
            </label>

        </p>

        <p>

           Antall sider:

<input
    type="number"
    name="pages"
    value="1"
    min="1"
    max="20">

        </p>

        <p>

            <label>

                <input type="checkbox" name="solution">

                Ta med fasit

            </label>

        </p>

        <button type="submit">

            🖨️ Skriv ut Sudoku

        </button>

    </fieldset>

</form>

</body>
</html>