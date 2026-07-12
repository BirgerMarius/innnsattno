<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-3">
        <div class="col-md-8">
            <label for="title" class="form-label">Tittel</label>
            <input id="title" name="title" type="text" maxlength="255" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $resource->title) }}" required>
            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label for="category_id" class="form-label">Kategori</label>
            <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                <option value="">Velg kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) old('category_id', $resource->category_id) === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <label for="url" class="form-label">HTTPS-lenke</label>
            <input id="url" name="url" type="url" maxlength="2048" class="form-control @error('url') is-invalid @enderror" value="{{ old('url', $resource->url) }}" placeholder="https://..." required>
            @error('url')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <label for="comment" class="form-label">Kort kommentar eller anbefaling</label>
            <textarea id="comment" name="comment" rows="5" maxlength="5000" class="form-control @error('comment') is-invalid @enderror">{{ old('comment', $resource->comment) }}</textarea>
            <div class="form-text">Obligatorisk ved publisering.</div>
            @error('comment')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label for="publisher" class="form-label">Kilde eller utgiver</label>
            <input id="publisher" name="publisher" type="text" maxlength="255" class="form-control @error('publisher') is-invalid @enderror" value="{{ old('publisher', $resource->publisher) }}">
            @error('publisher')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label for="media_type" class="form-label">Medietype</label>
            <select id="media_type" name="media_type" class="form-select @error('media_type') is-invalid @enderror">
                <option value="">Velg medietype</option>
                @foreach ($mediaTypes as $value => $mediaType)
                    <option value="{{ $value }}" @selected(old('media_type', $resource->media_type) === $value)>
                        {{ $mediaType['icon'] }} {{ $mediaType['label'] }}
                    </option>
                @endforeach
            </select>
            <div class="form-text">Velg formatet ressursen er publisert som. Obligatorisk ved publisering.</div>
            @error('media_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label for="publication_year" class="form-label">Publiseringsår</label>
            <input id="publication_year" name="publication_year" type="number" min="1900" max="{{ date('Y') + 1 }}" class="form-control @error('publication_year') is-invalid @enderror" value="{{ old('publication_year', $resource->publication_year) }}">
            @error('publication_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <label for="tags" class="form-label">Emneord</label>
            <input id="tags" name="tags" type="text" maxlength="1000" class="form-control @error('tags') is-invalid @enderror" value="{{ old('tags', $resource->exists ? $resource->tags->pluck('name')->implode(', ') : '') }}" placeholder="tilbakeføring, arbeid, NAV">
            <div class="form-text">Skill flere emneord med komma, for eksempel: tilbakeføring, arbeid, NAV.</div>
            @error('tags')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $resource->status ?: \App\ProfessionalResource::STATUS_DRAFT) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label for="sort_order" class="form-label">Sorteringsrekkefølge</label>
            <input id="sort_order" name="sort_order" type="number" class="form-control @error('sort_order') is-invalid @enderror" value="{{ old('sort_order', $resource->sort_order ?? 0) }}" required>
            @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-4">
            <label for="last_checked_at" class="form-label">Sist kontrollert</label>
            <input id="last_checked_at" name="last_checked_at" type="date" class="form-control @error('last_checked_at') is-invalid @enderror" value="{{ old('last_checked_at', optional($resource->last_checked_at)->format('Y-m-d')) }}">
            @error('last_checked_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <div class="form-check">
                <input id="is_featured" name="is_featured" type="checkbox" value="1" class="form-check-input" @checked(old('is_featured', $resource->is_featured))>
                <label for="is_featured" class="form-check-label">Merk som særlig anbefalt</label>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <button type="submit" class="btn btn-success">{{ $submitLabel }}</button>
        <a href="{{ route('admin.professional-resources.index') }}" class="btn btn-outline-secondary">Avbryt</a>
    </div>
</form>
