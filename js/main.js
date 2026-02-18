async function unirPlato(grupoId) {
    console.log("Intentando unir grupo:", grupoId);
    if (!confirm("¿Deseas unir todas las partes de este plato?")) return;

    try {
        const res = await fetch('api/undo_split.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ grupo_id: grupoId })
        });

        // Intentamos leer el texto primero para ver si hay errores de PHP escondidos
        const text = await res.text();
        console.log("Respuesta cruda del servidor:", text);

        const data = JSON.parse(text); // Convertimos a JSON manualmente
        
        if (data.success) {
            cargarItemsMesa();
        } else {
            alert("Atención: " + data.error);
        }
    } catch (e) {
        console.error("Error detallado:", e);
        alert("Error crítico al intentar unir. Revisa la consola (F12).");
    }
}
