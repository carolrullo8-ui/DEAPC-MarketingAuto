<?php
/*
    gerirCampanha.php — MKT03, MKT04, MKT05, MKT11
    
    Gere campanhas: criar, editar, eliminar e associar leads.
    A ação é definida pelo parâmetro 'acao' no POST/GET.
*/

require_once 'iniciarDB.php';
session_start();

// Verificar autenticação
if (!isset($_SESSION['id_utilizador'])) {
    header('Content-Type: application/json');
    echo json_encode(['sucesso' => false, 'erro' => 'Nao autenticado']);
    exit();
}

header('Content-Type: application/json; charset=utf-8');

$db   = getDB();
$acao = $_REQUEST['acao'] ?? '';

switch ($acao) {

    // MKT03 — Criar campanha
    case 'criar':
        $campos = ['nome', 'publico_alvo', 'data_inicio'];
        foreach ($campos as $campo) {
            if (empty($_POST[$campo])) {
                echo json_encode(['sucesso' => false, 'erro' => "Campo $campo obrigatorio"]);
                exit();
            }
        }

        $stmt = $db->prepare("
            INSERT INTO campanhas (nome, descricao, publico_alvo, data_inicio, data_fim, estado, id_criador)
            VALUES (:nome, :descricao, :publico, :inicio, :fim, :estado, :criador)
        ");
        $stmt->bindValue(':nome',      htmlspecialchars($_POST['nome']), SQLITE3_TEXT);
        $stmt->bindValue(':descricao', htmlspecialchars($_POST['descricao'] ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(':publico',   htmlspecialchars($_POST['publico_alvo']), SQLITE3_TEXT);
        $stmt->bindValue(':inicio',    htmlspecialchars($_POST['data_inicio']), SQLITE3_TEXT);
        $stmt->bindValue(':fim',       htmlspecialchars($_POST['data_fim'] ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(':estado',    htmlspecialchars($_POST['estado'] ?? 'rascunho'), SQLITE3_TEXT);
        $stmt->bindValue(':criador',   $_SESSION['id_utilizador'], SQLITE3_INTEGER);

        $resultado = $stmt->execute();

        if ($resultado) {
            echo json_encode([
                'sucesso'  => true,
                'id'       => $db->lastInsertRowID(),
                'mensagem' => 'Campanha criada com sucesso'
            ]);
        } else {
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao criar campanha']);
        }
        break;

    // MKT04 — Editar campanha
    case 'editar':
        if (empty($_POST['id'])) {
            echo json_encode(['sucesso' => false, 'erro' => 'ID invalido']);
            break;
        }

        $stmt = $db->prepare("
            UPDATE campanhas
            SET nome         = :nome,
                descricao    = :descricao,
                publico_alvo = :publico,
                data_inicio  = :inicio,
                data_fim     = :fim,
                estado       = :estado
            WHERE id = :id
        ");
        $stmt->bindValue(':id',        (int)$_POST['id'], SQLITE3_INTEGER);
        $stmt->bindValue(':nome',      htmlspecialchars($_POST['nome']), SQLITE3_TEXT);
        $stmt->bindValue(':descricao', htmlspecialchars($_POST['descricao'] ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(':publico',   htmlspecialchars($_POST['publico_alvo']), SQLITE3_TEXT);
        $stmt->bindValue(':inicio',    htmlspecialchars($_POST['data_inicio']), SQLITE3_TEXT);
        $stmt->bindValue(':fim',       htmlspecialchars($_POST['data_fim'] ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(':estado',    htmlspecialchars($_POST['estado']), SQLITE3_TEXT);
        $stmt->execute();

        if ($db->changes() > 0) {
            echo json_encode(['sucesso' => true, 'mensagem' => 'Campanha atualizada']);
        } else {
            echo json_encode(['sucesso' => false, 'erro' => 'Campanha nao encontrada']);
        }
        break;

    // MKT05 — Eliminar campanha
    case 'eliminar':
        if (empty($_REQUEST['id'])) {
            echo json_encode(['sucesso' => false, 'erro' => 'ID invalido']);
            break;
        }

        $id = (int)$_REQUEST['id'];

        // Apagar primeiro as associações com leads
        $stmt = $db->prepare("DELETE FROM campanhas_leads WHERE id_campanha = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        // Apagar a campanha
        $stmt = $db->prepare("DELETE FROM campanhas WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        echo json_encode(['sucesso' => true, 'mensagem' => 'Campanha eliminada']);
        break;

    // MKT11 — Associar leads a uma campanha
    case 'associar_leads':
        if (empty($_POST['id_campanha']) || empty($_POST['ids_leads'])) {
            echo json_encode(['sucesso' => false, 'erro' => 'Dados invalidos']);
            break;
        }

        $id_campanha = (int)$_POST['id_campanha'];
        // ids_leads vem como array de ids separados por vírgula
        $ids_leads   = explode(',', $_POST['ids_leads']);
        // explode divide a string pelo separador ',' e cria um array

        $inseridos = 0;
        foreach ($ids_leads as $id_lead) {
            $id_lead = (int)trim($id_lead);
            if ($id_lead <= 0) continue;
            // continue salta para a próxima iteração do foreach

            // INSERT OR IGNORE: se a associação já existir, não dá erro
            $stmt = $db->prepare("
                INSERT OR IGNORE INTO campanhas_leads (id_campanha, id_lead)
                VALUES (:campanha, :lead)
            ");
            $stmt->bindValue(':campanha', $id_campanha, SQLITE3_INTEGER);
            $stmt->bindValue(':lead',     $id_lead, SQLITE3_INTEGER);
            $stmt->execute();
            $inseridos++;
        }

        echo json_encode([
            'sucesso'   => true,
            'inseridos' => $inseridos,
            'mensagem'  => "$inseridos leads associados"
        ]);
        break;

    default:
        echo json_encode(['sucesso' => false, 'erro' => 'Acao desconhecida']);
        break;
}
?>