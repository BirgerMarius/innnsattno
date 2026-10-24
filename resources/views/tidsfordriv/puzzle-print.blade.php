<!doctype html>
<html lang="no">
<head>
<meta charset="utf-8">
<meta name="robots" content="noindex,follow">
<title>{{ $meta['title'] }}{{ $answer ? ' – fasit' : '' }}</title>
<style>
@page { size:A4; margin:14mm; }
body { color:#111; font-family:Arial,sans-serif; margin:0; }
.head { border-bottom:2px solid #111; } .brand { float:right; font-size:11px; }
.grid { border-collapse:collapse; margin:8mm auto; break-inside:avoid; }
.grid td { border:1px solid #111; font-size:18px; height:12mm; text-align:center; width:12mm; }
.black { background:#111; } .clues li { margin:5px 0; }
.no-print { margin:0 0 8mm; } @media print { .no-print { display:none; } }
</style>
</head>
<body>
<div class="no-print"><button type="button" onclick="window.print()">Skriv ut</button><a href="/tidsfordriv/{{ $type }}?oppgave={{ urlencode($puzzle['id']) }}">Tilbake</a></div>
<div class="head"><span class="brand">Innsatt.no</span><h1>{{ $meta['title'] }}{{ $answer ? ' – fasit' : '' }}</h1><p>Vanskelighetsgrad: {{ $puzzle['difficulty'] }}</p><p>@if($type==='binaer') Fyll inn 0 og 1. Like mange av hvert tall i alle rader og kolonner, aldri tre like etter hverandre. @elseif($type==='nonogram') Tallene viser grupper av fylte ruter. @elseif($type==='tallpyramide') Hver rute er summen av de to under. @else Fyll inn ordene fra ledetrådene. @endif</p></div>
@if($type==='minikryssord')
<table class="grid">@foreach($puzzle['grid'] as $r)<tr>@foreach(str_split($r) as $c)<td class="{{ $c==='#'?'black':'' }}">{{ $answer&&$c!=='#'?$c:'' }}</td>@endforeach</tr>@endforeach</table>
<ol class="clues">@foreach($puzzle['entries'] as $e)<li>{{ $e['clue'] }} ({{ strlen($e['word']) }})</li>@endforeach</ol>
@else
<table class="grid">@foreach($puzzle['solution'] ?? $puzzle['grid'] as $y => $r)<tr>@foreach($r as $x => $v)<td>{{ $answer ? $v : ($type==='binaer' ? ($puzzle['given'][$y][$x] ?? '') : ($type==='tallpyramide' ? ($puzzle['given'][$y][$x] ?? '') : '')) }}</td>@endforeach</tr>@endforeach</table>
@endif
@include('partials.print-redirect', ['fallbackUrl' => '/tidsfordriv/'.$type.'?oppgave='.urlencode($puzzle['id']), 'autoPrint' => true])
</body>
</html>
