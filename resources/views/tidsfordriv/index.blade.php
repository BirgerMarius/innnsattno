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
        padding:0 15px;
        background:#f5f5f5;
    }

    h1{
        margin-bottom:10px;
    }

    .info{
        background:#eef6ff;
        border:1px solid #b9d7f5;
        padding:15px;
        border-radius:8px;
        margin-bottom:20px;
        line-height:1.5;
    }

    fieldset{
        background:#fff;
        border:1px solid #ccc;
        border-radius:8px;
        padding:25px;
    }

    legend{
        font-size:18px;
        font-weight:bold;
        padding:0 8px;
    }

    p{
        margin-bottom:18px;
    }

    label{
        line-height:1.8;
    }

    input[type=number]{
        width:80px;
        padding:6px;
        font-size:16px;
    }

    button{
        margin-top:10px;
        padding:12px 25px;
        font-size:18px;
        border:none;
        border-radius:6px;
        background:#0d6efd;
        color:white;
        cursor:pointer;
    }

    button:hover{
        background:#0b5ed7;
    }

    button:disabled{
        background:#999;
        cursor:not-allowed;
    }

    small{
        color:#666;
    }
</style>

</head>
<body>

<h1>🧩 Tidsfordriv</h1>
<div class="info">
    Velg vanskelighetsgrad og antall sider.<br>
    PDF-en åpnes automatisk og kan skrives ut eller lagres.<br>
    Dersom du huker av <strong>Ta med fasit</strong>, legges løsningene bakerst i dokumentet.
</div>
@if(session('error'))
    <div style="
        background:#fdecea;
        border:1px solid #f5c2c7;
        color:#842029;
        padding:12px;
        margin-bottom:20px;
        border-radius:6px;
    ">
        {{ session('error') }}
    </div>
@endif

<form id="sudokuForm" method="POST" action="/tidsfordriv/sudoku/print">

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
    max="6">

<br>

<small>Maks 6 sider per utskrift.</small>

        </p>

        <p>

            <label>

                <input type="checkbox" name="solution">

                Ta med fasit

            </label>

        </p>

        <button id="submitButton" type="submit">
    🖨️ Skriv ut Sudoku
</button>
<p id="loadingMessage" style="display:none; margin-top:15px; color:#0066cc;">
    ⏳ Genererer Sudoku... Dette kan ta noen sekunder.
</p>

    </fieldset>

</form>

<script>
document.getElementById('sudokuForm').addEventListener('submit', function () {

    const button = document.getElementById('submitButton');
    const message = document.getElementById('loadingMessage');

    button.disabled = true;
    button.textContent = 'Genererer...';

    message.style.display = 'block';

});
</script>
</body>
</html>