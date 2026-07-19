@extends('layouts.app')

@section('title', 'Hvem tar oppdraget?')

@section('content')

<div class="container page-container spin-page" id="spinApp">
    @include('partials.header')

    <main class="spin-content">
        <header class="spin-intro text-center">
            <span class="spin-intro__eyebrow">Oppdragshjulet</span>
            <h1>Hvem tar oppdraget?</h1>
            <p>Legg inn navnene, velg oppdraget og la hjulet avgjøre.</p>
        </header>

    <div class="row g-4 align-items-start">

        <!-- Venstre panel -->

        <div class="col-xl-4 col-lg-5">

    <div class="card shadow-sm h-100">

        <div class="card-header py-3">

    <h3 class="mb-1 fw-bold">
        Gjør klart hjulet
    </h3>

    <small class="text-muted">
        Fordel oppgaver tilfeldig
    </small>
</div>

                <div class="card-body">

                    <div class="mb-3">

                        <label class="form-label">

                            Oppdrag

                        </label>

                        <select
                            class="form-select"
                            id="taskSelect">

                            <option>Luftevakt</option>
                            <option>Annet</option>

                        </select>

                    </div>

                    <div
                        class="mb-3 d-none"
                        id="customTaskContainer">

                        <label class="form-label">

                            Egendefinert oppdrag

                        </label>

                        <input
                            class="form-control"
                            id="customTask">

                    </div>

                    <div class="mb-3">

                        <label class="form-label">

                            Modus

                        </label>

                        <select
                            class="form-select"
                            id="mode">

                            <option value="last">

                                Siste person igjen

                            </option>

                            <option value="single">

                                Velg én tilfeldig

                            </option>

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">

                            Deltakere
                            <small class="text-muted">
                                (ett navn pr linje)
                            </small>

                        </label>

                        <textarea

                            id="participants"

                            class="form-control"

                            rows="12"

                            placeholder="Ola
Kari
Per
Anne"></textarea>

                    </div>

                    <div class="d-grid gap-2">

                        <button

                            class="btn btn-success btn-lg"

                            id="startButton">

                           <span aria-hidden="true">✦</span>
                           La hjulet bestemme

                        </button>

                        <button

                            class="btn btn-outline-secondary"

                            id="resetButton">

                            Nullstill

                        </button>

                    </div>

                    <hr>

                    <div class="form-check">

                        <input

                            checked

                            class="form-check-input"

                            id="soundEnabled"

                            type="checkbox">

                        <label
                            class="form-check-label">

                            Lydeffekter

                        </label>

                    </div>

                </div>

            </div>

        </div>

        <!-- Høyre panel -->

        <div class="col-xl-8 col-lg-7">

            <div class="card shadow">

                <div class="card-body">

                    <div
                        class="wheel-container is-ready">

                        <canvas

                            id="wheelCanvas"

                            width="900"

                            height="900">

                        </canvas>

                        <div class="wheel-pointer" aria-hidden="true">
                            <span class="wheel-pointer__stem"></span>
                            <span class="wheel-pointer__tip"></span>
                        </div>

                    </div>

                    <div class="text-center mt-4">

                        <h2 id="statusText" aria-live="polite">

                            Klar for trekning

                        </h2>

                        <p
                            class="lead"

                            id="commentText">

                            Legg inn deltakerne og start hjulet.

                        </p>

                    </div>

   
<!-- Resultat -->

<div
    class="modal fade"
    id="winnerModal"
    tabindex="-1"
    aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content border-0 shadow-lg rounded-4 winner-modal">

            <div class="modal-header">

                <h3 class="modal-title">

                    Den utvalgte

                </h3>

            </div>

           <div class="modal-body text-center py-3">

    <div class="winner-badge">Oppdraget går til</div>

    <h1 id="winnerName" class="display-3 fw-bold text-success"></h1>

    <div id="winnerTask"></div>

</div>

            <div class="modal-footer">

                <button

                    class="btn btn-primary"

                    data-bs-dismiss="modal">

                    Lukk

                </button>

            </div>

        </div>

    </div>

</div>

                </div>

            </div>

        </div>

    </div>

    </main>

    @include('partials.footer')

</div>

@endsection

@push('styles')

<link rel="stylesheet" href="/css/spin.css">

@endpush

@push('scripts')

<script src="/js/wheel.js"></script>
<script src="/js/spin.js"></script>

@endpush
