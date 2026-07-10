const taskSelect = document.getElementById("taskSelect");
const customTask = document.getElementById("customTask");
const customTaskContainer = document.getElementById("customTaskContainer");
const winnerName = document.getElementById("winnerName");
const winnerTask = document.getElementById("winnerTask");
const startButton = document.getElementById("startButton");
const resetButton = document.getElementById("resetButton");
const statusText = document.getElementById("statusText");
const commentText = document.getElementById("commentText");
const soundEnabled = document.getElementById("soundEnabled");
const reducedMotionQuery = window.matchMedia("(prefers-reduced-motion: reduce)");

/*==========================================================
    spin.js
    Oppdragshjulet
==========================================================*/

const wheel = new Wheel("wheelCanvas");

const comments = [
    "Hjulet er klart.",
    "Ingen påvirkning er tillatt.",
    "Tilfeldigheten bestemmer.",
    "Lykke til!",
    "Hvem slipper unna?",
    "Nå blir det spennende...",
    "Hjulet spinner!",
    "Ingen klager til hjulet.",
    "Kun tilfeldigheten avgjør.",
    "Da starter vi!"
];

let mode = "last";
let participants = [];
let task = "";
let running = false;
let winnerModal = null;
let nextRoundTimeout = null;
let countdownInterval = null;
let roundToken = 0;

const audioHooks = {
    spin: null,
    stop: null,
    winner: null
};

/*************************************************
Lyd
*************************************************/

function playSound(type){

    if(!soundEnabled.checked || !audioHooks[type]){

        return;

    }

    // Senere kan spin-, stopp- og vinnerlyd kobles til her.
    // audioHooks.spin = new Audio("/audio/spin.mp3");
    // audioHooks.stop = new Audio("/audio/stop.mp3");
    // audioHooks.winner = new Audio("/audio/winner.mp3");

    audioHooks[type].pause();
    audioHooks[type].currentTime = 0;
    audioHooks[type].play();

}

/*************************************************
Kommentar
*************************************************/

function randomComment(){

    commentText.innerText = comments[Math.floor(Math.random() * comments.length)];

}

/*************************************************
Konfetti
*************************************************/

function confetti(){

    if(reducedMotionQuery.matches){

        return;

    }

    for(let i = 0; i < 90; i++){

        const c = document.createElement("div");

        c.className = "confetti";
        c.style.left = Math.random() * 100 + "vw";
        c.style.background = [
            "#ef4444",
            "#22c55e",
            "#3b82f6",
            "#f59e0b",
            "#8b5cf6"
        ][Math.floor(Math.random() * 5)];
        c.style.animationDelay = Math.random() * 1.2 + "s";
        c.style.transform = "rotate(" + Math.random() * 360 + "deg)";

        document.body.appendChild(c);

        setTimeout(() => {

            c.remove();

        }, 5200);

    }

}

/*************************************************
Les deltakere
*************************************************/

function getParticipants(){

    return document
        .getElementById("participants")
        .value
        .split("\n")
        .map((v) => v.trim())
        .filter((v) => v.length > 0);

}

/*************************************************
Oppdrag
*************************************************/

function getTask(){

    if(taskSelect.value === "Annet"){

        return customTask.value.trim();

    }

    return taskSelect.value;

}

/*************************************************
Status
*************************************************/

function setStatus(text){

    statusText.innerText = text;

}

function setRunning(nextRunning){

    running = nextRunning;
    startButton.disabled = nextRunning;
    startButton.classList.toggle("is-spinning", nextRunning);
    startButton.innerText = nextRunning ? "Spinner..." : "Start trekking";

}

function clearNextRoundTimers(){

    roundToken++;

    if(nextRoundTimeout){

        clearTimeout(nextRoundTimeout);
        nextRoundTimeout = null;

    }

    if(countdownInterval){

        clearInterval(countdownInterval);
        countdownInterval = null;

    }

}

function randomEliminationDelay(){

    return 3000 + Math.random() * 2000;

}

function scheduleNextRound(removedName, removedIndex){

    clearNextRoundTimers();

    const token = roundToken;
    const delay = randomEliminationDelay();
    const endsAt = Date.now() + delay;

    function updateCountdown(){

        const seconds = Math.max(1, Math.ceil((endsAt - Date.now()) / 1000));

        setStatus(removedName + " slipper unna.");
        commentText.innerText = "Neste runde starter om " + seconds + " sekunder";

    }

    updateCountdown();

    countdownInterval = setInterval(() => {

        if(token !== roundToken){

            return;

        }

        updateCountdown();

    }, 250);

    nextRoundTimeout = setTimeout(() => {

        if(token !== roundToken || !running || participants.length < 2){

            return;

        }

        clearNextRoundTimers();
        wheel.remove(removedIndex);
        setStatus("Spinner...");
        randomComment();
        playSound("spin");
        wheel.spin();

    }, delay);

}

/*************************************************
Start
*************************************************/

startButton.addEventListener("click", () => {

    if(running){

        return;

    }

    clearNextRoundTimers();

    participants = getParticipants();

    if(participants.length < 2){

        alert("Du må legge inn minst to deltakere.");

        return;

    }

    task = getTask();

    if(task === ""){

        alert("Velg oppdrag.");

        return;

    }

    mode = document.getElementById("mode").value;

    wheel.setParticipants(participants);

    setRunning(true);
    setStatus("Spinner...");
    randomComment();
    playSound("spin");
    wheel.spin();

});

/*************************************************
Tick
*************************************************/

wheel.tickCallback = () => {

    // Hook for eventuell diskret tick-lyd senere.

};

/*************************************************
Når hjulet stopper
*************************************************/

wheel.finishCallback = (name, index) => {

    playSound("stop");

    if(mode === "single"){

        finish(name);

        return;

    }

    participants.splice(index, 1);

    if(participants.length === 1){

        wheel.setParticipants(participants);
        wheel.highlight(0);
        finish(participants[0]);

        return;

    }

    scheduleNextRound(name, index);

};

/*************************************************
Finale
*************************************************/

function finish(name){

    setRunning(false);
    playSound("winner");
    confetti();

    setStatus("Oppdrag valgt");

    winnerName.innerText = name;
    winnerTask.innerHTML = "";

    const label = document.createElement("div");
    const taskName = document.createElement("div");

    label.className = "winner-task-label";
    label.innerText = "skal utføre";

    taskName.className = "winner-task-name";
    taskName.innerText = task;

    winnerTask.appendChild(label);
    winnerTask.appendChild(taskName);

    if(!winnerModal){

        winnerModal = new bootstrap.Modal(
            document.getElementById("winnerModal")
        );

    }

    const modalElement = document.getElementById("winnerModal");
    modalElement.classList.remove("winner-modal-pulse");
    void modalElement.offsetWidth;
    modalElement.classList.add("winner-modal-pulse");

    winnerModal.show();

}

/*************************************************
Nullstill
*************************************************/

resetButton.addEventListener("click", () => {

    clearNextRoundTimers();
    participants = [];
    setRunning(false);

    document.getElementById("participants").value = "";
    wheel.stop();
    wheel.setParticipants([]);
    setStatus("Klar for trekning");
    commentText.innerText = "Legg inn deltakere.";

});

/*************************************************
Annet...
*************************************************/

taskSelect.addEventListener("change", () => {

    customTaskContainer.classList.toggle(
        "d-none",
        taskSelect.value !== "Annet"
    );

});

/*************************************************
Starttekst
*************************************************/

randomComment();
