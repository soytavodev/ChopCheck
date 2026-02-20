// ==============================================================================
// PROYECTO: ChopCheck Pro
// ARCHIVO: js/main.js
// DESCRIPCIÓN: Lógica del lado del cliente (móvil en la mesa).
// OBJETIVO: El cliente entra con un alias, ve la cuenta de la mesa, reclama
//           sus productos, puede dividir platos y pedir pagar en caja con PIN.
// ==============================================================================

let ITEM_PARA_DIVIDIR = null;   // ID del item que se quiere dividir en el modal
let INTERVALO_ITEMS = null;     // Intervalo de refresco automático de la lista

/**
 * Devuelve el código de mesa desde la URL (?mesa=MESA-01), si existe.
 */
function obtenerCodigoMesaDesdeURL() {
    const params = new URLSearchParams(window.location.search);
    const codigo = params.get('mesa');
    return codigo ? codigo.trim() : '';
}

/**
 * Pinta en cabecera el código de mesa si existe un span con id "mesa-codigo".
 */
function pintarCabeceraMesa() {
    const codigoMesa = localStorage.getItem('cc_codigo_mesa');
    const etiquetaMesa = document.getElementById('mesa-codigo');

    if (codigoMesa && etiquetaMesa) {
        etiquetaMesa.innerText = 'Mesa ' + codigoMesa;
    }
}

/**
 * Al pulsar "Entrar" en el login:
 * 1) Lee alias.
 * 2) Detecta código de mesa (URL o prompt).
 * 3) Llama a api/auth.php y guarda datos en localStorage.
 */
async function accederAMesa() {
    const inputAlias = document.getElementById('alias');
    const alias = inputAlias ? inputAlias.value.trim() : '';

    if (!alias) {
        alert('Por favor, introduce tu nombre o apodo.');
        return;
    }

    // 1. Intentar coger la mesa de la URL (?mesa=MESA-01)
    let codigoMesa = obtenerCodigoMesaDesdeURL();

    // 2. Si no hay mesa en la URL (desarrollo sin QR), la pedimos por prompt
    if (!codigoMesa) {
        codigoMesa = prompt('Introduce el código de la mesa (por ejemplo, MESA-01):') || '';
        codigoMesa = codigoMesa.trim();
        if (!codigoMesa) {
            alert('Necesito un código de mesa para continuar.');
            return;
        }
    }

    try {
        const response = await fetch('api/auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                alias: alias,
                codigo_mesa: codigoMesa
            })
        });

        const data = await response.json();

        if (data.success) {
            // Guardamos datos mínimos de sesión
            localStorage.setItem('cc_user_id', data.usuario_id);
            localStorage.setItem('cc_alias', data.alias);
            localStorage.setItem('cc_mesa_id', data.sesion_id);     // ID real de la tabla sesiones
            localStorage.setItem('cc_codigo_mesa', data.codigo_mesa);

            mostrarApp(data.alias);
        } else {
            alert('No se pudo acceder a la mesa: ' + (data.error || 'Error desconocido.'));
        }
    } catch (e) {
        console.error('Error en accederAMesa:', e);
        alert('Error de conexión con el servidor.');
    }
}

/**
 * Muestra la app (oculta login) y arranca el refresco de items.
 */
function mostrarApp(alias) {
    const loginScreen = document.getElementById('login-screen');
    const appScreen = document.getElementById('app-screen');
    const welcomeLabel = document.getElementById('user-welcome');

    if (loginScreen && appScreen) {
        loginScreen.style.display = 'none';
        appScreen.style.display = 'flex';
    }

    if (welcomeLabel) {
        welcomeLabel.innerText = 'Hola, ' + alias;
    }

    // Pintamos la cabecera con el código de mesa
    pintarCabeceraMesa();

    // Cargamos items de la mesa
    cargarItemsMesa();

    // Arrancamos refresco periódico
    if (INTERVALO_ITEMS) {
        clearInterval(INTERVALO_ITEMS);
    }
    INTERVALO_ITEMS = setInterval(cargarItemsMesa, 4000);
}

/**
 * Llama al backend para obtener los items de la mesa actual.
 */
async function cargarItemsMesa() {
    const userId = localStorage.getItem('cc_user_id');
    const mesaId = localStorage.getItem('cc_mesa_id');

    if (!userId || !mesaId) {
        // Sin sesión válida no hacemos nada
        return;
    }

    try {
        const res = await fetch('api/items.php?mesa_id=' + encodeURIComponent(mesaId));
        const data = await res.json();

        if (Array.isArray(data)) {
            // Opcional: debug en consola para ver que llegan items
            console.log('[CLIENTE] Items recibidos:', data.length);
            renderizarItems(data, parseInt(userId, 10));
        } else if (data && data.success === false) {
            console.error('Error desde api/items.php:', data.error);
        }
    } catch (e) {
        console.error('Error al cargar items:', e);
    }
}

/**
 * Dibuja los items y calcula total de la mesa y total del usuario.
 */
function renderizarItems(items, userId) {
    const contenedor = document.getElementById('items-list');
    const displayMesa = document.getElementById('total-mesa');
    const displayMio = document.getElementById('total-mio');

    if (!contenedor) return;

    contenedor.innerHTML = '';

    let totalMesa = 0;
    let totalMio = 0;

    items.forEach(item => {
        const precio = parseFloat(item.precio);
        if (isNaN(precio)) return;

        totalMesa += precio;

        const esMio = (item.id_usuario_asignado == userId);
        const estaLibre = (item.estado === 'LIBRE');
        const esGrupo = (item.grupo_split !== null && item.grupo_split !== '');

        if (esMio) {
            totalMio += precio;
        }

        const statusClass = estaLibre
            ? 'status-libre'
            : (esMio ? 'status-mio' : 'status-otro');

        const fila = document.createElement('div');
        fila.style.display = 'flex';
        fila.style.alignItems = 'center';
        fila.style.marginBottom = '8px';

        const info = document.createElement('div');
        info.className = 'item-info ' + statusClass;

        const spanNombre = document.createElement('span');
        spanNombre.textContent = item.nombre_producto;

        const spanPrecio = document.createElement('span');
        spanPrecio.textContent = precio.toFixed(2) + '€';

        info.appendChild(spanNombre);
        info.appendChild(spanPrecio);

        // Clic en el bloque = reclamar/liberar
        info.addEventListener('click', () => {
            toggleReclamar(item.id);
        });

        fila.appendChild(info);

        if (estaLibre) {
            const actionIcons = document.createElement('div');
            actionIcons.className = 'action-icons';

            const btnIcon = document.createElement('button');
            btnIcon.className = 'btn-icon';

            if (esGrupo) {
                btnIcon.textContent = '🔗';
                btnIcon.title = 'Unir partes de este plato';
                btnIcon.addEventListener('click', (ev) => {
                    ev.stopPropagation();
                    unirPlato(item.grupo_split);
                });
            } else {
                btnIcon.textContent = '✂️';
                btnIcon.title = 'Dividir este plato';
                btnIcon.addEventListener('click', (ev) => {
                    ev.stopPropagation();
                    abrirModalSplit(item.id, item.nombre_producto);
                });
            }

            actionIcons.appendChild(btnIcon);
            fila.appendChild(actionIcons);
        }

        contenedor.appendChild(fila);
    });

    if (displayMesa) {
        displayMesa.innerText = totalMesa.toFixed(2) + '€';
    }
    if (displayMio) {
        displayMio.innerText = totalMio.toFixed(2) + '€';
    }
}

/**
 * Reclamar o liberar item.
 */
async function toggleReclamar(itemId) {
    const userId = localStorage.getItem('cc_user_id');
    if (!userId) return;

    try {
        const res = await fetch('api/toggle_item.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                item_id: parseInt(itemId, 10),
                usuario_id: parseInt(userId, 10)
            })
        });

        const data = await res.json();
        if (data.success) {
            cargarItemsMesa();
        } else {
            alert(data.error || 'No se pudo actualizar el producto.');
        }
    } catch (e) {
        console.error('Error en toggleReclamar:', e);
        alert('Error de conexión al reclamar el producto.');
    }
}

/**
 * Unir partes de un plato dividido.
 */
async function unirPlato(grupoId) {
    if (!grupoId) return;

    const confirmar = confirm('¿Deseas unir las partes de este plato?');
    if (!confirmar) return;

    try {
        const res = await fetch('api/undo_split.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ grupo_id: grupoId })
        });

        const data = await res.json();
        if (data.success) {
            cargarItemsMesa();
        } else {
            alert(data.error || 'No se pudo unir el plato.');
        }
    } catch (e) {
        console.error('Error en unirPlato:', e);
        alert('Error al unir platos.');
    }
}

/**
 * Abrir modal para dividir plato.
 */
function abrirModalSplit(id, nombre) {
    ITEM_PARA_DIVIDIR = id;

    const lblNombre = document.getElementById('split-item-name');
    const modal = document.getElementById('modal-split');
    const inputPartes = document.getElementById('split-parts');

    if (lblNombre) lblNombre.innerText = nombre || '';
    if (inputPartes) inputPartes.value = '2';
    if (modal) modal.style.display = 'flex';
}

/**
 * Cerrar modal de división.
 */
function cerrarModal() {
    const modal = document.getElementById('modal-split');
    if (modal) modal.style.display = 'none';
    ITEM_PARA_DIVIDIR = null;
}

/**
 * Confirmar división de plato.
 */
async function confirmarDivision() {
    const partesInput = document.getElementById('split-parts');
    if (!partesInput) return;

    const partes = parseInt(partesInput.value, 10);

    if (!ITEM_PARA_DIVIDIR) {
        alert('No hay producto seleccionado para dividir.');
        return;
    }

    if (isNaN(partes) || partes < 2) {
        alert('Debes dividir el plato en al menos 2 partes.');
        return;
    }

    try {
        const res = await fetch('api/split_item.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                item_id: ITEM_PARA_DIVIDIR,
                parts: partes
            })
        });

        const data = await res.json();

        if (data.success) {
            cerrarModal();
            cargarItemsMesa();
        } else {
            alert(data.error || 'No se pudo dividir el plato.');
        }
    } catch (e) {
        console.error('Error en confirmarDivision:', e);
        alert('Error al dividir el plato.');
    }
}

/**
 * Avisar que quiere pagar en caja.
 */
async function prepararPago() {
    const mesaId = localStorage.getItem('cc_mesa_id');
    if (!mesaId) {
        alert('No se ha encontrado la mesa en tu sesión.');
        return;
    }

    const totalMioLabel = document.getElementById('total-mio');
    let totalMio = 0;

    if (totalMioLabel) {
        const texto = totalMioLabel.innerText.replace('€', '').trim();
        totalMio = parseFloat(texto);
    }

    if (!totalMio || isNaN(totalMio) || totalMio <= 0) {
        alert('No tienes productos reclamados. Nada que pagar.');
        return;
    }

    const confirmar = confirm(
        'Vas a avisar al camarero/cajero de que quieres pagar ' +
        totalMio.toFixed(2) + '€. ¿Continuar?'
    );
    if (!confirmar) return;

    try {
        const res = await fetch('api/request_payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mesa_id: parseInt(mesaId, 10) })
        });

        const data = await res.json();
        if (data.success) {
            alert('Aviso enviado. El camarero/cajero verá tu mesa como "PAGANDO" y te dirá un PIN en caja.');
        } else {
            alert(data.error || 'No se pudo enviar el aviso de pago.');
        }
    } catch (e) {
        console.error('Error en prepararPago:', e);
        alert('Error al solicitar el pago.');
    }
}

/**
 * Confirmar pago introduciendo PIN.
 */
async function confirmarPagoConPin() {
    const mesaId = localStorage.getItem('cc_mesa_id');
    const usuarioId = localStorage.getItem('cc_user_id');
    const inputPin = document.getElementById('pin-pago');

    if (!mesaId || !usuarioId) {
        alert('Tu sesión de mesa o usuario no es válida.');
        return;
    }

    if (!inputPin) {
        alert('No se encontró el campo de PIN en la interfaz.');
        return;
    }

    const pin = inputPin.value.trim();
    if (pin.length !== 4) {
        alert('Introduce el PIN de 4 dígitos que te ha dado el cajero.');
        return;
    }

    try {
        const res = await fetch('api/payments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                mesa_id: parseInt(mesaId, 10),
                usuario_id: parseInt(usuarioId, 10),
                pin: pin
            })
        });

        const data = await res.json();
        if (data.success) {
            alert('PIN correcto. Tus productos han quedado marcados como pagados.');
            cargarItemsMesa();
        } else {
            alert(data.error || 'PIN incorrecto o error al procesar el pago.');
        }
    } catch (e) {
        console.error('Error en confirmarPagoConPin:', e);
        alert('Error al validar el PIN.');
    }
}

/**
 * Cerrar sesión y limpiar localStorage.
 */
function cerrarSesion() {
    const confirmar = confirm('¿Seguro que quieres cerrar sesión en esta mesa?');
    if (!confirmar) return;

    if (INTERVALO_ITEMS) {
        clearInterval(INTERVALO_ITEMS);
        INTERVALO_ITEMS = null;
    }

    localStorage.removeItem('cc_user_id');
    localStorage.removeItem('cc_alias');
    localStorage.removeItem('cc_mesa_id');
    localStorage.removeItem('cc_codigo_mesa');

    location.reload();
}

/**
 * Al cargar la página, si ya hay sesión en localStorage, la reanudamos.
 */
window.onload = function () {
    const loginScreen = document.getElementById('login-screen');
    const appScreen = document.getElementById('app-screen');

    if (loginScreen && appScreen) {
        loginScreen.style.display = 'flex';
        appScreen.style.display = 'none';
    }

    const aliasGuardado = localStorage.getItem('cc_alias');
    const userIdGuardado = localStorage.getItem('cc_user_id');
    const mesaIdGuardado = localStorage.getItem('cc_mesa_id');

    if (aliasGuardado && userIdGuardado && mesaIdGuardado) {
        mostrarApp(aliasGuardado);
    }
};
