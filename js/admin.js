// ==============================================================================
// PROYECTO: ChopCheck Pro 🔪
// ARCHIVO: js/admin.js (VERSIÓN COMPLETA Y ESTABLE)
// ==============================================================================

let MESA_ACTIVA = null;

async function initAdmin() {
    await cargarMesas();
    await cargarCarta();
    setInterval(() => {
        cargarMesas();
        if (MESA_ACTIVA) cargarDetalleComanda();
    }, 3000);
}

/**
 * CARGAS PRINCIPALES
 */
async function cargarMesas() {
    try {
        const res = await fetch('api/get_mesas.php');
        const mesas = await res.json();
        const contenedor = document.getElementById('lista-mesas');
        if (!contenedor) return;

        let html = '';
        mesas.forEach(m => {
            const activa = (MESA_ACTIVA == m.id) ? 'active' : '';
            const pidiendo = (m.estado === 'PAGANDO');
            const style = pidiendo ? 'background-color: #ffcdd2; border: 2px solid #d32f2f;' : '';
            const statusLabel = pidiendo ? '⚠️ Pidiendo Cuenta' : m.estado;

            html += `
                <div class="mesa-card ${activa}" style="${style}" onclick="seleccionarMesa(${m.id}, '${m.codigo_mesa}')">
                    <strong>${m.codigo_mesa}</strong><br><small>${statusLabel}</small>
                </div>`;
        });
        contenedor.innerHTML = html;
    } catch (e) { console.error(e); }
}

async function cargarCarta() {
    try {
        const res = await fetch('api/get_carta.php');
        const productos = await res.json();
        const contenedor = document.getElementById('carta-grid');
        if (!contenedor) return;

        let html = '';
        productos.forEach(p => {
            html += `
                <button class="btn-item" onclick="añadirItem('${p.nombre}', ${p.precio})">
                    <strong>${p.nombre}</strong><br>${parseFloat(p.precio).toFixed(2)}€
                </button>`;
        });
        contenedor.innerHTML = html;
    } catch (e) { console.error(e); }
}

/**
 * DETALLE DE COMANDA
 */
function seleccionarMesa(id, codigo) {
    MESA_ACTIVA = id;
    document.getElementById('titulo-mesa').innerText = codigo;
    cargarDetalleComanda();
}

async function cargarDetalleComanda() {
    if (!MESA_ACTIVA) return;
    try {
        const res = await fetch(`api/items.php?mesa_id=${MESA_ACTIVA}`);
        const items = await res.json();
        const contenedor = document.getElementById('detalle-comanda');
        const totalDisp = document.getElementById('total-mesa-admin');
        
        let html = '';
        let total = 0;

        items.forEach(i => {
            total += parseFloat(i.precio);
            // Mostrar quién tiene el plato si está asignado
            const badge = (i.id_usuario_asignado && i.nombre_usuario) ? 
                `<span class="badge-user" style="background:#d32f2f; color:white; padding:2px 5px; font-size:0.7rem; margin-left:10px;">${i.nombre_usuario}</span>` : '';
            
            html += `
                <div class="linea-comanda" style="display:flex; justify-content:space-between; padding:5px 0; border-bottom:1px solid #ddd;">
                    <span>${i.nombre_producto} ${badge}</span>
                    <span>${parseFloat(i.precio).toFixed(2)}€</span>
                </div>`;
        });

        contenedor.innerHTML = html;
        totalDisp.innerText = total.toFixed(2) + "€";
    } catch (e) { console.error(e); }
}

/**
 * ACCIONES STAFF
 */
async function añadirItem(nombre, precio) {
    if (!MESA_ACTIVA) return alert("Selecciona una mesa primero.");
    try {
        await fetch('api/orders.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ mesa_id: MESA_ACTIVA, nombre, precio })
        });
        cargarDetalleComanda();
    } catch (e) { alert("Error al marchar."); }
}

async function cobrarMesa() {
    if (!MESA_ACTIVA) return;
    if (!confirm("¿Cerrar mesa y limpiar pedidos?")) return;

    try {
        const res = await fetch('api/close_session.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ mesa_id: MESA_ACTIVA })
        });
        if ((await res.json()).success) {
            alert("Mesa liberada.");
            MESA_ACTIVA = null;
            location.reload();
        }
    } catch (e) { alert("Error al cerrar."); }
}

window.onload = initAdmin;
