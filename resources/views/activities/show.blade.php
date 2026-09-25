@extends('layouts.app')

@section('title', $activityData['title'].' | Spillforslag')

@section('content')
<div class="container page-container activity-page">
    @include('partials.header')
    <main class="activity-shell activity-screen">
        <nav class="activity-back" aria-label="Brødsmulesti"><a href="{{ route('activities.index') }}">← Spillforslag</a></nav>
        <div class="activity-screen-actions"><a class="btn btn-primary" href="{{ route('activities.print', $activity) }}">Skriv ut spillarket</a></div>
        @include('activities.partials.rules')
    </main>
    @include('partials.footer')
</div>
@endsection
