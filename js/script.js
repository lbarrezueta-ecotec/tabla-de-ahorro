// ============================================
// Lógica del tablero de ahorro (reemplaza Firebase)
// ============================================

let selectedUser = null;
let isNewUser = false;
let currentSavings = 0;
let checkedCells = new Set();
let savingsValues = [];
let hasPlayedGoalSound = false;

// Datos iniciales de PHP (inyectados en tablero.php)
const SALA_ID = window.SALA_ID || 0;
const USUARIO_ID = window.USUARIO_ID || 0;
let META_INICIAL = window.META_INICIAL || 1500;
const MODO_INICIAL = window.MODO_INICIAL || 'individual';
const TIPO_SUMA = window.TIPO_SUMA || 'conjunta';
let TOTAL_GRUPAL = window.TOTAL_GRUPAL || 0;
let prevSavings = 0;
const PROGRESO_INICIAL = window.PROGRESO_INICIAL || null;

// Elementos DOM (en tablero.php)
const progressFill = document.getElementById('progressFill');
const savedAmountEl = document.getElementById('savedAmount');
const remainingAmountEl = document.getElementById('remainingAmount');
const percentCompleteEl = document.getElementById('percentComplete');
const totalCellsEl = document.getElementById('totalCells');
const metaText = document.getElementById('metaText');
const metaValor = document.getElementById('metaValor');
const modoTexto = document.getElementById('modoTexto');

// Audio
const checkSound = document.getElementById('checkSound');
const goalSound = document.getElementById('goalSound');
if (checkSound) checkSound.volume = 0.4;
if (goalSound) goalSound.volume = 0.6;

function playSound(el) {
    try { el.currentTime = 0; el.play(); } catch (e) { console.log('Sonido:', e); }
}

function generateInitialValues() {
    const target = META_INICIAL || 1500;
    const values = [5,10,15,20,25,30,35,40,45,50];
    let result = [];
    for (let v of values) for (let i = 0; i < 5; i++) result.push(v);
    // Para metas grandes, permitir valores más altos y con variedad
    const maxVal = target > 4000 ? 150 : target > 2500 ? 100 : 50;
    const baseSum = result.reduce((a,b)=>a+b,0);
    const remaining = target - baseSum;
    const extra = [];
    let rem = remaining;
    for (let i=0; i<5; i++) {
        let v = Math.round(rem / (5 - i) / 5) * 5;
        v = Math.max(5, Math.min(maxVal, v));
        // Asegurar variedad: no todos iguales
        if (i>0 && v===extra[i-1] && v<maxVal) v+=5;
        if (v>maxVal) v=maxVal;
        if (v<5) v=5;
        extra.push(v);
        rem -= v;
    }
    let extraSum = extra.reduce((a,b)=>a+b,0);
    let diff = remaining - extraSum;
    if (diff !== 0) extra[extra.length-1] = Math.max(5, Math.min(maxVal, extra[extra.length-1] + diff));
    for (let v of extra) result.push(v);
    let total = result.reduce((a,b)=>a+b,0);
    if (total !== target) {
        const scale = target / total;
        let newResult = result.map(v => {
            let nv = Math.round(v*scale/5)*5;
            return Math.max(5, Math.min(maxVal, nv));
        });
        // Asegurar variedad: mezclar un poco
        let newSum = newResult.reduce((a,b)=>a+b,0);
        let d = target - newSum;
        let idx=0;
        while (d!==0 && idx<200) {
            for (let i=0;i<newResult.length && d!==0;i++) {
                if (d>0 && newResult[i] < maxVal) { newResult[i]+=5; d-=5; }
                else if (d<0 && newResult[i] > 5) { newResult[i]-=5; d+=5; }
            }
            idx++;
            if (idx>50 && d!==0) maxVal+=5;
        }
        result = newResult;
    }
    // Asegurar variedad final: si todos son 50 para 2990, distribuir
    const uniq = new Set(result);
    if (uniq.size < 5) {
        // Forzar variedad: cambiar algunos valores
        for (let i=0; i<result.length && uniq.size<5; i++) {
            if (result[i]===50 && Math.random()>0.5) { result[i]=45; uniq.add(45); }
        }
    }
    return shuffleArray(result);
}
function shuffleArray(arr) {
    for (let i = arr.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
}

// Inicializar valores desde el servidor - usar valores de la SALA (compartidos), no por usuario
if (window.SALA_VALORES && Array.isArray(window.SALA_VALORES) && window.SALA_VALORES.length) {
    savingsValues = window.SALA_VALORES;
} else if (PROGRESO_INICIAL && PROGRESO_INICIAL.valores) {
    try {
        const v = typeof PROGRESO_INICIAL.valores === 'string' ? JSON.parse(PROGRESO_INICIAL.valores) : PROGRESO_INICIAL.valores;
        if (Array.isArray(v) && v.length) savingsValues = v;
        else savingsValues = generateInitialValues();
    } catch(e) { savingsValues = generateInitialValues(); }
} else {
    savingsValues = generateInitialValues();
}

// Cargar progreso inicial de PHP
function loadFromServer() {
    fetch('../../controllers/ProgresoController.php?action=obtener', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sala_id: SALA_ID, usuario_id: USUARIO_ID })
    })
    .then(r => r.json())
    .then(data => {
        // Usar valores de la sala (compartidos) si están disponibles
        if (data.sala && data.sala.valores) {
            try {
                const sv = typeof data.sala.valores === 'string' ? JSON.parse(data.sala.valores) : data.sala.valores;
                if (Array.isArray(sv) && sv.length) savingsValues = sv;
            } catch(e) {}
        }
        if (data.progreso) {
            currentSavings = parseFloat(data.progreso.valor_ahorrado || 0);
            prevSavings = currentSavings;
            const idxStr = data.progreso.casillas_indices ?? '';
            let indices = [];
            if (typeof idxStr === 'string' && idxStr.length) {
                indices = idxStr.split(',').map(s => s.trim()).filter(s => s !== '').map(s => parseInt(s, 10)).filter(n => !isNaN(n));
            } else if (Array.isArray(data.progreso.casillas_indices)) {
                indices = data.progreso.casillas_indices.map(n => parseInt(n, 10)).filter(n => !isNaN(n));
            }
            checkedCells = new Set(indices);
            // Fallback: si no hay índices pero hay casillas_marcadas count, no podemos saber cuáles, dejar vacío
            // El valor ya está en currentSavings, la tabla se marcará según índices disponibles
        }
        if (data.todos) {
            TOTAL_GRUPAL = data.todos.reduce((a, u) => a + parseFloat(u.valor_ahorrado || 0), 0);
        }
        // Si no hay sala valores, generar y guardar en sala (solo admin podría, pero por ahora solo log)
        if (!savingsValues || !savingsValues.length) {
            savingsValues = generateInitialValues();
        }
        initializeTable();
    })
    .catch(err => {
        console.log('Carga inicial (offline):', err);
        prevSavings = currentSavings;
        initializeTable();
    });
}

function initializeTable() {
    const table = document.getElementById('savingsTable');
    if (!table) return;
    table.innerHTML = '';
    if (!savingsValues || savingsValues.length === 0) {
        savingsValues = generateInitialValues();
    }
    const totalSum = savingsValues.reduce((a,b)=>a+b,0);
    savingsValues.forEach((value, index) => {
        const cell = document.createElement('div');
        cell.className = 'savings-cell';
        cell.textContent = '$' + value;
        cell.dataset.index = index;
        cell.dataset.value = value;
        if (checkedCells.has(index)) cell.classList.add('checked');
        cell.addEventListener('click', () => toggleCell(index));
        table.appendChild(cell);
    });
    updateProgress();
}

function toggleCell(index) {
    const cell = document.querySelector('.savings-cell[data-index="' + index + '"]');
    if (!cell) return;
    const value = parseInt(cell.dataset.value);
    if (checkedCells.has(index)) {
        if (confirm('¿Deseas desmarcar esta casilla?')) {
            checkedCells.delete(index);
            cell.classList.remove('checked');
            currentSavings -= value;
            if (MODO_INICIAL === 'grupal' && TIPO_SUMA === 'conjunta') TOTAL_GRUPAL -= value;
            prevSavings = currentSavings;
            saveToServer();
            updateProgress();
        }
    } else {
        checkedCells.add(index);
        cell.classList.add('checked');
        currentSavings += value;
        if (MODO_INICIAL === 'grupal' && TIPO_SUMA === 'conjunta') TOTAL_GRUPAL += value;
        prevSavings = currentSavings;
        playSound(checkSound);
        animateCell(cell);
        saveToServer();
        updateProgress();
    }
}

function animateCell(cell) {
    cell.classList.add('animating');
    // partículas (simplificado)
    for (let i = 0; i < 3; i++) {
        const p = document.createElement('div');
        p.className = 'money-particle';
        const angle = Math.random() * Math.PI * 2;
        const dist = 20 + Math.random() * 30;
        p.style.left = (cell.offsetWidth/2 - 7) + 'px';
        p.style.top = (cell.offsetHeight/2 - 7) + 'px';
        p.style.setProperty('--tx', (Math.cos(angle)*dist) + 'px');
        p.style.setProperty('--ty', (Math.sin(angle)*dist) + 'px');
        cell.appendChild(p);
        setTimeout(() => p.remove(), 1000);
    }
    setTimeout(() => cell.classList.remove('animating'), 500);
}

function updateProgress() {
    const esConjunta = (MODO_INICIAL === 'grupal' && TIPO_SUMA === 'conjunta');
    const valorMostrar = esConjunta ? TOTAL_GRUPAL : currentSavings;
    if (savedAmountEl) savedAmountEl.textContent = Math.round(valorMostrar);
    if (remainingAmountEl) {
        const remaining = Math.max(META_INICIAL - valorMostrar, 0);
        remainingAmountEl.textContent = '$' + Math.round(remaining);
    }
    if (percentCompleteEl) percentCompleteEl.textContent = Math.round(Math.min((valorMostrar/META_INICIAL)*100, 100)) + '%';
    if (totalCellsEl) totalCellsEl.textContent = checkedCells.size;
    if (progressFill) {
        const pct = Math.min((valorMostrar / META_INICIAL) * 100, 100);
        progressFill.style.width = pct + '%';
        if (pct >= 100) {
            progressFill.style.backgroundColor = '#2ecc71';
            if (!hasPlayedGoalSound) { playSound(goalSound); hasPlayedGoalSound = true; }
        } else if (pct >= 75) progressFill.style.backgroundColor = '#3498db';
        else if (pct >= 50) progressFill.style.backgroundColor = '#f39c12';
        else progressFill.style.backgroundColor = '#e74c3c';
    }
    // Mostrar recuadro de meta superada
    const excedidoBox = document.getElementById('excedidoBox');
    if (excedidoBox) {
        if (valorMostrar > META_INICIAL) {
            const excedido = valorMostrar - META_INICIAL;
            excedidoBox.style.display = 'block';
            excedidoBox.textContent = ' ¡Meta superada por $' + Math.round(excedido) + '! (' + (esConjunta ? 'total grupal' : 'tu ahorro') + ' $' + Math.round(valorMostrar) + ' / $' + Math.round(META_INICIAL) + ')';
        } else {
            excedidoBox.style.display = 'none';
        }
    }
    // Actualizar barra horizontal del usuario actual (modo grupal)
    const myBar = document.querySelector('.usuario-bar[data-usuario="' + USUARIO_ID + '"] .usuario-fill');
    const myValEl = document.querySelector('.usuario-bar[data-usuario="' + USUARIO_ID + '"] .usuario-valor');
    if (myBar) {
        const pct = Math.min((currentSavings / META_INICIAL) * 100, 100);
        myBar.style.width = pct + '%';
        myBar.style.background = pct >= 100 ? '#2ecc71' : (pct >= 75 ? '#3498db' : (pct >= 50 ? '#f39c12' : '#e74c3c'));
        if (myValEl) myValEl.textContent = '$' + Math.round(currentSavings) + ' (' + Math.round(pct) + '%)';
    }
}

function saveToServer() {
    const indices = Array.from(checkedCells);
    fetch('../../controllers/ProgresoController.php?action=guardar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            sala_id: SALA_ID,
            usuario_id: USUARIO_ID,
            valor: currentSavings,
            casillas: checkedCells.size,
            indices: indices,
            casillas_indices: indices.join(','),
            valores: savingsValues
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) console.log('Guardado', indices.length, 'celdas');
    })
    .catch(err => console.log('Guardado local (offline):', err));
}

// Modal meta
const metaModal = document.getElementById('metaModal');
const editMetaBtn = document.getElementById('editMetaBtn');
const cancelarMetaBtn = document.getElementById('cancelarMetaBtn');
const guardarMetaBtn = document.getElementById('guardarMetaBtn');
const inputMeta = document.getElementById('inputMeta');
const selectModo = document.getElementById('selectModo');

if (editMetaBtn) {
    editMetaBtn.addEventListener('click', () => {
        if (metaModal) metaModal.style.display = 'flex';
        if (inputMeta) inputMeta.value = META_INICIAL;
        if (selectModo) selectModo.value = MODO_INICIAL;
    });
}
if (cancelarMetaBtn) {
    cancelarMetaBtn.addEventListener('click', () => {
        if (metaModal) metaModal.style.display = 'none';
    });
}
if (guardarMetaBtn) {
    guardarMetaBtn.addEventListener('click', () => {
        const rawMeta = inputMeta.value.trim();
        // Validación numérica (usa validarMeta si está disponible)
        if (typeof validarMeta === 'function') {
            if (!validarMeta(rawMeta)) return;
        } else {
            const n = parseFloat(rawMeta);
            if (isNaN(n) || n < 10 || n > 100000) {
                alert('La meta debe ser un número entre 10 y 100000');
                return;
            }
        }
        const nuevaMeta = parseFloat(rawMeta) || META_INICIAL;
        const modo = selectModo.value;
        // Actualizar en DB
        fetch('../../controllers/ProgresoController.php?action=actualizar_meta', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ sala_id: SALA_ID, meta: nuevaMeta })
        }).then(() => {
            fetch('../../controllers/ProgresoController.php?action=establecer_modo', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ sala_id: SALA_ID, modo: modo })
            }).then(() => {
                META_INICIAL = nuevaMeta;
                if (metaText) metaText.textContent = META_INICIAL;
                if (metaValor) metaValor.textContent = META_INICIAL;
                if (modoTexto) modoTexto.textContent = modo;
                if (metaModal) metaModal.style.display = 'none';
                updateProgress();
                // Recargar lista de usuarios para modo grupal si aplica
                location.reload();
            });
        });
    });
}

// Inicializar al cargar
window.addEventListener('load', () => {
    loadFromServer();
    if (metaText) metaText.textContent = META_INICIAL;
    if (metaValor) metaValor.textContent = META_INICIAL;
    if (modoTexto) modoTexto.textContent = MODO_INICIAL;
});
