(function () {
    'use strict';

    var app = document.getElementById('visitationApp');

    if (!app) {
        return;
    }

    var departments = JSON.parse(document.getElementById('visitationDepartments').textContent);
    var departmentInputs = app.querySelectorAll('input[name="department"]');
    var drawButton = document.getElementById('drawCell');
    var result = document.getElementById('visitationResult');
    var resultLabel = document.getElementById('resultLabel');
    var resultCell = document.getElementById('resultCell');
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    var drawingTimer = null;
    var drawingTimeout = null;

    function selectedDepartment() {
        return app.querySelector('input[name="department"]:checked').value;
    }

    function randomCell(cells) {
        return cells[Math.floor(Math.random() * cells.length)];
    }

    function clearDrawing() {
        window.clearInterval(drawingTimer);
        window.clearTimeout(drawingTimeout);
        drawingTimer = null;
        drawingTimeout = null;
        result.classList.remove('is-drawing');
    }

    function resetResult() {
        clearDrawing();
        drawButton.disabled = false;
        drawButton.textContent = 'Trekk celle';
        result.classList.remove('has-result');
        resultLabel.textContent = 'Klar for trekning';
        resultCell.textContent = '–';
    }

    function finishDrawing(department, cell) {
        clearDrawing();
        result.classList.add('has-result');
        resultLabel.textContent = 'Valgt celle fra avdeling ' + department;
        resultCell.textContent = cell;
        drawButton.disabled = false;
        drawButton.textContent = 'Trekk på nytt';
    }

    function drawCell() {
        var department = selectedDepartment();
        var cells = departments[department];
        var winner = randomCell(cells);

        clearDrawing();
        result.classList.remove('has-result');
        drawButton.disabled = true;
        drawButton.textContent = 'Trekker …';
        resultLabel.textContent = 'Trekker fra avdeling ' + department;
        result.classList.add('is-drawing');

        if (reducedMotion.matches) {
            finishDrawing(department, winner);
            return;
        }

        drawingTimer = window.setInterval(function () {
            resultCell.textContent = randomCell(cells);
        }, 90);

        drawingTimeout = window.setTimeout(function () {
            finishDrawing(department, winner);
        }, 1400);
    }

    departmentInputs.forEach(function (input) {
        input.addEventListener('change', resetResult);
    });

    drawButton.addEventListener('click', drawCell);
}());
