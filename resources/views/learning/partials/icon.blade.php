@if($icon === 'wallet')
<svg viewBox="0 0 64 64" role="img"><path d="M9 18h40a6 6 0 0 1 6 6v25a6 6 0 0 1-6 6H9a6 6 0 0 1-6-6V15a6 6 0 0 1 6-6h35v9"/><path d="M55 34H43a5 5 0 0 0 0 10h12"/><circle cx="43" cy="39" r="1.5"/></svg>
@elseif($icon === 'heart')
<svg viewBox="0 0 64 64" role="img"><path d="M32 54S8 39 8 22c0-7 5-12 12-12 6 0 10 4 12 8 2-4 6-8 12-8 7 0 12 5 12 12 0 17-24 32-24 32Z"/><path d="M18 31h8l3-7 6 15 4-8h8"/></svg>
@else
<svg viewBox="0 0 64 64" role="img"><path d="m32 6 4.8 16.2L52 18l-9.8 13.8L57 38l-17.2 1.7L40 56l-8-15-8 15 .2-16.3L7 38l14.8-6.2L12 18l15.2 4.2L32 6Z"/></svg>
@endif
