<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar':           listar();           break;
    case 'criar':            criar();            break;
    case 'atualizar':        atualizar();        break;
    case 'mover':            mover();            break;
    case 'reordenar':        reordenar();        break;
    case 'deletar':          deletar();          break;
    case 'restaurar':        restaurar();        break;
    case 'projetos':         projetos();         break;
    case 'criar_projeto':    criarProjeto();     break;
    case 'atualizar_projeto':atualizarProjeto(); break;
    case 'deletar_projeto':  deletarProjeto();   break;
    case 'historico':        historico();        break;
    case 'tipos':            tipos();            break;
    case 'criar_tipo':       criarTipo();        break;
    case 'atualizar_tipo':   atualizarTipo();    break;
    case 'deletar_tipo':     deletarTipo();      break;
    case 'tags':             tagsLista();        break;
    case 'criar_tag':        criarTag();         break;
    case 'atualizar_tag':    atualizarTag();     break;
    case 'deletar_tag':      deletarTag();       break;
    case 'config_get':       configGet();        break;
    case 'config_set':       configSet();        break;
    default: echo json_encode(['erro' => 'Ação inválida']);
}

/* ─── CONSTANTES ─── */
const COLUNAS_VALIDAS    = ['backlog', 'andamento', 'revisao', 'concluido', 'bloqueado'];
const PRIORIDADES_VALIDAS = ['baixa', 'media', 'alta', 'urgente'];

/* ─── HELPERS ─── */
function input(): array {
    return (array) json_decode(file_get_contents('php://input'), true);
}

function safeUrl(?string $url): string {
    if (!$url) return '';
    $url = trim($url);
    if (preg_match('/^javascript:/i', $url)) return '';
    return $url;
}

function safeColuna(string $val, string $default = 'backlog'): string {
    return in_array($val, COLUNAS_VALIDAS, true) ? $val : $default;
}

function safePrioridade(string $val, string $default = 'media'): string {
    return in_array($val, PRIORIDADES_VALIDAS, true) ? $val : $default;
}

function safeDate(?string $val): ?string {
    if (!$val) return null;
    $d = DateTime::createFromFormat('Y-m-d', $val);
    return ($d && $d->format('Y-m-d') === $val) ? $val : null;
}

function safeTagLike(PDO $db, string $nome): array {
    $safe = str_replace(['%', '_'], ['\%', '\_'], $nome);
    $stmt = $db->prepare("SELECT id, tags FROM atividades WHERE tags = ? OR tags LIKE ? OR tags LIKE ? OR tags LIKE ?");
    $stmt->execute([$nome, $safe.',%', '%,'.$safe, '%,'.$safe.',%']);
    return $stmt->fetchAll();
}

/* ─── ATIVIDADES ─── */
function listar(): void {
    $db = getDB();
    $where  = ['a.deletado_em IS NULL'];
    $params = [];

    if (!empty($_GET['busca'])) {
        $where[] = '(a.titulo LIKE ? OR a.descricao LIKE ? OR a.solicitado_por LIKE ?)';
        $b = '%' . $_GET['busca'] . '%';
        $params = array_merge($params, [$b, $b, $b]);
    }
    if (!empty($_GET['projeto_id'])) {
        $where[] = 'a.projeto_id = ?';
        $params[] = (int) $_GET['projeto_id'];
    }
    if (!empty($_GET['prioridade']) && in_array($_GET['prioridade'], PRIORIDADES_VALIDAS, true)) {
        $where[] = 'a.prioridade = ?';
        $params[] = $_GET['prioridade'];
    }
    if (!empty($_GET['tipo'])) {
        $where[] = 'a.tipo = ?';
        $params[] = $_GET['tipo'];
    }
    if (!empty($_GET['tag'])) {
        $t    = $_GET['tag'];
        $safe = str_replace(['%','_'], ['\%','\_'], $t);
        $where[] = '(a.tags = ? OR a.tags LIKE ? OR a.tags LIKE ? OR a.tags LIKE ?)';
        $params  = array_merge($params, [$t, $safe.',%', '%,'.$safe, '%,'.$safe.',%']);
    }
    if (!empty($_GET['de'])  && safeDate($_GET['de'])) {
        $where[] = "DATE(a.criado_em) >= ?";
        $params[] = $_GET['de'];
    }
    if (!empty($_GET['ate']) && safeDate($_GET['ate'])) {
        $where[] = "DATE(a.criado_em) <= ?";
        $params[] = $_GET['ate'];
    }

    $limit  = min((int)($_GET['limit'] ?? 200), 500);
    $offset = max((int)($_GET['offset'] ?? 0), 0);

    $sql = "SELECT a.*, p.nome AS projeto_nome, p.cor AS projeto_cor
            FROM atividades a
            LEFT JOIN projetos p ON a.projeto_id = p.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY a.coluna, a.ordem ASC, a.criado_em DESC
            LIMIT ? OFFSET ?";

    $params[] = $limit;
    $params[] = $offset;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());
}

function criar(): void {
    $db = getDB();
    $d  = input();

    $titulo = trim($d['titulo'] ?? '');
    if (!$titulo) { echo json_encode(['erro' => 'Título obrigatório']); return; }

    $coluna    = safeColuna($d['coluna']    ?? 'backlog');
    $prioridade = safePrioridade($d['prioridade'] ?? 'media');

    $maxOrdem = $db->prepare("SELECT COALESCE(MAX(ordem),0)+1 FROM atividades WHERE coluna = ?");
    $maxOrdem->execute([$coluna]);
    $ordem = (int) $maxOrdem->fetchColumn();

    $stmt = $db->prepare("INSERT INTO atividades
        (titulo, descricao, solucao, tipo, prioridade, coluna, projeto_id, tags,
         link, tempo_gasto, data_inicio, data_fim, solicitado_por, ordem)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

    $stmt->execute([
        $titulo,
        $d['descricao']     ?? '',
        $d['solucao']       ?? '',
        $d['tipo']          ?? 'Outro',
        $prioridade,
        $coluna,
        !empty($d['projeto_id']) ? (int)$d['projeto_id'] : null,
        $d['tags']          ?? '',
        safeUrl($d['link']  ?? ''),
        (int)($d['tempo_gasto'] ?? 0),
        safeDate($d['data_inicio'] ?? null),
        safeDate($d['data_fim']    ?? null),
        trim($d['solicitado_por'] ?? ''),
        $ordem,
    ]);

    $id = $db->lastInsertId();
    $db->prepare("INSERT INTO historico (atividade_id, coluna_anterior, coluna_nova, tipo) VALUES (?,NULL,?,'criar')")
       ->execute([$id, $coluna]);

    echo json_encode(['ok' => true, 'id' => $id]);
}

function atualizar(): void {
    $db = getDB();
    $d  = input();
    $id = (int) $d['id'];

    $titulo = trim($d['titulo'] ?? '');
    if (!$titulo) { echo json_encode(['erro' => 'Título obrigatório']); return; }

    $prioridade = safePrioridade($d['prioridade'] ?? 'media');

    $old = $db->prepare("SELECT * FROM atividades WHERE id = ?");
    $old->execute([$id]);
    $prev = $old->fetch();

    $stmt = $db->prepare("UPDATE atividades SET
        titulo=?, descricao=?, solucao=?, tipo=?, prioridade=?,
        projeto_id=?, tags=?, link=?, tempo_gasto=?, data_inicio=?,
        data_fim=?, solicitado_por=?, atualizado_em=datetime('now','localtime')
        WHERE id=?");

    $stmt->execute([
        $titulo,
        $d['descricao']         ?? '',
        $d['solucao']           ?? '',
        $d['tipo']              ?? 'Outro',
        $prioridade,
        !empty($d['projeto_id']) ? (int)$d['projeto_id'] : null,
        $d['tags']              ?? '',
        safeUrl($d['link']      ?? ''),
        (int)($d['tempo_gasto'] ?? 0),
        safeDate($d['data_inicio'] ?? null),
        safeDate($d['data_fim']    ?? null),
        trim($d['solicitado_por'] ?? ''),
        $id,
    ]);

    $changes = [];
    if ($prev && $titulo !== $prev['titulo'])
        $changes[] = 'Título alterado';
    if ($prev && $prioridade !== $prev['prioridade'])
        $changes[] = 'Prioridade: ' . $prev['prioridade'] . ' → ' . $prioridade;
    if ($prev && ($d['tipo'] ?? '') !== $prev['tipo'])
        $changes[] = 'Tipo: ' . $prev['tipo'] . ' → ' . $d['tipo'];

    if ($changes && $prev) {
        $db->prepare("INSERT INTO historico (atividade_id, coluna_anterior, coluna_nova, tipo, detalhe)
                      VALUES (?,?,?,'editar',?)")
           ->execute([$id, $prev['coluna'], $prev['coluna'], implode('; ', $changes)]);
    }

    echo json_encode(['ok' => true]);
}

function mover(): void {
    $db = getDB();
    $d  = input();
    $id = (int) $d['id'];
    $novaColuna = safeColuna($d['coluna'] ?? 'backlog');

    $stmt = $db->prepare("SELECT coluna FROM atividades WHERE id = ?");
    $stmt->execute([$id]);
    $anterior = $stmt->fetchColumn();

    if ($anterior === $novaColuna) { echo json_encode(['ok' => true]); return; }

    $maxOrdem = $db->prepare("SELECT COALESCE(MAX(ordem),0)+1 FROM atividades WHERE coluna = ?");
    $maxOrdem->execute([$novaColuna]);
    $ordem = (int) $maxOrdem->fetchColumn();

    $db->prepare("UPDATE atividades SET coluna=?, ordem=?, atualizado_em=datetime('now','localtime') WHERE id=?")
       ->execute([$novaColuna, $ordem, $id]);

    $db->prepare("INSERT INTO historico (atividade_id, coluna_anterior, coluna_nova, tipo) VALUES (?,?,?,'mover')")
       ->execute([$id, $anterior, $novaColuna]);

    echo json_encode(['ok' => true]);
}

function reordenar(): void {
    $db  = getDB();
    $d   = input();
    $ids = array_map('intval', (array)($d['ids'] ?? []));

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("UPDATE atividades SET ordem=? WHERE id=?");
        foreach ($ids as $i => $id) {
            $stmt->execute([$i, $id]);
        }
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['erro' => 'Falha ao reordenar']); return;
    }
    echo json_encode(['ok' => true]);
}

function deletar(): void {
    $db = getDB();
    $d  = input();
    $db->prepare("UPDATE atividades SET deletado_em=datetime('now','localtime') WHERE id=?")
       ->execute([(int)$d['id']]);
    echo json_encode(['ok' => true]);
}

function restaurar(): void {
    $db = getDB();
    $d  = input();
    $db->prepare("UPDATE atividades SET deletado_em=NULL WHERE id=?")->execute([(int)$d['id']]);
    echo json_encode(['ok' => true]);
}

/* ─── HISTORICO ─── */
function historico(): void {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM historico WHERE atividade_id=? ORDER BY criado_em DESC");
    $stmt->execute([(int)($_GET['id'] ?? 0)]);
    echo json_encode($stmt->fetchAll());
}

/* ─── PROJETOS ─── */
function projetos(): void {
    $db = getDB();
    echo json_encode($db->query("SELECT * FROM projetos ORDER BY nome")->fetchAll());
}

function criarProjeto(): void {
    $db = getDB();
    $d  = input();
    $nome = trim($d['nome'] ?? '');
    if (!$nome) { echo json_encode(['erro' => 'Nome obrigatório']); return; }
    $stmt = $db->prepare("INSERT INTO projetos (nome, cor) VALUES (?,?)");
    $stmt->execute([$nome, $d['cor'] ?? '#6c757d']);
    echo json_encode(['ok'=>true,'id'=>$db->lastInsertId(),'nome'=>$nome,'cor'=>$d['cor'] ?? '#6c757d']);
}

function atualizarProjeto(): void {
    $db = getDB();
    $d  = input();
    $db->prepare("UPDATE projetos SET nome=?, cor=? WHERE id=?")
       ->execute([trim($d['nome']), $d['cor'] ?? '#6c757d', (int)$d['id']]);
    echo json_encode(['ok' => true]);
}

function deletarProjeto(): void {
    $db = getDB();
    $d  = input();
    $id = (int)$d['id'];
    $db->prepare("UPDATE atividades SET projeto_id=NULL WHERE projeto_id=?")->execute([$id]);
    $db->prepare("DELETE FROM projetos WHERE id=?")->execute([$id]);
    echo json_encode(['ok' => true]);
}

/* ─── TIPOS ─── */
function tipos(): void {
    $db = getDB();
    echo json_encode($db->query("SELECT * FROM tipos ORDER BY nome")->fetchAll());
}

function criarTipo(): void {
    $db = getDB();
    $d  = input();
    $nome = trim($d['nome'] ?? '');
    if (!$nome) { echo json_encode(['erro' => 'Nome obrigatório']); return; }
    $stmt = $db->prepare("INSERT INTO tipos (nome, cor, cor_bg) VALUES (?,?,?)");
    $stmt->execute([$nome, $d['cor'] ?? '#8b949e', $d['cor_bg'] ?? '#1e2329']);
    echo json_encode(['ok'=>true,'id'=>$db->lastInsertId(),'nome'=>$nome,'cor'=>$d['cor']??'#8b949e','cor_bg'=>$d['cor_bg']??'#1e2329']);
}

function atualizarTipo(): void {
    $db = getDB();
    $d  = input();
    $id = (int)$d['id'];

    $stmt = $db->prepare("SELECT nome FROM tipos WHERE id=?");
    $stmt->execute([$id]);
    $oldNome = $stmt->fetchColumn();

    $nome = trim($d['nome']);
    if ($oldNome && $oldNome !== $nome)
        $db->prepare("UPDATE atividades SET tipo=? WHERE tipo=?")->execute([$nome, $oldNome]);

    $db->prepare("UPDATE tipos SET nome=?, cor=?, cor_bg=? WHERE id=?")
       ->execute([$nome, $d['cor'] ?? '#8b949e', $d['cor_bg'] ?? '#1e2329', $id]);
    echo json_encode(['ok' => true]);
}

function deletarTipo(): void {
    $db = getDB();
    $d  = input();
    $id = (int)$d['id'];
    $stmt = $db->prepare("SELECT nome FROM tipos WHERE id=?");
    $stmt->execute([$id]);
    $nome = $stmt->fetchColumn();
    if ($nome) $db->prepare("UPDATE atividades SET tipo='Outro' WHERE tipo=?")->execute([$nome]);
    $db->prepare("DELETE FROM tipos WHERE id=?")->execute([$id]);
    echo json_encode(['ok' => true]);
}

/* ─── TAGS ─── */
function tagsLista(): void {
    $db = getDB();
    echo json_encode($db->query("SELECT * FROM tags ORDER BY nome")->fetchAll());
}

function criarTag(): void {
    $db = getDB();
    $d  = input();
    $nome = trim($d['nome'] ?? '');
    if (!$nome) { echo json_encode(['erro' => 'Nome obrigatório']); return; }
    $stmt = $db->prepare("INSERT INTO tags (nome, cor) VALUES (?,?)");
    $stmt->execute([$nome, $d['cor'] ?? '#58a6ff']);
    echo json_encode(['ok'=>true,'id'=>$db->lastInsertId(),'nome'=>$nome,'cor'=>$d['cor']??'#58a6ff']);
}

function atualizarTag(): void {
    $db = getDB();
    $d  = input();
    $id = (int)$d['id'];

    $stmt = $db->prepare("SELECT nome FROM tags WHERE id=?");
    $stmt->execute([$id]);
    $oldNome = $stmt->fetchColumn();

    $nome = trim($d['nome']);
    if ($oldNome && $oldNome !== $nome) {
        foreach (safeTagLike($db, $oldNome) as $row) {
            $list = array_map('trim', explode(',', $row['tags']));
            $list = array_map(fn($t) => $t === $oldNome ? $nome : $t, $list);
            $db->prepare("UPDATE atividades SET tags=? WHERE id=?")
               ->execute([implode(',', $list), $row['id']]);
        }
    }

    $db->prepare("UPDATE tags SET nome=?, cor=? WHERE id=?")->execute([$nome, $d['cor'] ?? '#58a6ff', $id]);
    echo json_encode(['ok' => true]);
}

function deletarTag(): void {
    $db = getDB();
    $d  = input();
    $id = (int)$d['id'];

    $stmt = $db->prepare("SELECT nome FROM tags WHERE id=?");
    $stmt->execute([$id]);
    $nome = $stmt->fetchColumn();

    if ($nome) {
        foreach (safeTagLike($db, $nome) as $row) {
            $list = array_filter(array_map('trim', explode(',', $row['tags'])), fn($t) => $t !== $nome);
            $db->prepare("UPDATE atividades SET tags=? WHERE id=?")
               ->execute([implode(',', $list), $row['id']]);
        }
    }
    $db->prepare("DELETE FROM tags WHERE id=?")->execute([$id]);
    echo json_encode(['ok' => true]);
}

/* ─── CONFIGURAÇÕES ─── */
function configGet(): void {
    $db = getDB();
    $stmt = $db->query("SELECT chave, valor FROM configuracoes");
    $map  = [];
    foreach ($stmt->fetchAll() as $row) $map[$row['chave']] = $row['valor'];
    echo json_encode($map);
}

function configSet(): void {
    $db = getDB();
    $d  = input();
    $stmt = $db->prepare("INSERT INTO configuracoes (chave, valor) VALUES (?,?)
                          ON CONFLICT(chave) DO UPDATE SET valor=excluded.valor");
    foreach ($d as $chave => $valor) {
        $stmt->execute([
            $chave,
            ($valor === '' || $valor === null) ? null : (string)$valor,
        ]);
    }
    echo json_encode(['ok' => true]);
}
