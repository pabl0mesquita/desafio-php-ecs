<?php
require_once __DIR__.'/../vendor/autoload.php';

use Core\Database\Connection;
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 1));

// safeLoad() não falha se o .env não existir — em produção (ECS) as variáveis
// vêm do ambiente do container, não de um arquivo .env dentro da imagem.
$dotenv->safeLoad();

$con = Connection::getInstance();
$isConnected = (bool) $con;

$statusClass = $isConnected ? 'is-online' : 'is-offline';
$statusLabel = $isConnected ? 'Conexão Ativa' : 'Conexão Inativa';
$statusMessage = $isConnected
    ? 'A aplicação conseguiu se conectar ao banco de dados com sucesso.'
    : 'Não foi possível estabelecer conexão com o banco de dados.';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Status da Aplicação</title>
<style>
    :root {
        --bg-gradient-start: #020617;
        --bg-gradient-end: #0f172a;
        --card-bg: #1e293b;
        --card-border: rgba(255, 255, 255, 0.08);
        --text-main: #f1f5f9;
        --text-muted: #94a3b8;
        --online: #4ade80;
        --online-bg: rgba(74, 222, 128, 0.15);
        --offline: #f87171;
        --offline-bg: rgba(248, 113, 113, 0.15);
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        background: linear-gradient(135deg, var(--bg-gradient-start), var(--bg-gradient-end));
        padding: 24px;
    }

    .card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 16px;
        box-shadow: 0 20px 45px rgba(0, 0, 0, 0.45);
        padding: 40px 32px;
        max-width: 420px;
        width: 100%;
        text-align: center;
    }

    .card h1 {
        font-size: 1.5rem;
        margin: 0 0 4px;
        color: var(--text-main);
    }

    .card .subtitle {
        margin: 0 0 28px;
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    .status-button {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 14px 28px;
        border-radius: 999px;
        font-weight: 600;
        font-size: 1rem;
        border: none;
        cursor: default;
        user-select: none;
        transition: transform 0.15s ease;
    }

    .status-button:hover {
        transform: scale(1.03);
    }

    .status-button.is-online {
        background: var(--online-bg);
        color: var(--online);
    }

    .status-button.is-offline {
        background: var(--offline-bg);
        color: var(--offline);
    }

    .status-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .status-button.is-online .status-dot {
        background: var(--online);
        box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.6);
        animation: pulse-online 1.8s infinite;
    }

    .status-button.is-offline .status-dot {
        background: var(--offline);
        box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.6);
        animation: pulse-offline 1.8s infinite;
    }

    @keyframes pulse-online {
        0% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.5); }
        70% { box-shadow: 0 0 0 10px rgba(22, 163, 74, 0); }
        100% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
    }

    @keyframes pulse-offline {
        0% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.5); }
        70% { box-shadow: 0 0 0 10px rgba(220, 38, 38, 0); }
        100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
    }

    .status-message {
        margin-top: 20px;
        color: var(--text-muted);
        font-size: 0.85rem;
        line-height: 1.4;
    }

    .footer {
        margin-top: 28px;
        font-size: 0.75rem;
        color: #64748b;
    }
</style>
</head>
<body>
    <div class="card">
        <h1>Desafio AWS</h1>
        <p class="subtitle">Status da conexão com o banco de dados</p>

        <button type="button" class="status-button <?= $statusClass ?>">
            <span class="status-dot"></span>
            <?= htmlspecialchars($statusLabel) ?>
        </button>

        <p class="status-message"><?= htmlspecialchars($statusMessage) ?></p>

        <div class="footer">PHP <?= PHP_VERSION ?> · Apache</div>
    </div>
</body>
</html>
