<p>innsatt.no kunne ikke hente data fra en ekstern datakilde.</p>

<ul>
    <li><strong>Tjeneste:</strong> {{ $service }}</li>
    <li><strong>Operasjon:</strong> {{ $operation }}</li>
    <li><strong>Tidspunkt:</strong> {{ $occurredAt }}</li>
    <li><strong>Feiltype:</strong> {{ $failureKind }}</li>
    @if ($status !== null)
        <li><strong>HTTP-status:</strong> {{ $status }}</li>
    @endif
    <li><strong>Feilmelding:</strong> {{ $summary }}</li>
</ul>

<p>Samme tjeneste og operasjon varsles ikke igjen de neste {{ $cooldownSeconds }} sekundene.</p>
