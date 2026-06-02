<?php
/*
    gerirLead.php — MKT07, MKT08
    Criar, editar e eliminar leads.
*/

require_once 'iniciarDB.php';
session_start();

if (!isset($_SESSION['id_utilizador'])) {
    header('Content-Type: application/json');
    echo json_encode(['sucesso' => false, 'erro' => 'Nao autenticado']);
    exit();
}

header('Content-Type: application/json; charset=utf-8');

$db   = getDB();
$acao = $_REQUEST['acao'] ?? '';

switch ($acao) {

    // MKT07 — Adicionar lead
    case 'criar':
        if (empty($_POST['nome']) || empty($_POST['email']) || empty($_POST['segmento'])) {
            echo json_encode(['sucesso' => false, 'erro' => 'Campos obrigatorios em falta']);
            exit();
        }

        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['sucesso' => false, 'erro' => 'Email invalido']);
            exit();
        }

        // Verificar se o email já existe
        $stmt = $db->prepare("SELECT id FROM leads WHERE email = :email");
        $stmt->bindValue(':email', htmlspecialchars($_POST['email']), SQLITE3_TEXT);
        $res  = $stmt->execute();

        if ($res->fetchArray()) {
            echo json_encode(['sucesso' => false, 'erro' => 'Email ja registado']);
            exit();
        }

        $stmt = $db->prepare("
            INSERT INTO leads (nome, email, empresa, telefone, segmento, estado)
            VALUES (:nome, :email, :empresa, :telefone, :segmento, :estado)
        ");
        $stmt->bindValue(':nome',      htmlspecialchars($_POST['nome']), SQLITE3_TEXT);
        $stmt->bindValue(':email',     htmlspecialchars($_POST['email']), SQLITE3_TEXT);
        $stmt->bindValue(':empresa',   htmlspecialchars($_POST['empresa']  ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(':telefone',  htmlspecialchars($_POST['telefone'] ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(':segmento',  htmlspecialchars($_POST['segmento']), SQLITE3_TEXT);
        $stmt->bindValue(':estado',    htmlspecialchars($_POST['estado']   ?? 'novo'), SQLITE3_TEXT);

        $resultado = $stmt->execute();

        if ($resultado) {
            echo json_encode([
                'sucesso'  => true,
                'id'       => $db->lastInsertRowID(),
                'mensagem' => 'Lead criado com sucesso'
            ]);
        } else {
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao criar lead']);
        }
        break;

    // Editar lead
    case 'editar':
        if (empty($_POST['id'])) {
            echo json_encode(['sucesso' => false, 'erro' => 'ID invalido']);
            break;
        }

        $stmt = $db->prepare("
            UPDATE leads
            SET nome     = :nome,
                email    = :email,
                empresa  = :empresa,
                telefone = :telefone,
                segmento = :segmento,
                estado   = :estado
            WHERE id = :id
        ");
        $stmt->bindValue(':id',       (int)$_POST['id'], SQLITE3_INTEGER);
        $stmt->bindValue(':nome',     htmlspecialchars($_POST['nome']), SQLITE3_TEXT);
        $stmt->bindValue(':email',    htmlspecialchars($_POST['email']), SQLITE3_TEXT);
        $stmt->bindValue(':empresa',  htmlspecialchars($_POST['empresa']  ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(':telefone', htmlspecialchars($_POST['telefone'] ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(':segmento', htmlspecialchars($_POST['segmento']), SQLITE3_TEXT);
        $stmt->bindValue(':estado',   htmlspecialchars($_POST['estado']), SQLITE3_TEXT);
        $stmt->execute();

        echo json_encode(['sucesso' => true, 'mensagem' => 'Lead atualizado']);
        break;

    // Eliminar lead
    case 'eliminar':
        if (empty($_REQUEST['id'])) {
            echo json_encode(['sucesso' => false, 'erro' => 'ID invalido']);
            break;
        }

        $id = (int)$_REQUEST['id'];

        // Remover associações com campanhas primeiro
        $stmt = $db->prepare("DELETE FROM campanhas_leads WHERE id_lead = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        // Remover o lead
        $stmt = $db->prepare("DELETE FROM leads WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        echo json_encode(['sucesso' => true, 'mensagem' => 'Lead eliminado']);
        break;

    default:
        echo json_encode(['sucesso' => false, 'erro' => 'Acao desconhecida']);
        break;
}
?>