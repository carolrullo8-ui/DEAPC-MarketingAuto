<?php
/*
    listaLeads.php — MKT09, MKT10
    
    Lê os leads da BD com filtros opcionais por segmento,
    estado e pesquisa de texto. Devolve JSON.
*/

require_once 'iniciarDB.php';
session_start();

if (!isset($_SESSION['id_utilizador'])) {
    header('Content-Type: application/json');
    echo json_encode(['erro' => 'Nao autenticado']);
    exit();
}

header('Content-Type: application/json; charset=utf-8');

$db    = getDB();
$query = "SELECT * FROM leads WHERE 1=1";
$params = [];

// Filtro por segmento
if (!empty($_GET['segmento'])) {
    $query .= " AND segmento = :segmento";
    $params[':segmento'] = htmlspecialchars($_GET['segmento']);
}

// Filtro por estado
if (!empty($_GET['estado'])) {
    $query .= " AND estado = :estado";
    $params[':estado'] = htmlspecialchars($_GET['estado']);
}

// Pesquisa por nome, email ou empresa
if (!empty($_GET['pesquisa'])) {
    $query .= " AND (nome LIKE :pesquisa OR email LIKE :pesquisa OR empresa LIKE :pesquisa)";
    $params[':pesquisa'] = '%' . htmlspecialchars($_GET['pesquisa']) . '%';
}

$query .= " ORDER BY data_registo DESC";

$stmt = $db->prepare($query);
foreach ($params as $placeholder => $valor) {
    $stmt->bindValue($placeholder, $valor, SQLITE3_TEXT);
}

$resultado = $stmt->execute();
$leads     = [];

while ($linha = $resultado->fetchArray(SQLITE3_ASSOC)) {
    $leads[] = $linha;
}

echo json_encode($leads, JSON_UNESCAPED_UNICODE);
?>