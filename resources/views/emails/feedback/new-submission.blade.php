<!doctype html>
<html lang="no">
<head>
    <meta charset="utf-8">
    <title>Nytt forslag mottatt på Innsatt.no</title>
</head>
<body>
    <h1>Nytt forslag mottatt på Innsatt.no</h1>

    <p>Et nytt forslag eller en ny tilbakemelding er mottatt.</p>

    <dl>
        <dt>Dato og klokkeslett</dt>
        <dd>{{ optional($feedbackSubmission->created_at)->format('d.m.Y H:i') }}</dd>

        <dt>Type innspill</dt>
        <dd>{{ $typeLabel }}</dd>

        <dt>Tittel</dt>
        <dd>{{ $feedbackSubmission->title }}</dd>

        <dt>Beskrivelse</dt>
        <dd>{{ $feedbackSubmission->message }}</dd>

        <dt>Anonym innsending</dt>
        <dd>{{ $feedbackSubmission->is_anonymous ? 'Ja' : 'Nei' }}</dd>

        @if (! $feedbackSubmission->is_anonymous)
            <dt>Navn</dt>
            <dd>{{ $feedbackSubmission->name ?: 'Ikke oppgitt' }}</dd>

            <dt>E-post</dt>
            <dd>{{ $feedbackSubmission->email ?: 'Ikke oppgitt' }}</dd>
        @endif

        <dt>Status</dt>
        <dd>{{ $statusLabel }}</dd>
    </dl>
</body>
</html>
