<?php
/*
    listaCampanhas.php — MKT06
    
    Lê as campanhas da BD com filtros opcionais.
    Devolve JSON com a lista de campanhas e o número
    de leads associados a cada uma.
*/

require_once 'iniciarDB.php';
session_start();

// Verificar autenticação
if (!isset($_SESSION['id_utilizador'])) {
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Nao autenticado']);
    exit();
}

header('Content-Type: application/json; charset=utf-8');

$db = getDB();

// Query base com JOIN para contar os leads de cada campanha
$query = "
    SELECT
        c.*,
        COUNT(cl.id) as total_leads,
        SUM(cl.aberto)  as total_abertos,
        SUM(cl.clicou)  as total_cliques
    FROM campanhas c
    LEFT JOIN campanhas_leads cl ON c.id = cl.id_campanha
    WHERE 1=1
";
/*
    LEFT JOIN inclui campanhas mesmo sem leads associados
    COUNT(cl.id) conta o número de leads por campanha
    SUM(cl.aberto) soma os emails abertos
*/

$params = [];

// Filtro por estado
if (!empty($_GET['estado'])) {
    $query .= " AND c.estado = :estado";
    $params[':estado'] = htmlspecialchars($_GET['estado']);
}

// Filtro por público-alvo
if (!empty($_GET['publico'])) {
    $query .= " AND c.publico_alvo = :publico";
    $params[':publico'] = htmlspecialchars($_GET['publico']);
}

// Pesquisa por nome
if (!empty($_GET['pesquisa'])) {
    $query .= " AND c.nome LIKE :pesquisa";
    $params[':pesquisa'] = '%' . htmlspecialchars($_GET['pesquisa']) . '%';
}

// Agrupar por campanha (necessário por causa do COUNT e SUM)
$query .= " GROUP BY c.id ORDER BY c.data_criacao DESC";

$stmt = $db->prepare($query);

foreach ($params as $placeholder => $valor) {
    $stmt->bindValue($placeholder, $valor, SQLITE3_TEXT);
}

$resultado  = $stmt->execute();
$campanhas  = [];

while ($linha = $resultado->fetchArray(SQLITE3_ASSOC)) {
    // Calcular taxa de abertura
    if ($linha['total_leads'] > 0) {
        $linha['taxa_abertura'] = round(
            ($linha['total_abertos'] / $linha['total_leads']) * 100, 1
        );
        $linha['taxa_cliques'] = round(
            ($linha['total_cliques'] / $linha['total_leads']) * 100, 1
        );
    } else {
        $linha['taxa_abertura'] = 0;
        $linha['taxa_cliques']  = 0;
    }
    $campanhas[] = $linha;
}

echo json_encode($campanhas, JSON_UNESCAPED_UNICODE);
?>