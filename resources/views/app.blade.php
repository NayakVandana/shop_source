<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ShopSource') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    @inertiaHead
    @if(app()->environment('local'))
    <script type="module">
        // React Refresh preamble injection
        window.__vite_plugin_react_preamble_installed__ = true;
        window.$RefreshReg$ = () => {};
        window.$RefreshSig$ = () => (type) => type;
        
        // Ensure React Refresh runtime is available
        if (typeof window.$RefreshReg$ === 'undefined') {
            window.$RefreshReg$ = () => {};
        }
        if (typeof window.$RefreshSig$ === 'undefined') {
            window.$RefreshSig$ = () => (type) => type;
        }
    </script>
    @endif
</head>
<body>
    @inertia
</body>
</html>