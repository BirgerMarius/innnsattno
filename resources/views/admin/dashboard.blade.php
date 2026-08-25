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
                @if ($statistics['human_traffic'])
                    @php($selectedPeriod = $trafficDate ? (($statistics['daily'] ?? [])[$trafficDate] ?? null) : $statistics['periods'][$trafficPeriod])
                    <nav class="admin-period-nav mb-3" aria-label="Periode for trafikkstatistikk">
                        @foreach (['1' => 'I dag', '7' => 'Siste 7 dager', '30' => 'Siste 30 dager'] as $days => $label)
                            <a href="{{ route('admin.index', ['traffic_period' => $days]) }}#statistics-title" class="btn btn-sm {{ $trafficPeriod === (string) $days ? 'btn-primary' : 'btn-outline-secondary' }}" @if($trafficPeriod === (string) $days) aria-current="true" @endif>{{ $label }}</a>
                        @endforeach
                    </nav>
                    <form method="get" action="{{ route('admin.index') }}" class="d-flex flex-wrap align-items-end gap-2 mb-3">
                        <div><label for="traffic_date" class="form-label small mb-1">Velg spesifikk dato</label><input type="date" id="traffic_date" name="traffic_date" value="{{ $trafficDate }}" class="form-control form-control-sm"></div>
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Velg dato</button>
                        @if ($trafficDate)<a href="{{ route('admin.index', ['traffic_period' => '1']) }}#statistics-title" class="btn btn-sm btn-link">Vis i dag</a>@endif
                    </form>
                    @if ($trafficDateInvalid)
                        <div class="alert alert-warning" role="alert">Ugyldig dato. Velg en gyldig kalenderdato.</div>
                    @endif
                    @if ($trafficDate && $selectedPeriod === null)
                        <div class="alert alert-light border mb-0" role="status"><strong>Ingen data for {{ \Illuminate\Support\Carbon::createFromFormat('!Y-m-d', $trafficDate)->format('d.m.Y') }}.</strong><br><span class="text-muted">Datoen er gyldig, men finnes ikke i det lagrede statistikksammendraget.</span></div>
                    @else
                    @php($frontPage = collect($selectedPeriod['features'] ?? [])->firstWhere('name', 'Forside'))
                    <div class="admin-stat-grid">
                        @if ($statistics['current_methodology'])<div><span>Forsidevisninger</span><strong>{{ number_format($frontPage['pageviews'] ?? 0, 0, ',', ' ') }}</strong></div>@endif
                        <div><span>Sidevisninger etter filtrering</span><strong>{{ number_format($selectedPeriod['suspected_human_pageviews'], 0, ',', ' ') }}</strong></div>
                        <div><span>Anslåtte menneskelige besøksøkter</span><strong>{{ number_format($selectedPeriod['sessions'], 0, ',', ' ') }}</strong></div>
                        <div><span>Unike besøksnettverk</span><strong>{{ number_format($selectedPeriod['suspected_visitors'], 0, ',', ' ') }}</strong></div>
                        <div><span>Utskriftsvisninger</span><strong>{{ number_format($selectedPeriod['print_pageviews'], 0, ',', ' ') }}</strong></div>
                        <div class="admin-stat-wide"><span>Kjent automatisert/teknisk trafikk</span><strong>{{ number_format($selectedPeriod['traffic_quality']['known_automated_technical_requests'], 0, ',', ' ') }}</strong><small>Boter {{ number_format($selectedPeriod['traffic_quality']['known_bot'], 0, ',', ' ') }} · overvåking {{ number_format($selectedPeriod['traffic_quality']['monitoring'], 0, ',', ' ') }} · skanning {{ number_format($selectedPeriod['traffic_quality']['scanner'], 0, ',', ' ') }} · eksplisitt ekskludert {{ number_format($selectedPeriod['traffic_quality']['excluded'], 0, ',', ' ') }}</small></div>
                        <div class="admin-stat-wide"><span>Uklassifisert trafikk</span><strong>{{ number_format($selectedPeriod['traffic_quality']['other'], 0, ',', ' ') }}</strong><small>{{ number_format($selectedPeriod['traffic_quality']['single_page_candidates'], 0, ',', ' ') }} enkeltstående sidekandidater kan være legitime besøk.</small></div>
                    </div>
                    @if ($statistics['current_methodology'])
                        <div class="admin-ranking mt-4 pt-3 border-top">
                            <h3 class="h5 mb-2">Funksjoner og utskrifter</h3>
                            <div class="table-responsive"><table class="table admin-ranking-table align-middle mb-2"><thead><tr><th>Funksjon</th><th class="text-end">Sidevisninger</th><th class="text-end">Unike nettverk</th><th class="text-end">Utskriftsvisninger</th></tr></thead><tbody>
                            @foreach ($selectedPeriod['features'] as $feature)<tr><td>{{ $feature['name'] }}</td><td class="text-end">{{ number_format($feature['pageviews'], 0, ',', ' ') }}</td><td class="text-end">{{ number_format($feature['unique_networks'], 0, ',', ' ') }}</td><td class="text-end">{{ number_format($feature['print_pageviews'], 0, ',', ' ') }}</td></tr>@endforeach
                            </tbody></table></div>
                            <p class="small text-muted mb-0">En utskriftsvisning betyr at utskriftssiden ble åpnet; den garanterer ikke at en fysisk utskrift ble gjennomført.</p>
                        </div>
                        @if ($selectedPeriod['comparison'])<p class="small text-muted mt-3 mb-0">Sammenlignet med {{ $selectedPeriod['comparison']['from']->format('d.m.Y') }}–{{ $selectedPeriod['comparison']['to']->format('d.m.Y') }}: {{ number_format($selectedPeriod['comparison']['pageviews'], 0, ',', ' ') }} filtrerte sidevisninger og {{ number_format($selectedPeriod['comparison']['sessions'], 0, ',', ' ') }} økter.</p>@endif
                        <div class="admin-ranking mt-3"><h3 class="h5 mb-2">Trafikkutvikling per dag</h3><div class="table-responsive"><table class="table admin-ranking-table mb-0"><thead><tr><th>Dato</th><th class="text-end">Sidevisninger</th><th class="text-end">Økter</th></tr></thead><tbody>@foreach (array_slice($statistics['daily'] ?? [], -7, null, true) as $date => $day)<tr><td>{{ \Illuminate\Support\Carbon::createFromFormat('!Y-m-d', $date)->format('d.m.Y') }}</td><td class="text-end">{{ number_format($day['suspected_human_pageviews'], 0, ',', ' ') }}</td><td class="text-end">{{ number_format($day['sessions'], 0, ',', ' ') }}</td></tr>@endforeach</tbody></table></div></div>
                    @endif
                    <p class="small text-muted mt-3 mb-0"><strong>Datakvalitet{{ $statistics['current_methodology'] ? '' : ': under innkjøring' }}</strong> · Besøksøkter og unike nettverk er estimater fra anonymisert logganalyse; flere brukere kan dele offentlig IP. · @if($trafficDate)Dato {{ $selectedPeriod['from']->format('d.m.Y') }}@else Periode {{ $selectedPeriod['from']->format('d.m.Y') }}–{{ $selectedPeriod['to']->format('d.m.Y') }}@endif · @if($statistics['current_methodology']){{ $selectedPeriod['coverage']['covered_days'] }} av {{ $selectedPeriod['coverage']['expected_days'] }} dager med faktisk logggrunnlag{{ $selectedPeriod['coverage']['complete'] ? ' (fullstendig)' : ' (delvis)' }} · @endif{{ number_format($selectedPeriod['traffic_quality']['raw_requests'], 0, ',', ' ') }} rå forespørsler · Sist oppdatert {{ $statistics['generated_at']->copy()->timezone('Europe/Oslo')->format('d.m.Y H:i') }}</p>
                    @endif
                @else
                    <div class="admin-stat-grid">
                        <div><span>Sidevisninger {{ $statistics['latest_day']['date']->format('d.m.Y') }}</span><strong>{{ number_format($statistics['latest_day']['pageviews'], 0, ',', ' ') }}</strong></div>
                        <div><span>Unike besøkende {{ $statistics['latest_day']['date']->format('d.m.Y') }}</span><strong>{{ number_format($statistics['latest_day']['unique_visitors'], 0, ',', ' ') }}</strong></div>
                        <div><span>Sidevisninger siste 7 dager</span><strong>{{ number_format($statistics['last_7_days']['pageviews'], 0, ',', ' ') }}</strong></div>
                        <div><span>Forespørsler siste 7 dager</span><strong>{{ number_format($statistics['last_7_days']['requests'], 0, ',', ' ') }}</strong></div>
                        <div class="admin-stat-wide"><span>Mest besøkte side siste 7 dager</span><strong class="admin-stat-page">{{ $statistics['last_7_days']['top_page']['path'] }}</strong><small>{{ number_format($statistics['last_7_days']['top_page']['pageviews'], 0, ',', ' ') }} sidevisninger</small></div>
                    </div>
                    <p class="small text-muted mt-3 mb-0">Periode {{ $statistics['last_7_days']['from']->format('d.m.Y') }}–{{ $statistics['last_7_days']['to']->format('d.m.Y') }} · Sist oppdatert {{ $statistics['generated_at']->format('d.m.Y H:i') }}</p>
                @endif

                <div class="admin-ranking mt-4 pt-4 border-top">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-3">
                        <div><h3 class="h5 mb-1">Mest brukte faktiske sider og funksjoner</h3><p class="text-muted small mb-0">Registrerte ordinære HTML-sider, sortert etter antatt menneskelig bruk.</p></div>
                        @if (! $trafficDate)<nav class="admin-period-nav" aria-label="Periode for mest brukte sider">
                            @foreach (['1' => 'I dag', '7' => 'Siste 7 dager', '30' => 'Siste 30 dager'] as $days => $label)
                                <a href="{{ route('admin.index', ['traffic_period' => $days]) }}#mest-brukte-sider" class="btn btn-sm {{ $trafficPeriod === (string) $days ? 'btn-primary' : 'btn-outline-secondary' }}" @if($trafficPeriod === (string) $days) aria-current="true" @endif>{{ $label }}</a>
                            @endforeach
                        </nav>@endif
                    </div>
                    <div id="mest-brukte-sider">
                    @if ($trafficDate && $selectedPeriod !== null)
                        @php($ranking = ['from' => $selectedPeriod['from'], 'to' => $selectedPeriod['to'], 'pages' => $selectedPeriod['pages']])
                    @elseif (! $trafficDate && $statistics['top_pages'] !== null)
                        @php($ranking = $statistics['top_pages'][$trafficPeriod])
                    @endif
                    @if (($trafficDate && $selectedPeriod !== null) || (! $trafficDate && $statistics['top_pages'] !== null))
                        @if (count($ranking['pages']))
                            <div class="table-responsive admin-ranking-scroll"><table class="table admin-ranking-table align-middle mb-2">
                                <thead><tr><th scope="col">Side</th><th scope="col">URL</th><th scope="col" class="text-end">Sidevisninger</th><th scope="col" class="text-end">Unike besøksnettverk</th></tr></thead>
                                <tbody>@foreach ($ranking['pages'] as $page)<tr>
                                    <td><a href="{{ url($page['path']) }}" target="_blank" rel="noopener noreferrer">{{ $page['name'] }}</a></td>
                                    <td><code>{{ $page['path'] }}</code></td>
                                    <td class="text-end">{{ number_format($page['pageviews'], 0, ',', ' ') }}</td>
                                    <td class="text-end">{{ number_format($page['unique_visitors'], 0, ',', ' ') }}</td>
                                </tr>@endforeach</tbody>
                            </table></div>
                            <p class="small text-muted mb-0">Periode {{ $ranking['from']->format('d.m.Y') }}–{{ $ranking['to']->format('d.m.Y') }}. Unike besøksnettverk måles som unike IP-er; flere brukere kan dele samme offentlige IP.</p>
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
