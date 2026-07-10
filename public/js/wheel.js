/*==========================================================
    wheel.js
    Oppdragshjulet
    innsatt.no

    Canvas-hjulmotor
==========================================================*/

class Wheel {

    constructor(canvasId) {

        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext("2d");

        this.size = this.canvas.width;
        this.radius = this.size / 2;
        this.pointerAngle = Math.PI * 1.5;

        this.names = [];
        this.rotation = 0;
        this.isSpinning = false;
        this.prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

        this.colors = [
            ["#1d4ed8", "#60a5fa"],
            ["#047857", "#34d399"],
            ["#b45309", "#fbbf24"],
            ["#be123c", "#fb7185"],
            ["#6d28d9", "#a78bfa"],
            ["#0f766e", "#2dd4bf"],
            ["#c2410c", "#fb923c"],
            ["#4d7c0f", "#a3e635"],
            ["#be185d", "#f472b6"],
            ["#0369a1", "#38bdf8"],
            ["#4338ca", "#818cf8"],
            ["#0f766e", "#5eead4"]
        ];

        this.winnerIndex = -1;
        this.glowUntil = 0;

        this.tickCallback = null;
        this.finishCallback = null;
        this.lastTick = -1;

        this.spinStartTime = 0;
        this.spinDuration = 0;
        this.startRotation = 0;
        this.targetRotation = 0;

        requestAnimationFrame((time) => this.animate(time));

    }

    /*===================================*/

    setParticipants(list){

        this.names = [...list];
        this.rotation = 0;
        this.winnerIndex = -1;
        this.glowUntil = 0;
        this.lastTick = -1;
        this.draw();

    }

    /*===================================*/

    draw(){

        const ctx = this.ctx;

        ctx.clearRect(0, 0, this.size, this.size);

        this.drawDropShadow();

        if(this.names.length === 0){

            this.drawEmptyWheel();

            return;

        }

        const angle = (Math.PI * 2) / this.names.length;

        for(let i = 0; i < this.names.length; i++){

            const start = i * angle + this.rotation;
            const end = start + angle;

            this.drawSegment(i, start, end, angle);
            this.drawName(this.names[i], start + angle / 2, angle);

        }

        this.drawOuterRim();
        this.drawCenter();

    }

    /*===================================*/

    drawDropShadow(){

        const ctx = this.ctx;

        ctx.save();
        ctx.beginPath();
        ctx.arc(this.radius, this.radius + 12, this.radius - 22, 0, Math.PI * 2);
        ctx.fillStyle = "rgba(15,23,42,.22)";
        ctx.filter = "blur(18px)";
        ctx.fill();
        ctx.restore();

    }

    /*===================================*/

    drawEmptyWheel(){

        const ctx = this.ctx;
        const gradient = ctx.createRadialGradient(
            this.radius,
            this.radius,
            40,
            this.radius,
            this.radius,
            this.radius - 12
        );

        gradient.addColorStop(0, "#ffffff");
        gradient.addColorStop(1, "#cbd5e1");

        ctx.beginPath();
        ctx.arc(this.radius, this.radius, this.radius - 12, 0, Math.PI * 2);
        ctx.fillStyle = gradient;
        ctx.fill();

        this.drawOuterRim();
        this.drawCenter();

    }

    /*===================================*/

    drawSegment(index, start, end, angle){

        const ctx = this.ctx;
        const colors = this.colors[index % this.colors.length];
        const gradient = ctx.createRadialGradient(
            this.radius,
            this.radius,
            40,
            this.radius,
            this.radius,
            this.radius - 12
        );

        gradient.addColorStop(0, this.lighten(colors[1], 16));
        gradient.addColorStop(.62, colors[1]);
        gradient.addColorStop(1, colors[0]);

        ctx.beginPath();
        ctx.moveTo(this.radius, this.radius);
        ctx.arc(this.radius, this.radius, this.radius - 14, start, end);
        ctx.closePath();
        ctx.fillStyle = gradient;
        ctx.fill();

        ctx.strokeStyle = "rgba(255,255,255,.78)";
        ctx.lineWidth = Math.max(2, Math.min(5, 22 / this.names.length));
        ctx.stroke();

        if(index === this.winnerIndex && this.glowUntil > performance.now()){

            const glowProgress = (this.glowUntil - performance.now()) / 1500;
            const glow = this.prefersReducedMotion ? .42 : .24 + Math.sin(glowProgress * Math.PI * 8) * .18;

            ctx.save();
            ctx.beginPath();
            ctx.moveTo(this.radius, this.radius);
            ctx.arc(this.radius, this.radius, this.radius - 17, start, end);
            ctx.closePath();
            ctx.fillStyle = "rgba(255,255,255," + glow.toFixed(3) + ")";
            ctx.fill();
            ctx.lineWidth = 10;
            ctx.strokeStyle = "rgba(253,224,71,.88)";
            ctx.stroke();
            ctx.restore();

        }

    }

    /*===================================*/

    drawName(name, textAngle, sliceAngle){

        const ctx = this.ctx;
        const maxWidth = Math.max(70, this.radius - 145);
        const fontSize = Math.max(13, Math.min(28, 460 / Math.max(this.names.length, 8)));
        const text = this.fitText(name, maxWidth, fontSize);

        ctx.save();
        ctx.translate(this.radius, this.radius);
        ctx.rotate(textAngle);

        ctx.fillStyle = "#ffffff";
        ctx.shadowColor = "rgba(15,23,42,.55)";
        ctx.shadowBlur = 4;
        ctx.shadowOffsetY = 2;
        ctx.font = "700 " + fontSize + "px Arial, sans-serif";
        ctx.textBaseline = "middle";

        if(sliceAngle < .22){

            ctx.globalAlpha = .82;

        }

        if(textAngle > Math.PI / 2 && textAngle < Math.PI * 1.5) {

            ctx.rotate(Math.PI);
            ctx.textAlign = "left";
            ctx.fillText(text, -(this.radius - 54), 0, maxWidth);

        } else {

            ctx.textAlign = "right";
            ctx.fillText(text, this.radius - 54, 0, maxWidth);

        }

        ctx.restore();

    }

    /*===================================*/

    fitText(text, maxWidth, fontSize){

        const ctx = this.ctx;

        ctx.save();
        ctx.font = "700 " + fontSize + "px Arial, sans-serif";

        if(ctx.measureText(text).width <= maxWidth){

            ctx.restore();

            return text;

        }

        let shortened = text;

        while(shortened.length > 2 && ctx.measureText(shortened + "...").width > maxWidth){

            shortened = shortened.slice(0, -1);

        }

        ctx.restore();

        return shortened + "...";

    }

    /*===================================*/

    drawOuterRim(){

        const ctx = this.ctx;

        ctx.save();
        ctx.beginPath();
        ctx.arc(this.radius, this.radius, this.radius - 10, 0, Math.PI * 2);
        ctx.lineWidth = 16;
        ctx.strokeStyle = "#f8fafc";
        ctx.stroke();

        ctx.beginPath();
        ctx.arc(this.radius, this.radius, this.radius - 22, 0, Math.PI * 2);
        ctx.lineWidth = 3;
        ctx.strokeStyle = "rgba(15,23,42,.18)";
        ctx.stroke();
        ctx.restore();

    }

    /*===================================*/

    drawCenter(){

        const ctx = this.ctx;
        const gradient = ctx.createRadialGradient(
            this.radius - 18,
            this.radius - 24,
            10,
            this.radius,
            this.radius,
            82
        );

        gradient.addColorStop(0, "#475569");
        gradient.addColorStop(.7, "#111827");
        gradient.addColorStop(1, "#020617");

        ctx.save();
        ctx.beginPath();
        ctx.arc(this.radius, this.radius, 74, 0, Math.PI * 2);
        ctx.fillStyle = gradient;
        ctx.fill();
        ctx.lineWidth = 6;
        ctx.strokeStyle = "#ffffff";
        ctx.stroke();

        ctx.fillStyle = "#ffffff";
        ctx.font = "800 20px Arial, sans-serif";
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";
        ctx.fillText("INNSATT.NO", this.radius, this.radius + 2);
        ctx.restore();

    }

    /*===================================*/

    spin(){

        if(this.isSpinning) return;
        if(this.names.length < 2) return;

        const slice = (Math.PI * 2) / this.names.length;
        const selectedIndex = Math.floor(Math.random() * this.names.length);
        const selectedCenter = selectedIndex * slice + slice / 2;
        const fullTurns = this.prefersReducedMotion ? 1 : 6 + Math.floor(Math.random() * 3);
        let target = this.pointerAngle - selectedCenter + fullTurns * Math.PI * 2;

        while(target <= this.rotation){

            target += Math.PI * 2;

        }

        this.winnerIndex = -1;
        this.glowUntil = 0;
        this.isSpinning = true;
        this.spinStartTime = performance.now();
        this.spinDuration = this.prefersReducedMotion ? 650 : this.randomSpinDuration();
        this.startRotation = this.rotation;
        this.targetRotation = target;
        this.lastTick = -1;

    }

    /*===================================*/

    animate(time){

        if(this.isSpinning){

            const elapsed = time - this.spinStartTime;
            const progress = Math.min(1, elapsed / this.spinDuration);
            const eased = this.easeInOutCubic(progress);

            this.rotation = this.startRotation + (this.targetRotation - this.startRotation) * eased;
            this.tick();

            if(progress >= 1){

                this.rotation = this.targetRotation % (Math.PI * 2);
                this.isSpinning = false;
                this.finishSpin();

            }

        }

        this.draw();

        requestAnimationFrame((nextTime) => this.animate(nextTime));

    }

    /*===================================*/

    randomSpinDuration(){

        const base = 5000 + Math.random() * 3000;
        const drift = (Math.random() - .5) * 420;

        return Math.max(5000, Math.min(8000, base + drift));

    }

    /*===================================*/

    easeInOutCubic(t){

        if(t < .5){

            return 4 * t * t * t;

        }

        return 1 - Math.pow(-2 * t + 2, 3) / 2;

    }

    /*===================================*/

    tick(){

        if(this.names.length === 0){

            return;

        }

        const slice = (Math.PI * 2) / this.names.length;
        const pointer = this.normalizedPointer();
        const current = Math.floor(pointer / slice);

        if(current !== this.lastTick){

            this.lastTick = current;

            if(this.tickCallback){

                this.tickCallback();

            }

        }

    }

    /*===================================*/

    finishSpin(){

        const slice = (Math.PI * 2) / this.names.length;
        const index = Math.floor(this.normalizedPointer() / slice);

        this.winnerIndex = index;
        this.glowUntil = performance.now() + 1500;

        if(this.finishCallback){

            this.finishCallback(this.names[index], index);

        }

    }

    /*===================================*/

    normalizedPointer(){

        let pointer = this.pointerAngle - this.rotation;

        while(pointer < 0){

            pointer += Math.PI * 2;

        }

        return pointer % (Math.PI * 2);

    }

    /*===================================*/

    stop(){

        this.isSpinning = false;
        this.spinStartTime = 0;
        this.spinDuration = 0;
        this.startRotation = this.rotation;
        this.targetRotation = this.rotation;
        this.lastTick = -1;

    }

    /*===================================*/

    remove(index){

        this.names.splice(index, 1);
        this.rotation = 0;
        this.winnerIndex = -1;
        this.glowUntil = 0;
        this.lastTick = -1;
        this.draw();

    }

    /*===================================*/

    highlight(index){

        this.winnerIndex = index;
        this.glowUntil = performance.now() + 1500;
        this.draw();

    }

    /*===================================*/

    lighten(hex, percent){

        const value = hex.replace("#", "");
        const number = parseInt(value, 16);
        const amount = Math.round(2.55 * percent);
        const red = Math.min(255, (number >> 16) + amount);
        const green = Math.min(255, ((number >> 8) & 0x00FF) + amount);
        const blue = Math.min(255, (number & 0x0000FF) + amount);

        return "#" + (
            0x1000000 +
            red * 0x10000 +
            green * 0x100 +
            blue
        ).toString(16).slice(1);

    }

}
