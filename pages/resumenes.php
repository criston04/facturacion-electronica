<?php
include_once("template/cabecera.php");

if (!$logueado) {
    header("Location: ../index.php");
    exit();
}
?>
<main class="flex-2 p-5 w-full">

    <div class="flex items-center justify-between mb-4 md:hidden">
        <button onclick="toggleMenu()" class="text-2xl">☰</button>
        <span class="font-semibold">Resúmenes</span>
    </div>

    <div class="bg-white p-6 rounded-xl shadow mb-6">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Resumen Diario de Boletas</h1>
                <p class="text-sm text-gray-500">Informar a SUNAT las boletas emitidas en una fecha</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                <input type="date" id="fecha" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500" value="<?= date('Y-m-d') ?>">
                <button type="button" id="btnCargar" class="bg-teal-500 hover:bg-teal-600 text-white px-5 py-2 rounded-lg text-sm flex items-center gap-2">
                    <i class="fas fa-search"></i> Cargar boletas
                </button>
                <button type="button" id="btnEnviar" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm flex items-center gap-2">
                    <i class="fas fa-paper-plane"></i> Enviar resumen a SUNAT
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-teal-600 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">N°</th>
                    <th class="px-4 py-2 text-left">Comprobante</th>
                    <th class="px-4 py-2 text-right">Subtotal</th>
                    <th class="px-4 py-2 text-right">IGV</th>
                    <th class="px-4 py-2 text-right">Total</th>
                    <th class="px-4 py-2 text-center">Estado</th>
                </tr>
            </thead>
            <tbody id="tablaBoletas" class="divide-y divide-gray-200 text-gray-700"></tbody>
        </table>
        <p id="sinBoletas" class="text-center text-gray-500 p-6">Selecciona una fecha y pulsa “Cargar boletas”.</p>
    </div>

</main>

<script>
    const RESUMEN_URL = '../admin/classes/ResumenDiario.php';

    function cargarBoletas() {
        const fecha = document.getElementById('fecha').value;
        $.ajax({
            url: RESUMEN_URL, method: 'POST', dataType: 'json',
            data: { get_boletas_fecha: 1, fecha: fecha },
            success: function (rows) {
                const tbody = document.getElementById('tablaBoletas');
                tbody.innerHTML = '';
                document.getElementById('sinBoletas').style.display = rows.length ? 'none' : '';
                rows.forEach((b, i) => {
                    const tr = document.createElement('tr');
                    const num = b.serie + '-' + String(b.correlativo).padStart(6, '0');
                    const anulada = b.estado === 'ANULADO';
                    tr.innerHTML =
                        '<td class="px-4 py-2">' + (i + 1) + '</td>' +
                        '<td class="px-4 py-2 font-medium">' + num + '</td>' +
                        '<td class="px-4 py-2 text-right">S/ ' + parseFloat(b.subtotal).toFixed(2) + '</td>' +
                        '<td class="px-4 py-2 text-right">S/ ' + parseFloat(b.igv).toFixed(2) + '</td>' +
                        '<td class="px-4 py-2 text-right font-semibold">S/ ' + parseFloat(b.total).toFixed(2) + '</td>' +
                        '<td class="px-4 py-2 text-center">' +
                            '<span class="' + (anulada ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600') + ' px-2 py-1 rounded-full text-xs">' +
                            (anulada ? 'ANULA' : 'ADICIONA') + '</span></td>';
                    tbody.appendChild(tr);
                });
            },
            error: function () { toastr.error('No se pudieron cargar las boletas'); }
        });
    }

    function enviarResumen() {
        const fecha = document.getElementById('fecha').value;
        const btn = document.getElementById('btnEnviar');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i> Enviando...';
        $.ajax({
            url: RESUMEN_URL, method: 'POST',
            data: { enviar_resumen: 1, fecha: fecha },
            success: function (response) {
                let res; try { res = JSON.parse(response); } catch (e) { toastr.error('Respuesta inválida'); return; }
                if (res.status === 202) {
                    toastr.success(res.message + ' (' + res.total_boletas + ' boletas)');
                } else {
                    toastr.error(res.message);
                }
            },
            error: function () { toastr.error('Error al enviar el resumen'); },
            complete: function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar resumen a SUNAT';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('btnCargar').addEventListener('click', cargarBoletas);
        document.getElementById('btnEnviar').addEventListener('click', enviarResumen);
        cargarBoletas();
    });
</script>

<?php include_once("template/pie.php"); ?>
