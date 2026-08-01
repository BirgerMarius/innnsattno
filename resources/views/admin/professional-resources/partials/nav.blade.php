@include('admin.partials.nav')

<div class="d-flex flex-wrap gap-2 mb-4" aria-label="Fagstoffmeny">
    <a href="{{ route('admin.professional-resources.index') }}" class="btn btn-sm btn-outline-secondary">Ressurser</a>
    <a href="{{ route('admin.resource-categories.index') }}" class="btn btn-sm btn-outline-secondary">Kategorier</a>
</div>
