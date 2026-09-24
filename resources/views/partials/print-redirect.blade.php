@php($printReturnTarget = $printReturnUrl ?? $fallbackUrl)
<script>
    (() => {
        const returnUrl = @json($printReturnTarget, JSON_UNESCAPED_SLASHES);
        let printStarted = false;
        let returned = false;

        window.addEventListener('beforeprint', () => { printStarted = true; });
        window.addEventListener('afterprint', () => {
            if (!printStarted || returned) return;
            returned = true;
            window.location.replace(returnUrl);
        }, { once: true });

        @if($autoPrint ?? true)
        window.addEventListener('load', () => { window.print(); }, { once: true });
        @endif
    })();
</script>
