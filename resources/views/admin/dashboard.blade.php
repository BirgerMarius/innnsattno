@extends('layouts.app')

@section('title', 'Administrasjon')

@section('content')
<div class="container page-container admin-area py-4">
    @include('admin.partials.nav')

    <header class="admin-hero mb-4">
        <p class="admin-eyebrow mb-2">Innsatt.no</p>
        <h1 class="h2 mb-2">Administrasjon</h1>
        <p class="mb-0">Oversikt, oppfølging og driftsverktøy samlet på ett sted.</p>
    </header>

    <section class="mb-4" aria-labelledby="oppfolging-title">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-3">
            <div>
                <h2 class="h4 mb-1" id="oppfolging-title">Trenger oppfølging</h2>
                <p class="text-muted mb-0">Gå direkte til sakene som venter.</p>
            </div>
            <span class="admin-total-badge">{{ $draftCount + $pendingNewsCount + $newFeedbackCount + $newsSourceErrorCount }} åpne punkter</span>
        </div>
        <div class="admin-follow-up-grid">
            <a class="admin-follow-up-item" href="{{ route('admin.professional-resources.index', ['status' => 'draft']) }}">
                <span><strong>Fagstoff til ferdigstilling</strong><small>Kladder som ikke er publisert</small></span>
                <span class="admin-count">{{ $draftCount }}</span>
            </a>
            <a class="admin-follow-up-item" href="{{ route('admin.news.index', ['status' => 'pending']) }}">
                <span><strong>Nye artikler</strong><small>Venter på redaksjonell vurdering</small></span>
                <span class="admin-count">{{ $pendingNewsCount }}</span>
            </a>
            <a class="admin-follow-up-item" href="{{ route('admin.feedback.index', ['status' => 'new']) }}">
                <span><strong>Nye henvendelser</strong><small>Forslag og tilbakemeldinger</small></span>
                <span class="admin-count">{{ $newFeedbackCount }}</span>
            </a>
            <a class="admin-follow-up-item {{ $newsSourceErrorCount ? 'admin-follow-up-item--warning' : '' }}" href="{{ route('admin.news-sources.index') }}">
                <span><strong>Feil ved nyhetshenting</strong><small>Aktive kilder med registrert feil</small></span>
                <span class="admin-count">{{ $newsSourceErrorCount }}</span>
            </a>
        </div>
    </section>

    <section class="mb-4" aria-labelledby="innhold-title">
        <h2 class="h4 mb-3" id="innhold-title">Innhold og handlinger</h2>
        <div class="admin-action-grid">
            <article class="card admin-dashboard-section h-100"><div class="card-body d-flex flex-column">
                <p class="admin-card-label">Fagstoff</p><div class="admin-card-metrics"><strong>{{ $publishedCount }}</strong> publisert <span>·</span> <strong>{{ $draftCount }}</strong> kladd <span>·</span> <strong>{{ $activeCategoryCount }}</strong> kategorier</div>
                <div class="d-flex flex-wrap gap-2 mt-auto pt-3"><a href="{{ route('admin.professional-resources.index') }}" class="btn btn-primary">Se fagstoff</a><a href="{{ route('admin.professional-resources.create') }}" class="btn btn-outline-primary">Ny ressurs</a></div>
            </div></article>
            <article class="card admin-dashboard-section h-100"><div class="card-body d-flex flex-column">
                <p class="admin-card-label">Nyheter</p><div class="admin-card-metrics"><strong>{{ $pendingNewsCount }}</strong> til vurdering <span>·</span> <strong>{{ $activeNewsSourceCount }}</strong> aktive kilder</div>
                <div class="d-flex flex-wrap gap-2 mt-auto pt-3"><a href="{{ route('admin.news.index') }}" class="btn btn-primary">Se artikler</a><a href="{{ route('admin.news-sources.index') }}" class="btn btn-outline-primary">Administrer kilder</a></div>
            </div></article>
            <article class="card admin-dashboard-section h-100"><div class="card-body d-flex flex-column">
                <p class="admin-card-label">Forslag og henvendelser</p><div class="admin-card-metrics"><strong>{{ $newFeedbackCount }}</strong> nye innspill venter på oppfølging</div>
                <div class="mt-auto pt-3"><a href="{{ route('admin.feedback.index') }}" class="btn btn-primary">Se alle henvendelser</a></div>
            </div></article>
        </div>
    </section>

    <section class="card admin-dashboard-section mb-4" aria-labelledby="statistics-title">
        <div class="card-body p-lg-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-2 mb-3">
                <div><p class="admin-card-label mb-1">Ekstern trafikk</p><h2 class="h4 mb-1" id="statistics-title">Statistikk</h2><p class="text-muted mb-0">Nøkkeltall fra serverens trafikkhistorikk.</p></div>
                @if ($statistics && $statistics['test_data'])<span class="badge bg-warning text-dark">TESTDATA – ikke produksjonstall</span>@endif
            </div>
            @if ($statistics)
                <div class="admin-stat-grid">
                    <div><span>Sidevisninger {{ $statistics['latest_day']['date']->format('d.m.Y') }}</span><strong>{{ number_format($statistics['latest_day']['pageviews'], 0, ',', ' ') }}</strong></div>
                    <div><span>Unike besøkende {{ $statistics['latest_day']['date']->format('d.m.Y') }}</span><strong>{{ number_format($statistics['latest_day']['unique_visitors'], 0, ',', ' ') }}</strong></div>
                    <div><span>Sidevisninger siste 7 dager</span><strong>{{ number_format($statistics['last_7_days']['pageviews'], 0, ',', ' ') }}</strong></div>
                    <div><span>Forespørsler siste 7 dager</span><strong>{{ number_format($statistics['last_7_days']['requests'], 0, ',', ' ') }}</strong></div>
                    <div class="admin-stat-wide"><span>Mest besøkte side siste 7 dager</span><strong class="admin-stat-page">{{ $statistics['last_7_days']['top_page']['path'] }}</strong><small>{{ number_format($statistics['last_7_days']['top_page']['pageviews'], 0, ',', ' ') }} sidevisninger</small></div>
                </div>
                <p class="small text-muted mt-3 mb-0">Periode {{ $statistics['last_7_days']['from']->format('d.m.Y') }}–{{ $statistics['last_7_days']['to']->format('d.m.Y') }} · Sist oppdatert {{ $statistics['generated_at']->format('d.m.Y H:i') }}</p>

                <div class="admin-ranking mt-4 pt-4 border-top">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-3">
                        <div><h3 class="h5 mb-1">Alle offentlige sider</h3><p class="text-muted small mb-0">Registrerte ordinære HTML-sider, sortert etter bruk.</p></div>
                        <nav class="admin-period-nav" aria-label="Periode for mest brukte sider">
                            @foreach (['1' => 'I dag', '7' => 'Siste 7 dager', '30' => 'Siste 30 dager'] as $days => $label)
                                <a href="{{ route('admin.index', ['traffic_period' => $days]) }}#mest-brukte-sider" class="btn btn-sm {{ $trafficPeriod === (string) $days ? 'btn-primary' : 'btn-outline-secondary' }}" @if($trafficPeriod === (string) $days) aria-current="true" @endif>{{ $label }}</a>
                            @endforeach
                        </nav>
                    </div>
                    <div id="mest-brukte-sider">
                    @if ($statistics['top_pages'] !== null)
                        @php($ranking = $statistics['top_pages'][$trafficPeriod])
                        @if (count($ranking['pages']))
                            <div class="table-responsive admin-ranking-scroll"><table class="table admin-ranking-table align-middle mb-2">
                                <thead><tr><th scope="col">Side</th><th scope="col">URL</th><th scope="col" class="text-end">Sidevisninger</th><th scope="col" class="text-end">Unike IP-er</th></tr></thead>
                                <tbody>@foreach ($ranking['pages'] as $page)<tr>
                                    <td><a href="{{ url($page['path']) }}" target="_blank" rel="noopener noreferrer">{{ $page['name'] }}</a></td>
                                    <td><code>{{ $page['path'] }}</code></td>
                                    <td class="text-end">{{ number_format($page['pageviews'], 0, ',', ' ') }}</td>
                                    <td class="text-end">{{ number_format($page['unique_visitors'], 0, ',', ' ') }}</td>
                                </tr>@endforeach</tbody>
                            </table></div>
                            <p class="small text-muted mb-0">Periode {{ $ranking['from']->format('d.m.Y') }}–{{ $ranking['to']->format('d.m.Y') }}. Unike IP-er er et anslag på besøkende; flere brukere kan dele samme offentlige IP.</p>
                        @else
                            <p class="text-muted mb-0">Ingen offentlige sidevisninger er registrert i denne perioden.</p>
                        @endif
                    @else
                        <div class="alert alert-light border mb-0" role="status">Alle sider er ikke tilgjengelige ennå. Sidefordelt besøksstatistikk må først samles inn.</div>
                    @endif
                    </div>
                </div>
            @else
                <div class="alert alert-light border mb-0" role="status"><strong>Statistikk er ikke tilgjengelig akkurat nå.</strong><br><span class="text-muted">Oppsummeringsfilen mangler eller kunne ikke leses. Prøv igjen etter neste statistikkoppdatering.</span></div>
            @endif
        </div>
    </section>

    <section aria-labelledby="tools-title">
        <h2 class="h4 mb-3" id="tools-title">Driftsverktøy</h2>
        <div class="admin-tools-grid">
            <a href="{{ route('tv') }}" target="_blank" rel="noopener noreferrer"><strong>Offentlig forside</strong><span>Åpne innsatt.no slik besøkende ser siden</span></a>
            <a href="{{ route('change-history.index') }}" target="_blank" rel="noopener noreferrer"><strong>Endringshistorikk</strong><span>Se publiserte endringer på nettstedet</span></a>
            <a href="{{ config('admin.goaccess_report_url') }}" target="_blank" rel="noopener noreferrer"><strong>Full GoAccess-rapport</strong><span>Detaljert trafikk- og serverloggrapport</span></a>
        </div>
    </section>
</div>
@endsection
