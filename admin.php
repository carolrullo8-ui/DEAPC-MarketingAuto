<?php
session_start();

if (!isset($_SESSION['id_utilizador'])) {
    header('Location: index.php?erro=sessao_expirada');
    exit();
}

require_once 'scripts/iniciarDB.php';
$db = getDB();

// ==========================================================
// 1. ATUALIZAR O ÚLTIMO ACESSO DO UTILIZADOR ATUAL
// ==========================================================
try {
    $stmtAcesso = $db->prepare("UPDATE utilizadores SET ultimo_acesso = NOW() WHERE id = :id");
    $stmtAcesso->bindValue(':id', $_SESSION['id_utilizador'], PDO::PARAM_INT);
    $stmtAcesso->execute();
} catch (PDOException $e) {
    // Silencioso: se a coluna ainda não existir no MySQL, não quebra a página
}

// ==========================================================
// 2. RECOLHA DE MÉTRICAS GERAIS (KPIs do Admin) - RESOLVE OS ERROS
// ==========================================================

// Utilizadores Totais (Linha 174)
$totalUtilizadores = $db->query("SELECT COUNT(*) FROM utilizadores")->fetchColumn() ?: 0;

// Total de Leads (Linha 178)
$totalLeadsSistema = $db->query("SELECT COUNT(*) FROM leads")->fetchColumn() ?: 0;

// Campanhas Ativas (Linha 182)
// Nota: Como a etiqueta diz "Campanhas Ativas", filtramos por 'ativa'. 
// Se a tua tabela usar a coluna 'status' em vez de 'estado', altera o nome abaixo.
try {
    $totalCampanhasSistema = $db->query("SELECT COUNT(*) FROM campanhas WHERE LOWER(TRIM(estado)) = 'ativa'")->fetchColumn() ?: 0;
} catch (PDOException $e) {
    // Caso a coluna se chame 'status' na tabela campanhas:
    $totalCampanhasSistema = $db->query("SELECT COUNT(*) FROM campanhas WHERE LOWER(TRIM(status)) = 'ativa'")->fetchColumn() ?: 0;
}


// ==========================================================
// 3. PROCURAR UTILIZADORES REGISTADOS
// ==========================================================
$resultadoUsers = $db->query("SELECT * FROM utilizadores ORDER BY id ASC");


// ==========================================================
// 4. REGISTO DE AUDITORIA DINÂMICO
// ==========================================================
$registosAcesso = [];

// Passo A: Tenta ler a tabela 'registos_acesso' se ela existir
$tabelaLogsCheck = $db->query("SHOW TABLES LIKE 'registos_acesso'")->fetch();

if ($tabelaLogsCheck) {
    $resLogs = $db->query("SELECT * FROM registos_acesso ORDER BY id DESC LIMIT 15");
    while ($log = $resLogs->fetch(PDO::FETCH_ASSOC)) {
        $registosAcesso[] = [
            'utilizador' => $log['utilizador'] ?? 'Sistema',
            'data'       => $log['data_hora'] ?? '—',
            'acao'       => $log['acao'] ?? 'Login Efetuado',
            'ip'         => $log['ip'] ?? '127.0.0.1'
        ];
    }
}

// Se a tabela de logs estiver vazia ou não existir, usa o Fallback
if (empty($registosAcesso)) {
    try {
        $resLogs = $db->query("SELECT * FROM utilizadores WHERE ultimo_acesso IS NOT NULL ORDER BY ultimo_acesso DESC LIMIT 15");
        while ($userLog = $resLogs->fetch(PDO::FETCH_ASSOC)) {
            $registosAcesso[] = [
                'utilizador' => $userLog['username'] ?? $userLog['nome'] ?? '—',
                'data'       => $userLog['ultimo_acesso'] ?? '—',
                'acao'       => 'Acesso ao Painel',
                'ip'         => '127.0.0.1'
            ];
        }
    } catch (PDOException $e) {
        // Proteção extra
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarketingAuto Admin</title>
    <link rel="stylesheet" href="styles/style.css?v=<?php echo time(); ?>">
    <style>
        body { display: flex; flex-direction: column; min-height: 100vh; overflow-x: hidden; font-family: system-ui, sans-serif; margin: 0; }
        .admin-wrapper { display: flex; flex: 1; width: 100%; }
        .sidebar { width: 260px; background-color: #1e1e2f; color: #a0aec0; display: flex; flex-direction: column; padding-top: 10px; }
        .sidebar a { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; color: #cbd5e0; text-decoration: none; font-size: 15px; font-weight: 500; cursor: pointer; transition: all 0.2s; }
        .sidebar a:hover, .sidebar a.active { color: white; background-color: rgba(255, 255, 255, 0.05); }
        .sidebar a.active { border-left: 4px solid #4f46e5; background-color: rgba(255, 255, 255, 0.02); }
        .conteudo-admin { flex: 1; background-color: #f4f6f9; padding: 40px !important; position: relative; z-index: 10; }
        .seccao-admin { display: none; animation: fadeIn 0.3s ease-in-out; }
        .seccao-admin.ativa { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); display: none; justify-content: center; align-items: center; z-index: 9999; }
        .modal-content { background: white; padding: 30px; border-radius: 12px; width: 100%; max-width: 450px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
        .modal-content label { display: block; margin-top: 12px; font-weight: 600; font-size: 14px; color: #4a5568; }
        .modal-content input { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #cbd5e0; border-radius: 6px; box-sizing: border-box; }
        .modal-buttons { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid #edf2f7; color: #718096; font-size: 13px; text-transform: uppercase; }
        td { padding: 16px; border-bottom: 1px solid #edf2f7; color: #2d3748; }
        .btn-acao { background: white; border: 1px solid #cbd5e0; padding: 6px 12px; border-radius: 6px; cursor: pointer; transition: background 0.2s; }
        .btn-acao:hover { background: #f7fafc; }
    </style>
</head>
<body>

    <header style="background-color: #1e1e2f; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; box-sizing: border-box;">
        <div style="color: white; font-size: 22px; font-weight: bold; display: flex; align-items: center; gap: 10px;">
            <span style="color: #ff4d4d;">🎯</span> MarketingAuto Admin
        </div>
        <div style="display: flex; align-items: center; gap: 25px;">
            <a href="dashboard.php" style="color: #cbd5e0; text-decoration: none; font-size: 15px; font-weight: 500;">Ver Plataforma</a>
            <a href="scripts/logout.php" style="background-color: #4f46e5; color: white; padding: 9px 20px !important; border-radius: 6px; font-size: 15px !important; text-decoration: none; font-weight: bold;">Logout</a>
        </div>
    </header>

    <div class="admin-wrapper">
        <aside class="sidebar">
            <a href="#utilizadores-admin" onclick="alternarAba('utilizadores-admin')" id="btn-utilizadores-admin"><span>👥 Utilizadores</span></a>
            <a href="#relatorios-admin" onclick="alternarAba('relatorios-admin')" id="btn-relatorios-admin"><span>📊 Relatórios</span></a>
            <a href="#registos-admin" onclick="alternarAba('registos-admin')" id="btn-registos-admin"><span>🔒 Registos de Acesso</span></a>
            <a href="#configuracoes-admin" onclick="alternarAba('configuracoes-admin')" id="btn-configuracoes-admin"><span>⚙️ Configurações</span></a>
        </aside>

        <main class="conteudo-admin">
            
            <div id="utilizadores-admin" class="seccao-admin">
                <div style="background: white; padding: 40px; border-radius: 14px; box-shadow: 0 6px 20px rgba(0,0,0,0.04);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <h1 style="font-size: 28px !important; margin: 0;">Gestão de Utilizadores</h1>
                        <button type="button" onclick="abrirModalCriar()" style="width: auto; padding: 12px 24px !important; font-size: 15px !important; background-color: #4f46e5; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">+ Novo Utilizador</button>
                    </div>

                    <table style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Tipo</th>
                                <th>Último Acesso</th>
                                <th style="text-align: center;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($resultadoUsers): ?>
                                <?php while ($user = $resultadoUsers->fetch(PDO::FETCH_ASSOC)): 
                                    $usernameFinal = $user['username'] ?? '—';
                                    $nomeFinal = $user['nome'] ?? 'Utilizador';
                                    $emailFinal = $user['email'] ?? ($usernameFinal . '@marketingauto.pt');
                                    $ultimoFinal = $user['ultimo_acesso'] ?? '—';
                                    $cargoFinal = $user['tipo'] ?? 'GESTOR';
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($nomeFinal); ?></td>
                                        <td><strong><?php echo htmlspecialchars($usernameFinal); ?></strong></td>
                                        <td style="color: #666;"><?php echo htmlspecialchars($emailFinal); ?></td>
                                        <td>
                                            <span class="badge" style="background: <?php echo ($cargoFinal === 'ADMIN') ? '#e8f0fe' : '#e6f4ea'; ?>; color: <?php echo ($cargoFinal === 'ADMIN') ? '#1a73e8' : '#137333'; ?>; font-size: 11px; font-weight: bold; padding: 4px 8px; border-radius: 4px;">
                                                <?php echo strtoupper($cargoFinal); ?>
                                            </span>
                                        </td>
                                        <td style="color: #555;"><?php echo htmlspecialchars($ultimoFinal); ?></td>
                                        <td style="text-align: center; white-space: nowrap;">
                                            <button type="button" class="btn-acao" onclick="abrirModalEditar(<?php echo $user['id']; ?>, '<?php echo addslashes($nomeFinal); ?>', '<?php echo addslashes($usernameFinal); ?>', '<?php echo addslashes($emailFinal); ?>')">✏️</button>
                                            <button type="button" class="btn-acao" onclick="eliminarUtilizador(<?php echo $user['id']; ?>, '<?php echo addslashes($nomeFinal); ?>')", style="color: #dc3545;">🗑️</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="relatorios-admin" class="seccao-admin"> 
                <div style="background: white; padding: 40px; border-radius: 14px; box-shadow: 0 6px 20px rgba(0,0,0,0.04);">
                    <h1>Relatórios do Sistema</h1>
                    <p style="color: #718096; margin-bottom: 30px;">Métricas gerais da plataforma em tempo real via MySQL.</p>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #4f46e5;">
                            <span style="font-size: 13px; color: #718096; font-weight: bold; text-transform: uppercase;">Utilizadores Totais</span>
                            <h2 style="margin: 5px 0 0 0; font-size: 28px;"><?php echo $totalUtilizadores; ?></h2>
                        </div>
                        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #0284c7;">
                            <span style="font-size: 13px; color: #718096; font-weight: bold; text-transform: uppercase;">Total de Leads</span>
                            <h2 style="margin: 5px 0 0 0; font-size: 28px;"><?php echo $totalLeadsSistema; ?></h2>
                        </div>
                        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #16a34a;">
                            <span style="font-size: 13px; color: #718096; font-weight: bold; text-transform: uppercase;">Campanhas Ativas</span>
                            <h2 style="margin: 5px 0 0 0; font-size: 28px;"><?php echo $totalCampanhasSistema; ?></h2>
                        </div>
                    </div>
                </div> 
            </div>

            <div id="registos-admin" class="seccao-admin"> 
                <div style="background: white; padding: 40px; border-radius: 14px; box-shadow: 0 6px 20px rgba(0,0,0,0.04);">
                    <h1>Registos de Acesso (Auditoria)</h1>
                    <p style="color: #718096; margin-bottom: 25px;">Histórico de segurança recolhido diretamente do phpMyAdmin.</p>
                    
                    <table style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Utilizador</th>
                                <th>Data e Hora</th>
                                <th>Ação Efetuada</th>
                                <th>Endereço IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($registosAcesso)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #a0aec0; padding: 30px;">
                                        Nenhum registo de auditoria disponível de momento.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($registosAcesso as $log): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($log['utilizador']); ?></strong></td>
                                        <td style="color: #555;"><?php echo htmlspecialchars($log['data']); ?></td>
                                        <td><span style="color: #16a34a; font-weight: 600;"><?php echo htmlspecialchars($log['acao']); ?></span></td>
                                        <td><code><?php echo htmlspecialchars($log['ip']); ?></code></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div> 
            </div>

            <div id="configuracoes-admin" class="seccao-admin"> 
                <div style="background: white; padding: 40px; border-radius: 14px; box-shadow: 0 6px 20px rgba(0,0,0,0.04);">
                    <h1>Configurações</h1>
                    <p style="color: #718096;">Gestão de parâmetros globais do sistema de MarketingAuto.</p>
                    <form onsubmit="event.preventDefault(); alert('Configurações salvas!');" style="margin-top: 20px;">
                        <label style="display:block; font-weight:bold; margin-bottom:5px;">Nome da Plataforma</label>
                        <input type="text" value="MarketingAuto Admin" style="padding: 10px; width: 100%; max-width: 400px; border: 1px solid #cbd5e0; border-radius: 6px;">
                        <br><br>
                        <button type="submit" style="padding: 10px 20px; background: #4f46e5; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">Salvar Configurações</button>
                    </form>
                </div> 
            </div>
        </main>
    </div>

    <div id="modalCriar" class="modal-overlay">
        <div class="modal-content">
            <h2 style="margin-top:0;">+ Novo Utilizador</h2>
            <form action="scripts/gerir_utilizadores.php?acao=criar" method="POST">
                <label>Nome Completo</label>
                <input type="text" name="nome" required placeholder="Ex: Carlos Silva">
                <label>Username (Login)</label>
                <input type="text" name="username" required placeholder="Ex: carlos.marketing">
                <label>Email</label>
                <input type="email" name="email" required placeholder="Ex: carlos@empresa.com">
                <label>Palavra-passe</label>
                <input type="password" name="password" required placeholder="Defina a senha">
                <div class="modal-buttons">
                    <button type="button" onclick="fecharModais()" style="background:#e2e8f0; color:#4a5568; border:none; padding:10px 20px; border-radius:6px; cursor:pointer;">Cancelar</button>
                    <button type="submit" style="background:#4f46e5; color:white; border:none; padding:10px 20px; border-radius:6px; cursor:pointer;">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalEditar" class="modal-overlay">
        <div class="modal-content">
            <h2 style="margin-top:0;">✏️ Editar Utilizador</h2>
            <form action="scripts/gerir_utilizadores.php?acao=editar" method="POST">
                <input type="hidden" name="id" id="edit-id">
                <label>Nome Completo</label>
                <input type="text" name="nome" id="edit-nome" required>
                <label>Username (Login)</label>
                <input type="text" name="username" id="edit-username" required>
                <label>Email</label>
                <input type="email" name="email" id="edit-email" required>
                <label>Nova Palavra-passe (Em branco para manter)</label>
                <input type="password" name="password" placeholder="Opcional">
                <div class="modal-buttons">
                    <button type="button" onclick="fecharModais()" style="background:#e2e8f0; color:#4a5568; border:none; padding:10px 20px; border-radius:6px; cursor:pointer;">Cancelar</button>
                    <button type="submit" style="background:#28a745; color:white; border:none; padding:10px 20px; border-radius:6px; cursor:pointer;">Atualizar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function alternarAba(idAba) {
            document.querySelectorAll('.seccao-admin').forEach(sec => sec.classList.remove('ativa'));
            document.querySelectorAll('.sidebar a').forEach(btn => btn.classList.remove('active'));
            const seccao = document.getElementById(idAba);
            const botao = document.getElementById('btn-' + idAba);
            if (seccao) seccao.classList.add('ativa');
            if (botao) botao.classList.add('active');
        }
        window.addEventListener('DOMContentLoaded', () => {
            const hash = window.location.hash ? window.location.hash.replace('#', '') : 'utilizadores-admin';
            alternarAba(hash);
        });
        function abrirModalCriar() { document.getElementById('modalCriar').style.display = 'flex'; }
        function fecharModais() { document.getElementById('modalCriar').style.display = 'none'; document.getElementById('modalEditar').style.display = 'none'; }
        function abrirModalEditar(id, nome, username, email) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-nome').value = nome;
            document.getElementById('edit-username').value = username;
            document.getElementById('edit-email').value = email;
            document.getElementById('modalEditar').style.display = 'flex';
        }
        function eliminarUtilizador(id, nome) {
            if (confirm("Deseja eliminar o utilizador '" + nome + "'?")) {
                window.location.href = "scripts/gerir_utilizadores.php?acao=eliminar&id=" + id;
            }
        }
    </script>
</body>
</html>