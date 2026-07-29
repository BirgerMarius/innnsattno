/* Oppdragshjulet: client-side flow and local result scenes. */
(() => {
    "use strict";

    const byId = (id) => document.getElementById(id);
    const participantsInput = byId("participants");
    const taskSelect = byId("taskSelect");
    const customTask = byId("customTask");
    const customTaskContainer = byId("customTaskContainer");
    const modeSelect = byId("mode");
    const startButton = byId("startButton");
    const resetButton = byId("resetButton");
    const nextRoundButton = byId("nextRoundButton");
    const validationMessage = byId("validationMessage");
    const participantHint = byId("participantHint");
    const statusText = byId("statusText");
    const commentText = byId("commentText");
    const soundEnabled = byId("soundEnabled");
    const wheelContainer = document.querySelector(".wheel-container");
    const eliminationStatus = byId("eliminationStatus");
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
    const wheel = new Wheel("wheelCanvas");

    let participants = [];
    let eliminated = [];
    let task = "";
    let mode = "last";
    let running = false;
    let awaitingNextRound = false;
    let eliminationModal;
    let winnerModal;
    let lastEliminationScene = -1;
    let lastWinnerScene = -1;
    let audioContext;
    let tickThrottle = 0;
    let confettiTimer = null;
    let confettiLayer = null;

    const comments = [
        "Ingen påvirkning er tillatt.", "Tilfeldigheten bestemmer.",
        "Hvem slipper unna?", "Ingen klager til hjulet.", "Da starter vi!"
    ];
    const decorations = ["key", "shield", "star", "cuffs", "confetti", "tag"];

    function parseParticipants(value) {
        const occurrences = new Map();
        return value.split(/\r?\n/)
            .map((name) => name.trim())
            .filter(Boolean)
            .map((name) => {
                const key = name.toLocaleLowerCase("nb-NO");
                const number = (occurrences.get(key) || 0) + 1;
                occurrences.set(key, number);
                return { name, label: number === 1 ? name : `${name} (${number})` };
            });
    }

    function currentTask() {
        return taskSelect.value === "Annet" ? customTask.value.trim() : taskSelect.value.trim();
    }

    function escapeHtml(value) {
        const node = document.createElement("div");
        node.textContent = value;
        return node.innerHTML;
    }

    function sceneIcon(kind) {
        const icons = {
            key: '<path d="M22 42a14 14 0 1 1 9 13L57 29l8 8 7-7 8 8-16 16-8-8-17 17-8-8 5-5A14 14 0 0 1 22 42Zm0-7a7 7 0 1 0 0 14 7 7 0 0 0 0-14Z"/>',
            shield: '<path d="M50 10 82 22v25c0 21-14 34-32 43C32 81 18 68 18 47V22l32-12Zm0 15-18 7v15c0 11 6 20 18 27 12-7 18-16 18-27V32l-18-7Z"/>',
            star: '<path d="m50 7 11 27h29L67 51l9 29-26-17-26 17 9-29L10 34h29L50 7Z"/>',
            cuffs: '<path d="M27 25a19 19 0 1 0 14 32h18a19 19 0 1 0 0-14H41a19 19 0 0 0-14-18Zm0 12a7 7 0 1 1 0 14 7 7 0 0 1 0-14Zm46 0a7 7 0 1 1 0 14 7 7 0 0 1 0-14Z"/>',
            confetti: '<path d="m22 13 9 5-8 16-9-5 8-16Zm45 1 10 1-2 18-10-1 2-18ZM43 8h10v20H43V8ZM14 54l18-3 2 10-18 3-2-10Zm53-3 19 5-3 10-19-6 3-9ZM39 70l11-15 11 15-11 18-11-18Z"/>',
            tag: '<path d="M10 21 53 9l37 37-42 42L10 50V21Zm20 8a8 8 0 1 0 0 16 8 8 0 0 0 0-16Z"/>'
        };
        return `<svg class="scene-decoration scene-decoration--${kind}" viewBox="0 0 100 100" aria-hidden="true" focusable="false">${icons[kind]}</svg>`;
    }

    function randomDifferent(length, previous) {
        if (length < 2) return 0;
        let next;
        do next = Math.floor(Math.random() * length); while (next === previous);
        return next;
    }

    function decoration() {
        return sceneIcon(decorations[Math.floor(Math.random() * decorations.length)]);
    }

    const eliminationScenes = [
        (name) => `<div class="scene-art scene-art--escort">${decoration()}<div class="cartoon-officer" aria-hidden="true"><span class="officer-hat">VAKT</span><span class="officer-head"></span><span class="officer-body"></span><span class="officer-shield">STOPP</span></div><div class="scene-nameplate">${name}</div><div class="exit-arrow" aria-hidden="true">→ UT</div></div><p class="scene-kicker">Betjenten viser vei</p>`,
        (name) => `<div class="scene-art scene-art--cuffs">${decoration()}<div class="big-cuffs" aria-hidden="true"><i></i><b></b><i></i></div><div class="scene-nameplate">${name}</div></div><p class="scene-kicker">Klikk! Navnet er låst ute</p>`,
        (name) => `<div class="scene-art scene-art--key">${decoration()}<div class="giant-key" aria-hidden="true">⚿</div><div class="scene-nameplate">${name}</div><div class="lock-slot" aria-hidden="true"></div></div><p class="scene-kicker">Nøkkelen har talt</p>`,
        (name) => `<div class="scene-art scene-art--stamp">${decoration()}<div class="scene-card"><span>${name}</span></div><div class="out-stamp" aria-hidden="true">UTE</div></div><p class="scene-kicker">Offisielt stemplet ut</p>`,
        (name) => `<div class="scene-art scene-art--gate">${decoration()}<div class="scene-nameplate">${name}</div><div class="cell-gate" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div></div><p class="scene-kicker">Porten stenger for denne runden</p>`,
        (name) => `<div class="scene-art scene-art--whistle">${decoration()}<div class="whistle-officer" aria-hidden="true"><span>👮</span><b>♫</b><i></i></div><div class="scene-nameplate">${name}</div></div><p class="scene-kicker">Fløyta blåser: ute!</p>`
    ];

    const winnerScenes = [
        (name, job) => `<div class="winner-art winner-art--confetti">${decoration()}<div class="friendly-prisoner" aria-hidden="true">☺<i></i></div>${winnerCopy(name, job)}</div>`,
        (name, job) => `<div class="winner-art winner-art--chain">${decoration()}<div class="loose-chain" aria-hidden="true">◯—◯—◯　✦</div>${winnerCopy(name, job)}<div class="mission-sign">OPPDRAGET ER DITT!</div></div>`,
        (name, job) => `<div class="winner-art winner-art--door">${decoration()}<div class="open-door" aria-hidden="true"><i></i></div><div class="spotlight" aria-hidden="true"></div>${winnerCopy(name, job)}</div>`,
        (name, job) => `<div class="winner-art winner-art--key">${decoration()}<div class="winner-key" aria-hidden="true">⚿</div>${winnerCopy(name, job)}</div>`
    ];

    function winnerCopy(name, job) {
        return `<div class="winner-copy"><span>Oppdraget går til</span><strong>${name}</strong><span>skal utføre</span><b>${job}</b></div>`;
    }

    function setValidation(message = "") {
        validationMessage.textContent = message;
        validationMessage.classList.toggle("d-none", !message);
    }

    function updateInputState() {
        if (running || awaitingNextRound) return;
        participants = parseParticipants(participantsInput.value);
        wheel.setParticipants(participants.map((person) => person.label));
        participantHint.textContent = `${participants.length} gyldige deltakere`;
        startButton.disabled = participants.length < 2;
        if (participants.length >= 2 && currentTask()) setValidation();
        updateStatusPanel();
    }

    function updateStatusPanel() {
        byId("activeCount").textContent = participants.length;
        byId("eliminatedCount").textContent = eliminated.length;
        byId("activeParticipants").innerHTML = participants.length
            ? participants.map((person) => `<li>${escapeHtml(person.label)}</li>`).join("")
            : '<li class="participant-list__empty">Ingen deltakere ennå</li>';
        byId("eliminatedParticipants").innerHTML = eliminated.length
            ? eliminated.map((person) => `<li>${escapeHtml(person.label)}</li>`).join("")
            : '<li class="participant-list__empty">Ingen er slått ut</li>';
    }

    function setRunning(value) {
        running = value;
        startButton.disabled = value || awaitingNextRound || participants.length < 2;
        startButton.innerHTML = value
            ? "Spinner…"
            : '<span aria-hidden="true">✦</span> Spinn hjulet';
        participantsInput.disabled = value || awaitingNextRound;
        modeSelect.disabled = value || awaitingNextRound;
        wheelContainer.classList.toggle("is-ready", !value);
    }

    function tone(frequency, duration, type = "sine", delay = 0, volume = .045) {
        if (!soundEnabled.checked) return;
        audioContext = audioContext || new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gain = audioContext.createGain();
        const starts = audioContext.currentTime + delay;
        oscillator.type = type;
        oscillator.frequency.setValueAtTime(frequency, starts);
        gain.gain.setValueAtTime(volume, starts);
        gain.gain.exponentialRampToValueAtTime(.001, starts + duration);
        oscillator.connect(gain).connect(audioContext.destination);
        oscillator.start(starts);
        oscillator.stop(starts + duration);
    }

    function playSound(type, direction = 1) {
        if (!soundEnabled.checked) return;
        if (type === "tick") tone(direction < 0 ? 620 : 760, .025, "square", 0, .012);
        if (type === "stop") { tone(180, .13, "triangle"); tone(110, .16, "triangle", .09); }
        if (type === "scene") { tone(95, .09, "square"); tone(65, .16, "triangle", .07); }
        if (type === "winner") [523, 659, 784].forEach((note, i) => tone(note, .22, "sine", i * .11));
    }

    function clearWinnerConfetti() {
        if (confettiTimer) {
            window.clearTimeout(confettiTimer);
            confettiTimer = null;
        }
        if (confettiLayer) {
            confettiLayer.remove();
            confettiLayer = null;
        }
    }

    function launchWinnerConfetti() {
        clearWinnerConfetti();
        if (reducedMotion.matches) return;

        const colors = ["#ef4444", "#f59e0b", "#22c55e", "#2563eb", "#8b5cf6", "#ec4899"];
        confettiLayer = document.createElement("div");
        confettiLayer.className = "winner-confetti";
        confettiLayer.setAttribute("aria-hidden", "true");

        for (let index = 0; index < 72; index++) {
            const piece = document.createElement("i");
            const duration = 1.8 + Math.random() * 1;
            piece.style.setProperty("--confetti-x", `${Math.random() * 100}%`);
            piece.style.setProperty("--confetti-drift", `${-90 + Math.random() * 180}px`);
            piece.style.setProperty("--confetti-spin", `${360 + Math.random() * 900}deg`);
            piece.style.setProperty("--confetti-duration", `${duration}s`);
            piece.style.setProperty("--confetti-delay", `${Math.random() * .3}s`);
            piece.style.setProperty("--confetti-color", colors[Math.floor(Math.random() * colors.length)]);
            piece.style.setProperty("--confetti-size", `${6 + Math.random() * 9}px`);
            confettiLayer.appendChild(piece);
        }

        document.body.appendChild(confettiLayer);
        confettiTimer = window.setTimeout(clearWinnerConfetti, 3200);
    }

    function beginSpin() {
        awaitingNextRound = false;
        setRunning(true);
        statusText.textContent = "Spinner…";
        commentText.textContent = comments[Math.floor(Math.random() * comments.length)];
        wheel.spin();
    }

    function showElimination(person) {
        lastEliminationScene = randomDifferent(eliminationScenes.length, lastEliminationScene);
        const content = eliminationScenes[lastEliminationScene](escapeHtml(person.label));
        byId("eliminationScene").innerHTML = content;
        nextRoundButton.textContent = participants.length > 1 ? "Spinn neste runde" : "Vis vinneren";
        eliminationModal = eliminationModal || new bootstrap.Modal(byId("eliminationModal"));
        awaitingNextRound = true;
        setRunning(false);
        participantsInput.disabled = true;
        modeSelect.disabled = true;
        playSound("scene");
        eliminationModal.show();
    }

    function showWinner(person) {
        lastWinnerScene = randomDifferent(winnerScenes.length, lastWinnerScene);
        byId("winnerScene").innerHTML = winnerScenes[lastWinnerScene](
            escapeHtml(person.label), escapeHtml(task)
        );
        winnerModal = winnerModal || new bootstrap.Modal(byId("winnerModal"));
        awaitingNextRound = false;
        setRunning(false);
        wheelContainer.classList.add("is-winner");
        statusText.textContent = `${person.label} er den utvalgte!`;
        commentText.textContent = `Oppdraget er ${task}.`;
        const modalElement = byId("winnerModal");
        const celebrate = () => {
            modalElement.removeEventListener("shown.bs.modal", celebrate);
            playSound("winner");
            launchWinnerConfetti();
        };
        modalElement.addEventListener("shown.bs.modal", celebrate);
        winnerModal.show();
    }

    participantsInput.addEventListener("input", () => {
        eliminated = [];
        updateInputState();
    });
    customTask.addEventListener("input", () => { if (currentTask()) setValidation(); });
    taskSelect.addEventListener("change", () => {
        customTaskContainer.classList.toggle("d-none", taskSelect.value !== "Annet");
        if (currentTask()) setValidation();
    });
    modeSelect.addEventListener("change", () => {
        eliminationStatus.classList.toggle("d-none", modeSelect.value !== "last");
    });

    startButton.addEventListener("click", () => {
        if (running || awaitingNextRound) return;
        participants = parseParticipants(participantsInput.value);
        task = currentTask();
        if (participants.length < 2) return setValidation("Legg inn minst to gyldige deltakere.");
        if (!task) return setValidation("Skriv inn et oppdrag.");
        setValidation();
        eliminated = [];
        mode = modeSelect.value;
        wheel.setParticipants(participants.map((person) => person.label));
        updateStatusPanel();
        beginSpin();
    });

    nextRoundButton.addEventListener("click", () => {
        const modalElement = byId("eliminationModal");
        const continueRound = () => {
            modalElement.removeEventListener("hidden.bs.modal", continueRound);
            if (participants.length === 1) {
                wheel.setParticipants([participants[0].label]);
                wheel.highlight(0);
                showWinner(participants[0]);
            } else {
                wheel.setParticipants(participants.map((person) => person.label));
                beginSpin();
            }
        };
        modalElement.addEventListener("hidden.bs.modal", continueRound);
        eliminationModal.hide();
    });

    resetButton.addEventListener("click", () => {
        clearWinnerConfetti();
        wheel.stop();
        participantsInput.value = "";
        participants = [];
        eliminated = [];
        awaitingNextRound = false;
        task = "";
        wheel.setParticipants([]);
        wheelContainer.classList.remove("is-winner");
        statusText.textContent = "Klar for trekning";
        commentText.textContent = "Legg inn deltakerne og start hjulet.";
        setValidation();
        setRunning(false);
        updateInputState();
    });

    wheel.tickCallback = (direction) => {
        const now = performance.now();
        if (now - tickThrottle > 55) { playSound("tick", direction); tickThrottle = now; }
    };

    wheel.finishCallback = (label, index) => {
        playSound("stop");
        if (mode === "single") return showWinner(participants[index]);
        const removed = participants.splice(index, 1)[0];
        eliminated.push(removed);
        wheel.setParticipants(participants.map((person) => person.label));
        updateStatusPanel();
        statusText.textContent = `${removed.label} er slått ut`;
        commentText.textContent = "Resultatet vises før neste runde.";
        showElimination(removed);
    };

    byId("winnerModal").addEventListener("hidden.bs.modal", clearWinnerConfetti);

    updateInputState();
})();
