<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>check-db — SQLite data quality audit</title>
    <meta name="description" content="Upload a SQLite database and get an audit of its foreign keys, declared types, primary keys and indexes.">

    {{-- Applies the saved theme before first paint, so a dark-mode visitor never sees a white flash. --}}
    <script>
        try {
            var saved = localStorage.getItem('check-db.theme');
            if (saved === 'light' || saved === 'dark') {
                document.documentElement.setAttribute('data-theme', saved);
            }
            var lang = localStorage.getItem('check-db.locale');
            if (lang) document.documentElement.lang = lang;
        } catch (e) {}
    </script>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body>
<div id="app"></div>
</body>
</html>
