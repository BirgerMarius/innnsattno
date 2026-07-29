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
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow-sm spin-controls">
                    <div class="card-header py-3">
                        <h3 class="mb-1 fw-bold">Gjør klart hjulet</h3>
                        <small class="text-muted">Fordel oppgaver tilfeldig</small>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="taskSelect">Oppdrag</label>
                            <select class="form-select" id="taskSelect">
                                <option>Luftevakt</option>
                                <option>Annet</option>
                            </select>
                        </div>

                        <div class="mb-3 d-none" id="customTaskContainer">
                            <label class="form-label" for="customTask">Egendefinert oppdrag</label>
                            <input class="form-control" id="customTask">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="mode">Modus</label>
                            <select class="form-select" id="mode">
                                <option value="last">Siste person igjen</option>
                                <option value="single">Velg én tilfeldig</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="participants">
                                Deltakere <small class="text-muted">(ett navn per linje)</small>
                            </label>
                            <textarea id="participants" class="form-control" rows="10"
                                placeholder="Ola&#10;Kari&#10;Per&#10;Anne"></textarea>
                            <div class="participant-hint" id="participantHint">Skriv inn minst to deltakere.</div>
                        </div>

                        <div class="alert alert-danger d-none" id="validationMessage" role="alert" aria-live="polite"></div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-success btn-lg" id="startButton" disabled>
                                <span aria-hidden="true">✦</span> Spinn hjulet
                            </button>
                            <button class="btn btn-outline-secondary" id="resetButton">Nullstill</button>
                        </div>

                        <hr>
                        <div class="form-check">
                            <input checked class="form-check-input" id="soundEnabled" type="checkbox">
                            <label class="form-check-label" for="soundEnabled">Lydeffekter</label>
                        </div>
                    </div>
                </div>

                <section class="card shadow-sm elimination-status mt-4" id="eliminationStatus" aria-labelledby="eliminationTitle">
                    <div class="card-header py-3">
                        <h3 class="h5 mb-0 fw-bold" id="eliminationTitle">Siste person igjen</h3>
                    </div>
                    <div class="card-body">
                        <div class="status-heading">
                            <h4>Fortsatt med</h4>
                            <span class="status-count" id="activeCount">0</span>
                        </div>
                        <ul class="participant-list participant-list--active" id="activeParticipants">
                            <li class="participant-list__empty">Ingen deltakere ennå</li>
                        </ul>
                        <div class="status-heading status-heading--out">
                            <h4>Slått ut</h4>
                            <span class="status-count status-count--out" id="eliminatedCount">0</span>
                        </div>
                        <ol class="participant-list participant-list--out" id="eliminatedParticipants">
                            <li class="participant-list__empty">Ingen er slått ut</li>
                        </ol>
                    </div>
                </section>
            </div>

            <div class="col-xl-8 col-lg-7">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="wheel-container is-ready">
                            <canvas id="wheelCanvas" width="900" height="900"></canvas>
                            <div class="wheel-pointer" aria-hidden="true">
                                <span class="wheel-pointer__stem"></span>
                                <span class="wheel-pointer__tip"></span>
                            </div>
                        </div>
                        <div class="text-center mt-4">
                            <h2 id="statusText" aria-live="polite">Klar for trekning</h2>
                            <p class="lead" id="commentText">Legg inn deltakerne og start hjulet.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @include('partials.footer')
</div>

<div class="modal fade result-scene-modal" id="eliminationModal" tabindex="-1"
    aria-labelledby="eliminationModalTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h2 class="modal-title h4" id="eliminationModalTitle">Slått ut av hjulet</h2>
            </div>
            <div class="modal-body">
                <div class="result-scene" id="eliminationScene"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success btn-lg" id="nextRoundButton">Spinn neste runde</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade result-scene-modal" id="winnerModal" tabindex="-1"
    aria-labelledby="winnerModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg winner-modal">
            <div class="modal-header">
                <h2 class="modal-title h4" id="winnerModalTitle">Oppdraget er avgjort</h2>
            </div>
            <div class="modal-body">
                <div class="result-scene" id="winnerScene"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary btn-lg" data-bs-dismiss="modal">Lukk</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/spin.css') }}?v={{ filemtime(public_path('css/spin.css')) }}">
@endpush

@push('scripts')
<script src="{{ asset('js/wheel.js') }}?v={{ filemtime(public_path('js/wheel.js')) }}"></script>
<script src="{{ asset('js/spin.js') }}?v={{ filemtime(public_path('js/spin.js')) }}"></script>
@endpush
