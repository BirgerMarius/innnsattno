@extends('layouts.app')

@section('title', 'Admininnlogging')

@section('content')
<div class="container page-container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <h1 class="h3 mb-4">Admininnlogging</h1>

            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    Ugyldig e-postadresse eller passord.
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">E-post</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="form-control @error('email') is-invalid @enderror"
                        autocomplete="username"
                        required
                        autofocus>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Passord</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        autocomplete="current-password"
                        required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-success w-100">Logg inn</button>
            </form>
        </div>
    </div>
</div>
@endsection
