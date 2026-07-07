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

        this.names = [];

        this.rotation = 0;

        this.speed = 0;

        this.isSpinning = false;

        this.colors = [
            "#3B82F6",
            "#10B981",
            "#F59E0B",
            "#EF4444",
            "#8B5CF6",
            "#06B6D4",
            "#F97316",
            "#84CC16",
            "#EC4899",
            "#14B8A6",
            "#6366F1",
            "#A855F7",
            "#22C55E",
            "#E11D48",
            "#0EA5E9",
            "#CA8A04"
        ];

        this.winnerIndex = -1;

        this.tickCallback = null;

        this.finishCallback = null;

        this.lastTick = 0;

        requestAnimationFrame(() => this.animate());

    }

    /*===================================*/

    setParticipants(list){

        this.names = [...list];

        this.rotation = 0;

        this.draw();

    }

    /*===================================*/

    randomColor(i){

        return this.colors[i % this.colors.length];

    }

    /*===================================*/

    draw(){

        const ctx = this.ctx;

        ctx.clearRect(0,0,this.size,this.size);

        if(this.names.length===0){

            ctx.fillStyle="#ddd";

            ctx.beginPath();

            ctx.arc(this.radius,this.radius,this.radius-10,0,Math.PI*2);

            ctx.fill();

            return;

        }

        const angle = (Math.PI*2)/this.names.length;

        for(let i=0;i<this.names.length;i++){

            const start=i*angle+this.rotation;

            const end=start+angle;

            ctx.beginPath();

            ctx.moveTo(this.radius,this.radius);

            ctx.arc(

                this.radius,

                this.radius,

                this.radius-8,

                start,

                end

            );

            ctx.closePath();

            ctx.fillStyle=this.randomColor(i);

            ctx.fill();

            ctx.strokeStyle="#ffffff";

            ctx.lineWidth=3;

            ctx.stroke();

            ctx.save();

            ctx.translate(this.radius,this.radius);

            ctx.rotate(start+angle/2);

            ctx.textAlign="right";

            ctx.fillStyle="#fff";

            ctx.font="bold 28px Arial";

            ctx.fillText(

                this.names[i],

                this.radius-40,

                10

            );

            ctx.restore();

        }

        this.drawCenter();

    }

    /*===================================*/

    drawCenter(){

        const ctx=this.ctx;

        ctx.beginPath();

        ctx.arc(

            this.radius,

            this.radius,

            70,

            0,

            Math.PI*2

        );

        ctx.fillStyle="#1f2937";

        ctx.fill();

        ctx.strokeStyle="#ffffff";

        ctx.lineWidth=5;

        ctx.stroke();

        ctx.fillStyle="#ffffff";

        ctx.font="bold 20px Arial";

        ctx.textAlign="center";

        ctx.fillText(

            "INNSATT.NO",

            this.radius,

            this.radius+8

        );

    }

    /*===================================*/

    spin(){

        if(this.isSpinning) return;

        if(this.names.length<2) return;

        this.isSpinning=true;

        this.speed=

            0.35+

            Math.random()*0.20;

    }

    /*===================================*/

    animate(){

        if(this.isSpinning){

            this.rotation+=this.speed;

            this.speed*=0.9925;

            if(this.speed<0.002){

                this.speed=0;

                this.isSpinning=false;

                this.finishSpin();

            }

            this.tick();

        }

        this.draw();

        requestAnimationFrame(()=>this.animate());

    }

    /*===================================*/

    tick(){

        const slice=(Math.PI*2)/this.names.length;

        const current=Math.floor(

            ((this.rotation%(Math.PI*2))+Math.PI*2)

            /slice

        );

        if(current!==this.lastTick){

            this.lastTick=current;

            if(this.tickCallback){

                this.tickCallback();

            }

        }

    }

    /*===================================*/

    finishSpin(){

        const slice=(Math.PI*2)/this.names.length;

        let pointer=

            (Math.PI*1.5-this.rotation);

        while(pointer<0){

            pointer+=Math.PI*2;

        }

        pointer%=Math.PI*2;

        let index=

            Math.floor(pointer/slice);

        index=this.names.length-1-index;

        if(index<0){

            index+=this.names.length;

        }

        this.winnerIndex=index;

        if(this.finishCallback){

            this.finishCallback(

                this.names[index],

                index

            );

        }

    }

    /*===================================*/

    remove(index){

        this.names.splice(index,1);

        this.rotation=0;

        this.draw();

    }

}