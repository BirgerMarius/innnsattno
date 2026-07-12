@extends('layouts.app')

@section('title', 'Premier League 2026/27 | INNSATT.NO')

@section('content')
<div class="container my-5">
    @include('partials.header')

    <h1>Premier League 2026/27</h1>

    <p class="lead mb-2">Under utvikling</p>

    <p>
        Denne siden vil etter hvert vise neste serierunde, siste resultater og tabellen for Premier League.
    </p>

    <ul>
        <li>Neste serierunde</li>
        <li>Siste resultater</li>
        <li>Tabell</li>
        <li>Lagoversikt senere</li>
    </ul>

    @include('partials.footer')
</div>
@endsection
