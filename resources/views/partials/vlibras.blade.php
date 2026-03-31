{{--
    VLibras Widget (governo federal) — tradução automática para Libras.
    Documentação: https://vlibras.gov.br/doc/widget/
    Requisitos típicos: HTTPS em produção, rede permitindo vlibras.gov.br.
    Se o script não carregar (rede, bloqueio), o site continua funcionando.
--}}
<div vw class="enabled">
    <div vw-access-button class="active"></div>
    <div vw-plugin-wrapper>
        <div class="vw-plugin-top-wrapper"></div>
    </div>
</div>
<script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
<script>
    (function () {
        try {
            if (window.VLibras && typeof window.VLibras.Widget === 'function') {
                new window.VLibras.Widget('https://vlibras.gov.br/app');
            }
        } catch (e) { /* widget opcional */ }
    })();
</script>
