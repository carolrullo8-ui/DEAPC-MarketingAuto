<?php
/*
    dashboard.php
    Painel Principal com contadores globais, Campanhas Recentes (com total de leads real) e Leads Recentes.
*/
session_start();

if (!isset($_SESSION['id_utilizador'])) {
    header('Location: index.php?erro=sessao_expirada');
    exit();
}

require_once 'scripts/iniciarDB.php';
$db = getDB();

// 1. Contadores para os mini-painéis do topo
$totalCampanhasAtivas = $db->querySingle("SELECT COUNT(*) FROM campanhas WHERE LOWER(trim(estado)) = 'ativa'") ?: 0;
$totalLeads = $db->querySingle("SELECT COUNT(*) FROM leads") ?: 0;

// 2. QUERY ATUALIZADA: Conta as leads associadas a partir da tabela pivô 'campanha_leads'
$resultadoCampanhas = $db->query("
    SELECT c.id, c.nome, c.estado, COUNT(cl.id_lead) as total_leads 
    FROM campanhas c 
    LEFT JOIN campanha_leads cl ON c.id = cl.id_campanha 
    GROUP BY c.id 
    ORDER BY c.id DESC 
    LIMIT 4
");

// 3. Procurar as Leads Recentes para a tabela da direita
$resultadoLeads = $db->query("SELECT nome, empresa, estado FROM leads ORDER BY id DESC LIMIT 4");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarketingAuto — Dashboard</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>

    <header>
        <a href="dashboard.php" class="logo">🎯 MarketingAuto</a>
        <nav>
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="campanhas.php">Campanhas</a>
            <a href="leads.php">Gestão de Leads</a>
            <a href="relatorios.php">Relatórios</a>
            <span style="margin-left: 15px; color: #fff; opacity: 0.8; font-size: 14px;">Olá, <strong><?php echo htmlspecialchars($_SESSION['nome']); ?></strong></span>
            <a href="scripts/logout.php" style="color: #ff4d4d; margin-left: 10px; text-decoration: none; font-weight: bold;">Sair →</a>
        </nav>
    </header>

    <main style="padding: 30px; max-width: 1200px; margin: 0 auto;">
        
        <div style="margin-bottom: 30px;">
            <h1 style="margin: 0 0 5px 0;">Dashboard</h1>
            <p style="color: #666; margin: 0;">Visão geral da tua atividade de marketing</p>
        </div>

        <section style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-left: 4px solid #007bff; display: flex; align-items: center; gap: 15px;">
                <span style="font-size: 30px;">📢</span>
                <div>
                    <h3 style="margin:0; font-size: 24px; color:#333;"><?php echo $totalCampanhasAtivas; ?></h3>
                    <p style="margin:0; font-size: 11px; color:#aaa; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px;">Campanhas Ativas</p>
                </div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-left: 4px solid #28a745; display: flex; align-items: center; gap: 15px;">
                <span style="font-size: 30px;">👥</span>
                <div>
                    <h3 style="margin:0; font-size: 24px; color:#333;"><?php echo $totalLeads; ?></h3>
                    <p style="margin:0; font-size: 11px; color:#aaa; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px;">Total de Leads</p>
                </div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-left: 4px solid #ffc107; display: flex; align-items: center; gap: 15px;">
                <span style="font-size: 30px;">📩</span>
                <div>
                    <h3 style="margin:0; font-size: 24px; color:#333;">0%</h3>
                    <p style="margin:0; font-size: 11px; color:#aaa; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px;">Taxa de Abertura</p>
                </div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-left: 4px solid #17a2b8; display: flex; align-items: center; gap: 15px;">
                <span style="font-size: 30px;">🖱️</span>
                <div>
                    <h3 style="margin:0; font-size: 24px; color:#333;">0%</h3>
                    <p style="margin:0; font-size: 11px; color:#aaa; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px;">Taxa de Cliques</p>
                </div>
            </div>
        </section>

        <section style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            
            <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="margin: 0; font-size: 18px; color: #333;">Campanhas Recentes</h2>
                    <a href="campanhas.php" style="color: #4f46e5; text-decoration: none; font-size: 14px; font-weight: 500;">Ver todas →</a>
                </div>
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 1px solid #edf2f7; color: #718096; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <th style="padding: 12px 8px;">Nome</th>
                            <th style="padding: 12px 8px;">Estado</th>
                            <th style="padding: 12px 8px; text-align: center;">Leads</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($campanha = $resultadoCampanhas->fetchArray(SQLITE3_ASSOC)): ?>
                            <tr style="border-bottom: 1px solid #f7fafc; font-size: 14px;">
                                <td style="padding: 14px 8px; color: #2d3748;"><?php echo htmlspecialchars($campanha['nome']); ?></td>
                                <td style="padding: 14px 8px;">
                                    <?php 
                                    $est = strtolower(trim($campanha['estado']));
                                    if ($est === 'ativa') { $bg = '#e2f0d9'; $text = '#385723'; }
                                    elseif ($est === 'pausada') { $bg = '#fff2cc'; $text = '#7f6000'; }
                                    else { $bg = '#e9ecef'; $text = '#495057'; } // Rascunho
                                    ?>
                                    <span style="background: <?php echo $bg; ?>; color: <?php echo $text; ?>; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase;">
                                        <?php echo htmlspecialchars($campanha['estado']); ?>
                                    </span>
                                </td>
                                <td style="padding: 14px 8px; text-align: center; color: #4a5568; font-weight: 500;">
                                    <?php echo $campanha['total_leads']; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="margin: 0; font-size: 18px; color: #333;">Leads Recentes</h2>
                    <a href="leads.php" style="color: #4f46e5; text-decoration: none; font-size: 14px; font-weight: 500;">Ver todos →</a>
                </div>
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 1px solid #edf2f7; color: #718096; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                            <th style="padding: 12px 8px;">Nome</th>
                            <th style="padding: 12px 8px;">Empresa</th>
                            <th style="padding: 12px 8px; text-align: center;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($lead = $resultadoLeads->fetchArray(SQLITE3_ASSOC)): ?>
                            <tr style="border-bottom: 1px solid #f7fafc; font-size: 14px;">
                                <td style="padding: 14px 8px; font-weight: 500; color: #2d3748;"><?php echo htmlspecialchars($lead['nome']); ?></td>
                                <td style="padding: 14px 8px; color: #718096;"><?php echo htmlspecialchars($lead['empresa']); ?></td>
                                <td style="padding: 14px 8px; text-align: center;">
                                    <?php
                                    $statusle = strtolower(trim($lead['estado']));
                                    if ($statusle === 'novo') { $bgL = '#e8f0fe'; $textL = '#1a73e8'; }
                                    elseif ($statusle === 'contactado') { $bgL = '#fef3c7'; $textL = '#b45309'; }
                                    else { $bgL = '#d1fae5'; $textL = '#065f46'; } // Convertido
                                    ?>
                                    <span style="background: <?php echo $bgL; ?>; color: <?php echo $textL; ?>; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase;">
                                        <?php echo htmlspecialchars($lead['estado']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        </section>
    </main>

</body>
</html>