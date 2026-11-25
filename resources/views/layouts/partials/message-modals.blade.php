<link rel="stylesheet" href="{{ asset('assets/css/message-modals.css') }}?v=20261124">
<script src="{{ asset('assets/js/plugins/sweetalert.min.js') }}"></script>
<script src="{{ asset('assets/js/sweet_alerts.js') }}?v=20261124"></script>
@php
    $modalMessages = \App\Support\FlashMessages::forSession(app('session.store'), $errors ?? null);
@endphp
@if ($modalMessages !== [])
    <script>
        window.AppMessages.showMany({{ \Illuminate\Support\Js::from($modalMessages) }});
    </script>
@endif
