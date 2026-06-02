<?php
/*
    scripts/logout.php
    Encerra a sessão ativa de forma limpa e segura.
*/
session_start();

// Remove todas as variáveis guardadas na sessão
session_unset();

// Destrói o ficheiro temporário de sessão no servidor
session_destroy();

// Redireciona o utilizador de volta ao ecrã de login
header('Location: ../index.php');
exit();
?>