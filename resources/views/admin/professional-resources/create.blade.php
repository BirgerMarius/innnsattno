@extends('layouts.app')

@section('title', 'Ny ressurs')

@section('content')
<div class="container page-container py-4">
    @include('admin.professional-resources.partials.nav')
    <h1 class="h3 mb-4">Ny ressurs</h1>
    @include('admin.professional-resources.partials.messages')
    @include('admin.professional-resources.partials.form', [
        'action' => route('admin.professional-resources.store'),
        'method' => 'POST',
        'submitLabel' => 'Lagre',
    ])
</div>
@endsection
