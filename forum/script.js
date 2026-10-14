// Pede confirmação antes de enviar formulários de exclusão
document.addEventListener('DOMContentLoaded', function () {
    var forms = document.querySelectorAll('form[data-confirmar]');
    forms.forEach(function (form) {
        form.addEventListener('submit', function (evento) {
            var botao = evento.submitter;
            // Só pergunta em botões perigosos (apagar/banir)
            if (botao && botao.classList.contains('perigo')) {
                if (!confirm(form.getAttribute('data-confirmar'))) {
                    evento.preventDefault();
                }
            }
        });
    });
});
