/* DevTrack – Kanban JS */

const COLUNAS = [
    { id: 'backlog',   label: 'Backlog',      color: '#8b949e' },
    { id: 'andamento', label: 'Em Andamento', color: '#58a6ff' },
    { id: 'revisao',   label: 'Em Revisão',   color: '#a371f7' },
    { id: 'concluido', label: 'Concluído',    color: '#3fb950' },
    { id: 'bloqueado', label: 'Bloqueado',    color: '#f85149' },
]
const COL_LABELS = { backlog:'Backlog', andamento:'Em Andamento', revisao:'Em Revisão', concluido:'Concluído', bloqueado:'Bloqueado' }
const PRIORIDADES = {
    baixa:   { label:'Baixa',   color:'#3fb950' },
    media:   { label:'Média',   color:'#d29922' },
    alta:    { label:'Alta',    color:'#f0883e' },
    urgente: { label:'Urgente', color:'#f85149' },
}
const HIST_LABELS = { mover:'↔ Movido', editar:'✏ Editado', criar:'✚ Criado' }

let cards=[], projetos=[], tipos=[], allTags=[], selectedTags=new Set(), wipLimits={}
let filtros={ busca:'', projeto_id:'', prioridade:'', tipo:'', de:'', ate:'' }
let editingId=null, dragCardId=null, searchTimer=null
let currentView=localStorage.getItem('dt-view')||'board'
let sortField='criado_em', sortDir='desc'

/* ─── INIT ─── */
document.addEventListener('DOMContentLoaded', async () => {
    await Promise.all([loadProjetos(), loadTipos(), loadAllTags(), loadConfig()])
    applyView()
    await loadCards()
    setupFilters()
    setupKeyboard()
})

/* ─── API ─── */
async function api(action, body=null, extra={}) {
    const qs = new URLSearchParams({ action, ...extra }).toString()
    const opts = { headers:{'Content-Type':'application/json'} }
    if (body) { opts.method='POST'; opts.body=JSON.stringify(body) }
    const res = await fetch('api.php?'+qs, opts)
    return res.json()
}

async function loadProjetos() { projetos=await api('projetos'); buildProjetoSelects() }
async function loadTipos()    { tipos   =await api('tipos');    buildTipoSelects() }
async function loadAllTags()  { allTags =await api('tags');     renderTagPicker() }
async function loadConfig()   {
    const cached = localStorage.getItem('dt-wip-cache')
    const cacheTs = parseInt(localStorage.getItem('dt-wip-ts')||'0')
    if (cached && Date.now()-cacheTs < 60000) { wipLimits=JSON.parse(cached); return }
    const cfg=await api('config_get'); wipLimits={}
    for (const [k,v] of Object.entries(cfg))
        if (k.startsWith('wip_')&&v!==null) wipLimits[k.replace('wip_','')]=parseInt(v)
    localStorage.setItem('dt-wip-cache', JSON.stringify(wipLimits))
    localStorage.setItem('dt-wip-ts', Date.now())
}

async function loadCards() {
    showSkeleton()
    const data = await fetch('api.php?'+new URLSearchParams({action:'listar',limit:300,...filtros})).then(r=>r.json())
    cards = data
    render()
}

/* ─── SKELETON ─── */
function skeletonCard() {
    return `<div class="flex overflow-hidden rounded-xl border border-dt-border/40 bg-dt-card opacity-60">
        <div class="w-[3px] bg-dt-border flex-shrink-0 rounded-l-xl"></div>
        <div class="p-3 flex-1 space-y-2.5">
            <div class="skeleton h-3.5 w-14 rounded-md"></div>
            <div class="skeleton h-4 w-full rounded-md"></div>
            <div class="skeleton h-3 w-2/3 rounded-md"></div>
        </div>
    </div>`
}
function showSkeleton() {
    COLUNAS.forEach(col => {
        const list = document.querySelector(`.cards-list[data-col="${col.id}"]`)
        if (!list) return
        list.innerHTML = [1,2].map(skeletonCard).join('')
    })
}

/* ─── VIEW ─── */
function render() { currentView==='table' ? renderTable() : renderBoard() }

function setView(v) {
    currentView=v; localStorage.setItem('dt-view',v); applyView(); render()
}
function applyView() {
    const board=document.getElementById('board-view')
    const table=document.getElementById('table-view')
    const bB=document.getElementById('btn-view-board')
    const bT=document.getElementById('btn-view-table')
    if (currentView==='table') {
        if (board) board.style.display='none'
        if (table) table.style.display='block'
        bB?.classList.remove('bg-dt-card','text-dt-accent')
        bB?.classList.add('bg-transparent','text-dt-muted')
        bT?.classList.remove('bg-transparent','text-dt-muted')
        bT?.classList.add('bg-dt-card','text-dt-accent')
    } else {
        if (board) board.style.display=''
        if (table) table.style.display='none'
        bT?.classList.remove('bg-dt-card','text-dt-accent')
        bT?.classList.add('bg-transparent','text-dt-muted')
        bB?.classList.remove('bg-transparent','text-dt-muted')
        bB?.classList.add('bg-dt-card','text-dt-accent')
    }
}

/* ─── BOARD ─── */
function renderBoard() {
    COLUNAS.forEach(col => {
        const colCards=cards.filter(c=>c.coluna===col.id)
        const list=document.querySelector(`.cards-list[data-col="${col.id}"]`)
        const countEl=document.querySelector(`.col-count[data-col="${col.id}"]`)
        const wipEl=document.querySelector(`.wip-badge[data-col="${col.id}"]`)
        if (!list) return

        if (colCards.length === 0) {
            list.innerHTML = `
<div class="flex flex-col items-center justify-center gap-2 py-8 px-4 text-center">
    <div class="w-10 h-10 border-2 border-dashed border-dt-border/50 rounded-xl flex items-center justify-center text-dt-muted/40 text-xl">+</div>
    <span class="text-xs text-dt-muted/40 leading-snug">Arraste cards aqui<br>ou clique em +</span>
</div>`
        } else {
            list.innerHTML = colCards.map(renderCard).join('')
            list.querySelectorAll('.k-card').forEach(el => {
                el.addEventListener('dragstart', onDragStart)
                el.addEventListener('dragend',   onDragEnd)
                el.addEventListener('dragover',  onCardDragOver)
            })
        }

        if (countEl) countEl.textContent = colCards.length

        if (wipEl) {
            const limit = wipLimits[col.id]
            if (limit) {
                const over = colCards.length > limit
                wipEl.textContent = `${colCards.length}/${limit}`
                wipEl.className = `wip-badge text-[10px] font-bold px-1.5 py-0.5 rounded-full border ${over
                    ? 'bg-[#2b0e0d] text-dt-red border-[#5a2020] animate-wip-blink'
                    : 'bg-[#0b1f10] text-dt-green border-[#1a4a2a]'}`
            } else {
                wipEl.className = 'wip-badge hidden'
            }
        }

        if (!list._delegated) {
            list.addEventListener('click',    onListClick)
            list.addEventListener('dragover',  onListDragOver)
            list.addEventListener('dragleave', onDragLeave)
            list.addEventListener('drop',      onDrop)
            list._delegated = true
        }
    })
}

function tipoInfo(nome) {
    return tipos.find(t=>t.nome===nome)
        || tipos.find(t=>t.nome.toLowerCase()===(nome||'').toLowerCase())
        || { nome:nome||'Outro', cor:'#8b949e', cor_bg:'#1e2329' }
}

function renderCard(c) {
    const tipo = tipoInfo(c.tipo)
    const pri  = PRIORIDADES[c.prioridade] || PRIORIDADES.media
    const bdr  = makeBorder(tipo.cor)
    const badgeStyle = `color:${tipo.cor};background:${tipo.cor_bg};border:1px solid ${bdr}`

    const tags = (c.tags||'').split(',').map(t=>t.trim()).filter(Boolean).map(t => {
        const info = allTags.find(tg=>tg.nome===t)
        const cor  = info?.cor || '#8b949e'
        return `<span class="dt-tag" style="color:${cor};border-color:${cor}44;background:${cor}15">#${esc(t)}</span>`
    }).join('')

    return `
<div class="k-card dt-kanban-card" data-id="${c.id}" data-col="${c.coluna}" draggable="true">
    <div class="w-[3px] flex-shrink-0 rounded-l-xl" style="background:${pri.color}"></div>
    <div class="p-3 flex-1 min-w-0">
        <div class="flex items-start gap-1.5 mb-2">
            <span class="dt-badge" style="${badgeStyle}">${esc(tipo.nome)}</span>
            <span class="ml-auto w-1.5 h-1.5 rounded-full flex-shrink-0 mt-1${c.prioridade==='urgente'?' animate-pulse':''}" style="background:${pri.color}" title="${pri.label}"></span>
        </div>
        <p class="text-sm font-medium text-dt-text leading-snug mb-1.5">${esc(c.titulo)}</p>
        ${c.projeto_nome?`<p class="text-xs text-dt-muted mb-1.5 pl-2 border-l-2" style="border-color:${esc(c.projeto_cor)}">${esc(c.projeto_nome)}</p>`:''}
        ${c.solicitado_por?`<p class="text-xs text-dt-muted/70 mb-1.5">👤 ${esc(c.solicitado_por)}</p>`:''}
        ${tags?`<div class="flex flex-wrap gap-1 mb-1.5">${tags}</div>`:''}
        <div class="flex items-center gap-2 mt-2 pt-1.5 border-t border-dt-border/30">
            ${c.tempo_gasto>0?`<span class="text-xs text-dt-muted/60">⏱ ${fmtTime(c.tempo_gasto)}</span>`:''}
            <span class="text-[11px] text-dt-muted/50 ml-auto tabular-nums" title="${c.atualizado_em?'Atualizado em '+fmtDateTime(c.atualizado_em):''}">${c.atualizado_em?'✎ '+fmtDate(c.atualizado_em):fmtDate(c.criado_em)}</span>
        </div>
    </div>
</div>`
}

/* ─── EVENT DELEGATION ─── */
function onListClick(e) {
    const card = e.target.closest('.k-card')
    if (card) openModal(+card.dataset.id)
}

/* ─── DRAG & DROP ─── */
function onDragStart(e) {
    dragCardId = +e.currentTarget.dataset.id
    e.currentTarget.classList.add('dragging')
    e.dataTransfer.effectAllowed = 'move'
}
function onDragEnd(e) {
    e.currentTarget.classList.remove('dragging')
    document.querySelectorAll('.drop-ph').forEach(el=>el.remove())
    document.querySelectorAll('.cards-list').forEach(l=>l.classList.remove('drag-over-active'))
}
function getAfterEl(list, y) {
    const siblings = [...list.querySelectorAll('.k-card:not(.dragging)')]
    return siblings.reduce((closest, el) => {
        const box = el.getBoundingClientRect()
        const offset = y - box.top - box.height / 2
        return (offset < 0 && offset > closest.offset) ? { offset, element: el } : closest
    }, { offset: Number.NEGATIVE_INFINITY }).element
}
function insertPh(list, afterEl) {
    let ph = list.querySelector('.drop-ph')
    if (!ph) { ph = document.createElement('div'); ph.className = 'drop-ph' }
    afterEl ? list.insertBefore(ph, afterEl) : list.appendChild(ph)
}
function onCardDragOver(e) {
    e.preventDefault(); e.stopPropagation()
    const list = e.currentTarget.closest('.cards-list')
    const box  = e.currentTarget.getBoundingClientRect()
    insertPh(list, e.clientY < box.top + box.height / 2 ? e.currentTarget : e.currentTarget.nextElementSibling)
    list.classList.add('drag-over-active')
}
function onListDragOver(e) {
    e.preventDefault()
    insertPh(e.currentTarget, getAfterEl(e.currentTarget, e.clientY))
    e.currentTarget.classList.add('drag-over-active')
}
function onDragLeave(e) {
    if (!e.currentTarget.contains(e.relatedTarget)) {
        e.currentTarget.classList.remove('drag-over-active')
        e.currentTarget.querySelector('.drop-ph')?.remove()
    }
}
async function onDrop(e) {
    e.preventDefault()
    const list = e.currentTarget
    list.classList.remove('drag-over-active')
    const ph = list.querySelector('.drop-ph')
    const afterEl = ph ? ph.nextElementSibling : null
    ph?.remove()
    const newCol = list.dataset.col
    if (!dragCardId || !newCol) return
    const card = cards.find(c=>c.id===dragCardId)
    if (!card) return
    const oldCol = card.coluna
    const insertBeforeId = afterEl?.dataset.id ? +afterEl.dataset.id : null
    const idx = cards.findIndex(c=>c.id===dragCardId)
    if (idx>-1) cards.splice(idx,1)
    card.coluna = newCol
    if (insertBeforeId) { const i=cards.findIndex(c=>c.id===insertBeforeId); cards.splice(i>-1?i:cards.length,0,card) }
    else { const last=cards.reduce((l,c,i)=>c.coluna===newCol?i:l,-1); cards.splice(last+1,0,card) }
    renderBoard()
    if (oldCol!==newCol) await api('mover',{id:dragCardId,coluna:newCol})
    await api('reordenar',{ ids: cards.filter(c=>c.coluna===newCol).map(c=>c.id) })
    if (oldCol!==newCol) toast('Movido para '+COL_LABELS[newCol],'ok')
    dragCardId = null
}

/* ─── TABLE VIEW ─── */
const PRI_ORDER = { urgente:4, alta:3, media:2, baixa:1 }
const COL_ORDER = { backlog:1, andamento:2, revisao:3, concluido:4, bloqueado:5 }

function getSorted() {
    return [...cards].sort((a,b) => {
        const { field, dir } = { field: sortField, dir: sortDir }
        let va, vb
        if (field==='prioridade') { va=PRI_ORDER[a.prioridade]??0; vb=PRI_ORDER[b.prioridade]??0 }
        else if (field==='coluna') { va=COL_ORDER[a.coluna]??0; vb=COL_ORDER[b.coluna]??0 }
        else if (field==='tempo_gasto') { va=+a.tempo_gasto||0; vb=+b.tempo_gasto||0 }
        else {
            va=String(a[field]??''); vb=String(b[field]??'')
            return dir==='asc' ? va.localeCompare(vb,'pt-BR') : vb.localeCompare(va,'pt-BR')
        }
        return dir==='asc' ? va-vb : vb-va
    })
}
function setSort(f) {
    if (sortField===f) sortDir=sortDir==='asc'?'desc':'asc'; else { sortField=f; sortDir='asc' }
    renderTable()
}

function renderTable() {
    const container = document.getElementById('table-view')
    if (!container) return
    const sorted = getSorted()

    const th = (label, field) => `<th
        class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider border-b border-dt-border whitespace-nowrap select-none ${field?'cursor-pointer hover:text-dt-text':''} ${field&&sortField===field?'text-dt-accent':'text-dt-muted'}"
        ${field?`onclick="setSort('${field}')"`:''}>${label}${field?`<span class="ml-1 text-[10px] ${sortField===field?'opacity-100':'opacity-30'}">${sortField===field?(sortDir==='asc'?'↑':'↓'):'↕'}</span>`:''}</th>`

    const rows = sorted.map(c => {
        const tipo = tipoInfo(c.tipo)
        const pri  = PRIORIDADES[c.prioridade] || PRIORIDADES.media
        const bdr  = makeBorder(tipo.cor)
        const tags = (c.tags||'').split(',').map(t=>t.trim()).filter(Boolean).map(t => {
            const info = allTags.find(tg=>tg.nome===t)
            const cor  = info?.cor||'#8b949e'
            return `<span class="dt-tag" style="color:${cor};border-color:${cor}44;background:${cor}15">#${esc(t)}</span>`
        }).join('')
        const colOptions = COLUNAS.map(col =>
            `<option value="${col.id}"${c.coluna===col.id?' selected':''}>${col.label}</option>`
        ).join('')

        return `<tr class="border-b border-dt-border/60 cursor-pointer transition-colors hover:bg-dt-card/60 group"
                    onclick="openModal(${c.id})">
            <td class="px-3 py-3">
                <span class="dt-badge" style="color:${tipo.cor};background:${tipo.cor_bg};border:1px solid ${bdr}">${esc(tipo.nome)}</span>
            </td>
            <td class="px-3 py-3 max-w-[240px]">
                <p class="font-medium text-dt-text leading-snug">${esc(c.titulo)}</p>
                ${c.descricao?`<p class="text-xs text-dt-muted/70 mt-0.5 line-clamp-1">${esc(c.descricao.substring(0,80))}</p>`:''}
                ${c.link?`<a href="${esc(c.link)}" target="_blank" rel="noopener" class="text-xs text-dt-accent/70 hover:text-dt-accent no-underline" onclick="event.stopPropagation()">🔗</a>`:''}
            </td>
            <td class="px-3 py-3 whitespace-nowrap">
                ${c.projeto_nome
                    ?`<span class="flex items-center gap-1.5 text-xs text-dt-text"><i class="w-2 h-2 rounded-full flex-shrink-0" style="background:${esc(c.projeto_cor)}"></i>${esc(c.projeto_nome)}</span>`
                    :'<span class="text-dt-muted/30 text-xs">—</span>'}
            </td>
            <td class="px-3 py-3">
                <div class="flex flex-wrap gap-1 max-w-[150px]">${tags||'<span class="text-dt-muted/30 text-xs">—</span>'}</div>
            </td>
            <td class="px-3 py-3 whitespace-nowrap">
                <span class="flex items-center gap-1.5 text-xs">
                    <i class="w-1.5 h-1.5 rounded-full flex-shrink-0" style="background:${pri.color}"></i>
                    <span class="text-dt-text/80">${pri.label}</span>
                </span>
            </td>
            <td class="px-3 py-3" onclick="event.stopPropagation()">
                <select class="sel dt-select-sm !w-auto bg-dt-base border border-dt-border rounded-lg text-dt-text text-xs py-1 pl-2 outline-none focus:border-dt-accent cursor-pointer"
                    onchange="moveCardInline(${c.id},this.value)">${colOptions}</select>
            </td>
            <td class="px-3 py-3 text-xs text-dt-muted/70 whitespace-nowrap">${c.solicitado_por?esc(c.solicitado_por):'<span class="opacity-30">—</span>'}</td>
            <td class="px-3 py-3 text-xs text-dt-muted/70 whitespace-nowrap tabular-nums">${c.tempo_gasto>0?fmtTime(c.tempo_gasto):'<span class="opacity-30">—</span>'}</td>
            <td class="px-3 py-3 text-xs text-dt-muted/60 whitespace-nowrap tabular-nums">${fmtDate(c.criado_em)}</td>
            <td class="px-3 py-3" onclick="event.stopPropagation()">
                <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button class="dt-item-edit-btn" onclick="openModal(${c.id})">✏</button>
                    <button class="dt-item-delete-btn" onclick="deleteCardById(${c.id},event)">🗑</button>
                </div>
            </td>
        </tr>`
    }).join('')

    container.innerHTML = `
<div class="flex items-center justify-between mb-5">
    <div>
        <span class="text-sm font-semibold">${sorted.length}</span>
        <span class="text-sm text-dt-muted"> atividade${sorted.length!==1?'s':''}</span>
    </div>
    <button onclick="openModal()" class="dt-btn dt-btn-sm">+ Nova atividade</button>
</div>
<div class="overflow-x-auto rounded-xl border border-dt-border/60 shadow-card">
<table class="w-full border-collapse text-sm">
    <thead class="bg-dt-surface sticky top-0 z-10"><tr>
        ${th('Tipo','tipo')} ${th('Título','titulo')} ${th('Projeto','projeto_nome')}
        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-dt-muted border-b border-dt-border">Tags</th>
        ${th('Prioridade','prioridade')}
        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-dt-muted border-b border-dt-border">Coluna</th>
        ${th('Solicitado por','solicitado_por')} ${th('Tempo','tempo_gasto')} ${th('Criado em','criado_em')}
        <th class="border-b border-dt-border w-12"></th>
    </tr></thead>
    <tbody>${rows || '<tr><td colspan="10" class="text-center py-16 text-dt-muted/50 text-sm">Nenhuma atividade encontrada</td></tr>'}</tbody>
</table></div>`
}

async function moveCardInline(id, coluna) {
    const card = cards.find(c=>c.id===id)
    if (!card || card.coluna===coluna) return
    card.coluna = coluna
    await api('mover', {id, coluna})
    toast('Movido para '+COL_LABELS[coluna], 'ok')
}
async function deleteCardById(id, event) {
    event?.stopPropagation()
    if (!confirm('Mover para a lixeira?')) return
    await api('deletar', {id})
    cards = cards.filter(c=>c.id!==id)
    render()
    toastUndo('Atividade excluída', async () => { await api('restaurar',{id}); await loadCards(); toast('Restaurada','ok') })
}

/* ─── MODAL ─── */
const backdrop = document.getElementById('modal-backdrop')

function openModal(id=null, defaultColuna='backlog') {
    editingId = id
    const card = id ? cards.find(c=>c.id===id) : null
    document.getElementById('modal-title-text').textContent = card ? 'Editar Atividade' : 'Nova Atividade'
    document.getElementById('btn-delete-card').style.display = card ? 'inline-flex' : 'none'
    document.getElementById('f-titulo').value          = card?.titulo         ?? ''
    document.getElementById('f-descricao').value       = card?.descricao      ?? ''
    document.getElementById('f-solucao').value         = card?.solucao        ?? ''
    document.getElementById('f-prioridade').value      = card?.prioridade     ?? 'media'
    document.getElementById('f-coluna').value          = card?.coluna         ?? defaultColuna
    document.getElementById('f-projeto').value         = card?.projeto_id     ?? ''
    document.getElementById('f-link').value            = card?.link           ?? ''
    document.getElementById('f-tempo').value           = card?.tempo_gasto>0  ? fmtTime(card.tempo_gasto) : ''
    document.getElementById('f-data-inicio').value     = card?.data_inicio    ?? ''
    document.getElementById('f-data-fim').value        = card?.data_fim       ?? ''
    document.getElementById('f-solicitado-por').value  = card?.solicitado_por ?? ''
    document.getElementById('f-tipo').value            = card?.tipo ?? (tipos[0]?.nome ?? '')
    selectedTags = new Set((card?.tags||'').split(',').map(t=>t.trim()).filter(Boolean))
    renderTagPicker(); syncTagsInput()
    ;['new-tipo-section','new-proj-section','new-tag-section'].forEach(s => {
        document.getElementById(s)?.classList.add('hidden')
    })
    switchTab('detalhes')
    if (card) loadHistory(id)
    backdrop.classList.remove('hidden')
    backdrop.classList.add('flex')
    // Reaplica animação
    const box = backdrop.querySelector('.modal-box-inner')
    if (box) { box.classList.remove('modal-box-inner'); void box.offsetWidth; box.classList.add('modal-box-inner') }
    setTimeout(() => document.getElementById('f-titulo').focus(), 50)
}

function closeModal() {
    backdrop.classList.add('hidden')
    backdrop.classList.remove('flex')
    editingId = null
}

function switchTab(tab) {
    document.querySelectorAll('.modal-tab').forEach(t => {
        const active = t.dataset.tab === tab
        t.classList.toggle('border-dt-accent', active)
        t.classList.toggle('text-dt-accent',   active)
        t.classList.toggle('border-transparent', !active)
        t.classList.toggle('text-dt-muted',     !active)
    })
    document.querySelectorAll('.tab-panel').forEach(p =>
        p.classList.toggle('hidden', p.id !== 'tab-' + tab)
    )
}

async function loadHistory(id) {
    const hist = await api('historico', null, { id })
    const el = document.getElementById('history-list')
    if (!hist.length) { el.innerHTML = '<p class="text-dt-muted text-sm">Sem histórico.</p>'; return }
    el.innerHTML = hist.map(h => {
        const label = HIST_LABELS[h.tipo] ?? h.tipo
        let desc = ''
        if (h.tipo==='mover' && h.coluna_anterior)
            desc = `<span class="text-dt-muted">${COL_LABELS[h.coluna_anterior]}</span> → <span class="text-dt-text">${COL_LABELS[h.coluna_nova]}</span>`
        else if (h.tipo==='criar')
            desc = `em <span class="text-dt-text">${COL_LABELS[h.coluna_nova]}</span>`
        else if (h.detalhe)
            desc = esc(h.detalhe)
        return `<div class="flex gap-3 py-3 border-b border-dt-border/60 last:border-0">
            <div class="w-1.5 h-1.5 rounded-full bg-dt-accent flex-shrink-0 mt-1.5"></div>
            <div>
                <p class="text-sm"><strong>${label}</strong>${desc?' — '+desc:''}</p>
                <p class="text-xs text-dt-muted mt-0.5">${fmtDateTime(h.criado_em)}</p>
            </div>
        </div>`
    }).join('')
}

async function saveCard() {
    const titulo = document.getElementById('f-titulo').value.trim()
    if (!titulo) { document.getElementById('f-titulo').focus(); return }
    const link = document.getElementById('f-link').value.trim()
    if (link && /^javascript:/i.test(link)) { toast('Link inválido','err'); return }
    const payload = {
        titulo, link,
        descricao:     document.getElementById('f-descricao').value,
        solucao:       document.getElementById('f-solucao').value,
        tipo:          document.getElementById('f-tipo').value,
        prioridade:    document.getElementById('f-prioridade').value,
        coluna:        document.getElementById('f-coluna').value,
        projeto_id:    document.getElementById('f-projeto').value || null,
        tags:          [...selectedTags].join(','),
        tempo_gasto:   parseTime(document.getElementById('f-tempo').value),
        data_inicio:   document.getElementById('f-data-inicio').value || null,
        data_fim:      document.getElementById('f-data-fim').value    || null,
        solicitado_por:document.getElementById('f-solicitado-por').value.trim(),
    }
    if (editingId) { payload.id=editingId; await api('atualizar',payload); toast('Atualizada','ok') }
    else           {                        await api('criar',payload);      toast('Criada','ok') }
    closeModal()
    await loadCards()
}

async function deleteCard() {
    if (!editingId || !confirm('Mover para a lixeira?')) return
    const id = editingId
    await api('deletar', {id})
    closeModal(); await loadCards()
    toastUndo('Atividade excluída', async () => { await api('restaurar',{id}); await loadCards(); toast('Restaurada','ok') })
}

/* ─── PROJETOS ─── */
function buildProjetoSelects() {
    const opts = projetos.map(p=>`<option value="${p.id}">${esc(p.nome)}</option>`).join('')
    document.getElementById('f-projeto').innerHTML = '<option value="">— Sem projeto —</option>' + opts
    const fSel = document.getElementById('filter-projeto')
    if (fSel) fSel.innerHTML = '<option value="">Todos projetos</option>' + opts
}
async function addProject() {
    const nome = document.getElementById('new-proj-nome').value.trim()
    const cor  = document.getElementById('new-proj-cor').value
    if (!nome) return
    const res = await api('criar_projeto', {nome, cor})
    projetos.push({id:res.id, nome:res.nome, cor:res.cor})
    buildProjetoSelects()
    document.getElementById('f-projeto').value = res.id
    document.getElementById('new-proj-nome').value = ''
    document.getElementById('new-proj-section').classList.add('hidden')
    toast('Projeto "'+nome+'" criado','ok')
}

/* ─── TIPOS ─── */
function buildTipoSelects() {
    const opts = tipos.map(t=>`<option value="${esc(t.nome)}">${esc(t.nome)}</option>`).join('')
    document.getElementById('f-tipo').innerHTML = opts
    const fSel = document.getElementById('filter-tipo')
    if (fSel) fSel.innerHTML = '<option value="">Todos tipos</option>' + opts
}
async function addTipo() {
    const nome = document.getElementById('new-tipo-nome').value.trim()
    const cor  = document.getElementById('new-tipo-cor').value
    if (!nome) { document.getElementById('new-tipo-nome').focus(); return }
    const res = await api('criar_tipo', {nome, cor, cor_bg:makeBg(cor)})
    if (res.erro) { toast(res.erro,'err'); return }
    tipos.push({id:res.id, nome:res.nome, cor:res.cor, cor_bg:res.cor_bg})
    buildTipoSelects()
    document.getElementById('f-tipo').value = res.nome
    document.getElementById('new-tipo-nome').value = ''
    document.getElementById('new-tipo-section').classList.add('hidden')
    toast('Tipo "'+nome+'" criado','ok')
}

/* ─── TAG PICKER ─── */
function renderTagPicker() {
    const wrap = document.getElementById('tag-picker')
    if (!wrap) return
    if (!allTags.length) {
        wrap.innerHTML = '<span class="text-xs text-dt-muted/50">Crie tags em ⚙ Config</span>'
        return
    }
    wrap.innerHTML = allTags.map(t => {
        const sel = selectedTags.has(t.nome)
        return `<button onclick="toggleTag('${esc(t.nome)}')" title="${esc(t.nome)}"
            class="dt-tag cursor-pointer transition-all duration-100 ${sel
                ? 'border-solid scale-105 shadow-sm'
                : 'border-dashed opacity-40 hover:opacity-60'}"
            style="color:${t.cor};border-color:${sel ? t.cor+'88' : t.cor+'44'};background:${sel ? t.cor+'20' : 'transparent'}">#${esc(t.nome)}</button>`
    }).join('')
}
function toggleTag(nome) {
    selectedTags.has(nome) ? selectedTags.delete(nome) : selectedTags.add(nome)
    renderTagPicker(); syncTagsInput()
}
function syncTagsInput() {
    const inp = document.getElementById('f-tags')
    if (inp) inp.value = [...selectedTags].join(',')
}
async function addTagModal() {
    const nome = document.getElementById('new-tag-nome').value.trim()
    const cor  = document.getElementById('new-tag-cor').value
    if (!nome) { document.getElementById('new-tag-nome').focus(); return }
    const res = await api('criar_tag', {nome, cor})
    if (res.erro) { toast(res.erro,'err'); return }
    allTags.push({id:res.id, nome:res.nome, cor:res.cor})
    allTags.sort((a,b)=>a.nome.localeCompare(b.nome))
    selectedTags.add(nome)
    document.getElementById('new-tag-nome').value = ''
    document.getElementById('new-tag-section').classList.add('hidden')
    renderTagPicker(); syncTagsInput()
    toast('Tag "#'+nome+'" criada','ok')
}

/* ─── FILTERS ─── */
function setupFilters() {
    document.getElementById('search-input').addEventListener('input', e => {
        clearTimeout(searchTimer)
        searchTimer = setTimeout(() => { filtros.busca=e.target.value.trim(); loadCards() }, 300)
    })
    ;['filter-projeto','filter-tipo','filter-prioridade'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', e => {
            const key = id.replace('filter-','')
            filtros[key === 'projeto' ? 'projeto_id' : key] = e.target.value
            updateFilterDot()
            loadCards()
        })
    })
    document.getElementById('filter-de')?.addEventListener('change', e => { filtros.de=e.target.value; updateFilterDot(); loadCards() })
    document.getElementById('filter-ate')?.addEventListener('change', e => { filtros.ate=e.target.value; updateFilterDot(); loadCards() })
}
function updateFilterDot() {
    const active = filtros.prioridade || filtros.de || filtros.ate
    document.getElementById('filter-dot')?.classList.toggle('hidden', !active)
    const clearBtn = document.getElementById('btn-clear-filters')
    if (clearBtn) clearBtn.style.display = active ? '' : 'none'
}
function applyDateFilter() { updateFilterDot(); loadCards() }

/* ─── KEYBOARD ─── */
function setupKeyboard() {
    document.addEventListener('keydown', e => {
        if (e.ctrlKey && e.key==='n') { e.preventDefault(); openModal() }
        if (e.key==='Escape' && backdrop.classList.contains('flex')) closeModal()
        if (e.ctrlKey && e.key==='Enter' && backdrop.classList.contains('flex')) { e.preventDefault(); saveCard() }
    })
    backdrop.addEventListener('click', e => { if(e.target===backdrop) closeModal() })
}

/* ─── TOAST ─── */
function toast(msg, type='ok') {
    const el = document.createElement('div')
    el.className = `pointer-events-auto flex items-center gap-2.5 px-4 py-2.5 rounded-xl border text-sm font-medium animate-toast shadow-card backdrop-blur-sm
        ${type==='ok'
            ? 'bg-dt-surface/95 border-dt-green/30 text-dt-text border-l-[3px] border-l-dt-green'
            : 'bg-dt-surface/95 border-dt-red/30 text-dt-text border-l-[3px] border-l-dt-red'}`
    el.innerHTML = `<span class="text-base">${type==='ok'?'✓':'✗'}</span><span>${esc(msg)}</span>`
    document.getElementById('toast-container').appendChild(el)
    setTimeout(() => el.remove(), type==='err' ? 5000 : 3000)
}

function toastUndo(msg, onUndo) {
    const el = document.createElement('div')
    el.className = `pointer-events-auto flex items-center gap-2.5 px-4 py-2.5 rounded-xl border text-sm font-medium animate-toast shadow-card backdrop-blur-sm bg-dt-surface/95 border-dt-red/30 text-dt-text border-l-[3px] border-l-dt-red`
    el.innerHTML = `<span class="text-base">✗</span><span class="flex-1">${esc(msg)}</span><button class="text-dt-accent text-xs font-bold hover:underline bg-transparent border-0 cursor-pointer whitespace-nowrap">Desfazer</button>`
    el.querySelector('button').addEventListener('click', () => { el.remove(); onUndo() })
    document.getElementById('toast-container').appendChild(el)
    setTimeout(() => el.remove(), 6000)
}

/* ─── COLOR / TIME HELPERS ─── */
function makeBg(hex) {
    if (!hex||!/^#[0-9a-f]{6}$/i.test(hex)) return '#1e2329'
    const r=parseInt(hex.slice(1,3),16),g=parseInt(hex.slice(3,5),16),b=parseInt(hex.slice(5,7),16)
    return '#'+[r,g,b].map(v=>Math.round(v*.18).toString(16).padStart(2,'0')).join('')
}
function makeBorder(hex) {
    if (!hex||!/^#[0-9a-f]{6}$/i.test(hex)) return '#30363d'
    const r=parseInt(hex.slice(1,3),16),g=parseInt(hex.slice(3,5),16),b=parseInt(hex.slice(5,7),16)
    return '#'+[r,g,b].map(v=>Math.round(v*.35).toString(16).padStart(2,'0')).join('')
}
function esc(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;')
}
function fmtDate(s) {
    if (!s) return ''
    return new Date(s.replace(' ','T')).toLocaleDateString('pt-BR',{day:'2-digit',month:'2-digit'})
}
function fmtDateTime(s) {
    if (!s) return ''
    const d = new Date(s.replace(' ','T'))
    return d.toLocaleDateString('pt-BR')+' '+d.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'})
}
function fmtTime(min) {
    if (!min) return ''
    const h=Math.floor(min/60), m=min%60
    return h>0 ? `${h}h${m>0?m+'m':''}` : m+'m'
}
function parseTime(s) {
    if (!s) return 0
    s = s.toLowerCase().trim()
    const hM=s.match(/(\d+)\s*h/), mM=s.match(/(\d+)\s*m/)
    let t=0
    if (hM) t += parseInt(hM[1])*60
    if (mM) t += parseInt(mM[1])
    if (!hM && !mM) { const n=parseInt(s); if(!isNaN(n)) t=n }
    return t
}
