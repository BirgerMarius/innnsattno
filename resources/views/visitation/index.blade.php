@extends('layouts.app')

@section('title', 'Visitasjon')

@section('content')
<div class="container page-container visitation-page" id="visitationApp">
    @include('partials.header')

    <main class="visitation-content">
        <div class="mb-4">
            <h1 class="h2 mb-2">Visitasjon</h1>
            <p class="text-muted mb-0">Trekk en tilfeldig celle fra valgt avdeling.</p>
        </div>

        <div class="card shadow-sm visitation-card">
            <div class="card-body p-4 p-md-5">
                <fieldset class="mb-4">
                    <legend class="h5 mb-3">Velg avdeling</legend>

                    <div class="visitation-departments" role="radiogroup" aria-label="Avdeling">
                        @foreach ($departments as $department => $cells)
                            <input
                                class="btn-check"
                                type="radio"
                                name="department"
                                id="department{{ $department }}"
                                value="{{ $department }}"
                                @checked($loop->first)>
                            <label class="btn btn-outline-primary btn-lg" for="department{{ $department }}">
                                Avdeling {{ $department }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div
                    class="visitation-result"
                    id="visitationResult"
                    role="status"
                    aria-live="polite"
                    aria-atomic="true">
                    <span class="visitation-result__label" id="resultLabel">Klar for trekning</span>
                    <strong class="visitation-result__cell" id="resultCell">–</strong>
                </div>

                <div class="d-grid mt-4">
                    <button class="btn btn-success btn-lg visitation-draw-button" id="drawCell" type="button">
                        Trekk celle
                    </button>
                </div>

                <p class="small text-muted text-center mt-3 mb-0">
                    Trekningen lagres ikke.
                </p>
            </div>
        </div>
    </main>

    @include('partials.footer')
</div>
@endsection

@push('styles')
<link href="{{ asset('css/visitation.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script id="visitationDepartments" type="application/json">@json($departments)</script>
<script src="{{ asset('js/visitation.js') }}"></script>
@endpush
