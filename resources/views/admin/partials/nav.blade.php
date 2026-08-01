<nav class="admin-nav d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4" aria-label="Administrasjon">
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('admin.index') }}" class="btn {{ request()->routeIs('admin.index') ? 'btn-primary' : 'btn-outline-primary' }}">Oversikt</a>
        <a href="{{ route('admin.professional-resources.index') }}" class="btn {{ request()->routeIs('admin.professional-resources.*', 'admin.resource-categories.*') ? 'btn-primary' : 'btn-outline-primary' }}">Fagstoff</a>
        <a href="{{ route('admin.news.index') }}" class="btn {{ request()->routeIs('admin.news.*', 'admin.news-sources.*') ? 'btn-primary' : 'btn-outline-primary' }}">Nyheter</a>
        <a href="{{ route('admin.feedback.index') }}" class="btn {{ request()->routeIs('admin.feedback.*') ? 'btn-primary' : 'btn-outline-primary' }}">Forslag</a>
    </div>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit" class="btn btn-outline-secondary">Logg ut</button>
    </form>
</nav>
