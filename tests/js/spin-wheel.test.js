const assert = require("node:assert/strict");
const fs = require("node:fs");
const test = require("node:test");
const vm = require("node:vm");

const wheelSource = fs.readFileSync("public/js/wheel.js", "utf8") + "\n;globalThis.TestWheel = Wheel;";
const spinSource = fs.readFileSync("public/js/spin.js", "utf8");

function createWheelHarness(prefersReducedMotion) {
    let queuedFrame;
    let now = 0;
    const noop = () => {};
    const gradient = { addColorStop: noop };
    const context = new Proxy({}, {
        get: (_target, property) => property === "createRadialGradient"
            ? () => gradient
            : property === "measureText"
                ? () => ({ width: 1 })
                : noop
    });
    const sandbox = {
        Math: Object.create(Math),
        performance: { now: () => now },
        window: { matchMedia: () => ({ matches: prefersReducedMotion }) },
        document: { getElementById: () => ({ width: 900, getContext: () => context }) },
        requestAnimationFrame: (callback) => { queuedFrame = callback; }
    };
    sandbox.Math.random = () => 0;
    vm.createContext(sandbox);
    vm.runInContext(wheelSource, sandbox);
    const wheel = new sandbox.TestWheel("wheelCanvas");

    return {
        wheel,
        setNow(value) { now = value; },
        frame(value) { now = value; queuedFrame(value); }
    };
}

function createClassList() {
    const values = new Set();
    return {
        add: (...names) => names.forEach((name) => values.add(name)),
        remove: (...names) => names.forEach((name) => values.delete(name)),
        toggle: (name, force) => {
            const enabled = force === undefined ? !values.has(name) : force;
            if (enabled) values.add(name); else values.delete(name);
            return enabled;
        }
    };
}

class FakeElement extends EventTarget {
    constructor() {
        super();
        this.classList = createClassList();
        this.style = { setProperty() {} };
        this.value = "";
        this.checked = false;
        this.disabled = false;
        this.textContent = "";
        this._innerHTML = null;
    }

    get innerHTML() { return this._innerHTML === null ? this.textContent : this._innerHTML; }
    set innerHTML(value) { this._innerHTML = value; }

    appendChild() {}
    remove() {}
    setAttribute() {}
}

class WheelStub {
    static instances = [];

    constructor() {
        this.isSpinning = false;
        this.spinCalls = 0;
        this.setParticipantsCalls = 0;
        WheelStub.instances.push(this);
    }

    setParticipants() { this.setParticipantsCalls += 1; }
    highlight() {}
    stop() { this.isSpinning = false; }
    spin() { this.isSpinning = true; this.spinCalls += 1; return true; }
    finish(index) {
        this.isSpinning = false;
        this.finishCallback("ignored", index);
    }
}

function createSpinHarness() {
    WheelStub.instances = [];
    let now = 0;
    let nextTimerId = 0;
    const timers = new Map();
    const ids = [
        "participants", "taskSelect", "customTask", "customTaskContainer", "mode",
        "startButton", "resetButton", "nextRoundButton", "validationMessage",
        "participantHint", "statusText", "commentText", "soundEnabled",
        "eliminationStatus", "activeCount", "eliminatedCount", "activeParticipants",
        "eliminatedParticipants", "eliminationScene", "winnerScene", "eliminationModal",
        "winnerModal"
    ];
    const elements = Object.fromEntries(ids.map((id) => [id, new FakeElement()]));
    elements.taskSelect.value = "Luftevakt";
    elements.mode.value = "last";
    const wheelContainer = new FakeElement();
    const modalInstances = new Map();
    class ModalStub {
        constructor(element) {
            this.element = element;
            this.showCalls = 0;
            this.hideCalls = 0;
            modalInstances.set(element, this);
        }
        show() { this.showCalls += 1; this.element.dispatchEvent(new Event("shown.bs.modal")); }
        hide() { this.hideCalls += 1; }
    }
    const sandbox = {
        Wheel: WheelStub,
        bootstrap: { Modal: ModalStub },
        performance: { now: () => now },
        window: {
            matchMedia: () => ({ matches: false }),
            setTimeout: (callback) => {
                const id = ++nextTimerId;
                timers.set(id, callback);
                return id;
            },
            clearTimeout: (id) => timers.delete(id)
        },
        document: {
            body: new FakeElement(),
            getElementById: (id) => elements[id],
            querySelector: () => wheelContainer,
            createElement: () => new FakeElement()
        }
    };
    vm.createContext(sandbox);
    vm.runInContext(spinSource, sandbox);
    return {
        elements,
        modalInstances,
        wheel: WheelStub.instances[0],
        setNow(value) { now = value; },
        runTimers() {
            const callbacks = [...timers.values()];
            timers.clear();
            callbacks.forEach((callback) => callback());
        }
    };
}

function click(element) {
    element.dispatchEvent(new Event("click"));
}

test("normal and reduced-motion spins both last at least five seconds", () => {
    for (const reduced of [false, true]) {
        const harness = createWheelHarness(reduced);
        const { wheel } = harness;
        wheel.setParticipants(["A", "B"]);
        let finishes = 0;
        wheel.finishCallback = () => { finishes += 1; };
        assert.equal(wheel.spin(), true);
        assert.ok(wheel.spinDuration >= 5000);
        harness.frame(wheel.spinDuration - 1);
        assert.equal(finishes, 0);
        assert.equal(wheel.isSpinning, true);
        harness.frame(wheel.spinDuration);
        assert.equal(finishes, 1);
        assert.equal(wheel.isSpinning, false);
    }
});

test("reduced motion remains visibly in motion until its five-second finish", () => {
    const harness = createWheelHarness(true);
    const { wheel } = harness;
    wheel.setParticipants(["A", "B"]);
    wheel.spin();
    const initialRotation = wheel.rotation;
    harness.frame(2500);
    assert.equal(wheel.isSpinning, true);
    assert.notEqual(wheel.rotation, initialRotation);
    assert.notEqual(wheel.rotation, wheel.targetRotation % (Math.PI * 2));
});

test("60 Hz and 144 Hz use approximately the same elapsed duration", () => {
    for (const hz of [60, 144]) {
        const harness = createWheelHarness(false);
        const { wheel } = harness;
        wheel.setParticipants(["A", "B"]);
        let finishedAt = null;
        wheel.finishCallback = () => { finishedAt = currentTime; };
        wheel.spin();
        let currentTime = 0;
        const frameDuration = 1000 / hz;
        while (finishedAt === null) {
            currentTime += frameDuration;
            harness.frame(currentTime);
        }
        assert.ok(finishedAt >= 5000);
        assert.ok(finishedAt < 5010);
    }
});

test("controller does not present an early completion and reset invalidates it", () => {
    const harness = createSpinHarness();
    const { elements, modalInstances, wheel } = harness;
    elements.participants.value = "A\nB\nC";
    click(elements.startButton);
    wheel.finish(0);
    assert.equal(modalInstances.has(elements.eliminationModal), false);

    click(elements.resetButton);
    harness.setNow(5000);
    harness.runTimers();
    assert.equal(modalInstances.has(elements.eliminationModal), false);
    assert.equal(elements.statusText.textContent, "Klar for trekning");
});

test("double-clicking next round creates exactly one continuation", () => {
    const harness = createSpinHarness();
    const { elements, modalInstances, wheel } = harness;
    elements.participants.value = "A\nB\nC";
    click(elements.startButton);
    assert.equal(wheel.spinCalls, 1);

    harness.setNow(5000);
    wheel.finish(0);
    const eliminationModal = modalInstances.get(elements.eliminationModal);
    assert.equal(eliminationModal.showCalls, 1);
    assert.match(elements.eliminationScene.innerHTML, /A/);

    click(elements.nextRoundButton);
    click(elements.nextRoundButton);
    assert.equal(eliminationModal.hideCalls, 1);
    elements.eliminationModal.dispatchEvent(new Event("hidden.bs.modal"));
    assert.equal(wheel.spinCalls, 2);
});
