<template>
  <div class="persona-detail">
    <div class="page-header">
      <h1>Detalles de Persona</h1>
      <div class="header-actions">
        <el-button type="primary" @click="editPersona">
          <el-icon><Edit /></el-icon> Editar
        </el-button>
        <el-button @click="goBack">
          <el-icon><Back /></el-icon> Volver
        </el-button>
      </div>
    </div>

    <el-row :gutter="20" v-loading="loading">
      <el-col :span="8">
        <el-card class="persona-card">
          <div class="persona-avatar">
            <el-avatar v-if="persona.foto" :src="getPhotoUrl(persona.foto)" :size="150"></el-avatar>
            <el-avatar v-else :size="150" icon="el-icon-user"></el-avatar>
          </div>
          <div class="persona-info">
            <h2>{{ persona.nombre }} {{ persona.apellido }}</h2>
            <p v-if="persona.fechaNacimiento">
              <strong>Fecha de nacimiento:</strong> {{ formatDate(persona.fechaNacimiento) }}
              <span v-if="calcularEdad(persona.fechaNacimiento)"> ({{ calcularEdad(persona.fechaNacimiento) }} años)</span>
            </p>
            <div v-if="persona.observaciones" class="observaciones">
              <strong>Observaciones:</strong>
              <p>{{ persona.observaciones }}</p>
            </div>
          </div>
        </el-card>

        <el-card class="tags-card">
          <template #header>
            <div class="card-header">
              <h3>Tags / Membresías</h3>
            </div>
          </template>
          <div v-if="tags.length > 0" class="tags-container">
            <el-tag
              v-for="tag in tags"
              :key="tag.id"
              style="margin: 5px"
            >
              {{ tag.nombre }}
            </el-tag>
          </div>
          <el-empty v-else description="No hay tags asignados" />
        </el-card>
      </el-col>

      <el-col :span="16">
        <el-card class="relaciones-card">
          <template #header>
            <div class="card-header">
              <h3>Relaciones</h3>
              <el-button type="primary" size="small" @click="showAddRelationDialog">
                <el-icon><Plus /></el-icon> Añadir
              </el-button>
            </div>
          </template>
          <el-table
            v-if="relaciones.length > 0"
            :data="relaciones"
            style="width: 100%"
            border
          >
            <el-table-column prop="tipoRelacion.nombre" label="Tipo de Relación" />
            <el-table-column label="Persona">
              <template #default="scope">
                <el-button type="text" @click="navigateToPersona(scope.row.personaDestino.id)">
                  {{ scope.row.personaDestino.nombre }} {{ scope.row.personaDestino.apellido }}
                </el-button>
              </template>
            </el-table-column>
            <el-table-column label="Acciones" width="120">
              <template #default="scope">
                <el-button type="danger" size="small" @click="confirmDeleteRelation(scope.row)">
                  <el-icon><Delete /></el-icon>
                </el-button>
              </template>
            </el-table-column>
          </el-table>
          <el-empty v-else description="No hay relaciones" />
        </el-card>

        <el-card class="fechas-card">
          <template #header>
            <div class="card-header">
              <h3>Fechas Recordatorio</h3>
              <el-button type="primary" size="small" @click="showAddDateDialog">
                <el-icon><Plus /></el-icon> Añadir
              </el-button>
            </div>
          </template>
          <el-table
            v-if="fechasRecordatorio.length > 0"
            :data="fechasRecordatorio"
            style="width: 100%"
            border
          >
            <el-table-column label="Fecha">
              <template #default="scope">
                {{ formatDate(scope.row.fecha) }}
              </template>
            </el-table-column>
            <el-table-column prop="descripcion" label="Descripción" />
            <el-table-column label="Recurrente" width="120">
              <template #default="scope">
                <el-tag :type="scope.row.esRecurrente ? 'success' : 'info'">
                  {{ scope.row.esRecurrente ? 'Sí' : 'No' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="Acciones" width="120">
              <template #default="scope">
                <el-button type="danger" size="small" @click="confirmDeleteDate(scope.row)">
                  <el-icon><Delete /></el-icon>
                </el-button>
              </template>
            </el-table-column>
          </el-table>
          <el-empty v-else description="No hay fechas recordatorio" />
        </el-card>

        <el-card class="observaciones-card">
          <template #header>
            <div class="card-header">
              <h3>Diario de Observaciones</h3>
              <el-button type="primary" size="small" @click="showAddObservationDialog">
                <el-icon><Plus /></el-icon> Añadir
              </el-button>
            </div>
          </template>
          <el-timeline v-if="observaciones.length > 0">
            <el-timeline-item
              v-for="observacion in observaciones"
              :key="observacion.id"
              :timestamp="formatDateTime(observacion.fecha)"
              placement="top"
            >
              <el-card>
                <p>{{ observacion.texto }}</p>
                <div class="observation-actions">
                  <el-button type="danger" size="small" @click="confirmDeleteObservation(observacion)">
                    <el-icon><Delete /></el-icon>
                  </el-button>
                </div>
              </el-card>
            </el-timeline-item>
          </el-timeline>
          <el-empty v-else description="No hay observaciones registradas" />
        </el-card>
      </el-col>
    </el-row>

    <!-- Diálogo para añadir relación -->
    <el-dialog
      v-model="relationDialogVisible"
      title="Añadir Relación"
      width="500px"
    >
      <el-form :model="newRelation" label-position="top">
        <el-form-item label="Tipo de Relación">
          <el-select v-model="newRelation.tipoRelacionId" placeholder="Selecciona tipo" style="width: 100%">
            <el-option
              v-for="tipo in tiposRelacion"
              :key="tipo.id"
              :label="tipo.nombre"
              :value="tipo.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="Persona">
          <el-select
            v-model="newRelation.personaDestinoId"
            filterable
            remote
            reserve-keyword
            placeholder="Buscar persona"
            :remote-method="searchPersonas"
            :loading="loadingPersonas"
            style="width: 100%"
          >
            <el-option
              v-for="persona in availablePersonas"
              :key="persona.id"
              :label="`${persona.nombre} ${persona.apellido}`"
              :value="persona.id"
            />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="relationDialogVisible = false">Cancelar</el-button>
          <el-button type="primary" @click="addRelation">Añadir</el-button>
        </span>
      </template>
    </el-dialog>

    <!-- Diálogo para añadir fecha recordatorio -->
    <el-dialog
      v-model="dateDialogVisible"
      title="Añadir Fecha Recordatorio"
      width="500px"
    >
      <el-form :model="newDate" label-position="top">
        <el-form-item label="Fecha">
          <el-date-picker
            v-model="newDate.fecha"
            type="date"
            placeholder="Selecciona fecha"
            format="DD/MM/YYYY"
            value-format="YYYY-MM-DD"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="Descripción">
          <el-input
            v-model="newDate.descripcion"
            placeholder="Descripción del recordatorio"
          />
        </el-form-item>
        <el-form-item label="Recurrente">
          <el-switch v-model="newDate.esRecurrente" />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="dateDialogVisible = false">Cancelar</el-button>
          <el-button type="primary" @click="addDate">Añadir</el-button>
        </span>
      </template>
    </el-dialog>

    <!-- Diálogo para añadir observación -->
    <el-dialog
      v-model="observationDialogVisible"
      title="Añadir Observación"
      width="500px"
    >
      <el-form :model="newObservation" label-position="top">
        <el-form-item label="Fecha">
          <el-date-picker
            v-model="newObservation.fecha"
            type="datetime"
            placeholder="Selecciona fecha y hora"
            format="DD/MM/YYYY HH:mm"
            value-format="YYYY-MM-DD HH:mm:ss"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="Texto">
          <el-input
            v-model="newObservation.texto"
            type="textarea"
            :rows="4"
            placeholder="Escribe la observación"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="observationDialogVisible = false">Cancelar</el-button>
          <el-button type="primary" @click="addObservation">Añadir</el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { ElMessage, ElMessageBox } from 'element-plus';
import axios from 'axios';

export default {
  name: 'PersonaDetail',
  props: {
    id: {
      type: [String, Number],
      required: true
    }
  },
  setup(props) {
    const router = useRouter();
    const route = useRoute();
    const loading = ref(true);
    
    const persona = ref({});
    const relaciones = ref([]);
    const tags = ref([]);
    const fechasRecordatorio = ref([]);
    const observaciones = ref([]);
    
    // Relaciones
    const relationDialogVisible = ref(false);
    const tiposRelacion = ref([]);
    const availablePersonas = ref([]);
    const loadingPersonas = ref(false);
    
    // Fechas recordatorio
    const dateDialogVisible = ref(false);
    
    // Observaciones
    const observationDialogVisible = ref(false);

    const newRelation = reactive({
      tipoRelacionId: null,
      personaDestinoId: null
    });

    const newDate = reactive({
      fecha: '',
      descripcion: '',
      esRecurrente: false
    });

    const newObservation = reactive({
      fecha: new Date().toISOString().slice(0, 19).replace('T', ' '),
      texto: ''
    });

    const loadPersona = async () => {
      loading.value = true;
      try {
        const response = await axios.get(`/personas/${props.id}`);
        persona.value = response.data;
        
        // Cargar relaciones, tags, fechas recordatorio y observaciones
        await Promise.all([
          loadRelaciones(),
          loadTags(),
          loadFechasRecordatorio(),
          loadObservaciones()
        ]);
      } catch (error) {
        ElMessage.error('Error al cargar los datos de la persona');
        console.error(error);
      } finally {
        loading.value = false;
      }
    };

    const loadRelaciones = async () => {
      try {
        const response = await axios.get(`/relaciones/persona/${props.id}`);
        relaciones.value = response.data;
      } catch (error) {
        ElMessage.error('Error al cargar las relaciones');
        console.error(error);
      }
    };

    const loadTags = async () => {
      try {
        const response = await axios.get(`/personas/${props.id}/tags`);
        tags.value = response.data;
      } catch (error) {
        ElMessage.error('Error al cargar los tags');
        console.error(error);
      }
    };

    const loadFechasRecordatorio = async () => {
      try {
        const response = await axios.get(`/fechas-recordatorio/persona/${props.id}`);
        fechasRecordatorio.value = response.data;
      } catch (error) {
        ElMessage.error('Error al cargar las fechas recordatorio');
        console.error(error);
      }
    };

    const loadObservaciones = async () => {
      try {
        const response = await axios.get(`/observaciones/persona/${props.id}`);
        observaciones.value = response.data;
        
        // Ordenar por fecha descendente
        observaciones.value.sort((a, b) => new Date(b.fecha) - new Date(a.fecha));
      } catch (error) {
        ElMessage.error('Error al cargar las observaciones');
        console.error(error);
      }
    };

    const loadTiposRelacion = async () => {
      try {
        const response = await axios.get('/tipos-relacion');
        tiposRelacion.value = response.data;
      } catch (error) {
        ElMessage.error('Error al cargar los tipos de relación');
        console.error(error);
      }
    };

    const searchPersonas = async (query) => {
      if (query.length < 2) return;
      
      loadingPersonas.value = true;
      try {
        const response = await axios.get('/personas', {
          params: { query }
        });
        availablePersonas.value = response.data.filter(p => p.id !== props.id);
      } catch (error) {
        console.error(error);
      } finally {
        loadingPersonas.value = false;
      }
    };

    const showAddRelationDialog = () => {
      newRelation.tipoRelacionId = null;
      newRelation.personaDestinoId = null;
      relationDialogVisible.value = true;
    };

    const addRelation = async () => {
      if (!newRelation.tipoRelacionId || !newRelation.personaDestinoId) {
        ElMessage.warning('Por favor completa todos los campos');
        return;
      }
      
      try {
        await axios.post(`/relaciones`, {
          personaOrigenId: props.id,
          personaDestinoId: newRelation.personaDestinoId,
          tipoRelacionId: newRelation.tipoRelacionId
        });
        
        // Recargar relaciones
        await loadRelaciones();
        
        relationDialogVisible.value = false;
        ElMessage.success('Relación añadida correctamente');
      } catch (error) {
        ElMessage.error('Error al añadir la relación');
        console.error(error);
      }
    };

    const confirmDeleteRelation = (relacion) => {
      ElMessageBox.confirm(
        `¿Estás seguro de eliminar esta relación?`,
        'Confirmar eliminación',
        {
          confirmButtonText: 'Eliminar',
          cancelButtonText: 'Cancelar',
          type: 'warning'
        }
      )
        .then(() => {
          deleteRelation(relacion.id);
        })
        .catch(() => {
          // Cancelado
        });
    };

    const deleteRelation = async (id) => {
      try {
        await axios.delete(`/relaciones/${id}`);
        await loadRelaciones();
        ElMessage.success('Relación eliminada correctamente');
      } catch (error) {
        ElMessage.error('Error al eliminar la relación');
        console.error(error);
      }
    };

    const showAddDateDialog = () => {
      newDate.fecha = '';
      newDate.descripcion = '';
      newDate.esRecurrente = false;
      dateDialogVisible.value = true;
    };

    const addDate = async () => {
      if (!newDate.fecha || !newDate.descripcion) {
        ElMessage.warning('Por favor completa todos los campos');
        return;
      }
      
      try {
        await axios.post(`/fechas-recordatorio`, {
          fecha: newDate.fecha,
          descripcion: newDate.descripcion,
          esRecurrente: newDate.esRecurrente,
          personaId: props.id
        });
        
        // Recargar fechas
        await loadFechasRecordatorio();
        
        dateDialogVisible.value = false;
        ElMessage.success('Fecha recordatorio añadida correctamente');
      } catch (error) {
        ElMessage.error('Error al añadir la fecha recordatorio');
        console.error(error);
      }
    };

    const confirmDeleteDate = (fecha) => {
      ElMessageBox.confirm(
        `¿Estás seguro de eliminar esta fecha recordatorio?`,
        'Confirmar eliminación',
        {
          confirmButtonText: 'Eliminar',
          cancelButtonText: 'Cancelar',
          type: 'warning'
        }
      )
        .then(() => {
          deleteDate(fecha.id);
        })
        .catch(() => {
          // Cancelado
        });
    };

    const deleteDate = async (id) => {
      try {
        await axios.delete(`/fechas-recordatorio/${id}`);
        await loadFechasRecordatorio();
        ElMessage.success('Fecha recordatorio eliminada correctamente');
      } catch (error) {
        ElMessage.error('Error al eliminar la fecha recordatorio');
        console.error(error);
      }
    };

    const showAddObservationDialog = () => {
      newObservation.fecha = new Date().toISOString().slice(0, 19).replace('T', ' ');
      newObservation.texto = '';
      observationDialogVisible.value = true;
    };

    const addObservation = async () => {
      if (!newObservation.fecha || !newObservation.texto) {
        ElMessage.warning('Por favor completa todos los campos');
        return;
      }
      
      try {
        await axios.post(`/observaciones`, {
          fecha: newObservation.fecha,
          texto: newObservation.texto,
          personaId: props.id
        });
        
        // Recargar observaciones
        await loadObservaciones();
        
        observationDialogVisible.value = false;
        ElMessage.success('Observación añadida correctamente');
      } catch (error) {
        ElMessage.error('Error al añadir la observación');
        console.error(error);
      }
    };

    const confirmDeleteObservation = (observacion) => {
      ElMessageBox.confirm(
        `¿Estás seguro de eliminar esta observación?`,
        'Confirmar eliminación',
        {
          confirmButtonText: 'Eliminar',
          cancelButtonText: 'Cancelar',
          type: 'warning'
        }
      )
        .then(() => {
          deleteObservation(observacion.id);
        })
        .catch(() => {
          // Cancelado
        });
    };

    const deleteObservation = async (id) => {
      try {
        await axios.delete(`/observaciones/${id}`);
        await loadObservaciones();
        ElMessage.success('Observación eliminada correctamente');
      } catch (error) {
        ElMessage.error('Error al eliminar la observación');
        console.error(error);
      }
    };

    const editPersona = () => {
      router.push(`/personas/${props.id}/editar`);
    };

    const goBack = () => {
      router.push('/personas');
    };

    const navigateToPersona = (id) => {
      router.push(`/personas/${id}`);
    };

    const formatDate = (dateString) => {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString();
    };

    const formatDateTime = (dateTimeString) => {
      if (!dateTimeString) return '';
      const date = new Date(dateTimeString);
      return date.toLocaleString();
    };

    const calcularEdad = (fechaNacimiento) => {
      if (!fechaNacimiento) return null;
      
      const hoy = new Date();
      const fechaNac = new Date(fechaNacimiento);
      let edad = hoy.getFullYear() - fechaNac.getFullYear();
      const m = hoy.getMonth() - fechaNac.getMonth();
      
      if (m < 0 || (m === 0 && hoy.getDate() < fechaNac.getDate())) {
        edad--;
      }
      
      return edad;
    };

    const getPhotoUrl = (photoPath) => {
      return `/uploads/fotos/${photoPath}`;
    };

    onMounted(() => {
      loadTiposRelacion();
      loadPersona();
    });

    return {
      persona,
      relaciones,
      tags,
      fechasRecordatorio,
      observaciones,
      loading,
      relationDialogVisible,
      tiposRelacion,
      newRelation,
      availablePersonas,
      loadingPersonas,
      dateDialogVisible,
      newDate,
      observationDialogVisible,
      newObservation,
      editPersona,
      goBack,
      navigateToPersona,
      showAddRelationDialog,
      searchPersonas,
      addRelation,
      confirmDeleteRelation,
      showAddDateDialog,
      addDate,
      confirmDeleteDate,
      showAddObservationDialog,
      addObservation,
      confirmDeleteObservation,
      formatDate,
      formatDateTime,
      calcularEdad,
      getPhotoUrl
    };
  }
};
</script>

<style scoped>
.persona-detail {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.persona-card, .tags-card, .relaciones-card, .fechas-card, .observaciones-card {
  margin-bottom: 20px;
}

.persona-avatar {
  display: flex;
  justify-content: center;
  margin-bottom: 20px;
}

.persona-info {
  text-align: center;
}

.observaciones {
  margin-top: 15px;
  text-align: left;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.tags-container {
  display: flex;
  flex-wrap: wrap;
}

.observation-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 10px;
}
</style>
