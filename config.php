<?php require_once 'db.php'; getDB(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Config — DevTrack</title>
    <?php include '_tailwind.php'; ?>
</head>
<body class="bg-dt-base text-dt-text font-sans min-h-screen">

<nav class="sticky top-0 z-50 flex items-center gap-3 px-4 h-14 bg-dt-surface border-b border-dt-border">
    <a href="index.php" class="text-dt-accent font-bold text-sm no-underline">⬡ DevTrack</a>
    <span class="text-dt-muted text-sm">/ configurações</span>
    <div class="flex-1"></div>
    <a href="index.php" class="text-xs text-dt-muted border border-dt-border rounded-md px-3 py-1.5 hover:text-dt-text hover:border-dt-muted transition-colors no-underline">← Voltar ao Board</a>
</nav>

<div class="max-w-[1100px] mx-auto p-6">
    <h1 class="text-lg font-bold mb-5">⚙ Configurações</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start" id="config-grid">
        <!-- Renderizado por JS -->
        <div class="bg-dt-surface border border-dt-border rounded-xl p-6 text-dt-muted text-sm col-span-3">Carregando...</div>
    </div>

    <!-- WIP Limits -->
    <div class="bg-dt-surface border border-dt-border rounded-xl overflow-hidden mt-5">
        <div class="flex items-center gap-2 px-4 py-3 border-b border-dt-border">
            <span class="text-base">⚡</span>
            <span class="font-semibold text-sm flex-1">WIP Limits — Limite de cards por coluna</span>
            <span class="text-[11px] text-dt-muted">Em branco = sem limite</span>
        </div>
        <div class="p-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4" id="wip-grid">
            <?php
            $wipCols=[
                ['id'=>'backlog',   'label'=>'Backlog',      'color'=>'#8b949e'],
                ['id'=>'andamento',  'label'=>'Em Andamento',      'color'=>'#58a6ff'],
                ['id'=>'aguardando', 'label'=>'Aguardando Retorno', 'color'=>'#d29922'],
                ['id'=>'revisao',    'label'=>'Em Revisão',         'color'=>'#a371f7'],
                ['id'=>'concluido', 'label'=>'Concluído',    'color'=>'#3fb950'],
                ['id'=>'bloqueado', 'label'=>'Bloqueado',    'color'=>'#f85149'],
            ];
            foreach ($wipCols as $wc): ?>
            <div>
                <label class="flex items-center gap-1.5 mb-1.5 text-[11px] font-semibold text-dt-muted uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full" style="background:<?= $wc['color'] ?>"></span>
                    <?= $wc['label'] ?>
                </label>
                <input type="number" min="1" max="99" id="wip-<?= $wc['id'] ?>" placeholder="∞"
                    class="w-full bg-dt-base border border-dt-border rounded-md text-dt-text px-3 py-1.5 text-sm outline-none focus:border-dt-accent transition-colors placeholder:text-dt-muted"
                    onchange="saveWip('<?= $wc['id'] ?>',this.value)"
                    onkeydown="if(event.key==='Enter')saveWip('<?= $wc['id'] ?>',this.value)">
            </div>
            <?php endforeach; ?>
        </div>
        <p class="px-4 pb-3 text-[11px] text-dt-muted">Ao ultrapassar o limite, um badge vermelho pulsante aparece no cabeçalho da coluna.</p>
    </div>
</div>

<div id="toast-container" class="fixed bottom-6 right-6 z-[200] flex flex-col gap-2"></div>

<script>
/* ── helpers ── */
async function req(action,body=null){
    const opts={headers:{'Content-Type':'application/json'}}
    if(body){opts.method='POST';opts.body=JSON.stringify(body)}
    const r=await fetch('api.php?action='+action,opts)
    return r.json()
}
function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;')}
function makeBg(hex){if(!hex||!/^#[0-9a-f]{6}$/i.test(hex))return'#1e2329';const r=parseInt(hex.slice(1,3),16),g=parseInt(hex.slice(3,5),16),b=parseInt(hex.slice(5,7),16);return'#'+[r,g,b].map(v=>Math.round(v*.18).toString(16).padStart(2,'0')).join('')}
function makeBorder(hex){if(!hex||!/^#[0-9a-f]{6}$/i.test(hex))return'#30363d';const r=parseInt(hex.slice(1,3),16),g=parseInt(hex.slice(3,5),16),b=parseInt(hex.slice(5,7),16);return'#'+[r,g,b].map(v=>Math.round(v*.35).toString(16).padStart(2,'0')).join('')}
function toast(msg,type='ok'){
    const el=document.createElement('div')
    el.className=`flex items-center gap-2 px-4 py-2 rounded-lg border text-sm animate-toast bg-dt-card ${type==='ok'?'border-l-[3px] border-l-dt-green border-dt-border':'border-l-[3px] border-l-dt-red border-dt-border'}`
    el.innerHTML=`<span>${type==='ok'?'✓':'✗'}</span><span>${esc(msg)}</span>`
    document.getElementById('toast-container').appendChild(el)
    setTimeout(()=>el.remove(),3000)
}

/* ── State ── */
let tipos=[], projetos=[], tags=[]
let editing={tipo:null,proj:null,tag:null}

/* ── Init ── */
;(async()=>{
    await Promise.all([loadTipos(),loadProjetos(),loadTags(),loadWip()])
})()

/* ── WIP ── */
async function loadWip(){
    const cfg=await req('config_get')
    Object.entries(cfg).forEach(([k,v])=>{
        if(k.startsWith('wip_')&&v){const el=document.getElementById(k);if(el)el.value=v}
    })
}
async function saveWip(col,value){
    const val=value===''?'':String(Math.max(1,parseInt(value)||1))
    await req('config_set',{['wip_'+col]:val})
    localStorage.removeItem('dt-wip-cache')
    toast(val===''?`Limite de "${col}" removido`:`WIP "${col}" → ${val}`,'ok')
}

/* ── Render grid ── */
function renderGrid(){
    document.getElementById('config-grid').innerHTML=`
        ${renderSection('tipos','🏷','Tipos de atividade',tipos,renderTipoItem,'<div id="add-tipo-form">'+renderAddTipo()+'</div>')}
        ${renderSection('projetos','📁','Projetos',projetos,renderProjItem,'<div id="add-proj-form">'+renderAddProj()+'</div>')}
        ${renderSection('tags','#','Tags',tags,renderTagItem,'<div id="add-tag-form">'+renderAddTag()+'</div>')}
    `
}

function renderSection(id,icon,title,items,renderFn,addForm){
    const rows=items.map(renderFn).join('')
    return `<div class="bg-dt-surface border border-dt-border rounded-xl overflow-hidden shadow-card">
        <div class="flex items-center gap-2 px-4 py-3.5 border-b border-dt-border">
            <span class="text-base">${icon}</span>
            <span class="font-semibold text-sm flex-1 tracking-tight">${title}</span>
            <span class="text-xs font-semibold text-dt-muted bg-dt-base border border-dt-border rounded-full px-2 py-0.5 tabular-nums">${items.length}</span>
        </div>
        <div class="divide-y divide-dt-border/60">${rows}</div>
        <div class="p-3.5 bg-[#0d1115] border-t border-dt-border">${addForm}</div>
    </div>`
}

/* ── TIPOS ── */
async function loadTipos(){tipos=await req('tipos');renderGrid()}

function renderTipoItem(t){
    const bs=`color:${t.cor};background:${t.cor_bg};border:1px solid ${makeBorder(t.cor)}`
    const isEditing=editing.tipo===t.id
    return `<div>
        <div class="dt-item-row">
            <span class="dt-badge" style="${bs}">${esc(t.nome)}</span>
            <div class="dt-item-actions">
                <button class="dt-item-edit-btn" onclick="toggleEditTipo(${t.id})">✏</button>
                <button class="dt-item-delete-btn" onclick="delTipo(${t.id},'${esc(t.nome)}')">🗑</button>
            </div>
        </div>
        ${isEditing?`<div class="dt-edit-form">
            <div class="flex gap-1.5 mt-1.5">
                <input id="et-nome-${t.id}" type="text" value="${esc(t.nome)}" class="dt-edit-input" onkeydown="if(event.key==='Enter')saveTipo(${t.id})">
                <input id="et-cor-${t.id}" type="color" value="${t.cor}" class="dt-color-pick" title="Cor texto">
                <input id="et-bg-${t.id}" type="color" value="${t.cor_bg}" class="dt-color-pick" title="Cor fundo">
                <button onclick="saveTipo(${t.id})" class="dt-btn-xs">Salvar</button>
            </div>
        </div>`:''}
    </div>`
}

function toggleEditTipo(id,nome,cor,corBg){editing.tipo=editing.tipo===id?null:id;renderGrid()}

async function saveTipo(id){
    const nome=document.getElementById('et-nome-'+id).value.trim()
    const cor=document.getElementById('et-cor-'+id).value
    const cor_bg=document.getElementById('et-bg-'+id).value
    if(!nome)return
    await req('atualizar_tipo',{id,nome,cor,cor_bg})
    editing.tipo=null; toast('Tipo atualizado','ok'); await loadTipos()
}
async function delTipo(id,nome){
    if(!confirm(`Excluir "${nome}"?`))return
    await req('deletar_tipo',{id})
    toast('Tipo excluído','ok'); await loadTipos()
}

function renderAddTipo(){
    return `<p class="dt-label">Novo tipo</p>
    <div class="flex gap-1.5">
        <input id="add-tipo-nome" type="text" placeholder="Ex: Code Review" class="dt-edit-input flex-1" onkeydown="if(event.key==='Enter')criarTipo()">
        <input id="add-tipo-cor" type="color" value="#58a6ff" title="Cor texto" class="dt-color-pick">
        <input id="add-tipo-bg" type="color" value="#0c1e38" title="Cor fundo" class="dt-color-pick">
        <button onclick="criarTipo()" class="dt-btn-xs">Criar</button>
    </div>`
}
async function criarTipo(){
    const nome=document.getElementById('add-tipo-nome').value.trim()
    const cor=document.getElementById('add-tipo-cor').value
    const cor_bg=document.getElementById('add-tipo-bg').value
    if(!nome)return
    const res=await req('criar_tipo',{nome,cor,cor_bg})
    if(res.erro){toast(res.erro,'err');return}
    document.getElementById('add-tipo-nome').value=''
    toast(`Tipo "${nome}" criado`,'ok'); await loadTipos()
}

/* ── PROJETOS ── */
async function loadProjetos(){projetos=await req('projetos');renderGrid()}

function renderProjItem(p){
    const isEditing=editing.proj===p.id
    return `<div>
        <div class="dt-item-row">
            <span class="flex items-center gap-2 text-sm text-dt-text">
                <i class="w-3 h-3 rounded-full flex-shrink-0 inline-block" style="background:${p.cor}"></i>${esc(p.nome)}
            </span>
            <div class="dt-item-actions">
                <button class="dt-item-edit-btn" onclick="toggleEditProj(${p.id})">✏</button>
                <button class="dt-item-delete-btn" onclick="delProj(${p.id},'${esc(p.nome)}')">🗑</button>
            </div>
        </div>
        ${isEditing?`<div class="dt-edit-form">
            <div class="flex gap-1.5 mt-1.5">
                <input id="ep-nome-${p.id}" type="text" value="${esc(p.nome)}" class="dt-edit-input" onkeydown="if(event.key==='Enter')saveProj(${p.id})">
                <input id="ep-cor-${p.id}" type="color" value="${p.cor}" class="dt-color-pick">
                <button onclick="saveProj(${p.id})" class="dt-btn-xs">Salvar</button>
            </div>
        </div>`:''}
    </div>`
}
function toggleEditProj(id){editing.proj=editing.proj===id?null:id;renderGrid()}
async function saveProj(id){
    const nome=document.getElementById('ep-nome-'+id).value.trim()
    const cor=document.getElementById('ep-cor-'+id).value
    if(!nome)return
    await req('atualizar_projeto',{id,nome,cor})
    editing.proj=null; toast('Projeto atualizado','ok'); await loadProjetos()
}
async function delProj(id,nome){
    if(!confirm(`Excluir "${nome}"?`))return
    await req('deletar_projeto',{id}); toast('Projeto excluído','ok'); await loadProjetos()
}
function renderAddProj(){
    return `<p class="dt-label">Novo projeto</p>
    <div class="flex gap-1.5">
        <input id="add-proj-nome" type="text" placeholder="Ex: API de Pagamentos" class="dt-edit-input flex-1" onkeydown="if(event.key==='Enter')criarProj()">
        <input id="add-proj-cor" type="color" value="#58a6ff" class="dt-color-pick">
        <button onclick="criarProj()" class="dt-btn-xs">Criar</button>
    </div>`
}
async function criarProj(){
    const nome=document.getElementById('add-proj-nome').value.trim()
    const cor=document.getElementById('add-proj-cor').value
    if(!nome)return
    await req('criar_projeto',{nome,cor})
    document.getElementById('add-proj-nome').value=''
    toast(`Projeto "${nome}" criado`,'ok'); await loadProjetos()
}

/* ── TAGS ── */
async function loadTags(){tags=await req('tags');renderGrid()}

function renderTagItem(t){
    const isEditing=editing.tag===t.id
    return `<div>
        <div class="dt-item-row">
            <span class="dt-tag" style="color:${t.cor};background:${t.cor}18;border-color:${t.cor}44">#${esc(t.nome)}</span>
            <div class="dt-item-actions">
                <button class="dt-item-edit-btn" onclick="toggleEditTag(${t.id})">✏</button>
                <button class="dt-item-delete-btn" onclick="delTag(${t.id},'${esc(t.nome)}')">🗑</button>
            </div>
        </div>
        ${isEditing?`<div class="dt-edit-form">
            <div class="flex gap-1.5 mt-1.5">
                <input id="etag-nome-${t.id}" type="text" value="${esc(t.nome)}" class="dt-edit-input" onkeydown="if(event.key==='Enter')saveTag(${t.id})">
                <input id="etag-cor-${t.id}" type="color" value="${t.cor}" class="dt-color-pick">
                <button onclick="saveTag(${t.id})" class="dt-btn-xs">Salvar</button>
            </div>
        </div>`:''}
    </div>`
}
function toggleEditTag(id){editing.tag=editing.tag===id?null:id;renderGrid()}
async function saveTag(id){
    const nome=document.getElementById('etag-nome-'+id).value.trim()
    const cor=document.getElementById('etag-cor-'+id).value
    if(!nome)return
    await req('atualizar_tag',{id,nome,cor})
    editing.tag=null; toast('Tag atualizada','ok'); await loadTags()
}
async function delTag(id,nome){
    if(!confirm(`Excluir "#${nome}"?`))return
    await req('deletar_tag',{id}); toast('Tag excluída','ok'); await loadTags()
}
function renderAddTag(){
    return `<p class="dt-label">Nova tag</p>
    <div class="flex gap-1.5">
        <input id="add-tag-nome" type="text" placeholder="Ex: performance" class="dt-edit-input flex-1" onkeydown="if(event.key==='Enter')criarTag()">
        <input id="add-tag-cor" type="color" value="#a371f7" class="dt-color-pick">
        <button onclick="criarTag()" class="dt-btn-xs">Criar</button>
    </div>`
}
async function criarTag(){
    const nome=document.getElementById('add-tag-nome').value.trim()
    const cor=document.getElementById('add-tag-cor').value
    if(!nome)return
    const res=await req('criar_tag',{nome,cor})
    if(res.erro){toast(res.erro,'err');return}
    document.getElementById('add-tag-nome').value=''
    toast(`Tag "#${nome}" criada`,'ok'); await loadTags()
}
</script>
</body>
</html>
