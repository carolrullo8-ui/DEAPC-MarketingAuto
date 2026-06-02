<?php
/*
    dashboard.php — MKT02
    Página principal após login.
    Mostra métricas gerais e listas resumo de campanhas e leads.
*/

require_once 'scripts/iniciarDB.php';
session_start();

// Redirecionar para login se não estiver autenticado
if (!isset($_SESSION['id_utilizador'])) {
    header('Location: index.html');
    exit();
}

$db = getDB();

// Carregar métricas gerais
$res             = $db->query("SELECT COUNT(*) as total FROM campanhas WHERE estado='ativa'");
$campanhas_ativas = $res->fetchArray()['total'];

$res         = $db->query("SELECT COUNT(*) as total FROM leads");
$total_leads  = $res->fetchArray()['total'];

$res    = $db->query("SELECT COUNT(*) as total FROM campanhas_leads");
$envios  = $res->fetchArray()['total'];

$res    = $db->query("SELECT SUM(aberto) as total FROM campanhas_leads");
$abertos = $res->fetchArray()['total'] ?? 0;

// Taxa de abertura geral
$taxa_abertura = $envios > 0 ? round(($abertos / $envios) * 100, 1) : 0;

$res    = $db->query("SELECT SUM(clicou) as total FROM campanhas_leads");
$cliques = $res->fetchArray()['total'] ?? 0;
$taxa_cliques = $envios > 0 ? round(($cliques / $envios) * 100, 1) : 0;

// Últimas 4 campanhas
$res_campanhas = $db->query("
    SELECT c.*, COUNT(cl.id) as total_leads
    FROM campanhas c
    LEFT JOIN campanhas_leads cl ON c.id = cl.id_campanha
    GROUP BY c.id
    ORDER BY c.data_criacao DESC
    LIMIT 4
");
$campanhas = [];
while ($linha = $res_campanhas->fetchArray(SQLITE3_ASSOC)) {
    $campanhas[] = $linha;
}

// Últimos 4 leads
$res_leads = $db->query("SELECT * FROM leads ORDER BY data_registo DESC LIMIT 4");
$leads     = [];
while ($linha = $res_leads->fetchArray(SQLITE3_ASSOC)) {
    $leads[] = $linha;
}
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
        <button class="menu-toggle" onclick="toggleMenu()">☰</button>
        <nav id="menu-principal">
            <a href="dashboard.php" class="ativo">Dashboard</a>
            <a href="campanhas.php">Campanhas</a>
            <a href="leads.php">Leads</a>
            <a href="relatorios.php">Relatórios</a>
            <?php if ($_SESSION['tipo'] === 'admin'): ?>
                <a href="admin.php">Admin</a>
            <?php endif; ?>
            <span class="nav-utilizador">
                Olá, <strong><?= htmlspecialchars($_SESSION['nome']) ?></strong>
            </span>
            <a href="scripts/logout.php" class="btn-primario">Logout</a>
        </nav>
    </header>

    <main>
        <div class="pagina-conteudo">

            <div class="pagina-titulo">
                <div>
                    <h1>Dashboard</h1>
                    <p>Visão geral da tua atividade de marketing</p>
                </div>
            </div>

            <!-- MÉTRICAS GERAIS -->
            <section class="metricas-grid">

                <div class="card-metrica">
                    <div class="metrica-icone">📢</div>
                    <div class="metrica-info">
                        <span class="metrica-valor"><?= $campanhas_ativas ?></span>
                        <span class="metrica-label">Campanhas Ativas</span>
                    </div>
                </div>

                <div class="card-metrica">
                    <div class="metrica-icone">👥</div>
                    <div class="metrica-info">
                        <span class="metrica-valor"><?= $total_leads ?></span>
                        <span class="metrica-label">Total de Leads</span>
                    </div>
                </div>

                <div class="card-metrica">
                    <div class="metrica-icone">📧</div>
                    <div class="metrica-info">
                        <span class="metrica-valor"><?= $taxa_abertura ?>%</span>
                        <span class="metrica-label">Taxa de Abertura</span>
                    </div>
                </div>

                <div class="card-metrica">
                    <div class="metrica-icone">🖱️</div>
                    <div class="metrica-info">
                        <span class="metrica-valor"><?= $taxa_cliques ?>%</span>
                        <span class="metrica-label">Taxa de Cliques</span>
                    </div>
                </div>

            </section>

            <!-- CAMPANHAS + LEADS RECENTES -->
            <div class="dashboard-grid">

                <!-- CAMPANHAS RECENTES -->
                <section class="card-secao">
                    <div class="secao-header">
                        <h2>Campanhas Recentes</h2>
                        <a href="campanhas.php" class="link-ver-todas">Ver todas →</a>
                    </div>
                    <table class="tabela-dashboard">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Estado</th>
                                <th>Leads</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campanhas as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['nome']) ?></td>
                                <td>
                                    <span class="estado <?= htmlspecialchars($c['estado']) ?>">
                                        <?= htmlspecialchars($c['estado']) ?>
                                    </span>
                                </td>
                                <td><?= $c['total_leads'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>

                <!-- LEADS RECENTES -->
                <section class="card-secao">
                    <div class="secao-header">
                        <h2>Leads Recentes</h2>
                        <a href="leads.php" class="link-ver-todas">Ver todos →</a>
                    </div>
                    <table class="tabela-dashboard">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Empresa</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leads as $l): ?>
                            <tr>
                                <td><?= htmlspecialchars($l['nome']) ?></td>
                                <td><?= htmlspecialchars($l['empresa'] ?: '—') ?></td>
                                <td>
                                    <span class="estado <?= htmlspecialchars($l['estado']) ?>">
                                        <?= htmlspecialchars($l['estado']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>

            </div>

        </div>
    </main>

    <footer>
        <p>© 2025 MarketingAuto</p>
    </footer>

    <script src="scripts/main.js"></script>
</body>
</html>