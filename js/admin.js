// ==============================================================================
// PROYECTO: ChopCheck Pro
// ARCHIVO: js/admin.js
// DESCRIPCIÓN: Lógica del panel de administración (camarero / cajero).
// OBJETIVO: Ver estado de las mesas, añadir productos y cerrar mesas.
// ==============================================================================

// ID de la mesa seleccionada en el panel
let MESA_ACTIVA = null;

// Intervalo de refresco del panel
let INTERVALO_ADMIN = null;

/**
 * Inicializa el panel de admin al cargar la página.
 * Carga mesas y carta, y arranca un refresco periódico.
 */
async function initAdmin() {
    await cargarMesas();
    await cargarCarta();

    if (INTERVALO_ADMIN) {
        clearInterval(INTERVALO_ADMIN);
    }

    INTERVALO_ADMIN = setInterval(() => {
        cargarMesas();
        if (MESA_ACTIVA) {
            cargarDetalleComanda();
        }
    }, 3000);
}

/**
 * Carga el listado de mesas desde api/get_mesas.php
 * y las pinta en el contenedor #lista-mesas.
 */
async function cargarMesas() {
    try {
        const res = await fetch('api/get_mesas.php');
        const mesas = await res.json();

        const contenedor = document.getElementById('lista-mesas');
        if (!contenedor) return;

        contenedor.innerHTML = '';

        // Si el backend enviara error, lo mostramos y salimos
        if (!Array.isArray(mesas)) {
            console.error('Error al obtener mesas:', mesas);
            return;
        }

        mesas.forEach(m => {
            const btn = document.createElement('button');
            btn.style.width = '100%';
            btn.style.marginBottom = '8px';
            btn.style.padding = '8px';
            btn.style.textAlign = 'left';
            btn.style.borderRadius = '4px';
            btn.style.border = '1px solid #444';
            btn.style.cursor = 'pointer';
            btn.style.display = 'block';

            const esActiva = (MESA_ACTIVA == m.id); // == int/string
            const estaPagando = (m.estado === 'PAGANDO');

            // Estilos según estado
            if (esActiva) {
                btn.style.border = '2px solid #1976d2';
                btn.style.fontWeight = 'bold';
            }

            if (estaPagando) {
                // Color de alerta para mesas que piden la cuenta
                btn.style.backgroundColor = '#ffcdd2';
                btn.style.borderColor = '#d32f2f';
            } else {
                btn.style.backgroundColor = '#f5f5f5';
            }

            const total = (typeof m.total === 'number')
                ? m.total
                : parseFloat(m.total || 0);

            let etiqueta = m.codigo_mesa + ' | Estado: ' + m.estado +
                ' | Total: ' + total.toFixed(2) + '€';

            if (estaPagando && m.pin_pago_mesa) {
                etiqueta += ' | PIN: ' + m.pin_pago_mesa;
            }

            btn.textContent = etiqueta;

            btn.addEventListener('click', () => {
                seleccionarMesa(m.id, m.codigo_mesa);
            });

            contenedor.appendChild(btn);
        });
    } catch (e) {
        console.error('Error al cargar las mesas:', e);
    }
}

/**
 * Carga la carta desde api/get_carta.php
 * y pinta cada producto como un botón clicable para añadir a la mesa activa.
 */
async function cargarCarta() {
    try {
        const res = await fetch('api/get_carta.php');
        const productos = await res.json();

        const contenedor = document.getElementById('carta-grid');
        if (!contenedor) return;

        contenedor.innerHTML = '';

        if (!Array.isArray(productos)) {
            console.error('Error al obtener la carta:', productos);
            return;
        }

        productos.forEach(p => {
            const btn = document.createElement('button');
            btn.style.width = '100%';
            btn.style.margin = '4px 0';
            btn.style.padding = '8px';
            btn.style.borderRadius = '4px';
            btn.style.border = '1px solid #999';
            btn.style.cursor = 'pointer';
            btn.style.backgroundColor = '#fff';
            btn.style.display = 'block';

            const precio = parseFloat(p.precio);
            const texto = `${p.nombre} - ${precio.toFixed(2)}€`;

            btn.textContent = texto;

            btn.addEventListener('click', () => {
                añadirItem(p.nombre, precio);
            });

            contenedor.appendChild(btn);
        });
    } catch (e) {
        console.error('Error al cargar la carta:', e);
    }
}

/**
 * Marca una mesa como activa y carga su detalle de comanda.
 */
function seleccionarMesa(id, codigo) {
    MESA_ACTIVA = id;

    const tituloMesa = document.getElementById('titulo-mesa');
    if (tituloMesa) {
        tituloMesa.innerText = codigo;
    }

    cargarDetalleComanda();
}

/**
 * Carga los items de la mesa activa y los pinta en #detalle-comanda,
 * mostrando también el total de la mesa en #total-mesa-admin.
 */
async function cargarDetalleComanda() {
    if (!MESA_ACTIVA) return;

    try {
        const res = await fetch('api/items.php?mesa_id=' + encodeURIComponent(MESA_ACTIVA));
        const items = await res.json();

        const contenedor = document.getElementById('detalle-comanda');
        const totalDisp = document.getElementById('total-mesa-admin');

        if (!contenedor || !Array.isArray(items)) {
            console.error('Error al obtener items en admin:', items);
            return;
        }

        contenedor.innerHTML = '';
        let total = 0;

        items.forEach(i => {
            const precio = parseFloat(i.precio);
            if (isNaN(precio)) return;

            total += precio;

            const fila = document.createElement('div');
            fila.style.display = 'flex';
            fila.style.justifyContent = 'space-between';
            fila.style.alignItems = 'center';
            fila.style.marginBottom = '4px';
            fila.style.padding = '4px 0';
            fila.style.borderBottom = '1px dashed #ccc';

            const spanNombre = document.createElement('span');
            let textoNombre = i.nombre_producto;

            if (i.nombre_usuario) {
                textoNombre += ' (' + i.nombre_usuario + ')';
            }

            spanNombre.textContent = textoNombre;

            const spanPrecio = document.createElement('span');
            spanPrecio.textContent = precio.toFixed(2) + '€';

            fila.appendChild(spanNombre);
            fila.appendChild(spanPrecio);

            contenedor.appendChild(fila);
        });

        if (totalDisp) {
            totalDisp.innerText = total.toFixed(2) + '€';
        }
    } catch (e) {
        console.error('Error al cargar detalle de comanda:', e);
    }
}

/**
 * Añade un producto a la mesa activa usando api/orders.php
 */
async function añadirItem(nombre, precio) {
    if (!MESA_ACTIVA) {
        alert('Selecciona una mesa primero antes de añadir productos.');
        return;
    }

    try {
        const res = await fetch('api/orders.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                mesa_id: MESA_ACTIVA,
                nombre: nombre,
                precio: precio
            })
        });

        const data = await res.json();

        if (data.success) {
            cargarDetalleComanda();
        } else {
            alert(data.error || 'No se pudo añadir el producto a la mesa.');
        }
    } catch (e) {
        console.error('Error al añadir item:', e);
        alert('Error de conexión al añadir el producto.');
    }
}

/**
 * Cierra una mesa, limpiando los items y dejándola en estado ABIERTA de nuevo.
 * Usa api/close_session.php
 */
async function cobrarMesa() {
    if (!MESA_ACTIVA) {
        alert('No hay mesa activa seleccionada.');
        return;
    }

    const confirmar = confirm('¿Cerrar mesa y limpiar todos los pedidos?');
    if (!confirmar) return;

    try {
        const res = await fetch('api/close_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mesa_id: MESA_ACTIVA })
        });

        const data = await res.json();

        if (data.success) {
            alert('Mesa cerrada y liberada correctamente.');
            MESA_ACTIVA = null;

            const detalle = document.getElementById('detalle-comanda');
            const tituloMesa = document.getElementById('titulo-mesa');
            const totalDisp = document.getElementById('total-mesa-admin');

            if (detalle) detalle.innerHTML = '';
            if (tituloMesa) tituloMesa.innerText = 'Sin mesa activa';
            if (totalDisp) totalDisp.innerText = '0.00€';

            cargarMesas();
        } else {
            alert(data.error || 'Error al cerrar la mesa.');
        }
    } catch (e) {
        console.error('Error al cerrar mesa:', e);
        alert('Error de conexión al cerrar la mesa.');
    }
}

/**
 * Al cargar admin.html, iniciamos el panel.
 */
window.onload = initAdmin;
