<nav class="admin-nav d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4" aria-label="Administrasjon">
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.index') }}" class="btn btn-outline-primary">Anbefalt fagstoff</a>
        <a href="{{ route('admin.professional-resources.index') }}" class="btn btn-outline-primary">Ressurser</a>
        <a href="{{ route('admin.resource-categories.index') }}" class="btn btn-outline-primary">Kategorier</a>
        <a href="{{ route('admin.feedback.index') }}" class="btn btn-outline-secondary">Forslag</a>
    </div>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit" class="btn btn-outline-secondary">Logg ut</button>
    </form>
</nav>
