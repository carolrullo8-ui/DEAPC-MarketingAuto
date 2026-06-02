<?php
/*
    admin.php — ADM02, ADM04, ADM05
    Gestão de utilizadores e registos de acesso.
    Só acessível por administradores.
*/

require_once 'iniciarDB.php';
session_start();

// Verificar que é administrador
if (!isset($_SESSION['id_utilizador']) || $_SESSION['tipo'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['sucesso' => false, 'erro' => 'Sem permissao']);
    exit();
}

header('Content-Type: application/json; charset=utf-8');

$db   = getDB();
$acao = $_REQUEST['acao'] ?? '';

switch ($acao) {

    // ADM02 — Listar utilizadores
    case 'listar_utilizadores':
        $res = $db->query("
            SELECT id, username, nome, email, tipo, ultimo_acesso, data_registo
            FROM utilizadores
            ORDER BY data_registo DESC
        ");
        // Sem a password por segurança

        $utilizadores = [];
        while ($linha = $res->fetchArray(SQLITE3_ASSOC)) {
            $utilizadores[] = $linha;
        }

        echo json_encode($utilizadores, JSON_UNESCAPED_UNICODE);
        break;

    // ADM02 — Criar utilizador
    case 'criar_utilizador':
        if (empty($_POST['username']) || empty($_POST['password']) ||
            empty($_POST['nome'])     || empty($_POST['email'])) {
            echo json_encode(['sucesso' => false, 'erro' => 'Campos obrigatorios em falta']);
            exit();
        }

        // Verificar se o username já existe
        $stmt = $db->prepare("SELECT id FROM utilizadores WHERE username = :username");
        $stmt->bindValue(':username', htmlspecialchars($_POST['username']), SQLITE3_TEXT);
        $res  = $stmt->execute();

        if ($res->fetchArray()) {
            echo json_encode(['sucesso' => false, 'erro' => 'Username ja existe']);
            exit();
        }

        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $db->prepare("
            INSERT INTO utilizadores (username, password, nome, email, tipo)
            VALUES (:username, :password, :nome, :email, :tipo)
        ");
        $stmt->bindValue(':username', htmlspecialchars($_POST['username']), SQLITE3_TEXT);
        $stmt->bindValue(':password', $hash, SQLITE3_TEXT);
        $stmt->bindValue(':nome',     htmlspecialchars($_POST['nome']), SQLITE3_TEXT);
        $stmt->bindValue(':email',    htmlspecialchars($_POST['email']), SQLITE3_TEXT);
        $stmt->bindValue(':tipo',     htmlspecialchars($_POST['tipo'] ?? 'gestor'), SQLITE3_TEXT);
        $stmt->execute();

        echo json_encode(['sucesso' => true, 'mensagem' => 'Utilizador criado']);
        break;

    // ADM02 — Remover utilizador
    case 'remover_utilizador':
        if (empty($_REQUEST['id'])) {
            echo json_encode(['sucesso' => false, 'erro' => 'ID invalido']);
            break;
        }

        $id = (int)$_REQUEST['id'];

        // Não se pode remover a si próprio
        if ($id === (int)$_SESSION['id_utilizador']) {
            echo json_encode(['sucesso' => false, 'erro' => 'Nao podes remover a tua propria conta']);
            break;
        }

        $stmt = $db->prepare("DELETE FROM utilizadores WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        echo json_encode(['sucesso' => true, 'mensagem' => 'Utilizador removido']);
        break;

    // ADM05 — Registos de acesso
    case 'registos_acesso':
        $res = $db->query("
            SELECT username, nome, tipo, ultimo_acesso, data_registo
            FROM utilizadores
            WHERE ultimo_acesso IS NOT NULL
            ORDER BY ultimo_acesso DESC
        ");

        $registos = [];
        while ($linha = $res->fetchArray(SQLITE3_ASSOC)) {
            $registos[] = $linha;
        }

        echo json_encode($registos, JSON_UNESCAPED_UNICODE);
        break;

    default:
        echo json_encode(['sucesso' => false, 'erro' => 'Acao desconhecida']);
        break;
}
?>