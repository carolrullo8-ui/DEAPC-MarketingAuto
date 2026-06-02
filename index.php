<?php
/*
    index.php
    Página de entrada e formulário de autenticação para o MarketingAuto.
    
    Verifica se o utilizador já tem sessão ativa e exibe mensagens 
    de erro dinâmicas baseadas nas tentativas de login anteriores.
*/
session_start();

// 1. SE JÁ ESTIVER AUTENTICADO: Redireciona diretamente para evitar novo login
if (isset($_SESSION['id_utilizador'])) {
    if ($_SESSION['tipo'] === 'admin') {
        header('Location: admin.php');
    } else {
        header('Location: dashboard.php');
    }
    exit();
}

// 2. CAPTURA DE ERROS DA URL: Converte os códigos em mensagens amigáveis
$mensagemErro = "";
if (isset($_GET['erro'])) {
    switch ($_GET['erro']) {
        case 'campos_vazios':
            $mensagemErro = "Por favor, preencha o utilizador e a palavra-passe.";
            break;
        case 'credenciais_invalidas':
            $mensagemErro = "Utilizador ou palavra-passe incorretos.";
            break;
        case 'dados_invalidos':
            $mensagemErro = "Ocorreu um erro no envio dos dados. Tente novamente.";
            break;
        default:
            $mensagemErro = "Ocorreu um erro inesperado. Tente novamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarketingAuto — Login</title>
    <link rel="stylesheet" href="styles/style.css">
    </head>

<body class="pagina-login">
<header class="header-login">
    <div class="logo">
            🎯 <span>MarketingAuto</span>
            </div>

    </header>

    <main class="main-login">
    <div class="caixa-login">
        <div class="login-titulo">
                <h1>🎯 MarketingAuto</h1>
                <p>Plataforma de Automatização de Marketing</p>
                </div>

            <form class="form-login" action="scripts/login.php" method="POST">
                
                <?php if (!empty($mensagemErro)): ?>
                    <div class="alerta-erro" style="background-color: #f8d7da; color: #721c24; padding: 12px; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 20px; font-size: 14px; text-align: center; font-weight: bold;">
                        <?php echo htmlspecialchars($mensagemErro); ?>
                    </div>
                <?php endif; ?>

                <div class="form-grupo">
                <label for="username">Username</label>
                    <input type="text" 
                           id="username" 
                           name="username"
                           placeholder="Ex: gestor.marketing"
                           required>
                    </div>

                <div class="form-grupo">
                    <label for="password">Password</label>

                    <input type="password" 
                           id="password" 
                           name="password"
                           placeholder="A tua password"
                           required>
                    </div>

                <button type="submit" class="btn-primario btn-login">
                    Entrar →
                </button>

                <p class="login-nota">
                    Acesso restrito a utilizadores autorizados.
                </p>
                </form>
            </div>
        </main>

    <footer class="footer-login">
        <p>© 2025 MarketingAuto</p>
    </footer>

    <script src="scripts/main.js"></script>
    </body>
</html>