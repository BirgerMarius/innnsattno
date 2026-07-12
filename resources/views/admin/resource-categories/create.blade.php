@extends('layouts.app')

@section('title', 'Ny fagkategori')

@section('content')
<div class="container page-container py-4">
    @include('admin.professional-resources.partials.nav')
    <h1 class="h3 mb-4">Ny kategori</h1>
    @include('admin.professional-resources.partials.messages')
    @include('admin.resource-categories.partials.form', [
        'action' => route('admin.resource-categories.store'),
        'method' => 'POST',
        'submitLabel' => 'Lagre kategori',
    ])
</div>
@endsection
