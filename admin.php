<?php
/*
    admin.php — ADM01 / Alínea 8c
    Página de Gestão Administrativa e Auditoria de Acessos.
    
    Protegida por sessão: Apenas utilizadores do tipo 'admin' têm permissão de acesso.
*/
session_start();

// 1. CONTROLO DE ACESSO: Verifica se está logado E se é administrador
if (!isset($_SESSION['id_utilizador']) || $_SESSION['tipo'] !== 'admin') {
    // Se não for admin, limpa a sessão por segurança e expulsa para o login
    header('Location: index.php?erro=sessao_expirada');
    exit();
}

// 2. LIGAÇÃO À BASE DE DADOS
require_once 'scripts/iniciarDB.php';
$db = getDB();

// Consulta 1: Todos os utilizadores cadastrados (para a Secção de Gestão)
$resultadoUtilizadores = $db->query("SELECT nome, username, email, tipo, ultimo_acesso FROM utilizadores ORDER BY nome ASC");

// Consulta 2: Utilizadores que já efetuaram login (para a Secção de Registos de Acesso)
$resultadoAcessos = $db->query("SELECT username, ultimo_acesso FROM utilizadores WHERE ultimo_acesso IS NOT NULL ORDER BY ultimo_acesso DESC");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarketingAuto — Administração</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>

    <header>
        <a href="dashboard.php" class="logo">🎯 MarketingAuto Admin</a>
        <nav>
            <a href="dashboard.php">Ver Plataforma</a>
            <a href="scripts/logout.php" class="btn-primario">Logout</a>
        </nav>
    </header>

    <div class="admin-layout">

        <aside class="admin-sidebar">
            <a href="#utilizadores" class="ativo">👥 Utilizadores</a>
            <a href="#relatorios-admin">📊 Relatórios</a>
            <a href="#acessos">🔐 Registos de Acesso</a>
            <a href="#configuracoes">⚙️ Configurações</a>
        </aside>

        <main class="admin-conteudo">

            <section id="utilizadores">
                <div class="pagina-titulo">
                    <h2>Gestão de Utilizadores</h2>
                    <button class="btn-primario">+ Novo Utilizador</button>
                </div>
                <br>
                <table class="tabela-completa">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Último Acesso</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $resultadoUtilizadores->fetchArray(SQLITE3_ASSOC)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['nome']); ?></td>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <td><?php echo !empty($user['email']) ? htmlspecialchars($user['email']) : ($user['username'] . '@marketingauto.pt'); ?></td>
                                <td>
                                    <?php if ($user['tipo'] === 'admin'): ?>
                                        <span class="estado novo">Admin</span>
                                    <?php else: ?>
                                        <span class="estado ativa">Gestor</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-family: monospace;">
                                    <?php echo !empty($user['ultimo_acesso']) ? htmlspecialchars($user['ultimo_acesso']) : "Nunca acedeu"; ?>
                                </td>
                                <td class="acoes-celula">
                                    <button class="btn-acao" title="Editar">✏️</button>
                                    <button class="btn-acao btn-acao-perigo" title="Remover">🗑</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </section>

            <br><br>

            <section id="acessos">
                <h2>Registos de Acesso</h2>
                <br>
                <table class="tabela-completa">
                    <thead>
                        <tr>
                            <th>Utilizador</th>
                            <th>Data e Hora</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $haAcessos = false;
                        while ($acesso = $resultadoAcessos->fetchArray(SQLITE3_ASSOC)): 
                            $haAcessos = true;
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($acesso['username']); ?></strong></td>
                                <td style="font-family: monospace;"><?php echo htmlspecialchars($acesso['ultimo_acesso']); ?></td>
                                <td><span style="color: #28a745; font-weight: bold;">Login Efetuado</span></td>
                            </tr>
                        <?php endwhile; ?>
                        
                        <?php if (!$haAcessos): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: #888; padding: 15px;">Nenhum registo de atividade guardado até ao momento.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

        </main>
    </div>

    <footer>
        <p>© 2026 MarketingAuto</p>
    </footer>

</body>
</html>