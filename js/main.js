// ==============================================================================
// PROYECTO: ChopCheck Pro 🔪
// ARCHIVO: js/main.js (VERSIÓN INTEGRAL - SIN ERRORES DE SINTAXIS)
// ==============================================================================

let ITEM_PARA_DIVIDIR = null;

/**
 * 1. ACCESO Y SESIÓN
 */
async function accederAMesa() {
    const aliasInput = document.getElementById('alias');
    const alias = aliasInput ? aliasInput.value.trim() : "";
    
    if (!alias) return alert("Por favor, introduce tu nombre.");

    try {
        const response = await fetch('api/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ alias: alias, codigo_mesa: 'Terraza 1' }) 
        });
        const data = await response.json();
        
        if (data.success) {
            localStorage.setItem('cc_user_id', data.usuario_id);
            localStorage.setItem('cc_alias', data.alias);
            localStorage.setItem('cc_mesa_id', data.sesion_id); 
            mostrarApp(data.alias);
        } else {
            alert("Error: " + data.error);
        }
    } catch (e) {
        alert("Error de conexión con el servidor.");
    }
}

function mostrarApp(alias) {
    const login = document.getElementById('login-screen');
    const app = document.getElementById('app-screen');
    if (login && app) {
        login.style.display = 'none';
        app.style.display = 'flex';
        document.getElementById('user-welcome').innerText = "Hola, " + alias;
        cargarItemsMesa();
        setInterval(cargarItemsMesa, 4000);
    }
}

/**
 * 2. RENDERIZADO DE LA CUENTA
 */
async function cargarItemsMesa() {
    const userId = localStorage.getItem('cc_user_id');
    const mesaId = localStorage.getItem('cc_mesa_id') || 1;
    try {
        const res = await fetch('api/items.php?mesa_id=' + mesaId);
        const items = await res.json();
        renderizarItems(items, userId);
    } catch (e) {
        console.error("Error al cargar items:", e);
    }
}

function renderizarItems(items, userId) {
    const contenedor = document.getElementById('items-list');
    const displayMesa = document.getElementById('total-mesa');
    const displayMio = document.getElementById('total-mio');
    
    if (!contenedor) return;
    contenedor.innerHTML = ''; 
    let tMesa = 0; 
    let tMio = 0;

    items.forEach(item => {
        const precio = parseFloat(item.precio);
        tMesa += precio;
        const esMio = (item.id_usuario_asignado == userId);
        if (esMio) tMio += precio;

        const estaLibre = (item.estado === 'LIBRE');
        const esGrupo = (item.grupo_split !== null && item.grupo_split !== "");
        const statusClass = estaLibre ? 'status-libre' : (esMio ? 'status-mio' : 'status-otro');

        const div = document.createElement('div');
        div.style.display = "flex";
        div.style.alignItems = "center";
        div.style.marginBottom = "8px";

        // Usamos comillas simples y concatenación para evitar el error de resaltado naranja
        let html = '<div class="item-info ' + statusClass + '" style="flex-grow:1; display:flex; justify-content:space-between;" onclick="toggleReclamar(' + item.id + ')">';
        html += '<span>' + item.nombre_producto + '</span>';
        html += '<span>' + precio.toFixed(2) + '€</span>';
        html += '</div>';

        if (estaLibre) {
            html += '<div class="action-icons" style="display:flex; gap:5px; margin-left:10px;">';
            if (esGrupo) {
                html += '<button class="btn-icon" title="Unir" onclick="unirPlato(\'' + item.grupo_split + '\')">🔗</button>';
            } else {
                html += '<button class="btn-icon" title="Dividir" onclick="abrirModalSplit(' + item.id + ', \'' + item.nombre_producto + '\')">✂️</button>';
            }
            html += '</div>';
        }

        div.innerHTML = html;
        contenedor.appendChild(div);
    });

    if(displayMesa) displayMesa.innerText = tMesa.toFixed(2) + "€";
    if(displayMio) displayMio.innerText = tMio.toFixed(2) + "€";
}

/**
 * 3. FUNCIONES DE INTERACCIÓN
 */
async function toggleReclamar(id) {
    const userId = localStorage.getItem('cc_user_id');
    try {
        const res = await fetch('api/toggle_item.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ item_id: id, usuario_id: userId })
        });
        const data = await res.json();
        if (data.success) cargarItemsMesa();
    } catch (e) { console.error("Error al reclamar:", e); }
}

async function unirPlato(grupoId) {
    if (!confirm("¿Deseas unir las partes de este plato?")) return;
    try {
        const res = await fetch('api/undo_split.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ grupo_id: grupoId })
        });
        const data = await res.json();
        if (data.success) cargarItemsMesa();
    } catch (e) { alert("Error al unir platos."); }
}

function abrirModalSplit(id, nombre) {
    ITEM_PARA_DIVIDIR = id;
    document.getElementById('split-item-name').innerText = nombre;
    document.getElementById('modal-split').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modal-split').style.display = 'none';
}

async function confirmarDivision() {
    const parts = document.getElementById('split-parts').value;
    try {
        const res = await fetch('api/split_item.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ item_id: ITEM_PARA_DIVIDIR, parts: parts })
        });
        const data = await res.json();
        if (data.success) {
            cerrarModal();
            cargarItemsMesa();
        }
    } catch (e) { alert("Error al dividir."); }
}

/**
 * 4. PAGO Y SALIDA
 */
async function prepararPago() {
    const mesaId = localStorage.getItem('cc_mesa_id') || 1;
    const total = document.getElementById('total-mio').innerText;
    
    if (parseFloat(total) === 0) return alert("No tienes items reclamados.");
    if (!confirm("¿Quieres avisar al camarero para pagar tu cuenta de " + total + "?")) return;

    try {
        const res = await fetch('api/request_payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mesa_id: mesaId })
        });
        const data = await res.json();
        if (data.success) {
            alert("Aviso enviado. El camarero vendrá a cobrarte.");
        }
    } catch (e) { alert("Error al solicitar el pago."); }
}

function cerrarSesion() {
    if (confirm("¿Cerrar sesión?")) {
        localStorage.clear();
        location.reload();
    }
}

window.onload = function() {
    const alias = localStorage.getItem('cc_alias');
    if (alias) mostrarApp(alias);
};
