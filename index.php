<?php
require_once 'db.php';
try { getDB(); } catch (Exception) { header('Location: install.php'); exit; }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DevTrack</title>
    <?php include '_tailwind.php'; ?>
</head>
<body class="bg-dt-base text-dt-text font-sans min-h-screen">

<!-- ── NAVBAR ── -->
<nav class="sticky top-0 z-50 flex items-center gap-2.5 px-4 h-14 bg-dt-surface border-b border-dt-border" style="position:relative">
    <a href="index.php" class="text-dt-accent font-bold text-sm flex items-center gap-1.5 no-underline whitespace-nowrap mr-1">
        ⬡ <span>DevTrack</span>
    </a>

    <div class="h-4 w-px bg-dt-border"></div>

    <input id="search-input" type="text" placeholder="Buscar..."
        class="dt-input max-w-[220px] py-1.5 text-xs">

    <div class="hidden md:flex items-center gap-2">
        <select id="filter-projeto" class="dt-select sel dt-select-sm !w-auto min-w-[120px]">
            <option value="">Todos projetos</option>
        </select>
        <select id="filter-tipo" class="dt-select sel dt-select-sm !w-auto min-w-[100px]">
            <option value="">Todos tipos</option>
        </select>
    </div>

    <!-- Filtros avançados (dropdown) -->
    <div class="relative">
        <button id="btn-filtros" onclick="toggleFilterPanel()"
            class="dt-btn-ghost-sm relative flex items-center gap-1.5">
            <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 4h12M4 8h8M6 12h4"/></svg>
            Filtros
            <span id="filter-dot" class="filter-dot hidden"></span>
        </button>
    </div>

    <div class="flex-1"></div>

    <!-- View toggle -->
    <div class="flex bg-dt-base border border-dt-border rounded-lg overflow-hidden text-xs">
        <button id="btn-view-board" onclick="setView('board')"
            class="px-3 py-1.5 font-medium cursor-pointer border-0 bg-dt-card text-dt-accent transition-colors">⊞ Board</button>
        <button id="btn-view-table" onclick="setView('table')"
            class="px-3 py-1.5 font-medium cursor-pointer border-0 bg-transparent text-dt-muted hover:text-dt-text transition-colors">☰ Tabela</button>
    </div>

    <a href="relatorio.php" target="_blank" class="dt-btn-ghost-sm hidden sm:inline-flex">📊 Relatório</a>
    <a href="config.php" class="dt-btn-ghost-sm hidden sm:inline-flex">⚙ Config</a>
    <button onclick="openModal()" class="dt-btn dt-btn-sm">+ Nova</button>
</nav>

<!-- ── FILTER PANEL (dropdown) ── -->
<div id="filter-panel" style="display:none">
    <div class="flex flex-col gap-1">
        <label class="dt-label">Prioridade</label>
        <select id="filter-prioridade" class="dt-select sel dt-select-sm !w-auto">
            <option value="">Todas</option>
            <option value="urgente">🔴 Urgente</option>
            <option value="alta">🟠 Alta</option>
            <option value="media">🟡 Média</option>
            <option value="baixa">🟢 Baixa</option>
        </select>
    </div>
    <div class="flex flex-col gap-1">
        <label class="dt-label">De</label>
        <input id="filter-de"  type="date" class="dt-input dt-input-sm !w-[130px]">
    </div>
    <div class="flex flex-col gap-1">
        <label class="dt-label">Até</label>
        <input id="filter-ate" type="date" class="dt-input dt-input-sm !w-[130px]">
    </div>
    <button id="btn-clear-filters" onclick="clearAllFilters()" style="display:none"
        class="dt-btn-ghost-sm text-dt-red border-dt-red/30 hover:bg-dt-red/10 self-end">
        ✕ Limpar filtros
    </button>
    <button onclick="toggleFilterPanel()" class="ml-auto self-end dt-btn-ghost-sm">Fechar</button>
</div>

<!-- ── BOARD ── -->
<div id="board-view" class="flex gap-4 p-5 overflow-x-auto min-h-[calc(100vh-56px)] items-start">
    <?php
    $cols = [
        ['id'=>'backlog',   'label'=>'Backlog',      'color'=>'#8b949e'],
        ['id'=>'andamento', 'label'=>'Em Andamento', 'color'=>'#58a6ff'],
        ['id'=>'revisao',   'label'=>'Em Revisão',   'color'=>'#a371f7'],
        ['id'=>'concluido', 'label'=>'Concluído',    'color'=>'#3fb950'],
        ['id'=>'bloqueado', 'label'=>'Bloqueado',    'color'=>'#f85149'],
    ];
    foreach ($cols as $col): ?>
    <div class="flex flex-col min-w-[280px] max-w-[280px] bg-dt-surface border border-dt-border rounded-xl max-h-[calc(100vh-90px)]">
        <div class="flex items-center gap-2 px-3 py-2.5 border-b border-dt-border flex-shrink-0 bg-dt-surface rounded-t-xl">
            <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:<?= $col['color'] ?>"></span>
            <span class="font-semibold text-sm flex-1 tracking-tight"><?= $col['label'] ?></span>
            <span class="col-count text-xs font-semibold text-dt-muted bg-dt-base border border-dt-border rounded-full px-2 py-0.5 tabular-nums" data-col="<?= $col['id'] ?>">0</span>
            <span class="wip-badge hidden text-[10px] font-bold px-1.5 py-0.5 rounded-full border" data-col="<?= $col['id'] ?>"></span>
            <button onclick="openModal(null,'<?= $col['id'] ?>')"
                class="w-6 h-6 flex items-center justify-center text-dt-muted hover:text-dt-text hover:bg-dt-border/60 rounded-md text-lg leading-none cursor-pointer bg-transparent border-0 transition-colors ml-0.5">+</button>
        </div>
        <div class="cards-list flex-1 overflow-y-auto p-2 flex flex-col gap-1.5 min-h-[80px] rounded-b-xl" data-col="<?= $col['id'] ?>">
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── TABLE VIEW ── -->
<div id="table-view" style="display:none" class="p-5"></div>

<!-- ── MODAL ── -->
<div id="modal-backdrop" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
<div class="modal-box-inner bg-dt-surface border border-dt-border/80 rounded-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden shadow-modal">

    <div class="flex items-center gap-3 px-4 py-3 border-b border-dt-border flex-shrink-0">
        <span id="modal-title-text" class="font-semibold text-sm flex-1 tracking-tight">Nova Atividade</span>
        <button id="btn-delete-card" onclick="deleteCard()" style="display:none" class="dt-btn-danger">🗑 Excluir</button>
        <button onclick="closeModal()" class="w-6 h-6 flex items-center justify-center text-dt-muted hover:text-dt-text hover:bg-dt-border/60 rounded-lg cursor-pointer bg-transparent border-0 transition-colors text-sm">✕</button>
    </div>

    <div class="flex border-b border-dt-border flex-shrink-0 px-1">
        <button class="modal-tab px-3 py-2 text-xs font-medium border-b-2 border-dt-accent text-dt-accent cursor-pointer bg-transparent border-x-0 border-t-0 transition-colors" data-tab="detalhes" onclick="switchTab('detalhes')">Detalhes</button>
        <button class="modal-tab px-3 py-2 text-xs font-medium border-b-2 border-transparent text-dt-muted hover:text-dt-text cursor-pointer bg-transparent border-x-0 border-t-0 transition-colors" data-tab="historico" onclick="switchTab('historico')">Histórico</button>
    </div>

    <div class="overflow-y-auto flex-1 px-4 py-4">

        <div class="tab-panel flex flex-col gap-2" id="tab-detalhes">

            <div>
                <label class="dt-label">Título *</label>
                <input id="f-titulo" type="text" class="dt-input" placeholder="O que foi feito?">
            </div>

            <div class="grid grid-cols-3 gap-x-3 gap-y-2">
                <div>
                    <label class="dt-label">Tipo</label>
                    <div class="flex gap-1.5">
                        <select id="f-tipo" class="dt-select sel flex-1"></select>
                        <button onclick="toggleInline('new-tipo-section','new-tipo-nome')" class="dt-btn-icon flex-shrink-0">＋</button>
                    </div>
                    <div id="new-tipo-section" class="hidden mt-2 flex gap-1.5">
                        <input id="new-tipo-nome" type="text" class="dt-input flex-1 !py-1.5 !text-xs" placeholder="Nome">
                        <input id="new-tipo-cor" type="color" class="dt-color-pick" value="#58a6ff">
                        <button onclick="addTipo()" class="dt-btn-xs">Criar</button>
                    </div>
                </div>
                <div>
                    <label class="dt-label">Prioridade</label>
                    <select id="f-prioridade" class="dt-select sel">
                        <option value="baixa">🟢 Baixa</option>
                        <option value="media" selected>🟡 Média</option>
                        <option value="alta">🟠 Alta</option>
                        <option value="urgente">🔴 Urgente</option>
                    </select>
                </div>
                <div>
                    <label class="dt-label">Coluna</label>
                    <select id="f-coluna" class="dt-select sel">
                        <option value="backlog">Backlog</option>
                        <option value="andamento">Em Andamento</option>
                        <option value="revisao">Em Revisão</option>
                        <option value="concluido">Concluído</option>
                        <option value="bloqueado">Bloqueado</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-x-3 gap-y-2">
                <div>
                    <label class="dt-label">Projeto</label>
                    <div class="flex gap-1.5">
                        <select id="f-projeto" class="dt-select sel flex-1"><option value="">— Sem projeto —</option></select>
                        <button onclick="toggleInline('new-proj-section','new-proj-nome')" class="dt-btn-icon flex-shrink-0">＋</button>
                    </div>
                    <div id="new-proj-section" class="hidden mt-2 flex gap-1.5">
                        <input id="new-proj-nome" type="text" class="dt-input flex-1 !py-1.5 !text-xs" placeholder="Nome">
                        <input id="new-proj-cor" type="color" class="dt-color-pick" value="#58a6ff">
                        <button onclick="addProject()" class="dt-btn-xs">Criar</button>
                    </div>
                </div>
                <div>
                    <label class="dt-label">Solicitado por</label>
                    <input id="f-solicitado-por" type="text" class="dt-input" placeholder="Nome de quem pediu">
                </div>
            </div>

            <div>
                <label class="dt-label">Descrição</label>
                <textarea id="f-descricao" rows="3" class="dt-input resize-y min-h-[72px]" placeholder="Contexto e o que foi solicitado..."></textarea>
            </div>

            <div>
                <label class="dt-label">Solução / O que foi feito</label>
                <select id="f-solucao-preset" class="dt-select sel mb-2" onchange="onSolucaoPreset(this.value)">
                    <option value="">— Selecione uma mensagem pronta —</option>
                    <optgroup label="Conclusão">
                        <option value="Projeto realizado com sucesso.">Projeto realizado com sucesso</option>
                        <option value="Funcionalidade implementada e testada.">Funcionalidade implementada e testada</option>
                        <option value="Tarefa concluída conforme solicitado.">Tarefa concluída conforme solicitado</option>
                        <option value="Deploy realizado com sucesso em produção.">Deploy realizado em produção</option>
                        <option value="Correção aplicada e validada em ambiente de produção.">Correção aplicada e validada</option>
                    </optgroup>
                    <optgroup label="Desenvolvimento">
                        <option value="Código refatorado para melhorar legibilidade e manutenção.">Código refatorado</option>
                        <option value="Nova API desenvolvida e documentada.">Nova API desenvolvida e documentada</option>
                        <option value="Integração com serviço externo implementada.">Integração com serviço externo</option>
                        <option value="Otimização de consulta ao banco de dados realizada.">Otimização de consulta ao banco</option>
                        <option value="Testes automatizados criados e passando.">Testes automatizados criados</option>
                    </optgroup>
                    <optgroup label="Correção">
                        <option value="Bug identificado e corrigido.">Bug identificado e corrigido</option>
                        <option value="Hotfix aplicado com urgência.">Hotfix aplicado com urgência</option>
                        <option value="Problema de performance identificado e resolvido.">Problema de performance resolvido</option>
                        <option value="Falha de segurança corrigida.">Falha de segurança corrigida</option>
                    </optgroup>
                    <optgroup label="Comunicação">
                        <option value="Reunião realizada e alinhamentos documentados.">Reunião realizada e alinhamentos documentados</option>
                        <option value="Documentação atualizada.">Documentação atualizada</option>
                        <option value="Revisão de código realizada e aprovada.">Revisão de código aprovada</option>
                    </optgroup>
                    <option value="__custom__">✏️ Adicionar comentário personalizado...</option>
                </select>
                <textarea id="f-solucao" rows="4" class="dt-input resize-y min-h-[88px] mono hidden" placeholder="Descreva a solução técnica aplicada..."></textarea>
            </div>

            <div>
                <label class="dt-label flex items-center gap-1.5">
                    Tags
                    <button onclick="toggleInline('new-tag-section','new-tag-nome')" class="w-4 h-4 flex items-center justify-center bg-dt-base border border-dt-border rounded text-dt-muted text-xs leading-none cursor-pointer hover:text-dt-text hover:border-dt-muted transition-colors font-normal">＋</button>
                </label>
                <input type="hidden" id="f-tags">
                <input type="hidden" id="f-link">
                <div id="tag-picker" class="tag-wrap flex flex-wrap gap-1 p-2 bg-dt-base border border-dt-border rounded-lg min-h-[32px] transition-all"></div>
                <div id="new-tag-section" class="hidden mt-1.5 flex gap-1.5">
                    <input id="new-tag-nome" type="text" class="dt-input flex-1 !py-1 !text-xs" placeholder="Nome da tag">
                    <input id="new-tag-cor" type="color" class="dt-color-pick" value="#a371f7">
                    <button onclick="addTagModal()" class="dt-btn-xs">Criar</button>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-x-3 gap-y-2">
                <div>
                    <label class="dt-label">Data início</label>
                    <input id="f-data-inicio" type="date" class="dt-input">
                </div>
                <div>
                    <label class="dt-label">Data fim</label>
                    <input id="f-data-fim" type="date" class="dt-input">
                </div>
                <div>
                    <label class="dt-label">Tempo gasto</label>
                    <input id="f-tempo" type="text" class="dt-input" placeholder="2h30m ou 45m">
                </div>
            </div>

        </div><!-- /tab-detalhes -->

        <div class="tab-panel hidden" id="tab-historico">
            <div id="history-list" class="text-dt-muted text-sm">Salve o card para ver o histórico.</div>
        </div>

    </div>

    <div class="flex items-center justify-between px-4 py-2.5 border-t border-dt-border flex-shrink-0 bg-dt-base/30">
        <span class="text-xs text-dt-muted/60">Ctrl+Enter salvar · Esc fechar</span>
        <div class="flex gap-2">
            <button onclick="closeModal()" class="dt-btn-ghost dt-btn-ghost-sm">Cancelar</button>
            <button onclick="saveCard()" class="dt-btn dt-btn-sm">Salvar</button>
        </div>
    </div>

</div>
</div>

<div id="toast-container" class="fixed bottom-6 right-6 z-[200] flex flex-col gap-2 pointer-events-none"></div>

<script src="assets/js/kanban.js"></script>
<script>
function onSolucaoPreset(val) {
    const ta = document.getElementById('f-solucao');
    if (val === '__custom__') {
        ta.classList.remove('hidden');
        ta.value = '';
        ta.focus();
    } else if (val === '') {
        ta.classList.add('hidden');
        ta.value = '';
    } else {
        ta.classList.remove('hidden');
        ta.value = val;
    }
}

// Ao abrir modal com card existente que já tem solução, mostra o textarea
window.addEventListener('load', function() {
    const _orig = window.openModal;
    window.openModal = function(card, col) {
        _orig(card, col);
        const ta     = document.getElementById('f-solucao');
        const preset = document.getElementById('f-solucao-preset');
        const val    = ta ? ta.value.trim() : '';
        if (val) {
            const match = [...preset.options].find(o => o.value === val && o.value !== '' && o.value !== '__custom__');
            preset.value = match ? val : '__custom__';
            ta.classList.remove('hidden');
        } else {
            preset.value = '';
            ta.classList.add('hidden');
        }
    };
});

function toggleInline(sId, fId) {
    const s = document.getElementById(sId);
    const isHidden = s.classList.contains('hidden');
    s.classList.toggle('hidden', !isHidden);
    if (isHidden) document.getElementById(fId)?.focus();
}
function toggleFilterPanel() {
    const p = document.getElementById('filter-panel');
    p.style.display = p.style.display === 'none' ? 'flex' : 'none';
}
function clearAllFilters() {
    ['filter-prioridade','filter-de','filter-ate'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    if (typeof filtros !== 'undefined') {
        filtros.prioridade = ''; filtros.de = ''; filtros.ate = '';
        if (typeof loadCards === 'function') loadCards();
    }
    document.getElementById('filter-dot').classList.add('hidden');
    document.getElementById('btn-clear-filters').style.display = 'none';
}
document.addEventListener('click', e => {
    const panel = document.getElementById('filter-panel');
    const btn   = document.getElementById('btn-filtros');
    if (panel && panel.style.display !== 'none' && !panel.contains(e.target) && !btn.contains(e.target)) {
        panel.style.display = 'none';
    }
});
</script>
</body>
</html>
