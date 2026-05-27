/* ─── DevTrack – Sistema de Alarme ─── */

let _audioCtx = null
let _beepTimer = null
let _alarmQueue = []  // alarmes aguardando confirmação
let _snoozeTimers = {}

function _getAudioCtx() {
    if (!_audioCtx) _audioCtx = new (window.AudioContext || window.webkitAudioContext)()
    return _audioCtx
}

function _playBeep() {
    try {
        const ctx = _getAudioCtx()
        if (ctx.state === 'suspended') ctx.resume()

        // Dois bipes curtos
        ;[0, 0.35].forEach(offset => {
            const osc  = ctx.createOscillator()
            const gain = ctx.createGain()
            osc.connect(gain)
            gain.connect(ctx.destination)
            osc.type = 'sine'
            osc.frequency.value = 880
            gain.gain.setValueAtTime(0, ctx.currentTime + offset)
            gain.gain.linearRampToValueAtTime(0.4, ctx.currentTime + offset + 0.02)
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + offset + 0.4)
            osc.start(ctx.currentTime + offset)
            osc.stop(ctx.currentTime + offset + 0.45)
        })
    } catch(e) {}
}

function _startBeeping() {
    _stopBeeping()
    _playBeep()
    _beepTimer = setInterval(_playBeep, 3000)
}

function _stopBeeping() {
    if (_beepTimer) { clearInterval(_beepTimer); _beepTimer = null }
}

/* ─── Overlay ─── */
function _ensureOverlay() {
    if (document.getElementById('dt-alarm-overlay')) return
    const el = document.createElement('div')
    el.id = 'dt-alarm-overlay'
    el.style.cssText = `
        position:fixed;inset:0;z-index:9999;
        display:none;align-items:center;justify-content:center;
        background:rgba(0,0,0,0.75);backdrop-filter:blur(4px);
    `
    el.innerHTML = `
        <div style="
            background:#161b22;border:1px solid #30363d;border-radius:16px;
            padding:28px 32px;max-width:380px;width:90%;text-align:center;
            box-shadow:0 24px 64px rgba(0,0,0,0.6);
        ">
            <div id="dt-alarm-icon" style="font-size:2.5rem;margin-bottom:8px;animation:dt-ring 0.6s infinite alternate">🔔</div>
            <p id="dt-alarm-title" style="font-size:0.95rem;font-weight:700;color:#e6edf3;margin:0 0 6px"></p>
            <p id="dt-alarm-body"  style="font-size:0.8rem;color:#8b949e;margin:0 0 24px;white-space:pre-wrap"></p>
            <div style="display:flex;gap:10px;justify-content:center">
                <button id="dt-alarm-snooze" style="
                    flex:1;padding:9px 14px;border-radius:10px;border:1px solid #30363d;
                    background:#21262d;color:#8b949e;font-size:0.78rem;font-weight:600;cursor:pointer;
                " onmouseenter="this.style.color='#e6edf3'" onmouseleave="this.style.color='#8b949e'">
                    😴 Soneca (10 min)
                </button>
                <button id="dt-alarm-stop" style="
                    flex:1;padding:9px 14px;border-radius:10px;border:0;
                    background:#58a6ff;color:#0d1117;font-size:0.78rem;font-weight:700;cursor:pointer;
                " onmouseenter="this.style.opacity='0.85'" onmouseleave="this.style.opacity='1'">
                    ✓ Ok, dispensar
                </button>
            </div>
        </div>
        <style>
            @keyframes dt-ring {
                from { transform: rotate(-15deg) scale(1.1); }
                to   { transform: rotate(15deg)  scale(1.2); }
            }
        </style>
    `
    document.body.appendChild(el)
}

function _showNext() {
    if (!_alarmQueue.length) return
    _ensureOverlay()
    const alarm = _alarmQueue[0]
    const overlay  = document.getElementById('dt-alarm-overlay')
    document.getElementById('dt-alarm-title').textContent = alarm.titulo
    document.getElementById('dt-alarm-body').textContent  = alarm.body || ''
    overlay.style.display = 'flex'
    _startBeeping()

    document.getElementById('dt-alarm-stop').onclick = () => {
        _stopBeeping()
        overlay.style.display = 'none'
        _alarmQueue.shift()
        alarm.onDismiss?.()
        if (_alarmQueue.length) setTimeout(_showNext, 500)
    }

    document.getElementById('dt-alarm-snooze').onclick = () => {
        _stopBeeping()
        overlay.style.display = 'none'
        _alarmQueue.shift()
        alarm.onSnooze?.()
        if (_alarmQueue.length) setTimeout(_showNext, 500)
    }
}

/**
 * Dispara um alarme com overlay + som.
 * @param {string} titulo  - Título principal
 * @param {string} body    - Descrição/observação
 * @param {Function} onDismiss - Callback ao dispensar
 * @param {Function} onSnooze  - Callback ao soneca
 */
function triggerAlarm(titulo, body, onDismiss, onSnooze) {
    // Notificação do browser também (para abas em background)
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification('🔔 ' + titulo, { body: body || '', tag: 'dt-alarm-' + Date.now() })
    }
    _alarmQueue.push({ titulo, body, onDismiss, onSnooze })
    if (_alarmQueue.length === 1) _showNext()
}

async function requestNotifPermission() {
    if (!('Notification' in window)) return
    if (Notification.permission === 'default') await Notification.requestPermission()
}
