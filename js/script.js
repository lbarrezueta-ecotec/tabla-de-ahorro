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
const META_INICIAL = window.META_INICIAL || 1500;
const MODO_INICIAL = window.MODO_INICIAL || 'individual';
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
    const values = [5,10,15,20,25,30,35,40,45,50];
    let result = [];
    for (let v of values) for (let i = 0; i < 5; i++) result.push(v);
    const remaining = 1500 - result.reduce((a,b)=>a+b,0); // ~125
    const rem = [25,25,25,25,25];
    for (let v of rem) result.push(v);
    return shuffleArray(result);
}
function shuffleArray(arr) {
    for (let i = arr.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
}

// Inicializar valores desde el servidor o generarlos
if (PROGRESO_INICIAL && PROGRESO_INICIAL.values && PROGRESO_INICIAL.values.length) {
    savingsValues = PROGRESO_INICIAL.values;
} else {
    savingsValues = generateInitialValues();
    // Si tiene valores iniciales guardados en DB, ya se cargaron en PHP
   // (se puede completar con endpoint si se requiere persistencia
    // de los valores de cada usuario; para esta entrega usamos los
    // valores generados por el usuario al registrar)
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
        if (data.progreso) {
            currentSavings = parseFloat(data.progreso.valor_ahorrado || 0);
            checkedCells = new Set((data.progreso.casillas_marcadas || '').toString().split(',').filter(Number));
            if (data.progreso.values && data.progreso.values.length) {
                savingsValues = data.progreso.values;
            }
        }
        initializeTable();
    })
    .catch(err => {
        console.log('Carga inicial (offline):', err);
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
    console.log('Total tabla:', totalSum, 'meta:', META_INICIAL);

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
            saveToServer();
            updateProgress();
        }
    } else {
        checkedCells.add(index);
        cell.classList.add('checked');
        currentSavings += value;
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
    if (savedAmountEl) savedAmountEl.textContent = Math.round(currentSavings);
    if (remainingAmountEl) {
        const remaining = Math.max(META_INICIAL - currentSavings, 0);
        remainingAmountEl.textContent = '$' + Math.round(remaining);
    }
    if (percentCompleteEl) percentCompleteEl.textContent = Math.round(Math.min((currentSavings/META_INICIAL)*100, 100)) + '%';
    if (totalCellsEl) totalCellsEl.textContent = checkedCells.size;
    if (progressFill) {
        const pct = Math.min((currentSavings / META_INICIAL) * 100, 100);
        progressFill.style.width = pct + '%';
        if (pct >= 100) {
            progressFill.style.backgroundColor = '#2ecc71';
            if (!hasPlayedGoalSound) { playSound(goalSound); hasPlayedGoalSound = true; }
        } else if (pct >= 75) progressFill.style.backgroundColor = '#3498db';
        else if (pct >= 50) progressFill.style.backgroundColor = '#f39c12';
        else progressFill.style.backgroundColor = '#e74c3c';
    }
}

function saveToServer() {
    fetch('../../controllers/ProgresoController.php?action=guardar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            sala_id: SALA_ID,
            usuario_id: USUARIO_ID,
            valor: currentSavings,
            casillas: checkedCells.size,
            values: savingsValues
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) console.log('Guardado');
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
        const nuevaMeta = parseFloat(inputMeta.value) || META_INICIAL;
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
