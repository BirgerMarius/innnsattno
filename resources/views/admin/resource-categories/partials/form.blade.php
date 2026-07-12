<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-3">
        <div class="col-md-8">
            <label for="name" class="form-label">Navn</label>
            <input id="name" name="name" type="text" maxlength="255" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category->name) }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label for="sort_order" class="form-label">Sorteringsrekkefølge</label>
            <input id="sort_order" name="sort_order" type="number" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $category->sort_order ?? 0) }}" required>
            @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <label for="description" class="form-label">Beskrivelse</label>
            <textarea id="description" name="description" rows="4" maxlength="2000" class="form-control @error('description') is-invalid @enderror">{{ old('description', $category->description) }}</textarea>
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <div class="form-check">
                <input id="is_active" name="is_active" type="checkbox" value="1" class="form-check-input" @checked(old('is_active', $category->is_active))>
                <label for="is_active" class="form-check-label">Aktiv kategori</label>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <button type="submit" class="btn btn-success">{{ $submitLabel }}</button>
        <a href="{{ route('admin.resource-categories.index') }}" class="btn btn-outline-secondary">Avbryt</a>
    </div>
</form>
