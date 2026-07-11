@extends('layouts.app')

@section('title', 'Forslag og tilbakemeldinger')

@section('content')
<div class="container page-container feedback-page">
    @include('partials.header')

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="mb-3">Forslag og tilbakemeldinger</h1>

            <div class="feedback-intro mb-4">
                <p class="lead mb-3">Har du en idé som kan gjøre Innsatt.no bedre?</p>
                <p>Jeg ønsker forslag fra brukere av siden. Har du en idé til en ny funksjon, en forbedring eller har du oppdaget en feil, hører jeg gjerne fra deg.</p>
                <p class="mb-0">Du kan sende inn anonymt. Oppgir du navn og e-postadresse, kan jeg kontakte deg dersom jeg trenger flere opplysninger om innspillet ditt.</p>
            </div>

            @if (session('success'))
                <div class="alert alert-success" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <strong>Skjemaet mangler noe.</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('feedback.store') }}" method="POST" class="feedback-form" data-disable-on-submit>
                @csrf

                <div class="mb-3">
                    <label for="type" class="form-label">Hva gjelder innspillet?</label>
                    <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="">Velg type</option>
                        @foreach ($feedbackTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="title" class="form-label">Kort tittel</label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        value="{{ old('title') }}"
                        maxlength="150"
                        class="form-control @error('title') is-invalid @enderror"
                        required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="message" class="form-label">Beskrivelse</label>
                    <textarea
                        id="message"
                        name="message"
                        rows="8"
                        maxlength="5000"
                        class="form-control @error('message') is-invalid @enderror"
                        required>{{ old('message') }}</textarea>
                    @error('message')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <fieldset class="mb-3">
                    <legend class="form-label mb-2">Innsendingstype</legend>

                    <div class="form-check">
                        <input
                            class="form-check-input @error('submission_type') is-invalid @enderror"
                            type="radio"
                            name="submission_type"
                            id="submission_anonymous"
                            value="anonymous"
                            @checked(old('submission_type', 'anonymous') === 'anonymous')>
                        <label class="form-check-label" for="submission_anonymous">Send inn anonymt</label>
                    </div>

                    <div class="form-check">
                        <input
                            class="form-check-input @error('submission_type') is-invalid @enderror"
                            type="radio"
                            name="submission_type"
                            id="submission_contact"
                            value="contact"
                            @checked(old('submission_type') === 'contact')>
                        <label class="form-check-label" for="submission_contact">Jeg kan kontaktes</label>
                    </div>

                    @error('submission_type')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </fieldset>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Navn</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            maxlength="150"
                            data-contact-required
                            class="form-control @error('name') is-invalid @enderror">
                        <div class="form-text">Påkrevd hvis du velger at du kan kontaktes.</div>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">E-post</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            maxlength="255"
                            data-contact-required
                            class="form-control @error('email') is-invalid @enderror">
                        <div class="form-text">Påkrevd og må være gyldig hvis du velger at du kan kontaktes.</div>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <p class="text-muted small">Kontaktinformasjonen brukes bare dersom vi trenger å stille oppfølgingsspørsmål om innspillet.</p>

                <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                    <button type="submit" class="btn btn-success btn-lg feedback-submit-button">
                        Send inn forslag
                    </button>
                    <a href="{{ url('/tv') }}" class="btn btn-outline-secondary btn-lg">
                        Tilbake til forsiden
                    </a>
                </div>
            </form>

            <p class="text-muted mt-4">Innsatt.no utvikles kontinuerlig, og mange av de beste idéene kommer fra brukerne av siden. Takk for at du bidrar!</p>
        </div>
    </div>

    @include('partials.footer')
</div>
@endsection

@push('scripts')
<script>
    function updateContactRequiredFields(form) {
        var contactSelected = form.querySelector('#submission_contact').checked;

        form.querySelectorAll('[data-contact-required]').forEach(function (field) {
            field.required = contactSelected;
        });
    }

    document.querySelectorAll('[data-disable-on-submit]').forEach(function (form) {
        updateContactRequiredFields(form);

        form.querySelectorAll('input[name="submission_type"]').forEach(function (field) {
            field.addEventListener('change', function () {
                updateContactRequiredFields(form);
            });
        });

        form.addEventListener('submit', function () {
            var button = form.querySelector('.feedback-submit-button');

            if (button) {
                button.disabled = true;
            }
        });
    });
</script>
@endpush
