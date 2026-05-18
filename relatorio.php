<?php
require_once 'db.php';

$db = getDB();

$de   = $_GET['de']       ?? date('Y-m-01');
$ate  = $_GET['ate']      ?? date('Y-m-d');
$proj = $_GET['projeto']  ?? '';
$tipo = $_GET['tipo']     ?? '';
$col  = $_GET['coluna']   ?? '';

$where  = ['DATE(a.criado_em) >= ?', 'DATE(a.criado_em) <= ?'];
$params = [$de, $ate];

if ($proj) { $where[] = 'a.projeto_id = ?'; $params[] = (int)$proj; }
if ($tipo) { $where[] = 'a.tipo = ?';       $params[] = $tipo; }
if ($col)  { $where[] = 'a.coluna = ?';     $params[] = $col; }

$sql = "SELECT a.*, p.nome AS projeto_nome, p.cor AS projeto_cor
        FROM atividades a
        LEFT JOIN projetos p ON a.projeto_id = p.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY a.criado_em DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$atividades = $stmt->fetchAll();

$projetos = $db->query("SELECT * FROM projetos ORDER BY nome")->fetchAll();
$tiposDB  = $db->query("SELECT * FROM tipos ORDER BY nome")->fetchAll();

$TIPOS_INFO = [];
foreach ($tiposDB as $t) $TIPOS_INFO[$t['nome']] = $t;

$totalMin = array_sum(array_column($atividades, 'tempo_gasto'));
$totalH   = floor($totalMin / 60);
$totalM   = $totalMin % 60;

$porTipo = [];
foreach ($atividades as $a) $porTipo[$a['tipo']] = ($porTipo[$a['tipo']] ?? 0) + 1;

$COLUNAS = [
    'backlog' => 'Backlog', 'andamento' => 'Em Andamento',
    'revisao' => 'Em Revisão', 'concluido' => 'Concluído', 'bloqueado' => 'Bloqueado',
];

function fmtMin(int $min): string {
    $h = (int)($min / 60); $m = $min % 60;
    return $h > 0 ? "{$h}h" . ($m ? "{$m}m" : '') : "{$m}m";
}

// ── EXPORT CSV ──────────────────────────────────────────────────────────────
if (($_GET['formato'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="devtrack-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM para Excel
    fputcsv($out, ['#','Título','Tipo','Prioridade','Coluna','Projeto','Tags','Solicitado Por','Tempo (min)','Tempo','Data Início','Data Fim','Criado Em'], ';');
    foreach ($atividades as $i => $a) {
        fputcsv($out, [
            $i + 1,
            $a['titulo'],
            $a['tipo'],
            $a['prioridade'],
            $COLUNAS[$a['coluna']] ?? $a['coluna'],
            $a['projeto_nome'] ?? '',
            $a['tags'] ?? '',
            $a['solicitado_por'] ?? '',
            $a['tempo_gasto'],
            $a['tempo_gasto'] > 0 ? fmtMin((int)$a['tempo_gasto']) : '',
            $a['data_inicio'] ?? '',
            $a['data_fim']    ?? '',
            $a['criado_em'],
        ], ';');
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório — DevTrack</title>
    <?php include '_tailwind.php'; ?>
    <style>
        @media print {
            .no-print { display:none !important; }
            body { background:#fff !important; color:#000 !important; }
            table { font-size:11px; }
        }
    </style>
</head>
<body class="bg-dt-base text-dt-text font-sans min-h-screen">

<!-- NAVBAR -->
<nav class="flex items-center gap-3 px-5 h-14 bg-dt-surface border-b border-dt-border">
    <a href="index.php" class="text-dt-accent font-bold text-sm no-underline">⬡ DevTrack</a>
    <span class="text-dt-muted">/</span>
    <span class="text-sm">Relatório de Atividades</span>
    <div class="ml-auto flex gap-2 no-print">
        <a id="btn-csv" href="#" class="dt-btn-ghost-sm">⬇ Exportar CSV</a>
        <button onclick="window.print()" class="dt-btn dt-btn-sm">🖨 Imprimir</button>
    </div>
</nav>

<div class="max-w-[960px] mx-auto px-5 py-6">

    <!-- FILTROS -->
    <form class="no-print bg-dt-surface border border-dt-border rounded-xl p-4 flex flex-wrap gap-4 items-end mb-6" method="get">
        <?php
        $fg = 'flex flex-col gap-1';
        $lbl = 'text-[11px] font-semibold text-dt-muted uppercase tracking-wider';
        $inp = 'bg-dt-base border border-dt-border rounded-md text-dt-text px-2.5 py-1.5 text-sm outline-none focus:border-dt-accent transition-colors';
        $sel = $inp . ' sel cursor-pointer';
        ?>
        <div class="<?= $fg ?>"><label class="<?= $lbl ?>">De</label><input type="date" name="de" class="<?= $inp ?>" value="<?= htmlspecialchars($de) ?>"></div>
        <div class="<?= $fg ?>"><label class="<?= $lbl ?>">Até</label><input type="date" name="ate" class="<?= $inp ?>" value="<?= htmlspecialchars($ate) ?>"></div>
        <div class="<?= $fg ?>">
            <label class="<?= $lbl ?>">Projeto</label>
            <select name="projeto" class="<?= $sel ?>">
                <option value="">Todos</option>
                <?php foreach ($projetos as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $proj == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="<?= $fg ?>">
            <label class="<?= $lbl ?>">Tipo</label>
            <select name="tipo" class="<?= $sel ?>">
                <option value="">Todos</option>
                <?php foreach ($tiposDB as $t): ?>
                <option value="<?= htmlspecialchars($t['nome']) ?>" <?= $tipo === $t['nome'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="<?= $fg ?>">
            <label class="<?= $lbl ?>">Coluna</label>
            <select name="coluna" class="<?= $sel ?>">
                <option value="">Todas</option>
                <?php foreach ($COLUNAS as $k => $v): ?>
                <option value="<?= $k ?>" <?= $col === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="dt-btn dt-btn-sm self-end">Filtrar</button>
    </form>

    <!-- PERÍODO -->
    <p class="text-sm text-dt-muted mb-5">
        Período: <strong class="text-dt-text"><?= date('d/m/Y', strtotime($de)) ?></strong> a
        <strong class="text-dt-text"><?= date('d/m/Y', strtotime($ate)) ?></strong>
        — <?= count($atividades) ?> atividade(s)
    </p>

    <!-- STATS -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <?php
        $colCounts = array_count_values(array_column($atividades, 'coluna'));
        $stats = [
            [count($atividades), '#58a6ff', 'Total'],
            [$colCounts['concluido'] ?? 0, '#3fb950', 'Concluídas'],
            [$colCounts['andamento'] ?? 0, '#f0883e', 'Em andamento'],
            [$totalMin > 0 ? ($totalH > 0 ? "{$totalH}h{$totalM}m" : "{$totalM}m") : '—', '#8b949e', 'Tempo total'],
        ];
        foreach ($stats as [$val, $cor, $lbl]): ?>
        <div class="bg-dt-surface border border-dt-border rounded-xl p-4 text-center">
            <div class="text-3xl font-bold leading-none mb-1" style="color:<?= $cor ?>"><?= $val ?></div>
            <div class="text-xs text-dt-muted"><?= $lbl ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- POR TIPO -->
    <?php if ($porTipo): ?>
    <p class="text-[11px] font-semibold text-dt-muted uppercase tracking-wider border-b border-dt-border pb-2 mb-3">Por tipo</p>
    <div class="flex flex-wrap gap-2 mb-6">
        <?php foreach ($porTipo as $t => $cnt):
            $ti = $TIPOS_INFO[$t] ?? ['cor' => '#8b949e', 'cor_bg' => '#1e2329']; ?>
        <div class="flex items-center gap-2 bg-dt-card border border-dt-border rounded-lg px-3 py-1.5 text-xs">
            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wide"
                style="color:<?= $ti['cor'] ?>;background:<?= $ti['cor_bg'] ?>;border:1px solid <?= $ti['cor'] ?>44"><?= htmlspecialchars($t) ?></span>
            <strong class="text-dt-text"><?= $cnt ?></strong>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- TABELA -->
    <p class="text-[11px] font-semibold text-dt-muted uppercase tracking-wider border-b border-dt-border pb-2 mb-3">Atividades</p>

    <?php if (empty($atividades)): ?>
    <p class="text-center text-dt-muted py-12">Nenhuma atividade encontrada para este período.</p>
    <?php else: ?>
    <div class="overflow-x-auto rounded-xl border border-dt-border mb-6">
    <table class="w-full border-collapse text-sm">
        <thead class="bg-dt-surface">
            <tr>
                <?php foreach (['#','Título','Tipo','Prioridade','Coluna','Projeto','Solicitado por','Tempo','Criado em'] as $h): ?>
                <th class="px-3 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-dt-muted border-b border-dt-border whitespace-nowrap"><?= $h ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
        <?php
        $colChips = [
            'backlog'   => ['#1e2329','#8b949e'],
            'andamento' => ['#0c2040','#58a6ff'],
            'revisao'   => ['#2d1f4a','#a371f7'],
            'concluido' => ['#0d2b1a','#3fb950'],
            'bloqueado' => ['#3d1a1a','#f85149'],
        ];
        $priColors = ['baixa'=>'#3fb950','media'=>'#d29922','alta'=>'#f0883e','urgente'=>'#f85149'];
        foreach ($atividades as $i => $a):
            $priColor = $priColors[$a['prioridade']] ?? '#8b949e';
            $ti = $TIPOS_INFO[$a['tipo']] ?? ['cor'=>'#8b949e','cor_bg'=>'#1e2329'];
            [$chipBg,$chipTxt] = $colChips[$a['coluna']] ?? ['#1e2329','#8b949e'];
        ?>
        <tr class="border-b border-dt-border hover:bg-dt-card transition-colors">
            <td class="px-3 py-2.5 text-[11px] text-dt-muted"><?= $i + 1 ?></td>
            <td class="px-3 py-2.5 max-w-[260px]">
                <p class="font-medium mb-0.5"><?= htmlspecialchars($a['titulo']) ?></p>
                <?php if ($a['descricao']): ?>
                <p class="text-[11px] text-dt-muted"><?= htmlspecialchars(mb_substr($a['descricao'],0,100)).(mb_strlen($a['descricao'])>100?'…':'') ?></p>
                <?php endif; ?>
                <?php if ($a['tags']): ?>
                <div class="flex flex-wrap gap-1 mt-1">
                    <?php foreach (array_filter(array_map('trim',explode(',',$a['tags']))) as $tag): ?>
                    <span class="text-[10px] text-dt-muted bg-dt-card border border-dt-border rounded px-1.5 py-0.5">#<?= htmlspecialchars($tag) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if ($a['link']): ?>
                <a href="<?= htmlspecialchars($a['link']) ?>" target="_blank" class="text-[11px] text-dt-accent no-underline hover:underline mt-0.5 block">🔗 Link</a>
                <?php endif; ?>
            </td>
            <td class="px-3 py-2.5">
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wide"
                    style="color:<?= $ti['cor'] ?>;background:<?= $ti['cor_bg'] ?>;border:1px solid <?= $ti['cor'] ?>44"><?= htmlspecialchars($a['tipo']) ?></span>
            </td>
            <td class="px-3 py-2.5 whitespace-nowrap">
                <span class="flex items-center gap-1.5 text-xs">
                    <i class="w-2 h-2 rounded-full inline-block flex-shrink-0" style="background:<?= $priColor ?>"></i>
                    <?= ucfirst($a['prioridade']) ?>
                </span>
            </td>
            <td class="px-3 py-2.5">
                <span class="text-[11px] font-medium px-2 py-0.5 rounded"
                    style="background:<?= $chipBg ?>;color:<?= $chipTxt ?>"><?= $COLUNAS[$a['coluna']] ?? $a['coluna'] ?></span>
            </td>
            <td class="px-3 py-2.5 text-xs text-dt-text whitespace-nowrap">
                <?php if ($a['projeto_nome']): ?>
                <span class="flex items-center gap-1.5"><i class="w-2 h-2 rounded-full flex-shrink-0 inline-block" style="background:<?= htmlspecialchars($a['projeto_cor']) ?>"></i><?= htmlspecialchars($a['projeto_nome']) ?></span>
                <?php else: ?><span class="text-dt-muted opacity-40">—</span><?php endif; ?>
            </td>
            <td class="px-3 py-2.5 text-xs text-dt-muted whitespace-nowrap"><?= htmlspecialchars($a['solicitado_por'] ?? '') ?: '<span class="opacity-40">—</span>' ?></td>
            <td class="px-3 py-2.5 text-xs text-dt-muted whitespace-nowrap"><?= $a['tempo_gasto'] > 0 ? fmtMin((int)$a['tempo_gasto']) : '<span class="opacity-40">—</span>' ?></td>
            <td class="px-3 py-2.5 text-xs text-dt-muted whitespace-nowrap">
                <?= date('d/m/Y', strtotime($a['criado_em'])) ?><br>
                <span class="text-[11px]"><?= date('H:i', strtotime($a['criado_em'])) ?></span>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>

    <p class="text-center text-[11px] text-dt-muted mt-6">Gerado em <?= date('d/m/Y H:i') ?> · DevTrack</p>

</div>

<script>
document.getElementById('btn-csv').addEventListener('click', function(e) {
    e.preventDefault();
    const p = new URLSearchParams(window.location.search);
    p.set('formato', 'csv');
    window.location.href = 'relatorio.php?' + p.toString();
});
</script>
</body>
</html>
