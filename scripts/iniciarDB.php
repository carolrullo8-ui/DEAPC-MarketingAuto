<?php
/*
    iniciarDB.php
    
    Cria a base de dados SQLite3 e todas as tabelas
    necessárias ao MarketingAuto.
    
    Incluído com require_once em todos os outros scripts
    para garantir que a BD existe antes de ser usada.
*/

// Caminho para o ficheiro da base de dados
// __DIR__ devolve o diretório onde este ficheiro está
define('DB_PATH', __DIR__ . '/../database/marketingauto.db');

function getDB() {
    
    // Criar o diretório se não existir
    if (!is_dir(dirname(DB_PATH))) {
        mkdir(dirname(DB_PATH), 0755, true);
    }
    
    // Abrir/criar a base de dados SQLite3
    $db = new SQLite3(DB_PATH);
    
    // Ativar verificação de chaves estrangeiras
    $db->exec('PRAGMA foreign_keys = ON;');
    
    // ---- CRIAR TABELAS ----
    
    // TABELA: utilizadores
    $db->exec("
        CREATE TABLE IF NOT EXISTS utilizadores (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            username        TEXT    NOT NULL UNIQUE,
            password        TEXT    NOT NULL,
            nome            TEXT    NOT NULL,
            email           TEXT    NOT NULL,
            tipo            TEXT    NOT NULL DEFAULT 'gestor',
            ultimo_acesso   TEXT,
            data_registo    TEXT    NOT NULL DEFAULT (datetime('now'))
        );
    ");
    
    // TABELA: campanhas
    $db->exec("
        CREATE TABLE IF NOT EXISTS campanhas (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            nome            TEXT    NOT NULL,
            descricao       TEXT,
            publico_alvo    TEXT    NOT NULL,
            data_inicio     TEXT    NOT NULL,
            data_fim        TEXT,
            estado          TEXT    NOT NULL DEFAULT 'rascunho',
            id_criador      INTEGER NOT NULL,
            data_criacao    TEXT    NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (id_criador) REFERENCES utilizadores(id)
        );
    ");
    /*
        estado pode ser: rascunho, ativa, pausada, concluida
        id_criador liga a campanha ao gestor que a criou
    */
    
    // TABELA: leads
    $db->exec("
        CREATE TABLE IF NOT EXISTS leads (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            nome            TEXT    NOT NULL,
            email           TEXT    NOT NULL UNIQUE,
            empresa         TEXT,
            telefone        TEXT,
            segmento        TEXT    NOT NULL,
            estado          TEXT    NOT NULL DEFAULT 'novo',
            data_registo    TEXT    NOT NULL DEFAULT (datetime('now'))
        );
    ");
    /*
        estado pode ser: novo, contactado, qualificado, convertido, perdido
        email UNIQUE: não pode haver dois leads com o mesmo email
    */
    
    // TABELA: campanhas_leads
    // Tabela de associação entre campanhas e leads
    // Permite rastrear o envolvimento de cada lead com cada campanha
    $db->exec("
        CREATE TABLE IF NOT EXISTS campanhas_leads (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            id_campanha     INTEGER NOT NULL,
            id_lead         INTEGER NOT NULL,
            data_envio      TEXT,
            aberto          INTEGER NOT NULL DEFAULT 0,
            clicou          INTEGER NOT NULL DEFAULT 0,
            data_abertura   TEXT,
            FOREIGN KEY (id_campanha) REFERENCES campanhas(id),
            FOREIGN KEY (id_lead)     REFERENCES leads(id),
            UNIQUE(id_campanha, id_lead)
            -- Um lead só pode estar associado uma vez a cada campanha
        );
    ");
    
    // ---- DADOS INICIAIS ----
    
    // Criar admin padrão se não existir nenhum
    $res  = $db->query("SELECT COUNT(*) as total FROM utilizadores WHERE tipo='admin'");
    $linha = $res->fetchArray();
    
    if ($linha['total'] == 0) {
        $hashAdmin = password_hash('admin123', PASSWORD_DEFAULT);
        $db->exec("
            INSERT INTO utilizadores (username, password, nome, email, tipo)
            VALUES ('admin', '$hashAdmin', 'Administrador', 'admin@marketingauto.pt', 'admin')
        ");
        
        // Criar também um gestor de exemplo
        $hashGestor = password_hash('gestor123', PASSWORD_DEFAULT);
        $db->exec("
            INSERT INTO utilizadores (username, password, nome, email, tipo)
            VALUES ('gestor1', '$hashGestor', 'Gestor Principal', 'gestor@marketingauto.pt', 'gestor')
        ");
    }
    
    // Criar campanhas de exemplo se não existirem
    $res  = $db->query("SELECT COUNT(*) as total FROM campanhas");
    $linha = $res->fetchArray();
    
    if ($linha['total'] == 0) {
        $db->exec("
            INSERT INTO campanhas (nome, descricao, publico_alvo, data_inicio, data_fim, estado, id_criador)
            VALUES
            ('Campanha Verao 2025', 'Campanha de verao para empresas', 'empresas', '2025-06-01', '2025-08-31', 'ativa', 2),
            ('Newsletter Junho', 'Newsletter mensal de junho', 'todos', '2025-06-01', '2025-06-30', 'pausada', 2),
            ('Promocao PMEs', 'Promocao especial para PMEs', 'pmes', '2025-07-15', NULL, 'rascunho', 2),
            ('Black Friday Antecipado', 'Campanha antecipada de black friday', 'startups', '2025-11-01', '2025-11-30', 'ativa', 2)
        ");
    }
    
    // Criar leads de exemplo se não existirem
    $res  = $db->query("SELECT COUNT(*) as total FROM leads");
    $linha = $res->fetchArray();
    
    if ($linha['total'] == 0) {
        $db->exec("
            INSERT INTO leads (nome, email, empresa, segmento, estado)
            VALUES
            ('Joao Silva',    'joao@xpto.pt',    'XPTO Lda',   'empresa', 'novo'),
            ('Ana Costa',     'ana@startup.pt',  'Startup ABC', 'startup', 'contactado'),
            ('Pedro Ferreira','pedro@pme.pt',    'PME XYZ',    'pme',     'convertido'),
            ('Maria Santos',  'maria@tech.pt',   'Tech SA',    'empresa', 'novo'),
            ('Carlos Lima',   'carlos@abc.pt',   'ABC Lda',    'startup', 'qualificado')
        ");
    }
    
    return $db;
}
?>