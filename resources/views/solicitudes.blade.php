    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gestión de Solicitudes</title>
    <!-- 1. CDN OFICIAL DE TAILWIND PLAY (Esta URL sí contiene el motor de estilos en vivo) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- 2. CDN DE ALPINE.JS (El que ya tenías configurado perfectamente) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    </head>
    <body class="bg-gray-50 text-gray-800 font-sans" x-data="solicitudesApp()">

        <div class="max-w-7xl mx-auto p-6">
            <header class="mb-8 flex justify-between items-center border-b pb-4">
                <h1 class="text-3xl font-bold text-gray-900">📥 Panel de Solicitudes</h1>
                <!--<span class="bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1 rounded-full">Laravel 12 + Alpine.js</span>-->
            </header>

            <!-- CONTENIDO PRINCIPAL: DOS COLUMNAS -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- COLUMNA IZQUIERDA: FORMULARIO (CREAR / EDITAR) -->
                <div class="lg:col-span-1">
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 sticky top-6">
                        <h2 class="text-xl font-semibold mb-4 text-gray-700" x-text="editando ? '📝 Editar Solicitud' : '✨ Nueva Solicitud'"></h2>

                        <form @submit.prevent="guardarSolicitud()">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-600 mb-1">Título</label>
                                <input type="text" x-model="form.title" required
                                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <label class="block text-sm font-medium text-gray-600 mb-1">Descripción</label>
                                <textarea x-model="form.description"  required
                                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>

                            </div>


                            <div class="flex gap-2">
                                <button type="submit"
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200"
                                    x-text="editando ? 'Actualizar' : 'Crear Solicitud'">
                                    Guardar
                                </button>
                                <button type="button" x-show="editando" @click="resetForm()"
                                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-lg transition duration-200">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>


    <!-- COLUMNA DERECHA: TABLA DE SOLICITUDES -->
    <!-- COLUMNA DERECHA: TABLA DE SOLICITUDES -->
    <div class="lg:col-span-2 space-y-4">

        <h2 class="text-xl font-semibold text-gray-700">
            📋 Listado de Solicitudes
            (<span x-text="lista.length"></span>)
        </h2>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">

            <table class="w-full text-sm text-left border-collapse">

                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-3 font-semibold text-gray-700">ID</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">Título</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">Descripción</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">Estado</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">Resumen IA</th>
                        <th class="px-4 py-3 font-semibold text-gray-700 text-center">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">

                    <template x-if="lista.length === 0">
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                No hay solicitudes registradas.
                            </td>
                        </tr>
                    </template>

                    <template x-for="item in lista" :key="item.id">
                        <tr class="hover:bg-gray-50 transition duration-150">

                            <!-- ID -->
                            <td class="px-4 py-3 text-gray-500 font-mono">
                                #<span x-text="item.id"></span>
                            </td>

                            <!-- Título -->
                            <td class="px-4 py-3 font-medium text-gray-900">
                                <span x-text="item.title"></span>
                            </td>

                            <!-- Descripción -->
                            <td class="px-4 py-3 text-gray-600 max-w-xs truncate">
                                <span :title="item.description" x-text="item.description"></span>
                            </td>

                            <!-- Estado -->
                            <td class="px-4 py-3">
                                <span :class="obtenerClaseEstado(item.status)"
                                      class="text-xs font-semibold px-2 py-1 rounded-full uppercase tracking-wider"
                                      x-text="item.status">
                                </span>
                            </td>

                            <!-- Resumen IA -->
                            <td class="px-4 py-3 text-gray-500 italic text-xs">
                                <span x-text="item.llm_summary || 'Sin resumen'"></span>
                            </td>

                            <!-- Acciones -->
                             
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-3">

                                    <!-- Botón de Edición -->
                                    <button @click="prepararEdicion(item)"
                                            class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center gap-1 transition">
                                        ✏️ <span class="underline">Editar</span>
                                    </button>

                                    <!-- Selector de Transiciones Dinámicas -->
                                          <div class="relative">
                                        <!-- Si el flujo está completado, bloqueamos el selector -->
                                        <template x-if="item.status === 'done'">
                                            <span class="text-xs text-green-600 font-medium bg-green-50 px-2 py-1 rounded border border-green-200">
                                                ✨ Completado
                                            </span>
                                        </template>

                                        <template x-if="item.status !== 'done'">
                                            <select
                                                @change="ejecutarTransicion(item.id, $event.target.value); $event.target.value = '';"
                                                class="bg-gray-50 border border-gray-300 text-gray-700 text-xs rounded-lg focus:ring-blue-500 focus:border-blue-500 p-1.5 cursor-pointer">

                                                <option value="">⚙️ Cambiar estado...</option>

                                                <template x-for="estadoDestino in item.transiciones_permitidas"
                                                          :key="estadoDestino">
                                                    <option :value="estadoDestino"
                                                            x-text="estadoDestino">
                                                    </option>
                                                </template>

                                            </select>
                                        </template>
                                    </div>
                                        
                                        </select>


                                        </template>
                                    </div>
                                    
                                </div>
                            </td>

                        </tr>
                    </template>

                </tbody>
            </table>
        </div>
    </div>

        </div>
    <!-- LÓGICA FRONTEND: Script de control de solicitudes -->
    <script>
        function solicitudesApp() {
            return {
                // Carga inicial de datos desde el controlador Laravel hacia JavaScript
                lista: @json(
                    $data->map(fn($solicitud) => array_merge(
                        $solicitud->toArray(),
                        [
                            'status' => $solicitud->status->value,
                            'transiciones_permitidas' => $solicitud->status->transicionesPermitidas()
                        ]
                    ))->values()
                ),
                editando: false,
                form: {
                    id: null,
                    title: '',
                    description: ''
                },

                // Retorna las clases estéticas de Tailwind dependiendo del slug del estado
                obtenerClaseEstado(status) {
                    const clases = {
                        'created': 'bg-gray-100 text-gray-800 border border-gray-200',
                        'in_review': 'bg-blue-100 text-blue-800 border border-blue-200',
                        'in_progress': 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                        'paused': 'bg-orange-100 text-orange-800 border border-orange-200',
                        'done': 'bg-green-100 text-green-800 border border-green-200'
                    };
                    return clases[status] || 'bg-purple-100 text-purple-800';
                },

                // Limpia el formulario y regresa al estado "Crear"
                resetForm() {
                    this.editando = false;
                    this.form = { id: null, title: '', description: '' };
                },

                // Prepara los campos para la edición de un registro
                prepararEdicion(item) {
                    this.editando = true;
                    this.form = {
                        id: item.id,
                        title: item.title,
                        description: item.description
                    };
                    // Desplazamiento suave hacia el formulario
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },

                // Lógica para enviar la transición al backend y actualizar el estado de la vista
                ejecutarTransicion(id, nuevoEstado) {
                    if (!nuevoEstado) return;

                    // POST hacia el endpoint asíncrono que creamos en Laravel
                    fetch(`/requests/${id}/transitions`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ status: nuevoEstado })
                    })
                    .then(response => response.json())
                    .then(res => {
                        if (res.error) {
                            alert(res.detalle || res.error);
                        } else {
                            // Buscamos la solicitud en nuestro arreglo local de Alpine
                            const index = this.lista.findIndex(item => item.id === id);
                            if (index !== -1) {
                                // Actualizamos el estado con la respuesta del servidor
                                this.lista[index].status = res.data.status;

                                // Refrescamos inmediatamente el catálogo de transiciones lícitas
                            location.reload();
                            }
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Error crítico de comunicación con el servidor backend.');
                    });
                },

                // guarda una solicitud
                guardarSolicitud() {
                    const url = this.editando ? `/requests/${this.form.id}` : '/requests';
                    const method = 'POST';

                    fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            title: this.form.title,
                            description: this.form.description
                        })
                    })
                    .then(response => response.json())
                    .then(res => {
                        if(res.errors) {
                            alert('Error de validación en los campos enviados.');
                            return;
                        }
                        // Aquí refrescas tu lista o recargas los datos locales
                        location.reload();
                    });
                }
            }
        }
    </script>


        </body>
    </html>
