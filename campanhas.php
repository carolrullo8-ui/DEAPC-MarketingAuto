<?php
/*
    campanhas.php
    Lista, cria, remove e altera campanhas, incluindo a associação de Leads.
*/
session_start();

if (!isset($_SESSION['id_utilizador'])) {
    header('Location: index.php?erro=sessao_expirada');
    exit();
}

require_once 'scripts/iniciarDB.php';
$db = getDB();

// GARANTIR QUE A TABELA DE LIGAÇÃO EXISTE NA BASE DE DADOS
$db->exec("CREATE TABLE IF NOT EXISTS campanha_leads (
    id_campanha INTEGER,
    id_lead INTEGER,
    PRIMARY KEY (id_campanha, id_lead)
)");

$mensagemSucesso = "";
$mensagemErro = "";

// ==========================================
// 1. BARREIRA DE SEGURANÇA: REMOÇÃO
// ==========================================
if (isset($_GET['remover'])) {
    if ($_SESSION['tipo'] !== 'ADMIN') {
        $mensagemErro = "Acesso negado: O seu perfil não permite eliminar campanhas.";
    } else {
        $id_remover = intval($_GET['remover']);
        try {
            $stmt = $db->prepare("DELETE FROM campanhas WHERE id = :id");
            $stmt->bindValue(':id', $id_remover, SQLITE3_INTEGER);
            $stmt->execute();
            
            $stmt2 = $db->prepare("DELETE FROM campanha_leads WHERE id_campanha = :id");
            $stmt2->bindValue(':id', $id_remover, SQLITE3_INTEGER);
            $stmt2->execute();

            $mensagemSucesso = "Campanha e as suas associações eliminadas!";
        } catch (Exception $e) {
            $mensagemErro = "Erro ao eliminar: " . $e->getMessage();
        }
    }
}

// ==========================================
// 2. LÓGICA DE CRIAÇÃO OU ALTERAÇÃO (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'], $_POST['data_inicio'], $_POST['estado'], $_POST['publico_alvo'])) {
    $nome         = trim($_POST['nome']);
    $data_inicio  = trim($_POST['data_inicio']);
    $estado       = trim($_POST['estado']);
    $publico_alvo = trim($_POST['publico_alvo']);
    $id_campanha  = isset($_POST['id_campanha']) ? intval($_POST['id_campanha']) : 0;
    
    $leads_selecionadas = isset($_POST['leads_associadas']) ? $_POST['leads_associadas'] : [];
    
    if (!empty($nome) && !empty($data_inicio) && !empty($estado) && !empty($publico_alvo)) {
        try {
            if ($id_campanha > 0) {
                // MODO EDIÇÃO
                if ($_SESSION['tipo'] !== 'ADMIN') {
                    $mensagemErro = "Erro: Sem permissão para alterar dados.";
                } else {
                    $stmt = $db->prepare("UPDATE campanhas SET nome = :nome, estado = :estado, data_inicio = :data_inicio, publico_alvo = :publico_alvo WHERE id = :id");
                    $stmt->bindValue(':nome', $nome, SQLITE3_TEXT);
                    $stmt->bindValue(':estado', $estado, SQLITE3_TEXT);
                    $stmt->bindValue(':data_inicio', $data_inicio, SQLITE3_TEXT);
                    $stmt->bindValue(':publico_alvo', $publico_alvo, SQLITE3_TEXT);
                    $stmt->bindValue(':id', $id_campanha, SQLITE3_INTEGER);
                    $stmt->execute();
                    
                    $db->exec("DELETE FROM campanha_leads WHERE id_campanha = $id_campanha");
                    foreach ($leads_selecionadas as $id_lead) {
                        $stmt_link = $db->prepare("INSERT INTO campanha_leads (id_campanha, id_lead) VALUES (:id_c, :id_l)");
                        $stmt_link->bindValue(':id_c', $id_campanha, SQLITE3_INTEGER);
                        $stmt_link->bindValue(':id_l', intval($id_lead), SQLITE3_INTEGER);
                        $stmt_link->execute();
                    }
                    
                    $mensagemSucesso = "Campanha e vínculos de leads atualizados!";
                }
            } else {
                // MODO CRIAÇÃO (Correção do id_criador garantindo que nunca vai vazio)
                $id_criador = isset($_SESSION['id_utilizador']) ? intval($_SESSION['id_utilizador']) : 1;
                
                $stmt = $db->prepare("INSERT INTO campanhas (nome, estado, data_inicio, publico_alvo, id_criador) VALUES (:nome, :estado, :data_inicio, :publico_alvo, :id_criador)");
                $stmt->bindValue(':nome', $nome, SQLITE3_TEXT);
                $stmt->bindValue(':estado', $estado, SQLITE3_TEXT);
                $stmt->bindValue(':data_inicio', $data_inicio, SQLITE3_TEXT);
                $stmt->bindValue(':publico_alvo', $publico_alvo, SQLITE3_TEXT);
                $stmt->bindValue(':id_criador', $id_criador, SQLITE3_INTEGER);
                $stmt->execute();
                
                $id_nova_campanha = $db->lastInsertId();
                
                foreach ($leads_selecionadas as $id_lead) {
                    $stmt_link = $db->prepare("INSERT INTO campanha_leads (id_campanha, id_lead) VALUES (:id_c, :id_l)");
                    $stmt_link->bindValue(':id_c', $id_nova_campanha, SQLITE3_INTEGER);
                    $stmt_link->bindValue(':id_l', intval($id_lead), SQLITE3_INTEGER);
                    $stmt_link->execute();
                }
                
                $mensagemSucesso = "Campanha criada com as leads associadas!";
            }
        } catch (Exception $e) {
            $mensagemErro = "Erro na base de dados: " . $e->getMessage();
        }
    } else {
        $mensagemErro = "Por favor, preencha todos os campos.";
    }
}

// ==========================================
// 3. VERIFICAR SE FOI SOLICITADA EDIÇÃO (GET)
// ==========================================
$campanhaParaEditar = null;
$leadsDaCampanhaEditada = [];

if (isset($_GET['editar'])) {
    if ($_SESSION['tipo'] !== 'ADMIN') {
        $mensagemErro = "Acesso negado para alteração.";
    } else {
        $id_editar = intval($_GET['editar']);
        
        $stmt = $db->prepare("SELECT id, nome, estado, data_inicio, publico_alvo FROM campanhas WHERE id = :id");
        $stmt->bindValue(':id', $id_editar, SQLITE3_INTEGER);
        $stmt->execute();
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $campanhaParaEditar = $row;
            
            $queryLeadsCampanha = $db->query("SELECT id_lead FROM campanha_leads WHERE id_campanha = $id_editar");
            while ($l = $queryLeadsCampanha->fetch(PDO::FETCH_ASSOC)) {
                $leadsDaCampanhaEditada[] = $l['id_lead'];
            }
        }
    }
}

// LINHA CORRIGIDA (Removido o "do sistema" que causava o Parse Error)
$todasAsLeads = $db->query("SELECT id, nome, empresa FROM leads ORDER BY nome ASC");

$resultadoCampanhas = $db->query("
    SELECT c.id, c.nome, c.estado, c.data_inicio, c.publico_alvo, COUNT(cl.id_lead) as total_leads 
    FROM campanhas c 
    LEFT JOIN campanha_leads cl ON c.id = cl.id_campanha 
    GROUP BY c.id 
    ORDER BY c.id DESC
");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarketingAuto — Campanhas</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body class="pagina-campanhas">

    <header>
        <a href="dashboard.php" class="logo">🎯 MarketingAuto</a>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="campanhas.php" class="active">Campanhas</a>
            <a href="leads.php">Gestão de Leads</a>
            <a href="relatorios.php">Relatórios</a>
            <span style="margin-left: 15px; color: #fff; opacity: 0.8; font-size: 14px;">Olá, <strong><?php echo htmlspecialchars($_SESSION['nome']); ?></strong> (<?php echo ucfirst($_SESSION['tipo']); ?>)</span>
            <a href="scripts/logout.php" style="color: #ff4d4d; margin-left: 10px; text-decoration: none; font-weight: bold;">Sair →</a>
        </nav>
    </header>

    <main style="padding: 30px; max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
        
        <section style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); height: fit-content;">
            <h2><?php echo $campanhaParaEditar ? "✏️ Editar Campanha" : "Nova Campanha"; ?></h2>
            <p style="color:#666; font-size:14px; margin-bottom:20px;">Defina os parâmetros e anexe os potenciais clientes.</p>
            
            <?php if (!empty($mensagemSucesso)): ?>
                <div style="background:#e2f0d9; color:#385723; padding:10px; border-radius:4px; margin-bottom:15px; font-size:14px; font-weight:bold; text-align:center;"><?php echo $mensagemSucesso; ?></div>
            <?php endif; ?>
            <?php if (!empty($mensagemErro)): ?>
                <div style="background:#fce4d6; color:#c65911; padding:10px; border-radius:4px; margin-bottom:15px; font-size:14px; font-weight:bold; text-align:center;"><?php echo $mensagemErro; ?></div>
            <?php endif; ?>

            <form action="campanhas.php" method="POST">
                <?php if ($campanhaParaEditar): ?>
                    <input type="hidden" name="id_campanha" value="<?php echo $campanhaParaEditar['id']; ?>">
                <?php endif; ?>

                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">Nome da Campanha</label>
                    <input type="text" name="nome" required value="<?php echo $campanhaParaEditar ? htmlspecialchars($campanhaParaEditar['nome']) : ''; ?>" placeholder="Ex: Promoção de Verão" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">Público-Alvo</label>
                    <input type="text" name="publico_alvo" required value="<?php echo $campanhaParaEditar ? htmlspecialchars($campanhaParaEditar['publico_alvo']) : ''; ?>" placeholder="Ex: PMEs" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">Data de Início</label>
                    <input type="date" name="data_inicio" required value="<?php echo $campanhaParaEditar ? htmlspecialchars($campanhaParaEditar['data_inicio']) : date('Y-m-d'); ?>" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">Estado</label>
                    <select name="estado" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box; background: white;">
                        <option value="Rascunho" <?php echo ($campanhaParaEditar && $campanhaParaEditar['estado'] === 'Rascunho') ? 'selected' : ''; ?>>Rascunho</option>
                        <option value="Ativa" <?php echo ($campanhaParaEditar && $campanhaParaEditar['estado'] === 'Ativa') ? 'selected' : ''; ?>>Ativa</option>
                        <option value="Pausada" <?php echo ($campanhaParaEditar && $campanhaParaEditar['estado'] === 'Pausada') ? 'selected' : ''; ?>>Pausada</option>
                    </select>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">Leads Interessadas / Anexadas</label>
                    <div style="max-height: 150px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; border-radius: 4px; background: #fafafa;">
                        <?php 
                        $haLeads = false;
                        while ($lead = $todasAsLeads->fetch(PDO::FETCH_ASSOC)): 
                            $haLeads = true;
                            $marcado = in_array($lead['id'], $leadsDaCampanhaEditada) ? 'checked' : '';
                        ?>
                            <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; font-size: 14px; cursor: pointer;">
                                <input type="checkbox" name="leads_associadas[]" value="<?php echo $lead['id']; ?>" <?php echo $marcado; ?>>
                                <span><?php echo htmlspecialchars($lead['nome']); ?> <small style="color:#777;">(<?php echo htmlspecialchars($lead['empresa']); ?>)</small></span>
                            </label>
                        <?php 
                        endwhile; 
                        if (!$haLeads):
                        ?>
                            <p style="font-size: 13px; color: #999; margin: 5px 0;">Nenhuma lead cadastrada no sistema.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <button type="submit" style="width:100%; padding:12px; background:<?php echo $campanhaParaEditar ? '#28a745' : '#007bff'; ?>; color:white; border:none; border-radius:4px; font-weight:bold; cursor:pointer;">
                    <?php echo $campanhaParaEditar ? "💾 Gravar Alterações" : "🚀 Criar Campanha"; ?>
                </button>

                <?php if ($campanhaParaEditar): ?>
                    <div style="text-align: center; margin-top: 12px;">
                        <a href="campanhas.php" style="color: #666; font-size: 14px; text-decoration: none;">❌ Cancelar Edição</a>
                    </div>
                <?php endif; ?>
            </form>
        </section>

        <section style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <h2>Campanhas Registadas</h2>
            <hr style="border:0; border-top:1px solid #eee; margin: 15px 0 20px 0;">

            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background-color: #f4f6f9; border-bottom: 2px solid #eee;">
                        <th style="padding: 12px; width: 50px;">ID</th>
                        <th style="padding: 12px;">Nome</th>
                        <th style="padding: 12px;">Estado</th>
                        <th style="padding: 12px; text-align: center;">Leads</th>
                        <th style="padding: 12px; text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($campanha = $resultadoCampanhas->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 12px; color: #666;"><?php echo $campanha['id']; ?></td>
                            <td style="padding: 12px;">
                                <strong><?php echo htmlspecialchars($campanha['nome']); ?></strong><br>
                                <small style="color: #888;">Alvo: <?php echo htmlspecialchars($campanha['publico_alvo']); ?></small>
                            </td>
                            
                            <td style="padding: 12px;">
                                <?php 
                                $estadoFiltro = strtolower(trim($campanha['estado']));
                                if ($estadoFiltro === 'ativa') { $bg = '#e2f0d9'; $text = '#385723'; }
                                elseif ($estadoFiltro === 'pausada') { $bg = '#fff2cc'; $text = '#7f6000'; }
                                else { $bg = '#e9ecef'; $text = '#495057'; }
                                ?>
                                <span style="background: <?php echo $bg; ?>; color: <?php echo $text; ?>; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase;">
                                    <?php echo htmlspecialchars($campanha['estado']); ?>
                                </span>
                            </td>

                            <td style="padding: 12px; text-align: center; font-weight: bold; color: #333;">
                                <span style="background: #e8f0fe; color: #1a73e8; padding: 3px 10px; border-radius: 10px; font-size: 13px;">
                                    <?php echo $campanha['total_leads']; ?>
                                </span>
                            </td>
                            
                            <td style="padding: 12px; text-align: center; white-space: nowrap;">
                                <?php if ($_SESSION['tipo'] === 'ADMIN'): ?>
                                    <a href="campanhas.php?editar=<?php echo $campanha['id']; ?>" style="text-decoration: none; font-size: 15px; margin-right: 10px;">✏️</a>
                                    <a href="campanhas.php?remover=<?php echo $campanha['id']; ?>" onclick="return confirm('Tem a certeza que quer eliminar a campanha?');" style="color: #ff4d4d; text-decoration: none; font-size: 15px;">❌</a>
                                <?php else: ?>
                                    <span style="font-size: 14px; color: #ccc;">🔒</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>