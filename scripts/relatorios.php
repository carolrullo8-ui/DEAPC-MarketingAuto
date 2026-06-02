<?php
/*
    relatorios.php — MKT12, ADM03
    
    Calcula e devolve métricas de envolvimento.
    Suporta filtro por campanha e por período.
*/

require_once 'iniciarDB.php';
session_start();

if (!isset($_SESSION['id_utilizador'])) {
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Nao autenticado']);
    exit();
}

header('Content-Type: application/json; charset=utf-8');

$db          = getDB();
$id_campanha = (int)($_GET['id_campanha'] ?? 0);

if ($id_campanha <= 0) {
    // Se não foi especificada campanha, devolve resumo geral
    $res = $db->query("
        SELECT
            COUNT(DISTINCT c.id)    AS total_campanhas,
            COUNT(DISTINCT l.id)    AS total_leads,
            COUNT(cl.id)            AS total_envios,
            SUM(cl.aberto)          AS total_abertos,
            SUM(cl.clicou)          AS total_cliques
        FROM campanhas c
        LEFT JOIN campanhas_leads cl ON c.id = cl.id_campanha
        LEFT JOIN leads l            ON cl.id_lead = l.id
    ");

    $dados = $res->fetchArray(SQLITE3_ASSOC);

    // Calcular taxas percentuais
    $envios = $dados['total_envios'] ?? 0;
    $dados['taxa_abertura'] = $envios > 0
        ? round(($dados['total_abertos'] / $envios) * 100, 1)
        : 0;
    $dados['taxa_cliques'] = $envios > 0
        ? round(($dados['total_cliques'] / $envios) * 100, 1)
        : 0;

    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit();
}

// Métricas detalhadas de uma campanha específica
$stmt = $db->prepare("
    SELECT
        c.id,
        c.nome,
        c.estado,
        c.data_inicio,
        c.data_fim,
        COUNT(cl.id)   AS total_leads,
        SUM(cl.aberto) AS total_abertos,
        SUM(cl.clicou) AS total_cliques
    FROM campanhas c
    LEFT JOIN campanhas_leads cl ON c.id = cl.id_campanha
    WHERE c.id = :id
    GROUP BY c.id
");
$stmt->bindValue(':id', $id_campanha, SQLITE3_INTEGER);
$res     = $stmt->execute();
$campanha = $res->fetchArray(SQLITE3_ASSOC);

if (!$campanha) {
    echo json_encode(['erro' => 'Campanha nao encontrada']);
    exit();
}

// Calcular taxas
$total = $campanha['total_leads'] ?? 0;
$campanha['taxa_abertura'] = $total > 0
    ? round(($campanha['total_abertos'] / $total) * 100, 1)
    : 0;
$campanha['taxa_cliques'] = $total > 0
    ? round(($campanha['total_cliques'] / $total) * 100, 1)
    : 0;

// Leads com maior envolvimento
$stmt = $db->prepare("
    SELECT
        l.nome,
        l.email,
        l.empresa,
        l.estado,
        cl.aberto,
        cl.clicou,
        cl.data_abertura
    FROM campanhas_leads cl
    JOIN leads l ON cl.id_lead = l.id
    WHERE cl.id_campanha = :id
    ORDER BY cl.aberto DESC, cl.clicou DESC
    LIMIT 10
");
$stmt->bindValue(':id', $id_campanha, SQLITE3_INTEGER);
$res  = $stmt->execute();
$leads_envolvidos = [];

while ($linha = $res->fetchArray(SQLITE3_ASSOC)) {
    $leads_envolvidos[] = $linha;
}

$campanha['leads_envolvidos'] = $leads_envolvidos;

echo json_encode($campanha, JSON_UNESCAPED_UNICODE);
?>