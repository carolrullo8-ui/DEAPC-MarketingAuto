// ================================================
// MarketingAuto — main.js
// Funções de interação da interface
// ================================================


// ---- MENU HAMBURGUER (mobile) ----
// Mostra/esconde o menu de navegação em ecrãs pequenos
function toggleMenu() {
    const nav = document.getElementById('menu-principal');
    // getElementById encontra o elemento com id="menu-principal"
    nav.classList.toggle('aberto');
    // toggle adiciona a classe se não existir, remove se existir
}


// ---- FORMULÁRIO DE NOVA CAMPANHA ----
// Mostra/esconde o painel de criação de campanhas
function toggleFormulario() {
    const painel = document.getElementById('formulario-campanha');

    if (painel.style.display === 'none') {
        painel.style.display = 'block';
        // Scroll suave até ao formulário
        painel.scrollIntoView({ behavior: 'smooth' });
    } else {
        painel.style.display = 'none';
    }
}


// ---- FORMULÁRIO DE NOVO LEAD ----
function toggleFormularioLead() {
    const painel = document.getElementById('formulario-lead');

    if (painel.style.display === 'none') {
        painel.style.display = 'block';
        painel.scrollIntoView({ behavior: 'smooth' });
    } else {
        painel.style.display = 'none';
    }
}


// ---- PAINEL DE IMPORTAÇÃO CSV ----
function toggleImportar() {
    const painel = document.getElementById('painel-importar');

    if (painel.style.display === 'none') {
        painel.style.display = 'block';
        painel.scrollIntoView({ behavior: 'smooth' });
    } else {
        painel.style.display = 'none';
    }
}


// ---- FECHAR MENU AO CLICAR FORA ----
// Se o utilizador clicar fora do menu em mobile, fecha-o
document.addEventListener('click', function(evento) {
    // evento.target é o elemento onde o utilizador clicou
    const nav    = document.getElementById('menu-principal');
    const botao  = document.querySelector('.menu-toggle');

    // Só fecha se o menu estiver aberto E o clique foi fora do menu e do botão
    if (nav && nav.classList.contains('aberto') &&
        !nav.contains(evento.target) &&
        !botao.contains(evento.target)) {
        nav.classList.remove('aberto');
    }
});