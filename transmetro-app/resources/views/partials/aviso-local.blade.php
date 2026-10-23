@if (app()->isLocal())
    {{-- Solo con APP_ENV=local: distingue la copia de la computadora de la versión publicada --}}
    <div class="aviso-local" role="note">
        <strong>Versión local</strong>
        <span>Los datos de esta copia solo existen en esta computadora y no aparecen en la versión publicada.</span>
    </div>
@endif
