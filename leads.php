<?php
/*
    leads.php
    Lista, cria, edita e remove contactos (Ações críticas apenas para ADMIN).
*/
session_start();

if (!isset($_SESSION['id_utilizador']) && !isset($_SESSION['utilizador'])) {
    header('Location: index.php?erro=sessao_expirada');
    exit();
}

require_once 'scripts/iniciarDB.php';
$db = getDB();

$mensagemSucesso = "";
$mensagemErro = "";

// ==========================================
// BARREIRA DE SEGURANÇA: REMOÇÃO DE LEAD
// ==========================================
if (isset($_GET['remover'])) {
    if ($_SESSION['tipo'] !== 'ADMIN') {
        $mensagemErro = "Acesso negado: O seu perfil não permite eliminar registos.";
    } else {
        $id_remover = intval($_GET['remover']);
        try {
            $stmt = $db->prepare("DELETE FROM leads WHERE id = :id");
            $stmt->bindValue(':id', $id_remover, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $mensagemSucesso = "Lead eliminada com sucesso!";
            } else {
                $mensagemErro = "Não foi possível eliminar a lead.";
            }
        } catch (Exception $e) {
            $mensagemErro = "Erro ao eliminar: " . $e->getMessage();
        }
    }
}

// ==========================================
// LÓGICA DE CRIAÇÃO OU ATUALIZAÇÃO (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'], $_POST['email'], $_POST['empresa'])) {
    $nome    = trim($_POST['nome']);
    $email   = trim($_POST['email']);
    $empresa = trim($_POST['empresa']);
    $id_lead = isset($_POST['id_lead']) ? intval($_POST['id_lead']) : 0;
    
    if (!empty($nome) && !empty($email) && !empty($empresa)) {
        try {
            if ($id_lead > 0) {
                // Segurança extra para alteração
                if ($_SESSION['tipo'] !== 'ADMIN') {
                    $mensagemErro = "Erro: Sem permissão para corrigir dados.";
                } else {
                    $stmt = $db->prepare("UPDATE leads SET nome = :nome, email = :email, empresa = :empresa WHERE id = :id");
                    $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
                    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
                    $stmt->bindValue(':empresa', $empresa, PDO::PARAM_STR);
                    $stmt->bindValue(':id', $id_lead, PDO::PARAM_INT);
                    
                    if ($stmt->execute()) {
                        $mensagemSucesso = "Lead corrigida com sucesso pelo Administrador!";
                    }
                }
            } else {
                // Inserção livre para utilizadores autenticados
                $stmt = $db->prepare("INSERT INTO leads (nome, email, status, segmento, empresa) VALUES (:nome, :email, 'Novo', 'Geral', :empresa)");
                $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
                $stmt->bindValue(':email', $email, PDO::PARAM_STR);
                $stmt->bindValue(':empresa', $empresa, PDO::PARAM_STR);
                
                if ($stmt->execute()) {
                    $mensagemSucesso = "Lead guardada com sucesso!";
                }
            }
        } catch (Exception $e) {
            $mensagemErro = "Erro: " . $e->getMessage();
        }
    } else {
        $mensagemErro = "Preencha todos os campos.";
    }
}

// ==========================================
// VERIFICAR SOLICITAÇÃO DE EDIÇÃO (GET)
// ==========================================
$leadParaEditar = null;
if (isset($_GET['editar'])) {
    if ($_SESSION['tipo'] !== 'ADMIN') {
        $mensagemErro = "Acesso negado: O seu perfil não permite editar registos.";
    } else {
        $id_editar = intval($_GET['editar']);
        $stmt = $db->prepare("SELECT id, nome, email, empresa FROM leads WHERE id = :id");
        $stmt->bindValue(':id', $id_editar, PDO::PARAM_INT);
        $stmt->execute();
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $leadParaEditar = $row;
        }
    }
}

// Nota: Usamos "status AS estado" para alinhar a coluna do MySQL com o teu HTML abaixo
$resultadoLeads = $db->query("SELECT id, nome, email, empresa, status AS estado FROM leads ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>MarketingAuto — Leads</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
    <header>
        <a href="dashboard.php" class="logo">🎯 MarketingAuto</a>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="campanhas.php">Campanhas</a>
            <a href="leads.php" class="active">Gestão de Leads</a>
            <a href="relatorios.php">Relatórios</a>
            <span style="margin-left: 15px; color: #fff; opacity: 0.8; font-size: 14px;">Olá, <strong><?php echo htmlspecialchars($_SESSION['utilizador'] ?? 'Utilizador'); ?></strong> (<?php echo ucfirst($_SESSION['tipo'] ?? 'Perfil'); ?>)</span>
            <a href="scripts/logout.php" style="color: #ff4d4d; margin-left: 10px; text-decoration: none; font-weight: bold;">Sair →</a>
        </nav>
    </header>

    <main style="padding: 30px; max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
        
        <section style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); height: fit-content;">
            <h2><?php echo $leadParaEditar ? "✏️ Corrigir Lead (ADMIN)" : "Adicionar Lead"; ?></h2>
            
            <?php if (!empty($mensagemSucesso)): ?>
                <div style="background:#e2f0d9; color:#385723; padding:10px; text-align:center; margin-bottom:15px; font-weight:bold;"><?php echo $mensagemSucesso; ?></div>
            <?php endif; ?>
            <?php if (!empty($mensagemErro)): ?>
                <div style="background:#fce4d6; color:#c65911; padding:10px; text-align:center; margin-bottom:15px; font-weight:bold;"><?php echo $mensagemErro; ?></div>
            <?php endif; ?>

            <form action="leads.php" method="POST">
                <?php if ($leadParaEditar): ?>
                    <input type="hidden" name="id_lead" value="<?php echo $leadParaEditar['id']; ?>">
                <?php endif; ?>

                <div style="margin-bottom: 15px;">
                    <label style="display:block; font-weight:bold; margin-bottom:5px;">Nome Completo</label>
                    <input type="text" name="nome" required value="<?php echo $leadParaEditar ? htmlspecialchars($leadParaEditar['nome']) : ''; ?>" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display:block; font-weight:bold; margin-bottom:5px;">Nome da Empresa</label>
                    <input type="text" name="empresa" required value="<?php echo $leadParaEditar ? htmlspecialchars($leadParaEditar['empresa']) : ''; ?>" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display:block; font-weight:bold; margin-bottom:5px;">E-mail</label>
                    <input type="email" name="email" required value="<?php echo $leadParaEditar ? htmlspecialchars($leadParaEditar['email']) : ''; ?>" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                </div>
                
                <button type="submit" style="width:100%; padding:12px; background:<?php echo $leadParaEditar ? '#28a745' : '#007bff'; ?>; color:white; border:none; border-radius:4px; font-weight:bold; cursor:pointer;">
                    <?php echo $leadParaEditar ? "Confirmar Alterações" : "Gravar Lead"; ?>
                </button>
                <?php if ($leadParaEditar): ?>
                    <div style="text-align: center; margin-top: 10px;"><a href="leads.php" style="color: #666; font-size: 14px; text-decoration: none;">❌ Cancelar</a></div>
                <?php endif; ?>
            </form>
        </section>

        <section style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <h2>Lista de Contactos (Leads)</h2>
            <table style="width: 100%; border-collapse: collapse; text-align: left; margin-top:15px;">
                <thead>
                    <tr style="background-color: #f4f6f9; border-bottom: 2px solid #eee;">
                        <th style="padding: 10px;">Nome</th>
                        <th style="padding: 10px;">Empresa</th>
                        <th style="padding: 10px;">E-mail</th>
                        <th style="padding: 10px;">Estado</th>
                        <th style="padding: 10px; text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($lead = $resultadoLeads->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr style="border-bottom: 1px solid #eee; <?php echo ($leadParaEditar && $leadParaEditar['id'] == $lead['id']) ? 'background-color: #fff9e6;' : ''; ?>">
                            <td style="padding: 10px;"><strong><?php echo htmlspecialchars($lead['nome']); ?></strong></td>
                            <td style="padding: 10px; color:#333;"><?php echo htmlspecialchars($lead['empresa']); ?></td>
                            <td style="padding: 10px; color:#555;"><?php echo htmlspecialchars($lead['email']); ?></td>
                            <td style="padding: 10px;"><span style="background:#e2f0d9; padding:3px 6px; border-radius:4px; font-size:11px; font-weight:bold; color:#385723;"><?php echo htmlspecialchars($lead['estado']); ?></span></td>
                            
                            <td style="padding: 10px; text-align: center; white-space: nowrap;">
                                <?php if ($_SESSION['tipo'] === 'ADMIN'): ?>
                                    <a href="leads.php?editar=<?php echo $lead['id']; ?>" style="text-decoration: none; font-size: 15px; margin-right: 8px;" title="Corrigir Dados">✏️</a>
                                    <a href="leads.php?remover=<?php echo $lead['id']; ?>" 
                                       onclick="return confirm('Tem a certeza que deseja remover o contacto de <?php echo htmlspecialchars($lead['nome']); ?>?');" 
                                       style="color: #ff4d4d; text-decoration: none; font-weight: bold; font-size: 15px;" title="Eliminar">❌</a>
                                <?php else: ?>
                                    <span style="font-size: 14px; color: #ccc; cursor: not-allowed;" title="Apenas Administradores têm permissões de alteração">🔒</span>
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