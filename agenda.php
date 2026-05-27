<?php require_once 'db.php'; getDB(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda — DevTrack</title>
    <?php include '_tailwind.php'; ?>
</head>
<body class="bg-dt-base text-dt-text font-sans min-h-screen">

<nav class="sticky top-0 z-50 flex items-center gap-2.5 px-4 h-14 bg-dt-surface border-b border-dt-border">
    <a href="index.php" class="text-dt-accent font-bold text-sm flex items-center gap-1.5 no-underline">⬡ DevTrack</a>
    <div class="h-4 w-px bg-dt-border"></div>
    <span class="text-dt-muted text-xs">Agenda</span>
    <div class="h-4 w-px bg-dt-border"></div>
    <span id="clock-browser" class="font-mono text-dt-text tabular-nums text-xs">--:--:--</span>
    <div class="relative">
        <button id="btn-alarm-avulso" onclick="toggleAlarmPanel()"
            class="dt-btn-ghost-sm flex items-center gap-1.5 relative">
            🔔
            <span id="alarm-avulso-badge" class="hidden absolute -top-1 -right-1 w-2 h-2 rounded-full bg-dt-yellow"></span>
        </button>
        <div id="alarm-avulso-panel" style="display:none"
            class="absolute left-0 top-full mt-2 w-72 bg-dt-surface border border-dt-border rounded-xl shadow-modal z-[200] p-4 flex flex-col gap-3">
            <p class="text-xs font-semibold text-dt-text">Novo lembrete</p>
            <div>
                <label class="dt-label">Horário</label>
                <input id="alarm-avulso-hora" type="time" class="dt-input">
            </div>
            <div>
                <label class="dt-label">Observação</label>
                <input id="alarm-avulso-obs" type="text" class="dt-input" placeholder="Do que você quer ser lembrado?">
            </div>
            <button onclick="adicionarAlarmAvulso()" class="dt-btn dt-btn-sm w-full">+ Adicionar lembrete</button>
            <div id="alarm-avulso-lista" class="flex flex-col gap-1.5 empty:hidden"></div>
        </div>
    </div>
    <div class="flex-1"></div>
    <a href="relatorio.php" target="_blank" class="dt-btn-ghost-sm hidden sm:inline-flex">📊 Relatório</a>
    <a href="config.php" class="dt-btn-ghost-sm hidden sm:inline-flex">⚙ Config</a>
    <a href="index.php" class="dt-btn-ghost-sm">← Board</a>
</nav>

<div class="max-w-[1300px] mx-auto p-5 flex gap-5">

    <!-- ── COLUNA PRINCIPAL ── -->
    <div class="flex-1 min-w-0">

        <!-- Controles -->
        <div class="flex items-center gap-2 mb-4">
            <button id="btn-prev" class="dt-btn-ghost dt-btn-ghost-sm px-3">‹</button>
            <h2 id="mes-label" class="text-base font-bold flex-1 text-center tracking-tight"></h2>
            <button id="btn-hoje" class="dt-btn-ghost dt-btn-ghost-sm">Hoje</button>
            <button id="btn-next" class="dt-btn-ghost dt-btn-ghost-sm px-3">›</button>
            <div class="h-4 w-px bg-dt-border mx-1"></div>
            <div class="flex bg-dt-base border border-dt-border rounded-lg overflow-hidden text-xs">
                <button id="btn-view-mes" onclick="setView('mes')"
                    class="px-3 py-1.5 font-medium cursor-pointer border-0 bg-dt-card text-dt-accent transition-colors">⊞ Mês</button>
                <button id="btn-view-semana" onclick="setView('semana')"
                    class="px-3 py-1.5 font-medium cursor-pointer border-0 bg-transparent text-dt-muted hover:text-dt-text transition-colors">☰ Semana</button>
            </div>
            <button onclick="abrirModalNovo()" class="dt-btn dt-btn-sm">+ Nova</button>
        </div>

        <!-- Calendário mês -->
        <div id="view-mes">
            <div class="grid grid-cols-7 mb-1">
                <?php foreach(['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'] as $d): ?>
                <div class="text-center text-[10px] font-semibold text-dt-muted uppercase tracking-wider py-1.5"><?= $d ?></div>
                <?php endforeach; ?>
            </div>
            <div id="cal-grid" class="grid grid-cols-7 gap-px bg-dt-border rounded-xl overflow-hidden"></div>
        </div>

        <!-- Calendário semana -->
        <div id="view-semana" class="hidden">
            <div id="semana-grid"></div>
        </div>

        <!-- Tarefas sem data -->
        <div class="mt-4 bg-dt-surface border border-dt-border rounded-xl overflow-hidden">
            <div class="flex items-center gap-2 px-4 py-2.5 border-b border-dt-border">
                <span class="text-sm">📋</span>
                <span class="font-semibold text-xs flex-1">Tarefas sem data agendada</span>
                <span id="sem-data-count" class="text-xs font-semibold text-dt-muted bg-dt-base border border-dt-border rounded-full px-2 py-0.5">0</span>
            </div>
            <div id="sem-data-list" class="max-h-44 overflow-y-auto divide-y divide-dt-border/40"></div>
        </div>
    </div>

    <!-- ── SIDEBAR RECORRÊNCIAS ── -->
    <div class="w-72 flex-shrink-0 flex flex-col gap-3">

        <div class="bg-dt-surface border border-dt-border rounded-xl overflow-hidden">
            <div class="flex items-center gap-2 px-3 py-2.5 border-b border-dt-border">
                <span class="text-sm">🔁</span>
                <span class="font-semibold text-xs flex-1">Tarefas Recorrentes</span>
                <span id="rec-count" class="text-xs font-semibold text-dt-muted bg-dt-base border border-dt-border rounded-full px-2 py-0.5">0</span>
                <button onclick="abrirModalRecorrencia()" class="dt-btn-icon w-6 h-6 text-sm" title="Nova recorrência">＋</button>
            </div>
            <div id="rec-list" class="divide-y divide-dt-border/40 max-h-[420px] overflow-y-auto"></div>
        </div>

        <!-- Legenda prioridades -->
        <div class="bg-dt-surface border border-dt-border rounded-xl px-3 py-2.5">
            <p class="text-[10px] font-semibold text-dt-muted uppercase tracking-wider mb-2">Legenda</p>
            <div class="flex flex-col gap-1.5">
                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-sm" style="background:#f8514922"></span><span class="text-xs text-dt-muted">🔴 Urgente</span></div>
                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-sm" style="background:#f0883e22"></span><span class="text-xs text-dt-muted">🟠 Alta</span></div>
                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-sm" style="background:#d2992222"></span><span class="text-xs text-dt-muted">🟡 Média</span></div>
                <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-sm" style="background:#3fb95022"></span><span class="text-xs text-dt-muted">🟢 Baixa</span></div>
                <div class="flex items-center gap-2 mt-1 pt-1.5 border-t border-dt-border">
                    <span class="text-xs px-1.5 rounded-sm font-bold" style="background:#58a6ff22;color:#58a6ff">🔁</span>
                    <span class="text-xs text-dt-muted">Recorrente</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── MODAL ATIVIDADE ── -->
<div id="modal-backdrop" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
<div class="bg-dt-surface border border-dt-border/80 rounded-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden shadow-modal">
    <div class="flex items-center gap-3 px-4 py-3 border-b border-dt-border flex-shrink-0">
        <span id="modal-title" class="font-semibold text-sm flex-1">Nova Atividade</span>
        <button id="btn-delete-card" onclick="deletarCard()" style="display:none" class="dt-btn-danger">🗑 Excluir</button>
        <button onclick="fecharModal()" class="w-6 h-6 flex items-center justify-center text-dt-muted hover:text-dt-text hover:bg-dt-border/60 rounded-lg cursor-pointer bg-transparent border-0 text-sm">✕</button>
    </div>
    <div class="overflow-y-auto flex-1 px-4 py-4 flex flex-col gap-2">

        <div>
            <label class="dt-label">Título *</label>
            <input id="f-titulo" type="text" class="dt-input" placeholder="O que precisa ser feito?">
        </div>

        <div class="grid grid-cols-3 gap-x-3 gap-y-2">
            <div>
                <label class="dt-label">Tipo</label>
                <select id="f-tipo" class="dt-select sel"></select>
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
                    <option value="aguardando">Aguardando Retorno</option>
                    <option value="revisao">Em Revisão</option>
                    <option value="concluido">Concluído</option>
                    <option value="bloqueado">Bloqueado</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-x-3 gap-y-2">
            <div>
                <label class="dt-label">Projeto</label>
                <select id="f-projeto" class="dt-select sel"><option value="">— Sem projeto —</option></select>
            </div>
            <div>
                <label class="dt-label">Solicitado por</label>
                <input id="f-solicitado-por" type="text" class="dt-input" placeholder="Nome de quem pediu">
            </div>
        </div>

        <div>
            <label class="dt-label">Descrição</label>
            <textarea id="f-descricao" rows="2" class="dt-input resize-y" placeholder="Contexto e o que foi solicitado..."></textarea>
        </div>

        <div>
            <label class="dt-label">Solução / O que foi feito</label>
            <textarea id="f-solucao" rows="3" class="dt-input resize-y" placeholder="Descreva a solução técnica aplicada..."></textarea>
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

        <div>
            <label class="dt-label flex items-center gap-1.5">🔔 Alarme <span class="text-[10px] text-dt-muted/60 font-normal">(notificação no browser)</span></label>
            <div class="flex gap-2 items-center">
                <input id="f-alarme" type="datetime-local" class="dt-input flex-1">
                <button type="button" id="btn-alarme-limpar" onclick="document.getElementById('f-alarme').value='';this.style.display='none'" style="display:none"
                    class="dt-btn-ghost-sm text-dt-red border-dt-red/30 hover:bg-dt-red/10 whitespace-nowrap">✕ Limpar</button>
            </div>
        </div>

    </div>
    <div class="flex items-center justify-between px-4 py-2.5 border-t border-dt-border flex-shrink-0 bg-dt-base/30">
        <a id="link-board" href="index.php" class="text-xs text-dt-muted/60 hover:text-dt-accent no-underline hidden">Ver no board →</a>
        <span class="text-xs text-dt-muted/50">Ctrl+Enter salvar · Esc fechar</span>
        <div class="flex gap-2">
            <button onclick="fecharModal()" class="dt-btn-ghost dt-btn-ghost-sm">Cancelar</button>
            <button onclick="salvarCard()" class="dt-btn dt-btn-sm">Salvar</button>
        </div>
    </div>
</div>
</div>

<!-- ── MODAL RECORRÊNCIA ── -->
<div id="modal-rec-backdrop" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
<div class="bg-dt-surface border border-dt-border/80 rounded-2xl w-full max-w-md flex flex-col overflow-hidden shadow-modal">
    <div class="flex items-center gap-3 px-4 py-3 border-b border-dt-border">
        <span id="rec-modal-title" class="font-semibold text-sm flex-1">🔁 Nova Tarefa Recorrente</span>
        <button id="btn-delete-rec" onclick="deletarRecorrencia()" style="display:none" class="dt-btn-danger">🗑 Excluir</button>
        <button onclick="fecharModalRec()" class="w-6 h-6 flex items-center justify-center text-dt-muted hover:text-dt-text hover:bg-dt-border/60 rounded-lg cursor-pointer bg-transparent border-0 text-sm">✕</button>
    </div>
    <div class="overflow-y-auto px-4 py-4 flex flex-col gap-3">
        <div>
            <label class="dt-label">Título *</label>
            <input id="r-titulo" type="text" class="dt-input" placeholder="Ex: Reunião de alinhamento">
        </div>

        <!-- Tipo de recorrência -->
        <div>
            <label class="dt-label">Repetir</label>
            <div class="flex gap-2">
                <button onclick="setTipoRec('diario')"   id="rt-diario"   class="rec-tipo-btn flex-1 py-1.5 text-xs font-semibold rounded-lg border border-dt-border bg-dt-base text-dt-muted cursor-pointer transition-colors">Todo dia</button>
                <button onclick="setTipoRec('semanal')"  id="rt-semanal"  class="rec-tipo-btn flex-1 py-1.5 text-xs font-semibold rounded-lg border border-dt-border bg-dt-base text-dt-muted cursor-pointer transition-colors">Semanal</button>
                <button onclick="setTipoRec('mensal')"   id="rt-mensal"   class="rec-tipo-btn flex-1 py-1.5 text-xs font-semibold rounded-lg border border-dt-border bg-dt-base text-dt-muted cursor-pointer transition-colors">Mensal</button>
            </div>
        </div>

        <!-- Dias da semana -->
        <div id="dias-semana-section">
            <label class="dt-label">Dias da semana</label>
            <div class="flex gap-1.5 flex-wrap">
                <?php foreach(['Dom'=>0,'Seg'=>1,'Ter'=>2,'Qua'=>3,'Qui'=>4,'Sex'=>5,'Sáb'=>6] as $label=>$val): ?>
                <button onclick="toggleDia(<?= $val ?>)" id="dia-s-<?= $val ?>"
                    class="dia-btn w-10 h-10 rounded-lg border border-dt-border text-xs font-semibold text-dt-muted bg-dt-base cursor-pointer transition-colors hover:border-dt-muted">
                    <?= $label ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Dias do mês -->
        <div id="dias-mes-section" class="hidden">
            <label class="dt-label">Dias do mês</label>
            <div class="flex flex-wrap gap-1">
                <?php for($i=1;$i<=31;$i++): ?>
                <button onclick="toggleDia(<?= $i ?>)" id="dia-m-<?= $i ?>"
                    class="dia-btn w-8 h-8 rounded-lg border border-dt-border text-[10px] font-semibold text-dt-muted bg-dt-base cursor-pointer transition-colors hover:border-dt-muted">
                    <?= $i ?>
                </button>
                <?php endfor; ?>
                <button onclick="toggleDia(0)" id="dia-m-0"
                    class="dia-btn h-8 px-2 rounded-lg border border-dt-border text-[10px] font-semibold text-dt-muted bg-dt-base cursor-pointer transition-colors hover:border-dt-muted whitespace-nowrap">
                    Último dia
                </button>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="dt-label">Prioridade</label>
                <select id="r-prioridade" class="dt-select sel">
                    <option value="baixa">🟢 Baixa</option>
                    <option value="media" selected>🟡 Média</option>
                    <option value="alta">🟠 Alta</option>
                    <option value="urgente">🔴 Urgente</option>
                </select>
            </div>
            <div>
                <label class="dt-label">Cor no calendário</label>
                <input id="r-cor" type="color" value="#58a6ff" class="dt-color-pick w-full h-9">
            </div>
        </div>
        <div>
            <label class="dt-label">Projeto</label>
            <select id="r-projeto" class="dt-select sel"><option value="">— Sem projeto —</option></select>
        </div>
        <div>
            <label class="dt-label">Descrição</label>
            <textarea id="r-descricao" rows="2" class="dt-input resize-y" placeholder="Detalhes opcionais..."></textarea>
        </div>

        <div>
            <label class="dt-label flex items-center gap-1.5">
                🔔 Alarme diário
                <span class="text-[10px] text-dt-muted/60 font-normal">(notificação no horário)</span>
            </label>
            <div class="flex gap-2 items-center">
                <input id="r-alarme-hora" type="time" class="dt-input flex-1">
                <button type="button" id="btn-rec-alarme-limpar" onclick="limparAlarmeRec()" style="display:none"
                    class="dt-btn-ghost-sm text-dt-red border-dt-red/30 hover:bg-dt-red/10 whitespace-nowrap">✕ Limpar</button>
            </div>
        </div>

        <!-- Toggle ativo -->
        <div id="r-ativo-section" class="hidden flex items-center gap-2 pt-1">
            <label class="text-xs text-dt-muted flex-1">Recorrência ativa</label>
            <button id="r-ativo-btn" onclick="toggleAtivo()" class="relative w-10 h-5 rounded-full border border-dt-border bg-dt-base cursor-pointer transition-colors">
                <span id="r-ativo-dot" class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-dt-muted transition-all"></span>
            </button>
        </div>
    </div>
    <div class="flex justify-end gap-2 px-4 py-2.5 border-t border-dt-border bg-dt-base/30 flex-shrink-0">
        <button onclick="fecharModalRec()" class="dt-btn-ghost dt-btn-ghost-sm">Cancelar</button>
        <button onclick="salvarRecorrencia()" class="dt-btn dt-btn-sm">Salvar</button>
    </div>
</div>
</div>

<!-- ── MODAL EXECUTAR RECORRÊNCIA ── -->
<div id="modal-exec-backdrop" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
<div class="bg-dt-surface border border-dt-border/80 rounded-2xl w-full max-w-md flex flex-col overflow-hidden shadow-modal">
    <div class="flex items-center gap-3 px-4 py-3 border-b border-dt-border">
        <span class="font-semibold text-sm flex-1">✅ Registrar execução</span>
        <button onclick="fecharModalExec()" class="w-6 h-6 flex items-center justify-center text-dt-muted hover:text-dt-text hover:bg-dt-border/60 rounded-lg cursor-pointer bg-transparent border-0 text-sm">✕</button>
    </div>
    <div class="px-4 py-4 flex flex-col gap-3">
        <p class="text-xs text-dt-muted">Registrar <strong id="exec-rec-titulo" class="text-dt-text"></strong> como executada em <strong id="exec-rec-data" class="text-dt-text"></strong>?</p>
        <div>
            <label class="dt-label">Coluna de destino</label>
            <select id="exec-coluna" class="dt-select sel">
                <option value="concluido">✅ Concluído</option>
                <option value="andamento">🔵 Em Andamento</option>
                <option value="revisao">🟣 Em Revisão</option>
            </select>
        </div>
        <div>
            <label class="dt-label">Observação (opcional)</label>
            <input id="exec-obs" type="text" class="dt-input" placeholder="Algum detalhe sobre a execução...">
        </div>
    </div>
    <div class="flex justify-end gap-2 px-4 py-2.5 border-t border-dt-border bg-dt-base/30 flex-shrink-0">
        <button onclick="fecharModalExec()" class="dt-btn-ghost dt-btn-ghost-sm">Cancelar</button>
        <button onclick="confirmarExecucao()" class="dt-btn dt-btn-sm">Registrar no board</button>
    </div>
</div>
</div>

<div id="toast-container" class="fixed bottom-6 right-6 z-[200] flex flex-col gap-2 pointer-events-none"></div>

<script src="assets/js/alarmes.js"></script>
<script>
/* ── relógio browser ── */
;(function(){
    function tick(){
        const d=new Date()
        const pad=n=>String(n).padStart(2,'0')
        document.getElementById('clock-browser').textContent=`${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
    }
    tick(); setInterval(tick,1000)
})()

/* ── alarmes avulsos ── */
const ALARM_KEY='dt-alarmes-avulsos'

function getAlarmes(){ try{ return JSON.parse(localStorage.getItem(ALARM_KEY)||'[]') }catch{ return [] } }
function saveAlarmes(list){ localStorage.setItem(ALARM_KEY, JSON.stringify(list)) }

function toggleAlarmPanel(){
    const p=document.getElementById('alarm-avulso-panel')
    const open=p.style.display==='none'
    p.style.display=open?'flex':'none'
    if(open){ renderAlarmesList(); document.getElementById('alarm-avulso-obs').focus() }
}

function adicionarAlarmAvulso(){
    const hora=document.getElementById('alarm-avulso-hora').value
    const obs=document.getElementById('alarm-avulso-obs').value.trim()
    if(!hora){ document.getElementById('alarm-avulso-hora').focus(); return }
    if(!obs){ document.getElementById('alarm-avulso-obs').focus(); return }
    const list=getAlarmes()
    list.push({ id: Date.now(), hora, obs, data: new Date().toISOString().slice(0,10) })
    saveAlarmes(list)
    document.getElementById('alarm-avulso-hora').value=''
    document.getElementById('alarm-avulso-obs').value=''
    renderAlarmesList()
    atualizarBadge()
    toast('Lembrete adicionado para '+hora,'ok')
}

function removerAlarmAvulso(id){
    saveAlarmes(getAlarmes().filter(a=>a.id!==id))
    renderAlarmesList()
    atualizarBadge()
}

function renderAlarmesList(){
    const el=document.getElementById('alarm-avulso-lista')
    const list=getAlarmes()
    if(!list.length){ el.innerHTML=''; return }
    el.innerHTML=`<p class="text-[10px] font-semibold text-dt-muted uppercase tracking-wider mt-1">Lembretes ativos</p>`+
        list.map(a=>`
        <div class="flex items-center gap-2 bg-dt-base border border-dt-border rounded-lg px-3 py-2">
            <span class="text-xs font-mono font-semibold text-dt-yellow tabular-nums">${esc(a.hora)}</span>
            <span class="text-xs flex-1 text-dt-text truncate">${esc(a.obs)}</span>
            <button onclick="removerAlarmAvulso(${a.id})" class="text-dt-muted hover:text-dt-red text-xs bg-transparent border-0 cursor-pointer p-0.5">✕</button>
        </div>`).join('')
}

function atualizarBadge(){
    const badge=document.getElementById('alarm-avulso-badge')
    badge.classList.toggle('hidden', getAlarmes().length===0)
}

function checkAlarmesAvulsos(){
    const now=new Date()
    const hh=String(now.getHours()).padStart(2,'0')
    const mm=String(now.getMinutes()).padStart(2,'0')
    const horaAtual=`${hh}:${mm}`
    const dataHoje=now.toISOString().slice(0,10)
    const list=getAlarmes()
    const restantes=[]
    list.forEach(a=>{
        if(a.hora===horaAtual && a.data===dataHoje){
            triggerAlarm(
                'Lembrete',
                a.obs,
                ()=>{ /* dismiss: já removido da lista */ },
                ()=>{
                    // soneca: reagendar em 10 min
                    const snoozeAt=new Date(Date.now()+10*60000)
                    const pad=n=>String(n).padStart(2,'0')
                    const novo={...a, hora:`${pad(snoozeAt.getHours())}:${pad(snoozeAt.getMinutes())}`, data:snoozeAt.toISOString().slice(0,10)}
                    saveAlarmes([...getAlarmes(), novo])
                    renderAlarmesList(); atualizarBadge()
                }
            )
        } else {
            restantes.push(a)
        }
    })
    if(restantes.length!==list.length){
        saveAlarmes(restantes)
        renderAlarmesList()
        atualizarBadge()
    }
}

// fechar panel ao clicar fora
document.addEventListener('click',e=>{
    const panel=document.getElementById('alarm-avulso-panel')
    const btn=document.getElementById('btn-alarm-avulso')
    if(panel&&panel.style.display!=='none'&&!panel.contains(e.target)&&!btn.contains(e.target))
        panel.style.display='none'
})

document.getElementById('alarm-avulso-obs')?.addEventListener('keydown',e=>{
    if(e.key==='Enter'){ e.preventDefault(); adicionarAlarmAvulso() }
})

atualizarBadge()

/* ── helpers ── */
function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;')}
function toast(msg,type='ok'){
    const el=document.createElement('div')
    el.className=`pointer-events-auto flex items-center gap-2 px-4 py-2.5 rounded-xl border text-xs font-medium animate-toast shadow-card bg-dt-surface/95 ${type==='ok'?'border-l-[3px] border-l-dt-green border-dt-border':'border-l-[3px] border-l-dt-red border-dt-border'}`
    el.innerHTML=`<span>${type==='ok'?'✓':'✗'}</span><span>${esc(msg)}</span>`
    document.getElementById('toast-container').appendChild(el)
    setTimeout(()=>el.remove(),type==='err'?5000:3000)
}
async function req(action,body=null,extra={}){
    const qs=new URLSearchParams({action,...extra}).toString()
    const opts={headers:{'Content-Type':'application/json'}}
    if(body){opts.method='POST';opts.body=JSON.stringify(body)}
    const r=await fetch('api.php?'+qs,opts);return r.json()
}

const PRI={baixa:{label:'Baixa',color:'#3fb950'},media:{label:'Média',color:'#d29922'},alta:{label:'Alta',color:'#f0883e'},urgente:{label:'Urgente',color:'#f85149'}}
const COL_LABELS={backlog:'Backlog',andamento:'Em Andamento',aguardando:'Aguardando Retorno',revisao:'Em Revisão',concluido:'Concluído',bloqueado:'Bloqueado'}
const DIAS_SEMANA=['Dom','Seg','Ter','Qua','Qui','Sex','Sáb']

/* ── State ── */
let hoje=new Date()
let viewDate=new Date(hoje.getFullYear(),hoje.getMonth(),1)
let allCards=[], projetos=[], recorrencias=[], tipos=[]
let editingId=null, editingRecId=null
let currentView='mes'
let tipoRec='semanal', diasSelecionados=new Set(), ativoRec=true

/* ── Init ── */
;(async()=>{
    await Promise.all([
        req('projetos').then(d=>{ projetos=d }),
        req('tipos').then(d=>{ tipos=d }),
    ])
    buildProjetoSelects()
    buildTipoSelect()
    await Promise.all([carregarCards(), carregarRecorrencias()])
    buildExecLookup()
    render()
})()

async function carregarCards(){
    allCards=await req('listar',null,{limit:500})
}

let execLookup = {} // "recId_date" -> coluna

function buildExecLookup(){
    execLookup={}
    // índice por recorrencia_id (cards novos)
    allCards.forEach(c=>{
        if(c.recorrencia_id && c.data_inicio){
            execLookup[`${c.recorrencia_id}_${c.data_inicio.slice(0,10)}`]=c.coluna
        }
    })
    // fallback: cruza título + data para cards criados antes do campo existir
    recorrencias.forEach(r=>{
        allCards.forEach(c=>{
            if(!c.recorrencia_id && c.data_inicio &&
               c.titulo.trim().toLowerCase()===r.titulo.trim().toLowerCase()){
                const date=c.data_inicio.slice(0,10)
                const key=`${r.id}_${date}`
                if(!execLookup[key]) execLookup[key]=c.coluna
            }
        })
    })
}
async function carregarRecorrencias(){
    recorrencias=await req('recorrencias')
    renderSidebar()
}

/* ── EXPANSÃO DE RECORRÊNCIAS ── */
function recorrenciasParaDia(dateStr){
    const d=new Date(dateStr+'T00:00:00')
    const diaSemana=d.getDay()         // 0-6
    const diaMes=d.getDate()           // 1-31

    const ultimoDiaMes = new Date(d.getFullYear(), d.getMonth()+1, 0).getDate()
    const isUltimoDia  = diaMes === ultimoDiaMes

    return recorrencias.filter(r=>{
        if(!r.ativo) return false
        const dias=JSON.parse(r.dias||'[]')
        if(r.tipo==='diario') return true
        if(r.tipo==='semanal') return dias.includes(diaSemana)
        if(r.tipo==='mensal')  return dias.includes(diaMes) || (isUltimoDia && dias.includes(0))
        return false
    })
}

/* ── RENDER PRINCIPAL ── */
function render(){
    const m=viewDate.getMonth(), y=viewDate.getFullYear()
    const meses=['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro']
    document.getElementById('mes-label').textContent=`${meses[m]} ${y}`
    currentView==='mes' ? renderMes() : renderSemana()
    renderSemData()
}

function renderMes(){
    const y=viewDate.getFullYear(),m=viewDate.getMonth()
    const primeiro=new Date(y,m,1).getDay()
    const ultimoDia=new Date(y,m+1,0).getDate()
    const hojeStr=toDateStr(hoje)
    let html=''

    for(let i=0;i<primeiro;i++)
        html+=`<div class="bg-dt-base/40 min-h-[110px] p-1.5"></div>`

    for(let d=1;d<=ultimoDia;d++){
        const dateStr=`${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`
        const isHoje=dateStr===hojeStr
        const isPassado=dateStr<hojeStr

        const dayCards=allCards.filter(c=>{
            if(!c.data_inicio) return false
            const ini=c.data_inicio.slice(0,10)
            const fim=c.data_fim?c.data_fim.slice(0,10):ini
            return dateStr>=ini&&dateStr<=fim
        })
        const dayRec=recorrenciasParaDia(dateStr)
        const todos=[...dayCards.map(c=>({...c,_rec:false})), ...dayRec.map(r=>({...r,_rec:true,_titulo:r.titulo}))]

        const pills=todos.slice(0,4).map(item=>{
            if(item._rec){
                const done = execLookup[`${item.id}_${dateStr}`]
                if(done){
                    return `<div class="w-full text-[10px] px-1.5 py-0.5 rounded-md truncate flex items-center gap-1"
                        style="background:#3fb95022;color:#3fb950;border-left:2px solid #3fb950"
                        title="${esc(item.titulo)} — concluída">
                        <span class="text-[8px]">✓</span>${esc(item.titulo)}</div>`
                }
                const cor=item.cor||'#58a6ff'
                return `<button onclick="event.stopPropagation();abrirModalExec(${item.id},'${dateStr}')"
                    class="w-full text-left text-[10px] px-1.5 py-0.5 rounded-md truncate flex items-center gap-1 cursor-pointer border-0 hover:opacity-80 transition-opacity"
                    style="background:${cor}22;color:${cor};border-left:2px solid ${cor}"
                    title="${esc(item.titulo)} — clique para registrar execução">
                    <span class="opacity-60 text-[8px]">🔁</span>${esc(item.titulo)}</button>`
            }
            const pri=PRI[item.prioridade]||PRI.media
            return `<button onclick="abrirModalEditar(${item.id})"
                class="w-full text-left text-[10px] px-1.5 py-0.5 rounded-md truncate cursor-pointer border-0 hover:opacity-80 transition-colors"
                style="background:${pri.color}22;color:${pri.color};border-left:2px solid ${pri.color}"
                title="${esc(item.titulo)}">${esc(item.titulo)}</button>`
        }).join('')

        const extra=todos.length>4?`<span class="text-[9px] text-dt-muted/60 pl-1">+${todos.length-4}</span>`:''

        html+=`<div onclick="abrirModalNovoDia('${dateStr}')"
            class="bg-dt-surface min-h-[110px] p-1.5 cursor-pointer hover:bg-dt-card transition-colors group ${isPassado?'opacity-55':''}"
            data-date="${dateStr}">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-semibold w-6 h-6 flex items-center justify-center rounded-full
                    ${isHoje?'bg-dt-accent text-dt-base':'text-dt-muted group-hover:text-dt-text'}">${d}</span>
                ${todos.length>0?`<span class="text-[9px] text-dt-muted/50">${todos.length}</span>`:''}
            </div>
            <div class="flex flex-col gap-0.5" onclick="event.stopPropagation()">${pills}${extra}</div>
        </div>`
    }

    const total=primeiro+ultimoDia
    const resto=(7-total%7)%7
    for(let i=0;i<resto;i++)
        html+=`<div class="bg-dt-base/40 min-h-[110px] p-1.5"></div>`

    document.getElementById('cal-grid').innerHTML=html
}

function renderSemana(){
    const base=new Date(viewDate)
    const ds=base.getDay()
    const ini=new Date(base); ini.setDate(base.getDate()-ds)
    const hojeStr=toDateStr(hoje)
    let html=`<div class="grid grid-cols-7 gap-1">`

    for(let i=0;i<7;i++){
        const dia=new Date(ini); dia.setDate(ini.getDate()+i)
        const dateStr=toDateStr(dia)
        const isHoje=dateStr===hojeStr

        const dayCards=allCards.filter(c=>{
            if(!c.data_inicio) return false
            const ini2=c.data_inicio.slice(0,10)
            const fim=c.data_fim?c.data_fim.slice(0,10):ini2
            return dateStr>=ini2&&dateStr<=fim
        })
        const dayRec=recorrenciasParaDia(dateStr)

        const cardPills=dayCards.map(c=>{
            const pri=PRI[c.prioridade]||PRI.media
            return `<button onclick="abrirModalEditar(${c.id})"
                class="w-full text-left text-[10px] px-2 py-1 rounded-md cursor-pointer border-0 hover:opacity-80 transition-colors"
                style="background:${pri.color}22;color:${pri.color};border-left:2px solid ${pri.color}">
                <div class="font-medium truncate">${esc(c.titulo)}</div>
                <div class="opacity-60">${COL_LABELS[c.coluna]||c.coluna}</div>
            </button>`
        }).join('')

        const recPills=dayRec.map(r=>{
            const done = execLookup[`${r.id}_${dateStr}`]
            if(done){
                return `<div class="w-full text-[10px] px-2 py-1 rounded-md flex gap-1 items-start"
                    style="background:#3fb95022;color:#3fb950;border-left:2px solid #3fb950">
                    <span class="mt-0.5">✓</span>
                    <div>
                        <div class="font-medium">${esc(r.titulo)}</div>
                        <div class="opacity-60">${descRecorrencia(r)}</div>
                    </div>
                </div>`
            }
            const cor=r.cor||'#58a6ff'
            return `<button onclick="abrirModalExec(${r.id},'${dateStr}')"
                class="w-full text-left text-[10px] px-2 py-1 rounded-md flex gap-1 items-start cursor-pointer border-0 hover:opacity-80 transition-opacity"
                style="background:${cor}22;color:${cor};border-left:2px solid ${cor}"
                title="${esc(r.titulo)} — clique para registrar execução">
                <span class="opacity-60 mt-0.5">🔁</span>
                <div>
                    <div class="font-medium">${esc(r.titulo)}</div>
                    <div class="opacity-60">${descRecorrencia(r)}</div>
                </div>
            </button>`
        }).join('')

        html+=`<div class="flex flex-col gap-1 min-h-[200px]">
            <div onclick="abrirModalNovoDia('${dateStr}')" class="flex flex-col items-center py-2 cursor-pointer hover:bg-dt-card rounded-lg transition-colors">
                <span class="text-[10px] text-dt-muted uppercase font-semibold">${DIAS_SEMANA[i]}</span>
                <span class="text-sm font-bold w-8 h-8 flex items-center justify-center rounded-full mt-0.5
                    ${isHoje?'bg-dt-accent text-dt-base':'text-dt-text'}">${dia.getDate()}</span>
            </div>
            <div class="flex flex-col gap-1 px-1">${cardPills}${recPills}</div>
        </div>`
    }
    html+=`</div>`
    document.getElementById('semana-grid').innerHTML=html
}

function renderSemData(){
    const semData=allCards.filter(c=>!c.data_inicio&&c.coluna!=='concluido')
    document.getElementById('sem-data-count').textContent=semData.length
    const el=document.getElementById('sem-data-list')
    if(!semData.length){ el.innerHTML='<p class="text-xs text-dt-muted/50 px-4 py-3">Nenhuma.</p>'; return }
    el.innerHTML=semData.map(c=>{
        const pri=PRI[c.prioridade]||PRI.media
        return `<button onclick="abrirModalEditar(${c.id})"
            class="w-full text-left flex items-center gap-3 px-4 py-2 hover:bg-dt-card transition-colors border-0 bg-transparent cursor-pointer">
            <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background:${pri.color}"></span>
            <span class="text-xs flex-1 truncate">${esc(c.titulo)}</span>
            <span class="text-[10px] text-dt-muted/50">${COL_LABELS[c.coluna]||c.coluna}</span>
        </button>`
    }).join('')
}

/* ── SIDEBAR RECORRÊNCIAS ── */
function renderSidebar(){
    document.getElementById('rec-count').textContent=recorrencias.length
    const el=document.getElementById('rec-list')
    if(!recorrencias.length){
        el.innerHTML='<p class="text-xs text-dt-muted/50 px-3 py-3">Nenhuma tarefa recorrente.</p>'
        return
    }
    el.innerHTML=recorrencias.map(r=>{
        const cor=r.cor||'#58a6ff'
        const pri=PRI[r.prioridade]||PRI.media
        return `<div class="flex items-start gap-2 px-3 py-2.5 hover:bg-dt-card transition-colors group/rec">
            <span class="w-2 h-2 rounded-full flex-shrink-0 mt-1" style="background:${cor}"></span>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium truncate ${r.ativo?'text-dt-text':'text-dt-muted line-through'}">${esc(r.titulo)}</p>
                <p class="text-[10px] text-dt-muted/70 mt-0.5">${descRecorrencia(r)}${r.alarme_hora?` · 🔔 ${r.alarme_hora}`:''}</p>
                ${r.projeto_nome?`<p class="text-[10px] text-dt-muted/50 truncate">${esc(r.projeto_nome)}</p>`:''}
            </div>
            <div class="flex gap-1 opacity-0 group-hover/rec:opacity-100 transition-opacity">
                <button onclick="abrirModalRecorrenciaEditar(${r.id})" class="text-dt-muted hover:text-dt-text text-xs p-0.5 bg-transparent border-0 cursor-pointer">✏</button>
            </div>
        </div>`
    }).join('')

    // hover manual (group-hover não funciona em JS no Tailwind CDN)
    el.querySelectorAll('[class*="group/rec"]').forEach(row=>{
        const btns=row.querySelector('.flex.gap-1')
        if(!btns) return
        row.addEventListener('mouseenter',()=>btns.style.opacity='1')
        row.addEventListener('mouseleave',()=>btns.style.opacity='0')
    })
}

function descRecorrencia(r){
    const dias=JSON.parse(r.dias||'[]')
    if(r.tipo==='diario') return 'Todo dia'
    if(r.tipo==='semanal') return 'Toda '+dias.map(d=>DIAS_SEMANA[d]).join(', ')
    if(r.tipo==='mensal')  return 'Dia '+dias.map(d=>d===0?'último':d).join(', ')+' do mês'
    return ''
}

/* ── NAVEGAÇÃO ── */
document.getElementById('btn-prev').addEventListener('click',()=>{
    if(currentView==='mes') viewDate.setMonth(viewDate.getMonth()-1)
    else viewDate.setDate(viewDate.getDate()-7)
    carregarCards().then(()=>{ buildExecLookup(); render() })
})
document.getElementById('btn-next').addEventListener('click',()=>{
    if(currentView==='mes') viewDate.setMonth(viewDate.getMonth()+1)
    else viewDate.setDate(viewDate.getDate()+7)
    carregarCards().then(()=>{ buildExecLookup(); render() })
})
document.getElementById('btn-hoje').addEventListener('click',()=>{
    viewDate=new Date(hoje.getFullYear(),hoje.getMonth(),1)
    carregarCards().then(()=>{ buildExecLookup(); render() })
})
function setView(v){
    currentView=v
    document.getElementById('view-mes').classList.toggle('hidden',v!=='mes')
    document.getElementById('view-semana').classList.toggle('hidden',v!=='semana')
    document.getElementById('btn-view-mes').className=`px-3 py-1.5 font-medium cursor-pointer border-0 transition-colors ${v==='mes'?'bg-dt-card text-dt-accent':'bg-transparent text-dt-muted hover:text-dt-text'}`
    document.getElementById('btn-view-semana').className=`px-3 py-1.5 font-medium cursor-pointer border-0 transition-colors ${v==='semana'?'bg-dt-card text-dt-accent':'bg-transparent text-dt-muted hover:text-dt-text'}`
    render()
}

/* ── MODAL ATIVIDADE ── */
const backdrop=document.getElementById('modal-backdrop')

function abrirModalNovo(){
    editingId=null
    document.getElementById('modal-title').textContent='Nova Atividade'
    document.getElementById('btn-delete-card').style.display='none'
    document.getElementById('link-board').classList.add('hidden')
    limparModal(); abrirBackdrop(backdrop)
    setTimeout(()=>document.getElementById('f-titulo').focus(),50)
}
function abrirModalNovoDia(dateStr){
    abrirModalNovo()
    document.getElementById('f-data-inicio').value=dateStr
    document.getElementById('f-data-fim').value=dateStr
}
function abrirModalEditar(id){
    const card=allCards.find(c=>c.id===id); if(!card) return
    editingId=id
    document.getElementById('modal-title').textContent='Editar Atividade'
    document.getElementById('btn-delete-card').style.display='inline-flex'
    document.getElementById('link-board').classList.remove('hidden')
    document.getElementById('f-titulo').value=card.titulo||''
    document.getElementById('f-descricao').value=card.descricao||''
    document.getElementById('f-solucao').value=card.solucao||''
    document.getElementById('f-prioridade').value=card.prioridade||'media'
    document.getElementById('f-coluna').value=card.coluna||'backlog'
    document.getElementById('f-projeto').value=card.projeto_id||''
    document.getElementById('f-solicitado-por').value=card.solicitado_por||''
    document.getElementById('f-data-inicio').value=card.data_inicio?card.data_inicio.slice(0,10):''
    document.getElementById('f-data-fim').value=card.data_fim?card.data_fim.slice(0,10):''
    document.getElementById('f-tempo').value=card.tempo_gasto>0?fmtTempo(card.tempo_gasto):''
    document.getElementById('f-tipo').value=card.tipo||(tipos[0]?.nome||'Outro')
    const alarmeEl=document.getElementById('f-alarme')
    alarmeEl.value=card.alarme_em?card.alarme_em.replace(' ','T').slice(0,16):''
    document.getElementById('btn-alarme-limpar').style.display=card.alarme_em?'':'none'
    abrirBackdrop(backdrop)
    setTimeout(()=>document.getElementById('f-titulo').focus(),50)
}
function fecharModal(){ fecharBackdrop(backdrop); editingId=null }
function limparModal(){
    ['f-titulo','f-descricao','f-solucao','f-solicitado-por','f-data-inicio','f-data-fim','f-tempo','f-alarme']
        .forEach(id=>{ const el=document.getElementById(id); if(el) el.value='' })
    document.getElementById('f-prioridade').value='media'
    document.getElementById('f-coluna').value='backlog'
    document.getElementById('f-projeto').value=''
    document.getElementById('f-tipo').value=tipos[0]?.nome||'Outro'
    document.getElementById('btn-alarme-limpar').style.display='none'
    document.getElementById('link-board').classList.add('hidden')
    document.getElementById('btn-delete-card').style.display='none'
}
function fmtTempo(min){ if(!min) return ''; const h=Math.floor(min/60),m=min%60; return h>0?`${h}h${m>0?m+'m':''}`:m+'m' }
function parseTempo(s){
    if(!s) return 0; s=s.toLowerCase().trim()
    const hM=s.match(/(\d+)\s*h/),mM=s.match(/(\d+)\s*m/); let t=0
    if(hM) t+=parseInt(hM[1])*60; if(mM) t+=parseInt(mM[1])
    if(!hM&&!mM){ const n=parseInt(s); if(!isNaN(n)) t=n }
    return t
}
function buildTipoSelect(){
    const opts=tipos.map(t=>`<option value="${esc(t.nome)}">${esc(t.nome)}</option>`).join('')
    document.getElementById('f-tipo').innerHTML=opts||'<option value="Outro">Outro</option>'
}
document.addEventListener('change',e=>{
    if(e.target.id==='f-alarme')
        document.getElementById('btn-alarme-limpar').style.display=e.target.value?'':'none'
})
async function salvarCard(){
    const titulo=document.getElementById('f-titulo').value.trim()
    if(!titulo){document.getElementById('f-titulo').focus();return}
    const payload={
        titulo,
        descricao:  document.getElementById('f-descricao').value,
        solucao:    document.getElementById('f-solucao').value,
        tipo:       document.getElementById('f-tipo').value||'Outro',
        prioridade: document.getElementById('f-prioridade').value,
        coluna:     document.getElementById('f-coluna').value,
        projeto_id: document.getElementById('f-projeto').value||null,
        solicitado_por: document.getElementById('f-solicitado-por').value.trim(),
        data_inicio:document.getElementById('f-data-inicio').value||null,
        data_fim:   document.getElementById('f-data-fim').value||null,
        tempo_gasto:parseTempo(document.getElementById('f-tempo').value),
        alarme_em:  document.getElementById('f-alarme').value||null,
        tags:'', link:'',
    }
    if(editingId){payload.id=editingId;await req('atualizar',payload);toast('Atualizada','ok')}
    else{await req('criar',payload);toast('Criada','ok')}
    fecharModal(); await carregarCards(); buildExecLookup(); render()
}
async function deletarCard(){
    if(!editingId||!confirm('Mover para a lixeira?'))return
    await req('deletar',{id:editingId}); toast('Excluída','ok')
    fecharModal(); await carregarCards(); buildExecLookup(); render()
}

/* ── MODAL RECORRÊNCIA ── */
const recBackdrop=document.getElementById('modal-rec-backdrop')

function abrirModalRecorrencia(){
    editingRecId=null
    document.getElementById('rec-modal-title').textContent='🔁 Nova Tarefa Recorrente'
    document.getElementById('btn-delete-rec').style.display='none'
    document.getElementById('r-ativo-section').classList.add('hidden')
    limparModalRec()
    abrirBackdrop(recBackdrop)
    setTimeout(()=>document.getElementById('r-titulo').focus(),50)
}
function abrirModalRecorrenciaEditar(id){
    const r=recorrencias.find(x=>x.id===id); if(!r) return
    editingRecId=id
    document.getElementById('rec-modal-title').textContent='🔁 Editar Recorrência'
    document.getElementById('btn-delete-rec').style.display='inline-flex'
    document.getElementById('r-ativo-section').classList.remove('hidden')
    document.getElementById('r-titulo').value=r.titulo||''
    document.getElementById('r-descricao').value=r.descricao||''
    document.getElementById('r-prioridade').value=r.prioridade||'media'
    document.getElementById('r-cor').value=r.cor||'#58a6ff'
    document.getElementById('r-projeto').value=r.projeto_id||''
    const alarmeHoraEl=document.getElementById('r-alarme-hora')
    alarmeHoraEl.value=r.alarme_hora||''
    document.getElementById('btn-rec-alarme-limpar').style.display=r.alarme_hora?'':'none'
    ativoRec=!!r.ativo; atualizarToggleAtivo()
    // tipo e dias
    setTipoRec(r.tipo||'semanal')
    diasSelecionados=new Set(JSON.parse(r.dias||'[]'))
    diasSelecionados.forEach(n=>toggleDia(n))
    abrirBackdrop(recBackdrop)
    setTimeout(()=>document.getElementById('r-titulo').focus(),50)
}
function fecharModalRec(){ fecharBackdrop(recBackdrop); editingRecId=null }
function limparModalRec(){
    ['r-titulo','r-descricao','r-alarme-hora'].forEach(id=>document.getElementById(id).value='')
    document.getElementById('r-prioridade').value='media'
    document.getElementById('r-cor').value='#58a6ff'
    document.getElementById('r-projeto').value=''
    document.getElementById('btn-rec-alarme-limpar').style.display='none'
    diasSelecionados=new Set(); ativoRec=true
    setTipoRec('semanal')
}

function limparAlarmeRec(){
    document.getElementById('r-alarme-hora').value=''
    document.getElementById('btn-rec-alarme-limpar').style.display='none'
}

function setTipoRec(tipo){
    tipoRec=tipo
    document.getElementById('dias-semana-section').classList.toggle('hidden', tipo!=='semanal')
    document.getElementById('dias-mes-section').classList.toggle('hidden', tipo!=='mensal')
    document.querySelectorAll('.rec-tipo-btn').forEach(b=>{
        const active=b.id==='rt-'+tipo
        b.className=`rec-tipo-btn flex-1 py-1.5 text-xs font-semibold rounded-lg border cursor-pointer transition-colors ${active?'border-dt-accent bg-dt-accent/10 text-dt-accent':'border-dt-border bg-dt-base text-dt-muted'}`
    })
    // Limpar seleção ao trocar tipo
    diasSelecionados=new Set()
    document.querySelectorAll('.dia-btn').forEach(b=>{
        const isS=b.id.startsWith('dia-s-')
        const size=isS?'w-10 h-10':'w-8 h-8'
        const textSize=isS?'text-xs':'text-[10px]'
        b.className=`dia-btn ${size} rounded-lg border ${textSize} font-semibold cursor-pointer transition-colors border-dt-border bg-dt-base text-dt-muted`
    })
}

function toggleDia(n){
    diasSelecionados.has(n)?diasSelecionados.delete(n):diasSelecionados.add(n)
    const prefix = tipoRec==='semanal' ? 'dia-s-' : 'dia-m-'
    const btn=document.getElementById(prefix+n)
    if(!btn) return
    const sel=diasSelecionados.has(n)
    const isUltimo = n===0
    const size=tipoRec==='semanal'?'w-10 h-10':isUltimo?'h-8 px-2 whitespace-nowrap':'w-8 h-8'
    const textSize=tipoRec==='semanal'?'text-xs':'text-[10px]'
    btn.className=`dia-btn ${size} rounded-lg border ${textSize} font-semibold cursor-pointer transition-colors ${sel?'border-dt-accent bg-dt-accent/10 text-dt-accent':'border-dt-border bg-dt-base text-dt-muted'}`
}

function toggleAtivo(){
    ativoRec=!ativoRec; atualizarToggleAtivo()
}
function atualizarToggleAtivo(){
    const btn=document.getElementById('r-ativo-btn')
    const dot=document.getElementById('r-ativo-dot')
    btn.style.background=ativoRec?'#58a6ff33':'transparent'
    btn.style.borderColor=ativoRec?'#58a6ff':'#30363d'
    dot.style.background=ativoRec?'#58a6ff':'#8b949e'
    dot.style.transform=ativoRec?'translateX(20px)':'translateX(0)'
}

async function salvarRecorrencia(){
    const titulo=document.getElementById('r-titulo').value.trim()
    if(!titulo){document.getElementById('r-titulo').focus();return}
    if(tipoRec!=='diario'&&diasSelecionados.size===0){
        toast('Selecione pelo menos um dia','err'); return
    }
    const payload={
        titulo, descricao:document.getElementById('r-descricao').value,
        tipo:tipoRec, dias:[...diasSelecionados],
        prioridade:document.getElementById('r-prioridade').value,
        cor:document.getElementById('r-cor').value,
        projeto_id:document.getElementById('r-projeto').value||null,
        ativo:ativoRec,
        alarme_hora:document.getElementById('r-alarme-hora').value||null,
    }
    if(editingRecId){
        payload.id=editingRecId
        await req('atualizar_recorrencia',payload); toast('Recorrência atualizada','ok')
    } else {
        await req('criar_recorrencia',payload); toast('Recorrência criada','ok')
    }
    fecharModalRec(); await carregarRecorrencias(); render()
}

async function deletarRecorrencia(){
    if(!editingRecId||!confirm('Excluir esta recorrência?'))return
    await req('deletar_recorrencia',{id:editingRecId}); toast('Excluída','ok')
    fecharModalRec(); await carregarRecorrencias(); render()
}

/* ── MODAL EXECUTAR RECORRÊNCIA ── */
const execBackdrop = document.getElementById('modal-exec-backdrop')
let execRecId = null, execRecData = null

function abrirModalExec(recId, dateStr) {
    const r = recorrencias.find(x => x.id === recId)
    if (!r) return
    if (execLookup[`${recId}_${dateStr}`]) return // já executada
    execRecId = recId
    execRecData = dateStr
    const [y,m,d] = dateStr.split('-')
    document.getElementById('exec-rec-titulo').textContent = r.titulo
    document.getElementById('exec-rec-data').textContent = `${d}/${m}/${y}`
    document.getElementById('exec-coluna').value = 'concluido'
    document.getElementById('exec-obs').value = ''
    abrirBackdrop(execBackdrop)
    setTimeout(() => document.getElementById('exec-obs').focus(), 50)
}

function fecharModalExec() {
    fecharBackdrop(execBackdrop)
    execRecId = null; execRecData = null
}

async function confirmarExecucao() {
    const r = recorrencias.find(x => x.id === execRecId)
    if (!r || !execRecData) return
    const obs = document.getElementById('exec-obs').value.trim()
    const coluna = document.getElementById('exec-coluna').value
    const payload = {
        titulo: r.titulo,
        descricao: r.descricao || '',
        solucao: obs,
        tipo: 'Outro',
        prioridade: r.prioridade || 'media',
        coluna,
        projeto_id: r.projeto_id || null,
        recorrencia_id: r.id,
        tags: '', link: '', tempo_gasto: 0, solicitado_por: '',
        data_inicio: execRecData,
        data_fim: execRecData,
    }
    await req('criar', payload)
    toast(`"${r.titulo}" registrada no board ✓`, 'ok')
    fecharModalExec()
    await carregarCards()
    buildExecLookup()
    render()
}

execBackdrop.addEventListener('click', e => { if (e.target === execBackdrop) fecharModalExec() })
document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && execBackdrop.classList.contains('flex')) fecharModalExec()
    if (e.ctrlKey && e.key === 'Enter' && execBackdrop.classList.contains('flex')) { e.preventDefault(); confirmarExecucao() }
})

/* ── SELECTS ── */
function buildProjetoSelects(){
    const opts=projetos.map(p=>`<option value="${p.id}">${esc(p.nome)}</option>`).join('')
    const base='<option value="">— Sem projeto —</option>'+opts
    document.getElementById('f-projeto').innerHTML=base
    document.getElementById('r-projeto').innerHTML=base
}

/* ── UTILS ── */
function toDateStr(d){ return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}` }
function abrirBackdrop(el){ el.classList.remove('hidden'); el.classList.add('flex') }
function fecharBackdrop(el){ el.classList.add('hidden'); el.classList.remove('flex') }

/* ── KEYBOARD ── */
document.addEventListener('keydown',e=>{
    if(e.key==='Escape'){
        if(backdrop.classList.contains('flex')) fecharModal()
        if(recBackdrop.classList.contains('flex')) fecharModalRec()
    }
    if(e.ctrlKey&&e.key==='Enter'){
        e.preventDefault()
        if(backdrop.classList.contains('flex')) salvarCard()
        if(recBackdrop.classList.contains('flex')) salvarRecorrencia()
    }
})
backdrop.addEventListener('click',e=>{ if(e.target===backdrop) fecharModal() })
recBackdrop.addEventListener('click',e=>{ if(e.target===recBackdrop) fecharModalRec() })

document.addEventListener('change',e=>{
    if(e.target.id==='r-alarme-hora'){
        document.getElementById('btn-rec-alarme-limpar').style.display=e.target.value?'':'none'
    }
})

/* ── ALARMES RECORRÊNCIAS ── */
function recorrenciaAtivaHoje(r){
    const d=new Date()
    const diaSemana=d.getDay(), diaMes=d.getDate()
    const ultimoDiaMes=new Date(d.getFullYear(),d.getMonth()+1,0).getDate()
    const isUltimoDia=diaMes===ultimoDiaMes
    const dias=JSON.parse(r.dias||'[]')
    if(!r.ativo) return false
    if(r.tipo==='diario') return true
    if(r.tipo==='semanal') return dias.includes(diaSemana)
    if(r.tipo==='mensal') return dias.includes(diaMes)||(isUltimoDia&&dias.includes(0))
    return false
}

function _snoozeRecorrencia(id){
    const snoozeAt=new Date(Date.now()+10*60000)
    const pad=n=>String(n).padStart(2,'0')
    localStorage.setItem(`dt-rec-snooze-${id}`,`${pad(snoozeAt.getHours())}:${pad(snoozeAt.getMinutes())}`)
}

function checkAlarmesRec(){
    const now=new Date()
    const hh=String(now.getHours()).padStart(2,'0')
    const mm=String(now.getMinutes()).padStart(2,'0')
    const horaAtual=`${hh}:${mm}`
    const dataHoje=now.toISOString().slice(0,10)
    recorrencias.forEach(r=>{
        if(!r.alarme_hora||!r.ativo) return
        if(!recorrenciaAtivaHoje(r)) return
        const snoozeKey=`dt-rec-snooze-${r.id}`
        const horaEfetiva=localStorage.getItem(snoozeKey)||r.alarme_hora
        if(horaEfetiva!==horaAtual) return
        const fireKey=`dt-rec-alarm-${r.id}-${dataHoje}-${horaAtual}`
        if(localStorage.getItem(fireKey)) return
        localStorage.setItem(fireKey,'1')
        localStorage.removeItem(snoozeKey)
        triggerAlarm('Recorrente – '+r.titulo, r.descricao||'', ()=>{}, ()=>_snoozeRecorrencia(r.id))
    })
}

requestNotifPermission()
setInterval(()=>{ checkAlarmesRec(); checkAlarmesAvulsos() }, 60000)
setTimeout(()=>{ checkAlarmesRec(); checkAlarmesAvulsos() }, 2000)
</script>
</body>
</html>
