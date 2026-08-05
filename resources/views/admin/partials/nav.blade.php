<nav class="admin-nav d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4" aria-label="Administrasjon">
    <div class="admin-nav-links">
        <a href="{{ route('admin.index') }}" class="{{ request()->routeIs('admin.index') ? 'active' : '' }}" @if(request()->routeIs('admin.index')) aria-current="page" @endif>Oversikt</a>
        <a href="{{ route('admin.professional-resources.index') }}" class="{{ request()->routeIs('admin.professional-resources.*', 'admin.resource-categories.*') ? 'active' : '' }}">Fagstoff</a>
        <a href="{{ route('admin.news.index') }}" class="{{ request()->routeIs('admin.news.*', 'admin.news-sources.*') ? 'active' : '' }}">Nyheter</a>
        <a href="{{ route('admin.feedback.index') }}" class="{{ request()->routeIs('admin.feedback.*') ? 'active' : '' }}">Henvendelser</a>
    </div>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-secondary">Logg ut</button>
    </form>
</nav>
