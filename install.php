<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Instalação - DevTrack</title>
    <style>
        body { font-family: monospace; background: #0d1117; color: #e6edf3; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .box { background: #161b22; border: 1px solid #30363d; border-radius: 8px; padding: 2rem; max-width: 480px; width: 100%; }
        h2 { color: #58a6ff; margin-top: 0; }
        .ok  { color: #3fb950; }
        .err { color: #f85149; }
        a { color: #58a6ff; }
        p { margin: 0.5rem 0; }
        code { background: #21262d; padding: 2px 6px; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>
<div class="box">
    <h2>⚙️ DevTrack — Instalação</h2>
<?php
require_once 'db.php';

try {
    $db = getDB();

    $db->exec("CREATE TABLE IF NOT EXISTS projetos (
        id        INTEGER PRIMARY KEY AUTOINCREMENT,
        nome      TEXT NOT NULL,
        cor       TEXT DEFAULT '#6c757d',
        criado_em TEXT DEFAULT (datetime('now','localtime'))
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS tipos (
        id        INTEGER PRIMARY KEY AUTOINCREMENT,
        nome      TEXT NOT NULL UNIQUE,
        cor       TEXT DEFAULT '#8b949e',
        cor_bg    TEXT DEFAULT '#1e2329',
        criado_em TEXT DEFAULT (datetime('now','localtime'))
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS atividades (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo      TEXT NOT NULL,
        descricao   TEXT,
        solucao     TEXT,
        tipo        TEXT DEFAULT 'Outro',
        prioridade  TEXT DEFAULT 'media',
        coluna      TEXT DEFAULT 'backlog',
        projeto_id  INTEGER,
        tags        TEXT,
        link        TEXT,
        tempo_gasto INTEGER DEFAULT 0,
        data_inicio TEXT,
        data_fim    TEXT,
        ordem       INTEGER DEFAULT 0,
        criado_em   TEXT DEFAULT (datetime('now','localtime')),
        FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE SET NULL
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS historico (
        id              INTEGER PRIMARY KEY AUTOINCREMENT,
        atividade_id    INTEGER NOT NULL,
        coluna_anterior TEXT,
        coluna_nova     TEXT NOT NULL,
        criado_em       TEXT DEFAULT (datetime('now','localtime')),
        FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE
    )");

    $countProj = $db->query("SELECT COUNT(*) FROM projetos")->fetchColumn();
    if ($countProj == 0) {
        $db->exec("INSERT INTO projetos (nome, cor) VALUES
            ('Geral',    '#6c757d'),
            ('Backend',  '#58a6ff'),
            ('Frontend', '#f0883e'),
            ('DevOps',   '#3fb950'),
            ('Mobile',   '#a371f7')
        ");
    }

    $countTipos = $db->query("SELECT COUNT(*) FROM tipos")->fetchColumn();
    if ($countTipos == 0) {
        $db->exec("INSERT INTO tipos (nome, cor, cor_bg) VALUES
            ('Bug',          '#f85149', '#2b0e0d'),
            ('Feature',      '#58a6ff', '#0c1e38'),
            ('Hotfix',       '#f0883e', '#2e1e00'),
            ('Refactor',     '#a371f7', '#1e1433'),
            ('Deploy',       '#3fb950', '#0b1f10'),
            ('Reunião',      '#39c5cf', '#0b1e1f'),
            ('Documentação', '#d29922', '#261e05'),
            ('Outro',        '#8b949e', '#1e2329')
        ");
    }

    echo '<p class="ok">✅ Banco SQLite criado em <code>data/devtrack.sqlite</code></p>';
    echo '<p class="ok">✅ Tabelas criadas!</p>';
    echo '<p class="ok">✅ Projetos e tipos padrão inseridos!</p>';
    echo '<br><p>Redirecionando... <a href="index.php">clique aqui</a> se não redirecionar.</p>';
    echo '<script>setTimeout(() => window.location = "index.php", 2000)</script>';

} catch (Exception $e) {
    echo '<p class="err">❌ Erro: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>Verifique se a extensão <code>pdo_sqlite</code> está ativa no <code>php.ini</code> do XAMPP.</p>';
}
?>
</div>
</body>
</html>
