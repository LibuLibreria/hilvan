<template>
  <div class="relaciones-list">
    <div class="page-header">
      <h1>Gestión de Relaciones</h1>
    </div>

    <el-card class="filter-card">
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="Tipo de relación">
          <el-select v-model="searchForm.tipoRelacionId" placeholder="Todos" clearable>
            <el-option
              v-for="tipo in tiposRelacion"
              :key="tipo.id"
              :label="tipo.nombre"
              :value="tipo.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="Buscar persona">
          <el-input v-model="searchForm.query" placeholder="Nombre, apellido..." clearable @clear="loadRelaciones" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="searchRelaciones">Buscar</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card class="tipos-relacion-card">
      <template #header>
        <div class="card-header">
          <h3>Tipos de Relación</h3>
          <el-button type="primary" size="small" @click="showAddTipoRelacionDialog">
            <el-icon><Plus /></el-icon> Nuevo Tipo
          </el-button>
        </div>
      </template>
      <el-table
        v-loading="loadingTipos"
        :data="tiposRelacion"
        style="width: 100%"
        border
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="nombre" label="Nombre" />
        <el-table-column prop="nombreInverso" label="Nombre Inverso" />
        <el-table-column label="Acciones" width="150">
          <template #default="scope">
            <el-button-group>
              <el-button type="warning" size="small" @click="editTipoRelacion(scope.row)">
                <el-icon><Edit /></el-icon>
              </el-button>
              <el-button type="danger" size="small" @click="confirmDeleteTipoRelacion(scope.row)">
                <el-icon><Delete /></el-icon>
              </el-button>
            </el-button-group>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-card class="relaciones-card">
      <template #header>
        <div class="card-header">
          <h3>Listado de Relaciones</h3>
          <el-button type="primary" size="small" @click="showAddRelacionDialog">
            <el-icon><Plus /></el-icon> Nueva Relación
          </el-button>
        </div>
      </template>
      <el-table
        v-loading="loading"
        :data="relaciones"
        style="width: 100%"
        border
      >
        <el-table-column prop="tipoRelacion.nombre" label="Tipo de Relación" />
        <el-table-column label="Persona Origen">
          <template #default="scope">
            <el-button type="text" @click="navigateToPersona(scope.row.personaOrigen.id)">
              {{ scope.row.personaOrigen.nombre }} {{ scope.row.personaOrigen.apellido }}
            </el-button>
          </template>
        </el-table-column>
        <el-table-column label="Persona Destino">
          <template #default="scope">
            <el-button type="text" @click="navigateToPersona(scope.row.personaDestino.id)">
              {{ scope.row.personaDestino.nombre }} {{ scope.row.personaDestino.apellido }}
            </el-button>
          </template>
        </el-table-column>
        <el-table-column label="Acciones" width="120">
          <template #default="scope">
            <el-button type="danger" size="small" @click="confirmDeleteRelacion(scope.row)">
              <el-icon><Delete /></el-icon>
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-container">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          :total="total"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- Diálogo para añadir/editar tipo de relación -->
    <el-dialog
      v-model="tipoRelacionDialogVisible"
      :title="editingTipoRelacion ? 'Editar Tipo de Relación' : 'Nuevo Tipo de Relación'"
      width="500px"
    >
      <el-form :model="tipoRelacionForm" :rules="tipoRelacionRules" ref="tipoRelacionFormRef" label-position="top">
        <el-form-item label="Nombre" prop="nombre">
          <el-input v-model="tipoRelacionForm.nombre" placeholder="Ej: Padre" />
        </el-form-item>
        <el-form-item label="Nombre Inverso" prop="nombreInverso">
          <el-input v-model="tipoRelacionForm.nombreInverso" placeholder="Ej: Hijo" />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="tipoRelacionDialogVisible = false">Cancelar</el-button>
          <el-button type="primary" @click="saveTipoRelacion" :loading="savingTipoRelacion">
            {{ editingTipoRelacion ? 'Actualizar' : 'Crear' }}
          </el-button>
        </span>
      </template>
    </el-dialog>

    <!-- Diálogo para añadir relación -->
    <el-dialog
      v-model="relacionDialogVisible"
      title="Nueva Relación"
      width="500px"
    >
      <el-form :model="relacionForm" :rules="relacionRules" ref="relacionFormRef" label-position="top">
        <el-form-item label="Tipo de Relación" prop="tipoRelacionId">
          <el-select v-model="relacionForm.tipoRelacionId" placeholder="Selecciona tipo" style="width: 100%">
            <el-option
              v-for="tipo in tiposRelacion"
              :key="tipo.id"
              :label="tipo.nombre"
              :value="tipo.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="Persona Origen" prop="personaOrigenId">
          <el-select
            v-model="relacionForm.personaOrigenId"
            filterable
            remote
            reserve-keyword
            placeholder="Buscar persona origen"
            :remote-method="searchPersonasOrigen"
            :loading="loadingPersonasOrigen"
            style="width: 100%"
          >
            <el-option
              v-for="persona in availablePersonasOrigen"
              :key="persona.id"
              :label="`${persona.nombre} ${persona.apellido}`"
              :value="persona.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="Persona Destino" prop="personaDestinoId">
          <el-select
            v-model="relacionForm.personaDestinoId"
            filterable
            remote
            reserve-keyword
            placeholder="Buscar persona destino"
            :remote-method="searchPersonasDestino"
            :loading="loadingPersonasDestino"
            style="width: 100%"
          >
            <el-option
              v-for="persona in availablePersonasDestino"
              :key="persona.id"
              :label="`${persona.nombre} ${persona.apellido}`"
              :value="persona.id"
            />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="relacionDialogVisible = false">Cancelar</el-button>
          <el-button type="primary" @click="saveRelacion" :loading="savingRelacion">Crear</el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage, ElMessageBox } from 'element-plus';
import axios from 'axios';

export default {
  name: 'RelacionesList',
  setup() {
    const router = useRouter();
    const loading = ref(false);
    const loadingTipos = ref(false);
    const currentPage = ref(1);
    const pageSize = ref(10);
    const total = ref(0);
    
    const relaciones = ref([]);
    const tiposRelacion = ref([]);
    
    // Búsqueda
    const searchForm = reactive({
      tipoRelacionId: null,
      query: ''
    });
    
    // Tipo de relación
    const tipoRelacionDialogVisible = ref(false);
    const editingTipoRelacion = ref(false);
    const savingTipoRelacion = ref(false);
    const tipoRelacionFormRef = ref(null);
    
    const tipoRelacionForm = reactive({
      id: null,
      nombre: '',
      nombreInverso: ''
    });
    
    const tipoRelacionRules = {
      nombre: [
        { required: true, message: 'Por favor ingresa el nombre', trigger: 'blur' },
        { min: 2, message: 'El nombre debe tener al menos 2 caracteres', trigger: 'blur' }
      ],
      nombreInverso: [
        { required: true, message: 'Por favor ingresa el nombre inverso', trigger: 'blur' },
        { min: 2, message: 'El nombre inverso debe tener al menos 2 caracteres', trigger: 'blur' }
      ]
    };
    
    // Relación
    const relacionDialogVisible = ref(false);
    const savingRelacion = ref(false);
    const relacionFormRef = ref(null);
    
    const relacionForm = reactive({
      tipoRelacionId: null,
      personaOrigenId: null,
      personaDestinoId: null
    });
    
    const relacionRules = {
      tipoRelacionId: [
        { required: true, message: 'Por favor selecciona el tipo de relación', trigger: 'change' }
      ],
      personaOrigenId: [
        { required: true, message: 'Por favor selecciona la persona origen', trigger: 'change' }
      ],
      personaDestinoId: [
        { required: true, message: 'Por favor selecciona la persona destino', trigger: 'change' }
      ]
    };
    
    // Búsqueda de personas
    const availablePersonasOrigen = ref([]);
    const loadingPersonasOrigen = ref(false);
    const availablePersonasDestino = ref([]);
    const loadingPersonasDestino = ref(false);

    const loadRelaciones = async () => {
      loading.value = true;
      try {
        const params = {
          page: currentPage.value,
          limit: pageSize.value
        };
        
        if (searchForm.tipoRelacionId) {
          params.tipoRelacionId = searchForm.tipoRelacionId;
        }
        
        if (searchForm.query) {
          params.query = searchForm.query;
        }
        
        const response = await axios.get('/relaciones', { params });
        relaciones.value = response.data.items || response.data;
        total.value = response.data.total || relaciones.value.length;
      } catch (error) {
        ElMessage.error('Error al cargar las relaciones');
        console.error(error);
      } finally {
        loading.value = false;
      }
    };

    const loadTiposRelacion = async () => {
      loadingTipos.value = true;
      try {
        const response = await axios.get('/tipos-relacion');
        tiposRelacion.value = response.data;
      } catch (error) {
        ElMessage.error('Error al cargar los tipos de relación');
        console.error(error);
      } finally {
        loadingTipos.value = false;
      }
    };

    const searchRelaciones = () => {
      currentPage.value = 1;
      loadRelaciones();
    };

    const handleSizeChange = (val) => {
      pageSize.value = val;
      loadRelaciones();
    };

    const handleCurrentChange = (val) => {
      currentPage.value = val;
      loadRelaciones();
    };

    const navigateToPersona = (id) => {
      router.push(`/personas/${id}`);
    };

    // Gestión de tipos de relación
    const showAddTipoRelacionDialog = () => {
      editingTipoRelacion.value = false;
      tipoRelacionForm.id = null;
      tipoRelacionForm.nombre = '';
      tipoRelacionForm.nombreInverso = '';
      tipoRelacionDialogVisible.value = true;
    };

    const editTipoRelacion = (tipoRelacion) => {
      editingTipoRelacion.value = true;
      tipoRelacionForm.id = tipoRelacion.id;
      tipoRelacionForm.nombre = tipoRelacion.nombre;
      tipoRelacionForm.nombreInverso = tipoRelacion.nombreInverso;
      tipoRelacionDialogVisible.value = true;
    };

    const saveTipoRelacion = () => {
      if (!tipoRelacionFormRef.value) return;
      
      tipoRelacionFormRef.value.validate(async (valid) => {
        if (valid) {
          savingTipoRelacion.value = true;
          
          try {
            if (editingTipoRelacion.value) {
              await axios.put(`/tipos-relacion/${tipoRelacionForm.id}`, tipoRelacionForm);
              ElMessage.success('Tipo de relación actualizado correctamente');
            } else {
              await axios.post('/tipos-relacion', tipoRelacionForm);
              ElMessage.success('Tipo de relación creado correctamente');
            }
            
            tipoRelacionDialogVisible.value = false;
            loadTiposRelacion();
          } catch (error) {
            ElMessage.error('Error al guardar el tipo de relación');
            console.error(error);
          } finally {
            savingTipoRelacion.value = false;
          }
        }
      });
    };

    const confirmDeleteTipoRelacion = (tipoRelacion) => {
      ElMessageBox.confirm(
        `¿Estás seguro de eliminar el tipo de relación "${tipoRelacion.nombre}"?`,
        'Confirmar eliminación',
        {
          confirmButtonText: 'Eliminar',
          cancelButtonText: 'Cancelar',
          type: 'warning'
        }
      )
        .then(() => {
          deleteTipoRelacion(tipoRelacion.id);
        })
        .catch(() => {
          // Cancelado
        });
    };

    const deleteTipoRelacion = async (id) => {
      try {
        await axios.delete(`/tipos-relacion/${id}`);
        ElMessage.success('Tipo de relación eliminado correctamente');
        loadTiposRelacion();
      } catch (error) {
        ElMessage.error('Error al eliminar el tipo de relación');
        console.error(error);
      }
    };

    // Gestión de relaciones
    const showAddRelacionDialog = () => {
      relacionForm.tipoRelacionId = null;
      relacionForm.personaOrigenId = null;
      relacionForm.personaDestinoId = null;
      relacionDialogVisible.value = true;
    };

    const searchPersonasOrigen = async (query) => {
      if (query.length < 2) return;
      
      loadingPersonasOrigen.value = true;
      try {
        const response = await axios.get('/personas', {
          params: { query }
        });
        availablePersonasOrigen.value = response.data;
      } catch (error) {
        console.error(error);
      } finally {
        loadingPersonasOrigen.value = false;
      }
    };

    const searchPersonasDestino = async (query) => {
      if (query.length < 2) return;
      
      loadingPersonasDestino.value = true;
      try {
        const response = await axios.get('/personas', {
          params: { query }
        });
        availablePersonasDestino.value = response.data;
      } catch (error) {
        console.error(error);
      } finally {
        loadingPersonasDestino.value = false;
      }
    };

    const saveRelacion = () => {
      if (!relacionFormRef.value) return;
      
      relacionFormRef.value.validate(async (valid) => {
        if (valid) {
          savingRelacion.value = true;
          
          try {
            await axios.post('/relaciones', relacionForm);
            ElMessage.success('Relación creada correctamente');
            relacionDialogVisible.value = false;
            loadRelaciones();
          } catch (error) {
            ElMessage.error('Error al crear la relación');
            console.error(error);
          } finally {
            savingRelacion.value = false;
          }
        }
      });
    };

    const confirmDeleteRelacion = (relacion) => {
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
          deleteRelacion(relacion.id);
        })
        .catch(() => {
          // Cancelado
        });
    };

    const deleteRelacion = async (id) => {
      try {
        await axios.delete(`/relaciones/${id}`);
        ElMessage.success('Relación eliminada correctamente');
        loadRelaciones();
      } catch (error) {
        ElMessage.error('Error al eliminar la relación');
        console.error(error);
      }
    };

    onMounted(() => {
      loadTiposRelacion();
      loadRelaciones();
    });

    return {
      relaciones,
      tiposRelacion,
      loading,
      loadingTipos,
      currentPage,
      pageSize,
      total,
      searchForm,
      tipoRelacionDialogVisible,
      editingTipoRelacion,
      savingTipoRelacion,
      tipoRelacionFormRef,
      tipoRelacionForm,
      tipoRelacionRules,
      relacionDialogVisible,
      savingRelacion,
      relacionFormRef,
      relacionForm,
      relacionRules,
      availablePersonasOrigen,
      loadingPersonasOrigen,
      availablePersonasDestino,
      loadingPersonasDestino,
      loadRelaciones,
      searchRelaciones,
      handleSizeChange,
      handleCurrentChange,
      navigateToPersona,
      showAddTipoRelacionDialog,
      editTipoRelacion,
      saveTipoRelacion,
      confirmDeleteTipoRelacion,
      showAddRelacionDialog,
      searchPersonasOrigen,
      searchPersonasDestino,
      saveRelacion,
      confirmDeleteRelacion
    };
  }
};
</script>

<style scoped>
.relaciones-list {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.filter-card {
  margin-bottom: 20px;
}

.search-form {
  display: flex;
  justify-content: flex-start;
  flex-wrap: wrap;
}

.tipos-relacion-card {
  margin-bottom: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}
</style>
