@pushOnce('styles')
    <link rel="stylesheet" href="{{ asset('modul-sop-pdf/assets/quill/quill.snow.css') }}">
    <link rel="stylesheet" href="{{ asset('modul-sop-pdf/blade-sop.css') }}">
@endPushOnce

@pushOnce('scripts')
    <script defer src="{{ asset('modul-sop-pdf/assets/quill/quill.js') }}"></script>
    <script defer src="{{ asset('modul-sop-pdf/assets/logo-data.js') }}"></script>
    <script defer src="{{ asset('modul-sop-pdf/template.js') }}"></script>
    <script defer src="{{ asset('modul-sop-pdf/procedure.js') }}"></script>
    <script defer src="{{ asset('modul-sop-pdf/layout.js') }}"></script>
    <script defer src="{{ asset('modul-sop-pdf/export.js') }}"></script>
    <script defer src="{{ asset('modul-sop-pdf/blade-sop.js') }}"></script>
@endPushOnce
