@extends('layouts.app')

@section('title', 'Administrer forslag')

@push('styles')
<style>
    .admin-feedback-message,
    .admin-feedback-note {
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }
</style>
@endpush

@section('content')
@php
    $badgeClasses = [
        'new' => 'bg-primary',
        'in_progress' => 'bg-warning text-dark',
        'completed' => 'bg-success',
        'archived' => 'bg-secondary',
    ];
    $filterLabels = [
        'new' => 'Nye',
        'in_progress' => 'Under behandling',
        'completed' => 'Ferdige',
        'archived' => 'Arkiverte',
    ];
@endphp

<div class="container page-container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Forslag og tilbakemeldinger</h1>
            <p class="text-muted mb-0">Nyeste innspill vises først.</p>
        </div>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary">Logg ut</button>
        </form>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning" role="alert">
            {{ session('warning') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>Endringen kunne ikke lagres.</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-4">
        <div class="d-flex flex-wrap gap-2">
            <a
                href="{{ route('admin.feedback.index') }}"
                class="btn {{ $activeStatus === null ? 'btn-primary' : 'btn-outline-secondary' }}">
                Alle <span class="badge bg-light text-dark">{{ $allStatusCount }}</span>
            </a>

            @foreach ($statuses as $value => $label)
                <a
                    href="{{ route('admin.feedback.index', ['status' => $value]) }}"
                    class="btn {{ $activeStatus === $value ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ $filterLabels[$value] ?? $label }} <span class="badge bg-light text-dark">{{ $statusCounts[$value] ?? 0 }}</span>
                </a>
            @endforeach
        </div>
    </div>

    @forelse ($feedbackSubmissions as $feedbackSubmission)
        @php
            $status = $feedbackSubmission->status ?: 'new';
            $statusLabel = $statuses[$status] ?? $status;
            $badgeClass = $badgeClasses[$status] ?? 'bg-secondary';
        @endphp

        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                    <div>
                        <div class="text-muted small mb-1">
                            {{ optional($feedbackSubmission->created_at)->format('d.m.Y H:i') }}
                            @if ($feedbackSubmission->updated_at && ! $feedbackSubmission->updated_at->equalTo($feedbackSubmission->created_at))
                                · Oppdatert {{ $feedbackSubmission->updated_at->format('d.m.Y H:i') }}
                            @endif
                        </div>
                        <h2 class="h5 mb-2">{{ $feedbackSubmission->title }}</h2>
                        <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                        <span class="badge bg-light text-dark border">{{ $feedbackTypes[$feedbackSubmission->type] ?? $feedbackSubmission->type }}</span>
                    </div>

                    <div class="text-lg-end small text-muted">
                        @if ($feedbackSubmission->is_anonymous)
                            Anonym innsending
                        @else
                            <div>{{ $feedbackSubmission->name ?: 'Navn ikke oppgitt' }}</div>
                            <div>{{ $feedbackSubmission->email ?: 'E-post ikke oppgitt' }}</div>
                        @endif
                    </div>
                </div>

                <div class="admin-feedback-message border rounded p-3 bg-light mb-4">{{ $feedbackSubmission->message }}</div>

                <form method="POST" action="{{ route('admin.feedback.update', $feedbackSubmission) }}">
                    @csrf
                    @method('PATCH')

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="status_{{ $feedbackSubmission->id }}" class="form-label">Status</label>
                            <select id="status_{{ $feedbackSubmission->id }}" name="status" class="form-select" required>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label for="admin_note_{{ $feedbackSubmission->id }}" class="form-label">Internt notat</label>
                            <textarea
                                id="admin_note_{{ $feedbackSubmission->id }}"
                                name="admin_note"
                                rows="4"
                                maxlength="5000"
                                class="form-control admin-feedback-note">{{ old('admin_note', $feedbackSubmission->admin_note) }}</textarea>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-success">Lagre</button>
                    </div>
                </form>
            </div>
        </div>
    @empty
        <div class="alert alert-info" role="alert">
            @if ($activeStatus)
                Det finnes ingen forslag eller tilbakemeldinger med statusen {{ strtolower($statuses[$activeStatus]) }}.
            @else
                Det finnes ingen forslag eller tilbakemeldinger ennå.
            @endif
        </div>
    @endforelse
</div>
@endsection
