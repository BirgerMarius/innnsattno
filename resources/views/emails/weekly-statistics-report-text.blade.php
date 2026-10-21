{{ $report['subject'] }}

Periode: {{ $report['week_start']->format('d.m.Y H:i') }}–{{ $report['week_end_exclusive']->format('d.m.Y H:i') }} (Europe/Oslo, eksklusiv sluttgrense)
Forsidevisninger: {{ $report['metrics']['front_pageviews'] }}
Filtrerte sidevisninger: {{ $report['metrics']['pageviews'] }}
Anslåtte menneskelige økter: {{ $report['metrics']['sessions'] }}
Unike besøksnettverk: {{ $report['metrics']['networks'] }}
Åpninger av utskriftssider: {{ $report['metrics']['print_opens'] }}
Kjent automatisert/teknisk trafikk: {{ $report['quality']['known_automated_technical_requests'] }}
Uklassifisert trafikk: {{ $report['quality']['other'] }}

Trafikkutvikling per dag:
@foreach($report['days'] as $day){{ $day['date']->format('d.m.Y') }}: {{ $day['pageviews'] === null ? 'Ukjent' : $day['pageviews'] }} sidevisninger, {{ $day['sessions'] === null ? 'Ukjent' : $day['sessions'] }} økter
@endforeach

Sider og funksjoner:
@foreach($report['pages'] as $page){{ $page['name'] }} ({{ $page['path'] }}): {{ $page['pageviews'] }} visninger, {{ $page['unique_visitors'] }} nettverk
@endforeach

Utskriftssider: Ringerike {{ $report['prints_by_prison']['ringerike'] }}, Ilseng {{ $report['prints_by_prison']['ilseng'] }}, øvrige {{ $report['prints_by_prison']['other'] }}.
Loggdekning: {{ $report['coverage']['covered_days'] }}/{{ $report['coverage']['expected_days'] }}. Klassifisererversjoner: {{ implode(', ', $report['classifier_versions']) }}. Oppdatert {{ $report['generated_at']->format('d.m.Y H:i') }}.
Nettverk og økter er estimater; utskriftsåpning bekrefter ikke fysisk utskrift.
