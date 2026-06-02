<?php
/*
    logout.php — MKT13
    Termina a sessão e redireciona para o login.
*/

session_start();
$_SESSION = [];
session_destroy();

header('Location: ../index.html?sucesso=logout');
exit();
?>