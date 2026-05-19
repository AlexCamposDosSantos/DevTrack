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
    <div class="flex-1"></div>
    <a href="relatorio.php" target="_blank" class="dt-btn-ghost-sm hidden sm:inline-flex">📊 Relatório</a>
    <a href="config.php" class="dt-btn-ghost-sm hidden sm:inline-flex">⚙ Config</a>
    <a href="index.php" class="dt-btn-ghost-sm">← Board</a>
</nav>

<div class="max-w-[1200px] mx-auto p-5">

    <!-- Cabeçalho da agenda -->
    <div class="flex items-center gap-3 mb-5">
        <button id="btn-prev" class="dt-btn-ghost dt-btn-ghost-sm px-3">‹</button>
        <h2 id="mes-label" class="text-base font-bold flex-1 text-center tracking-tight"></h2>
        <button id="btn-hoje" class="dt-btn-ghost dt-btn-ghost-sm">Hoje</button>
        <button id="btn-next" class="dt-btn-ghost dt-btn-ghost-sm px-3">›</button>

        <div class="h-4 w-px bg-dt-border mx-1"></div>

        <!-- Toggle visão -->
        <div class="flex bg-dt-base border border-dt-border rounded-lg overflow-hidden text-xs">
            <button id="btn-view-mes" onclick="setAgendaView('mes')"
                class="px-3 py-1.5 font-medium cursor-pointer border-0 bg-dt-card text-dt-accent transition-colors">⊞ Mês</button>
            <button id="btn-view-semana" onclick="setAgendaView('semana')"
                class="px-3 py-1.5 font-medium cursor-pointer border-0 bg-transparent text-dt-muted hover:text-dt-text transition-colors">☰ Semana</button>
        </div>

        <button onclick="abrirModalNovo()" class="dt-btn dt-btn-sm">+ Nova</button>
    </div>

    <!-- Grade do calendário (mês) -->
    <div id="view-mes">
        <div class="grid grid-cols-7 mb-1">
            <?php foreach(['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'] as $d): ?>
            <div class="text-center text-[10px] font-semibold text-dt-muted uppercase tracking-wider py-1.5"><?= $d ?></div>
            <?php endforeach; ?>
        </div>
        <div id="cal-grid" class="grid grid-cols-7 gap-px bg-dt-border rounded-xl overflow-hidden"></div>
    </div>

    <!-- Visão semana -->
    <div id="view-semana" class="hidden">
        <div id="semana-grid"></div>
    </div>

    <!-- Painel lateral: próximas tarefas sem data -->
    <div class="mt-5 bg-dt-surface border border-dt-border rounded-xl overflow-hidden">
        <div class="flex items-center gap-2 px-4 py-3 border-b border-dt-border">
            <span class="text-sm">📋</span>
            <span class="font-semibold text-sm flex-1">Tarefas sem data agendada</span>
            <span id="sem-data-count" class="text-xs font-semibold text-dt-muted bg-dt-base border border-dt-border rounded-full px-2 py-0.5">0</span>
        </div>
        <div id="sem-data-list" class="divide-y divide-dt-border/40 max-h-52 overflow-y-auto"></div>
    </div>
</div>

<!-- ── MODAL (reaproveitado do board) ── -->
<div id="modal-backdrop" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
<div class="modal-box-inner bg-dt-surface border border-dt-border/80 rounded-2xl w-full max-w-lg flex flex-col overflow-hidden shadow-modal">

    <div class="flex items-center gap-3 px-4 py-3 border-b border-dt-border flex-shrink-0">
        <span id="modal-title-text" class="font-semibold text-sm flex-1">Nova Atividade</span>
        <button id="btn-delete-card" onclick="deletarCard()" style="display:none" class="dt-btn-danger">🗑 Excluir</button>
        <button onclick="fecharModal()" class="w-6 h-6 flex items-center justify-center text-dt-muted hover:text-dt-text hover:bg-dt-border/60 rounded-lg cursor-pointer bg-transparent border-0 text-sm">✕</button>
    </div>

    <div class="overflow-y-auto px-4 py-4 flex flex-col gap-3">
        <div>
            <label class="dt-label">Título *</label>
            <input id="f-titulo" type="text" class="dt-input" placeholder="O que precisa ser feito?">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="dt-label">Data início</label>
                <input id="f-data-inicio" type="date" class="dt-input">
            </div>
            <div>
                <label class="dt-label">Data fim</label>
                <input id="f-data-fim" type="date" class="dt-input">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
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
        <div>
            <label class="dt-label">Projeto</label>
            <select id="f-projeto" class="dt-select sel"><option value="">— Sem projeto —</option></select>
        </div>
        <div>
            <label class="dt-label">Descrição</label>
            <textarea id="f-descricao" rows="2" class="dt-input resize-y" placeholder="Detalhes..."></textarea>
        </div>
    </div>

    <div class="flex items-center justify-between px-4 py-2.5 border-t border-dt-border bg-dt-base/30 flex-shrink-0">
        <a id="modal-link-board" href="index.php" class="text-xs text-dt-muted/60 hover:text-dt-accent no-underline hidden">Ver no board →</a>
        <span class="text-xs text-dt-muted/60" id="modal-hint">Ctrl+Enter salvar · Esc fechar</span>
        <div class="flex gap-2">
            <button onclick="fecharModal()" class="dt-btn-ghost dt-btn-ghost-sm">Cancelar</button>
            <button onclick="salvarCard()" class="dt-btn dt-btn-sm">Salvar</button>
        </div>
    </div>
</div>
</div>

<div id="toast-container" class="fixed bottom-6 right-6 z-[200] flex flex-col gap-2 pointer-events-none"></div>

<script>
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
function fmtDateBR(s){
    if(!s)return''
    const [y,m,d]=s.split('-')
    return `${d}/${m}`
}
function fmtDateFull(s){
    if(!s)return''
    const [y,m,d]=s.split('-')
    return `${d}/${m}/${y}`
}

const PRIORIDADES={baixa:{label:'Baixa',color:'#3fb950'},media:{label:'Média',color:'#d29922'},alta:{label:'Alta',color:'#f0883e'},urgente:{label:'Urgente',color:'#f85149'}}
const COL_LABELS={backlog:'Backlog',andamento:'Em Andamento',aguardando:'Aguardando Retorno',revisao:'Em Revisão',concluido:'Concluído',bloqueado:'Bloqueado'}

/* ── State ── */
let hoje = new Date()
let viewDate = new Date(hoje.getFullYear(), hoje.getMonth(), 1)
let allCards = []
let projetos = []
let editingId = null
let agendaView = 'mes'

/* ── Init ── */
;(async()=>{
    projetos = await req('projetos')
    buildProjetoSelect()
    await carregarCards()
    render()
})()

async function carregarCards(){
    // Busca cards do mês atual e adjacentes (janela de 3 meses)
    const de  = new Date(viewDate.getFullYear(), viewDate.getMonth()-1, 1).toISOString().slice(0,10)
    const ate = new Date(viewDate.getFullYear(), viewDate.getMonth()+2, 0).toISOString().slice(0,10)
    allCards = await req('listar', null, {limit:500})
}

function render(){
    const m = viewDate.getMonth(), y = viewDate.getFullYear()
    const meses=['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro']
    document.getElementById('mes-label').textContent = `${meses[m]} ${y}`

    if(agendaView==='mes') renderMes()
    else renderSemana()
    renderSemData()
}

/* ── VISÃO MÊS ── */
function renderMes(){
    const y=viewDate.getFullYear(), m=viewDate.getMonth()
    const primeiro=new Date(y,m,1).getDay()
    const ultimoDia=new Date(y,m+1,0).getDate()
    const hojeStr=`${hoje.getFullYear()}-${String(hoje.getMonth()+1).padStart(2,'0')}-${String(hoje.getDate()).padStart(2,'0')}`

    let html=''
    // Células vazias antes do primeiro dia
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
            return dateStr>=ini && dateStr<=fim
        })

        const pills=dayCards.slice(0,3).map(c=>{
            const pri=PRIORIDADES[c.prioridade]||PRIORIDADES.media
            return `<button onclick="abrirModalEditar(${c.id})"
                class="w-full text-left text-[10px] px-1.5 py-0.5 rounded-md truncate cursor-pointer border-0 transition-colors hover:opacity-80"
                style="background:${pri.color}22;color:${pri.color};border-left:2px solid ${pri.color}"
                title="${esc(c.titulo)}">${esc(c.titulo)}</button>`
        }).join('')

        const extra=dayCards.length>3?`<span class="text-[9px] text-dt-muted/60 pl-1">+${dayCards.length-3} mais</span>`:''

        html+=`<div onclick="abrirModalNovoDia('${dateStr}')"
            class="bg-dt-surface min-h-[110px] p-1.5 cursor-pointer transition-colors hover:bg-dt-card group ${isPassado?'opacity-60':''}"
            data-date="${dateStr}">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-semibold w-6 h-6 flex items-center justify-center rounded-full
                    ${isHoje?'bg-dt-accent text-dt-base':'text-dt-muted group-hover:text-dt-text'}">${d}</span>
                ${dayCards.length>0?`<span class="text-[9px] text-dt-muted/50 tabular-nums">${dayCards.length}</span>`:''}
            </div>
            <div class="flex flex-col gap-0.5" onclick="event.stopPropagation()">${pills}${extra}</div>
        </div>`
    }

    // Preenche restante da grade
    const total=primeiro+ultimoDia
    const resto=(7-total%7)%7
    for(let i=0;i<resto;i++)
        html+=`<div class="bg-dt-base/40 min-h-[110px] p-1.5"></div>`

    document.getElementById('cal-grid').innerHTML=html
}

/* ── VISÃO SEMANA ── */
function renderSemana(){
    const y=viewDate.getFullYear(), m=viewDate.getMonth()
    // Semana atual baseada em viewDate
    const base=new Date(viewDate)
    const diaSemana=base.getDay()
    const inicioSemana=new Date(base); inicioSemana.setDate(base.getDate()-diaSemana)
    const diasSemana=['Dom','Seg','Ter','Qua','Qui','Sex','Sáb']
    const hojeStr=hoje.toISOString().slice(0,10)

    let html=`<div class="grid grid-cols-7 gap-1">`
    for(let i=0;i<7;i++){
        const dia=new Date(inicioSemana); dia.setDate(inicioSemana.getDate()+i)
        const dateStr=dia.toISOString().slice(0,10)
        const isHoje=dateStr===hojeStr

        const dayCards=allCards.filter(c=>{
            if(!c.data_inicio) return false
            const ini=c.data_inicio.slice(0,10)
            const fim=c.data_fim?c.data_fim.slice(0,10):ini
            return dateStr>=ini && dateStr<=fim
        })

        const pills=dayCards.map(c=>{
            const pri=PRIORIDADES[c.prioridade]||PRIORIDADES.media
            const col=COL_LABELS[c.coluna]||c.coluna
            return `<button onclick="abrirModalEditar(${c.id})"
                class="w-full text-left text-[10px] px-2 py-1 rounded-md cursor-pointer border-0 hover:opacity-80 transition-colors"
                style="background:${pri.color}22;color:${pri.color};border-left:2px solid ${pri.color}">
                <div class="font-medium truncate">${esc(c.titulo)}</div>
                <div class="opacity-60">${col}</div>
            </button>`
        }).join('')

        html+=`<div class="flex flex-col gap-1 min-h-[200px]">
            <div onclick="abrirModalNovoDia('${dateStr}')" class="flex flex-col items-center py-2 cursor-pointer hover:bg-dt-card rounded-lg transition-colors">
                <span class="text-[10px] text-dt-muted uppercase font-semibold">${diasSemana[i]}</span>
                <span class="text-sm font-bold w-8 h-8 flex items-center justify-center rounded-full mt-0.5
                    ${isHoje?'bg-dt-accent text-dt-base':'text-dt-text'}">${dia.getDate()}</span>
            </div>
            <div class="flex flex-col gap-1 px-1">${pills}</div>
        </div>`
    }
    html+=`</div>`
    document.getElementById('semana-grid').innerHTML=html
}

/* ── SEM DATA ── */
function renderSemData(){
    const semData=allCards.filter(c=>!c.data_inicio && c.coluna!=='concluido')
    document.getElementById('sem-data-count').textContent=semData.length
    const el=document.getElementById('sem-data-list')
    if(!semData.length){
        el.innerHTML='<p class="text-xs text-dt-muted/50 px-4 py-3">Todas as tarefas têm data agendada.</p>'
        return
    }
    el.innerHTML=semData.map(c=>{
        const pri=PRIORIDADES[c.prioridade]||PRIORIDADES.media
        return `<button onclick="abrirModalEditar(${c.id})"
            class="w-full text-left flex items-center gap-3 px-4 py-2.5 hover:bg-dt-card transition-colors border-0 bg-transparent cursor-pointer">
            <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background:${pri.color}"></span>
            <span class="text-xs text-dt-text flex-1 truncate">${esc(c.titulo)}</span>
            <span class="text-[10px] text-dt-muted/50 whitespace-nowrap">${COL_LABELS[c.coluna]||c.coluna}</span>
            <span class="text-[10px] px-1.5 py-0.5 rounded-md" style="background:${pri.color}22;color:${pri.color}">${pri.label}</span>
        </button>`
    }).join('')
}

/* ── NAVEGAÇÃO ── */
document.getElementById('btn-prev').addEventListener('click',()=>{
    if(agendaView==='mes') viewDate.setMonth(viewDate.getMonth()-1)
    else viewDate.setDate(viewDate.getDate()-7)
    carregarCards().then(render)
})
document.getElementById('btn-next').addEventListener('click',()=>{
    if(agendaView==='mes') viewDate.setMonth(viewDate.getMonth()+1)
    else viewDate.setDate(viewDate.getDate()+7)
    carregarCards().then(render)
})
document.getElementById('btn-hoje').addEventListener('click',()=>{
    viewDate=new Date(hoje.getFullYear(),hoje.getMonth(),1)
    carregarCards().then(render)
})

function setAgendaView(v){
    agendaView=v
    document.getElementById('view-mes').classList.toggle('hidden', v!=='mes')
    document.getElementById('view-semana').classList.toggle('hidden', v!=='semana')
    document.getElementById('btn-view-mes').className=`px-3 py-1.5 font-medium cursor-pointer border-0 transition-colors ${v==='mes'?'bg-dt-card text-dt-accent':'bg-transparent text-dt-muted hover:text-dt-text'}`
    document.getElementById('btn-view-semana').className=`px-3 py-1.5 font-medium cursor-pointer border-0 transition-colors ${v==='semana'?'bg-dt-card text-dt-accent':'bg-transparent text-dt-muted hover:text-dt-text'}`
    render()
}

/* ── MODAL ── */
const backdrop=document.getElementById('modal-backdrop')

function abrirModalNovo(){
    editingId=null
    document.getElementById('modal-title-text').textContent='Nova Atividade'
    document.getElementById('btn-delete-card').style.display='none'
    document.getElementById('modal-link-board').classList.add('hidden')
    document.getElementById('modal-hint').classList.remove('hidden')
    limparModal()
    backdrop.classList.remove('hidden'); backdrop.classList.add('flex')
    setTimeout(()=>document.getElementById('f-titulo').focus(),50)
}

function abrirModalNovoDia(dateStr){
    abrirModalNovo()
    document.getElementById('f-data-inicio').value=dateStr
    document.getElementById('f-data-fim').value=dateStr
}

function abrirModalEditar(id){
    const card=allCards.find(c=>c.id===id)
    if(!card)return
    editingId=id
    document.getElementById('modal-title-text').textContent='Editar Atividade'
    document.getElementById('btn-delete-card').style.display='inline-flex'
    const linkBoard=document.getElementById('modal-link-board')
    linkBoard.classList.remove('hidden')
    document.getElementById('modal-hint').classList.add('hidden')
    document.getElementById('f-titulo').value=card.titulo||''
    document.getElementById('f-descricao').value=card.descricao||''
    document.getElementById('f-prioridade').value=card.prioridade||'media'
    document.getElementById('f-coluna').value=card.coluna||'backlog'
    document.getElementById('f-projeto').value=card.projeto_id||''
    document.getElementById('f-data-inicio').value=card.data_inicio?card.data_inicio.slice(0,10):''
    document.getElementById('f-data-fim').value=card.data_fim?card.data_fim.slice(0,10):''
    backdrop.classList.remove('hidden'); backdrop.classList.add('flex')
    setTimeout(()=>document.getElementById('f-titulo').focus(),50)
}

function fecharModal(){
    backdrop.classList.add('hidden'); backdrop.classList.remove('flex')
    editingId=null
}

function limparModal(){
    ['f-titulo','f-descricao','f-data-inicio','f-data-fim'].forEach(id=>{document.getElementById(id).value=''})
    document.getElementById('f-prioridade').value='media'
    document.getElementById('f-coluna').value='backlog'
    document.getElementById('f-projeto').value=''
}

async function salvarCard(){
    const titulo=document.getElementById('f-titulo').value.trim()
    if(!titulo){document.getElementById('f-titulo').focus();return}
    const payload={
        titulo,
        descricao:   document.getElementById('f-descricao').value,
        prioridade:  document.getElementById('f-prioridade').value,
        coluna:      document.getElementById('f-coluna').value,
        projeto_id:  document.getElementById('f-projeto').value||null,
        data_inicio: document.getElementById('f-data-inicio').value||null,
        data_fim:    document.getElementById('f-data-fim').value||null,
        tipo:'Outro', tags:'', solucao:'', link:'', tempo_gasto:0, solicitado_por:'',
    }
    if(editingId){ payload.id=editingId; await req('atualizar',payload); toast('Atualizada','ok') }
    else { await req('criar',payload); toast('Criada','ok') }
    fecharModal()
    await carregarCards(); render()
}

async function deletarCard(){
    if(!editingId||!confirm('Mover para a lixeira?'))return
    await req('deletar',{id:editingId}); toast('Excluída','ok')
    fecharModal(); await carregarCards(); render()
}

/* ── PROJETO SELECT ── */
function buildProjetoSelect(){
    const opts=projetos.map(p=>`<option value="${p.id}">${esc(p.nome)}</option>`).join('')
    document.getElementById('f-projeto').innerHTML='<option value="">— Sem projeto —</option>'+opts
}

/* ── KEYBOARD ── */
document.addEventListener('keydown',e=>{
    if(e.key==='Escape'&&backdrop.classList.contains('flex')) fecharModal()
    if(e.ctrlKey&&e.key==='Enter'&&backdrop.classList.contains('flex')){e.preventDefault();salvarCard()}
})
backdrop.addEventListener('click',e=>{if(e.target===backdrop)fecharModal()})
</script>
</body>
</html>
