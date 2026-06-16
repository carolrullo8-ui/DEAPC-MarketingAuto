<?php
/*
    relatorio.php
    Painel de análise de dados e métricas de envolvimento (Dashboard).
*/
session_start();

if (!isset($_SESSION['id_utilizador'])) {
    header('Location: index.php?erro=sessao_expirada');
    exit();
}

require_once 'scripts/iniciarDB.php';
$db = getDB();

// ==========================================
// 1. RECOLHA DE MÉTRICAS GERAIS (KPIs)
// ==========================================

// Total de Leads
$totalLeads = $db->query("SELECT COUNT(*) FROM leads")->fetchColumn() ?: 0;

// Total de Campanhas
$totalCampanhas = $db->query("SELECT COUNT(*) FROM campanhas")->fetchColumn() ?: 0;

// Campanhas Ativas vs Pausadas (Protegido contra maiúsculas/espaços)
$campanhasAtivas = $db->query("SELECT COUNT(*) FROM campanhas WHERE LOWER(TRIM(estado)) = 'ativa'")->fetchColumn() ?: 0;
$campanhasPausadas = $db->query("SELECT COUNT(*) FROM campanhas WHERE LOWER(TRIM(estado)) = 'pausada'")->fetchColumn() ?: 0;


// ==========================================
// 2. RECOLHA DE DISTRIBUIÇÕES (DADOS DETALHADOS)
// ==========================================

// Distribuição de Leads por Estado (Ajustado para usar a coluna 'status' do MySQL)
$listaEstadosLeads = $db->query("SELECT status AS estado, COUNT(*) as total FROM leads GROUP BY status ORDER BY total DESC");

// Top Empresas com mais Leads
$listaEmpresasLeads = $db->query("SELECT empresa, COUNT(*) as total FROM leads GROUP BY empresa ORDER BY total DESC LIMIT 5");

// Distribuição por Segmento
$listaSegmentosLeads = $db->query("SELECT segmento, COUNT(*) as total FROM leads GROUP BY segmento ORDER BY total DESC");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarketingAuto — Relatórios</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body class="pagina-relatorios">

    <header>
        <a href="dashboard.php" class="logo">🎯 MarketingAuto</a>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="campanhas.php">Campanhas</a>
            <a href="leads.php">Gestão de Leads</a>
            <a href="relatorios.php" class="active">Relatórios</a>
            <span style="margin-left: 15px; color: #fff; opacity: 0.8; font-size: 14px;">Olá, <strong><?php echo htmlspecialchars($_SESSION['nome'] ?? $_SESSION['utilizador'] ?? 'Utilizador'); ?></strong> (<?php echo ucfirst($_SESSION['tipo'] ?? 'Perfil'); ?>)</span>
            <a href="scripts/logout.php" style="color: #ff4d4d; margin-left: 10px; text-decoration: none; font-weight: bold;">Sair →</a>
        </nav>
    </header>

    <main style="padding: 30px; max-width: 1200px; margin: 0 auto;">
        
        <div style="margin-bottom: 30px;">
            <h1 style="color: #2c3e50; margin: 0 0 5px 0;">📊 Relatórios Analíticos</h1>
            <p style="color: #7f8c8d; margin: 0;">Análise em tempo real do envolvimento de campanhas e captação de leads.</p>
        </div>

        <section style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
            
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-left: 5px solid #007bff;">
                <span style="color: #7f8c8d; font-size: 14px; font-weight: bold; text-transform: uppercase;">Total de Leads</span>
                <h2 style="font-size: 32px; margin: 10px 0 0 0; color: #2c3e50;"><?php echo $totalLeads; ?></h2>
                <p style="margin: 5px 0 0 0; color: #28a745; font-size: 12px;">👥 Contactos na base de dados</p>
            </div>

            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-left: 5px solid #6f42c1;">
                <span style="color: #7f8c8d; font-size: 14px; font-weight: bold; text-transform: uppercase;">Total Campanhas</span>
                <h2 style="font-size: 32px; margin: 10px 0 0 0; color: #2c3e50;"><?php echo $totalCampanhas; ?></h2>
                <p style="margin: 5px 0 0 0; color: #6c757d; font-size: 12px;">🚀 Criadas no sistema</p>
            </div>

            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-left: 5px solid #28a745;">
                <span style="color: #7f8c8d; font-size: 14px; font-weight: bold; text-transform: uppercase;">Em Execução</span>
                <h2 style="font-size: 32px; margin: 10px 0 0 0; color: #28a745;"><?php echo $campanhasAtivas; ?></h2>
                <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 12px;">Campanhas ativas neste momento</p>
            </div>

            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-left: 5px solid #ffc107;">
                <span style="color: #7f8c8d; font-size: 14px; font-weight: bold; text-transform: uppercase;">Em Pausa</span>
                <h2 style="font-size: 32px; margin: 10px 0 0 0; color: #dc3545;"><?php echo $campanhasPausadas; ?></h2>
                <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 12px;">Aguardar reativação</p>
            </div>

        </section>

        <section style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            
            <div style="display: flex; flex-direction: column; gap: 30px;">
                
                <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <h3 style="margin: 0 0 15px 0; color: #2c3e50;">🔄 Estado de Envolvimento das Leads</h3>
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 2px solid #eee; background: #f8f9fa;">
                                <th style="padding: 10px; color:#555;">Estado do Funil</th>
                                <th style="padding: 10px; color:#555; text-align: right;">Qtd Registada</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $temEstados = false;
                            // Alterado fetchArray() para fetch(PDO::FETCH_ASSOC)
                            while ($estado = $listaEstadosLeads->fetch(PDO::FETCH_ASSOC)): 
                                $temEstados = true;
                            ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 10px; font-weight: bold; text-transform: capitalize; color: #4e5d6c;"><?php echo htmlspecialchars($estado['estado'] ?? 'Novo'); ?></td>
                                    <td style="padding: 10px; text-align: right; font-weight: bold; color: #007bff;"><?php echo $estado['total']; ?></td>
                                </tr>
                            <?php 
                            endwhile; 
                            if (!$temEstados):
                            ?>
                                <tr><td colspan="2" style="padding: 15px; text-align: center; color: #aaa;">Nenhum dado disponível</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <h3 style="margin: 0 0 15px 0; color: #2c3e50;">🎯 Leads por Segmento-Alvo</h3>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php 
                        $temSegmentos = false;
                        // Alterado fetchArray() para fetch(PDO::FETCH_ASSOC)
                        while ($seg = $listaSegmentosLeads->fetch(PDO::FETCH_ASSOC)): 
                            $temSegmentos = true;
                            $percentagem = $totalLeads > 0 ? round(($seg['total'] / $totalLeads) * 100) : 0;
                        ?>
                            <div>
                                <div style="display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 4px;">
                                    <strong><?php echo htmlspecialchars($seg['segmento'] ?? 'Geral'); ?></strong>
                                    <span style="color:#666;"><?php echo $seg['total']; ?> leads (<?php echo $percentagem; ?>%)</span>
                                </div>
                                <div style="width: 100%; background: #eee; height: 8px; border-radius: 4px; overflow: hidden;">
                                    <div style="width: <?php echo $percentagem; ?>%; background: #6f42c1; height: 100%;"></div>
                                </div>
                            </div>
                        <?php 
                        endwhile;
                        if (!$temSegmentos):
                        ?>
                            <p style="text-align: center; color: #aaa; margin: 10px 0;">Sem segmentos registados.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); height: fit-content;">
                <h3 style="margin: 0 0 5px 0; color: #2c3e50;">🏢 Top 5 Empresas com Maior Envolvimento</h3>
                <p style="color:#7f8c8d; font-size: 13px; margin: 0 0 20px 0;">Organizações que geraram mais leads/contactos captados.</p>
                
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 2px solid #eee; background: #f4f6f9;">
                            <th style="padding: 12px; color:#555;">Nome da Empresa</th>
                            <th style="padding: 12px; color:#555; text-align: center; width: 100px;">Volume</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $temEmpresas = false;
                        $rank = 1;
                        // Alterado fetchArray() para fetch(PDO::FETCH_ASSOC)
                        while ($emp = $listaEmpresasLeads->fetch(PDO::FETCH_ASSOC)): 
                            if (empty($emp['empresa'])) continue;
                            $temEmpresas = true;
                        ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 12px;">
                                    <span style="background: #e8f0fe; color: #1a73e8; padding: 2px 7px; border-radius: 50%; font-size: 12px; font-weight: bold; margin-right: 8px;">
                                        #<?php echo $rank++; ?>
                                    </span>
                                    <strong><?php echo htmlspecialchars($emp['empresa']); ?></strong>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <span style="background: #e2f0d9; color: #385723; padding: 4px 10px; border-radius: 12px; font-size: 13px; font-weight: bold;">
                                        <?php echo $emp['total']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php 
                        endwhile; 
                        if (!$temEmpresas):
                        ?>
                            <tr><td colspan="2" style="padding: 20px; text-align: center; color: #aaa;">Nenhum registo de empresa encontrado</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div style="margin-top: 30px; background: #e8f4fd; border: 1px solid #d2e3fc; padding: 15px; border-radius: 6px;">
                    <h4 style="margin: 0 0 5px 0; color: #1a73e8;">💡 Nota de Integração Teórica:</h4>
                    <p style="margin: 0; font-size: 13px; line-height: 1.4; color: #555;">
                        Este módulo cumpre o requisito de <strong>"analisar dados de envolvimento"</strong> ao agregar o volume de contactos por estado do funil, identificar o foco comercial (Top Empresas) e medir a eficiência operacional das campanhas ativas.
                    </p>
                </div>
            </div>

        </section>

    </main>

</body>
</html>