{{-- Estilo compartilhado: botões "Remover" em criar/editar evento --}}
<style>
.btn-form-remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    line-height: 1.25;
    color: rgb(220 38 38);
    background-color: rgb(254 242 242);
    border: 1px solid rgb(254 226 226);
    transition: background-color 0.15s ease, color 0.15s ease;
    flex-shrink: 0;
    cursor: pointer;
}
.btn-form-remove:hover {
    background-color: rgb(254 226 226);
    color: rgb(185 28 28);
}
.btn-form-remove ion-icon {
    font-size: 1rem;
    flex-shrink: 0;
}
.btn-form-remove--tall {
    min-height: 2.75rem;
    align-self: flex-start;
}
</style>
