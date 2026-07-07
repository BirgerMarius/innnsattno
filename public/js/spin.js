const taskSelect = document.getElementById("taskSelect");
const customTask = document.getElementById("customTask");
const customTaskContainer = document.getElementById("customTaskContainer");
const winnerName = document.getElementById("winnerName");
const winnerTask = document.getElementById("winnerTask");

/*==========================================================
    spin.js
    Oppdragshjulet
==========================================================*/

const wheel = new Wheel("wheelCanvas");

const comments = [

    "🎤 Hjulet er klart.",
    "🎤 Ingen påvirkning er tillatt.",
    "🎤 Tilfeldigheten bestemmer.",
    "🎤 Lykke til!",
    "🎤 Hvem slipper unna?",
    "🎤 Nå blir det spennende...",
    "🎤 Hjulet spinner!",
    "🎤 Ingen klager til hjulet.",
    "🎤 Kun tilfeldigheten avgjør.",
    "🎤 Da starter vi!"
];

let mode = "last";

let participants = [];

let task = "";

let running = false;

const statusText = document.getElementById("statusText");
const commentText = document.getElementById("commentText");

/*************************************************
Kommentar
*************************************************/

function randomComment(){

    commentText.innerText =
        comments[
            Math.floor(Math.random()*comments.length)
        ];

}

/*************************************************
Lyd
*************************************************/

function play(id){

    if(!document.getElementById("soundEnabled").checked){

        return;

    }

    const audio=document.getElementById(id);

    if(!audio){

        return;

    }

    audio.pause();

    audio.currentTime=0;

    audio.play();

}

/*************************************************
Konfetti
*************************************************/

function confetti(){

    for(let i=0;i<180;i++){

        const c=document.createElement("div");

        c.className="confetti";

        c.style.left=Math.random()*100+"vw";

        c.style.background=

            [

                "#ef4444",

                "#22c55e",

                "#3b82f6",

                "#f59e0b",

                "#8b5cf6"

            ][Math.floor(Math.random()*5)];

        c.style.animationDelay=(Math.random()*2)+"s";

        c.style.transform="rotate("+Math.random()*360+"deg)";

        document.body.appendChild(c);

        setTimeout(()=>{

            c.remove();

        },6000);

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
        .map(v=>v.trim())
        .filter(v=>v.length>0);

}

/*************************************************
Oppdrag
*************************************************/

function getTask(){

    if(taskSelect.value==="Annet..."){

        return customTask.value.trim();

    }

    return taskSelect.value;

}

/*************************************************
Status
*************************************************/

function setStatus(text){

    statusText.innerText=text;

}

/*************************************************
Start
*************************************************/

document
.getElementById("startButton")
.addEventListener("click",()=>{

    if(running){

        return;

    }

    participants=getParticipants();

    if(participants.length<2){

        alert("Du må legge inn minst to deltakere.");

        return;

    }

    task=getTask();

    if(task===""){

        alert("Velg oppdrag.");

        return;

    }

    mode=document.getElementById("mode").value;

    wheel.setParticipants(participants);

    running=true;

    setStatus("Spinner...");

    randomComment();

    play("drumAudio");

    setTimeout(()=>{

        wheel.spin();

    },900);

});

/*************************************************
Tick
*************************************************/

wheel.tickCallback=()=>{

    play("tickAudio");

};

/*************************************************
Når hjulet stopper
*************************************************/

wheel.finishCallback=(name,index)=>{

    if(mode==="single"){

        finish(name);

        return;

    }

    participants.splice(index,1);

    wheel.remove(index);

    if(participants.length===1){

        finish(participants[0]);

        return;

    }

    setStatus(

        name+" slipper unna."

    );

    randomComment();

    setTimeout(()=>{

        wheel.spin();

    },1800);

};

/*************************************************
Finale
*************************************************/

function finish(name){

    running=false;

    play("fanfareAudio");

    setTimeout(()=>{

        play("applauseAudio");

        confetti();

    },1200);

    setStatus("🏆 Oppdrag valgt");

    winnerName.innerText = name;

winnerTask.innerHTML = `
    <div class="mt-3">
        skal utføre
    </div>

    <div class="display-6 fw-bold text-primary mt-2">
        ${task}
    </div>
`;

document.getElementById("winnerCardName").innerText = name;

document.getElementById("winnerCardTask").innerText = task;

document.getElementById("winnerCard").style.display = "block";


}

/*************************************************
Nullstill
*************************************************/

document
.getElementById("resetButton")
.addEventListener("click",()=>{

    participants=[];

    running=false;

    document.getElementById("participants").value="";

    wheel.setParticipants([]);

    setStatus("Klar for trekning");

    commentText.innerText=
        "Legg inn deltakere.";
document.getElementById("winnerCard").style.display = "none";
});

/*************************************************
Annet...
*************************************************/

taskSelect.addEventListener("change",()=>{

    customTaskContainer.classList.toggle(

        "d-none",

        taskSelect.value!=="Annet..."

    );

});

/*************************************************
Starttekst
*************************************************/

randomComment();