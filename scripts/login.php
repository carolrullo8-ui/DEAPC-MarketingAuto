<?php
/*
    login.php — MKT01, ADM01
    
    Recebe username e password do formulário de login,
    valida contra a BD e inicia a sessão.
    Redireciona para o dashboard (gestor) ou admin (administrador).
*/

require_once 'iniciarDB.php';
session_start();

// Se já está autenticado, vai direto para o dashboard
if (isset($_SESSION['id_utilizador'])) {
    header('Location: ../dashboard.php');
    exit();
}

// Verificar se chegaram os dados
if (!isset($_POST['username']) || !isset($_POST['password'])) {
    header('Location: ../index.php?erro=dados_invalidos');
    exit();
}

// Limpar os dados
$username = trim(htmlspecialchars($_POST['username']));
$password = trim($_POST['password']);

if (empty($username) || empty($password)) {
    header('Location: ../index.php?erro=campos_vazios');
    exit();
}

// Ligar à BD
$db = getDB();

// Procurar o utilizador
$stmt = $db->prepare("
    SELECT id, username, password, nome, tipo
    FROM utilizadores
    WHERE username = :username
");
$stmt->bindValue(':username', $username, SQLITE3_TEXT);
$resultado    = $stmt->execute();
$utilizador   = $resultado->fetchArray(SQLITE3_ASSOC);

// Verificar se existe e se a password está correta
if (!$utilizador || !password_verify($password, $utilizador['password'])) {
    header('Location: ../index.php?erro=credenciais_invalidas');
    exit();
}

// ---- LOGIN BEM SUCEDIDO ----

// Guardar na sessão
$_SESSION['id_utilizador'] = $utilizador['id'];
$_SESSION['username']      = $utilizador['username'];
$_SESSION['nome']          = $utilizador['nome'];
$_SESSION['tipo']          = $utilizador['tipo'];

// Atualizar último acesso
$stmt = $db->prepare("
    UPDATE utilizadores
    SET ultimo_acesso = datetime('now')
    WHERE id = :id
");
$stmt->bindValue(':id', $utilizador['id'], SQLITE3_INTEGER);
$stmt->execute();

// Redirecionar conforme o tipo
if ($utilizador['tipo'] === 'admin') {
    header('Location: ../admin.php');
} else {
    header('Location: ../dashboard.php');
}
exit();
?>