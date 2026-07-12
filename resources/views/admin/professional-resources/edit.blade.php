@extends('layouts.app')

@section('title', 'Rediger ressurs')

@section('content')
<div class="container page-container py-4">
    @include('admin.professional-resources.partials.nav')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <h1 class="h3 mb-0">Rediger ressurs</h1>
        <a href="{{ route('admin.professional-resources.preview', $resource) }}" class="btn btn-outline-secondary">Forhåndsvis</a>
    </div>
    @include('admin.professional-resources.partials.messages')
    @include('admin.professional-resources.partials.form', [
        'action' => route('admin.professional-resources.update', $resource),
        'method' => 'PATCH',
        'submitLabel' => 'Lagre',
    ])
</div>
@endsection
