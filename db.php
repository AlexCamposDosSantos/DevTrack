<?php
define('DB_PATH', __DIR__ . '/data/devtrack.sqlite');
define('DB_VERSION', 4);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        if (!is_dir(__DIR__ . '/data')) {
            mkdir(__DIR__ . '/data', 0755, true);
        }
        $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode  = WAL');
        initSchema($pdo);
        runMigrations($pdo);
    }
    return $pdo;
}

function initSchema(PDO $db): void {
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

    $db->exec("CREATE TABLE IF NOT EXISTS tags (
        id        INTEGER PRIMARY KEY AUTOINCREMENT,
        nome      TEXT NOT NULL UNIQUE,
        cor       TEXT DEFAULT '#58a6ff',
        criado_em TEXT DEFAULT (datetime('now','localtime'))
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS atividades (
        id            INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo        TEXT NOT NULL,
        descricao     TEXT,
        solucao       TEXT,
        tipo          TEXT DEFAULT 'Outro',
        prioridade    TEXT DEFAULT 'media',
        coluna        TEXT DEFAULT 'backlog',
        projeto_id    INTEGER,
        tags          TEXT,
        link          TEXT,
        tempo_gasto   INTEGER DEFAULT 0,
        data_inicio   TEXT,
        data_fim      TEXT,
        solicitado_por TEXT,
        ordem         INTEGER DEFAULT 0,
        criado_em     TEXT DEFAULT (datetime('now','localtime')),
        atualizado_em TEXT,
        deletado_em   TEXT,
        FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE SET NULL
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS historico (
        id              INTEGER PRIMARY KEY AUTOINCREMENT,
        atividade_id    INTEGER NOT NULL,
        coluna_anterior TEXT,
        coluna_nova     TEXT NOT NULL,
        tipo            TEXT DEFAULT 'mover',
        detalhe         TEXT,
        criado_em       TEXT DEFAULT (datetime('now','localtime')),
        FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS configuracoes (
        chave TEXT PRIMARY KEY,
        valor TEXT
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS etapas (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        atividade_id INTEGER NOT NULL,
        texto        TEXT NOT NULL,
        criado_em    TEXT DEFAULT (datetime('now','localtime')),
        FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE
    )");

    $db->exec("CREATE INDEX IF NOT EXISTS idx_etapas_atv ON etapas(atividade_id)");

    // Índices para performance (apenas colunas que existem no schema base)
    $db->exec("CREATE INDEX IF NOT EXISTS idx_atv_coluna  ON atividades(coluna)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_atv_projeto ON atividades(projeto_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_atv_ordem   ON atividades(coluna, ordem)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_atv_criado  ON atividades(criado_em)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_hist_atv    ON historico(atividade_id)");

    seedDefaults($db);
}

function runMigrations(PDO $db): void {
    $version = (int) $db->query("PRAGMA user_version")->fetchColumn();

    if ($version < 1) {
        try { $db->exec("ALTER TABLE atividades ADD COLUMN solicitado_por TEXT"); } catch (Exception) {}
        $db->exec("PRAGMA user_version = 1");
    }

    if ($version < 2) {
        try { $db->exec("ALTER TABLE historico ADD COLUMN tipo    TEXT DEFAULT 'mover'"); } catch (Exception) {}
        try { $db->exec("ALTER TABLE historico ADD COLUMN detalhe TEXT"); } catch (Exception) {}
        $db->exec("PRAGMA user_version = 2");
    }

    if ($version < 4) {
        $db->exec("CREATE TABLE IF NOT EXISTS etapas (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            atividade_id INTEGER NOT NULL,
            texto        TEXT NOT NULL,
            criado_em    TEXT DEFAULT (datetime('now','localtime')),
            FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE
        )");
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_etapas_atv ON etapas(atividade_id)"); } catch (\Exception $e) {}
        $db->exec("PRAGMA user_version = 4");
    }

    if ($version < 3) {
        try { $db->exec("ALTER TABLE atividades ADD COLUMN atualizado_em TEXT"); } catch (\Exception $e) {}
        try { $db->exec("ALTER TABLE atividades ADD COLUMN deletado_em   TEXT"); } catch (\Exception $e) {}
        try { $db->exec("CREATE INDEX IF NOT EXISTS idx_atv_deletado ON atividades(deletado_em)"); } catch (\Exception $e) {}
        $db->exec("PRAGMA user_version = 3");
    }
}

function seedDefaults(PDO $db): void {
    if ($db->query("SELECT COUNT(*) FROM projetos")->fetchColumn() == 0) {
        $db->exec("INSERT INTO projetos (nome, cor) VALUES
            ('Geral',    '#6c757d'),
            ('Backend',  '#58a6ff'),
            ('Frontend', '#f0883e'),
            ('DevOps',   '#3fb950'),
            ('Mobile',   '#a371f7')
        ");
    }

    if ($db->query("SELECT COUNT(*) FROM tipos")->fetchColumn() == 0) {
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

    if ($db->query("SELECT COUNT(*) FROM tags")->fetchColumn() == 0) {
        $db->exec("INSERT INTO tags (nome, cor) VALUES
            ('backend',   '#58a6ff'),
            ('frontend',  '#f0883e'),
            ('banco',     '#3fb950'),
            ('api',       '#a371f7'),
            ('segurança', '#f85149'),
            ('urgente',   '#d29922'),
            ('infra',     '#39c5cf')
        ");
    }

    // Configs padrão: WIP limits (null = sem limite)
    $wips = ['wip_backlog', 'wip_andamento', 'wip_revisao', 'wip_concluido', 'wip_bloqueado'];
    foreach ($wips as $k) {
        $db->prepare("INSERT OR IGNORE INTO configuracoes (chave, valor) VALUES (?, NULL)")
           ->execute([$k]);
    }
}
