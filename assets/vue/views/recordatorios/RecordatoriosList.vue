<template>
  <div class="recordatorios-list">
    <div class="page-header">
      <h1>Gestión de Recordatorios</h1>
    </div>

    <el-row :gutter="20">
      <el-col :span="16">
        <el-card class="proximos-recordatorios-card">
          <template #header>
            <div class="card-header">
              <h3>Próximos Recordatorios</h3>
              <el-button type="primary" size="small" @click="showAddRecordatorioDialog">
                <el-icon><Plus /></el-icon> Nuevo Recordatorio
              </el-button>
            </div>
          </template>
          
          <el-table
            v-loading="loading"
            :data="proximosRecordatorios"
            style="width: 100%"
            border
          >
            <el-table-column label="Fecha">
              <template #default="scope">
                {{ formatDate(scope.row.fecha) }}
              </template>
            </el-table-column>
            <el-table-column prop="descripcion" label="Descripción" />
            <el-table-column label="Persona">
              <template #default="scope">
                <el-button type="text" @click="navigateToPersona(scope.row.persona.id)">
                  {{ scope.row.persona.nombre }} {{ scope.row.persona.apellido }}
                </el-button>
              </template>
            </el-table-column>
            <el-table-column label="Recurrente" width="120">
              <template #default="scope">
                <el-tag :type="scope.row.esRecurrente ? 'success' : 'info'">
                  {{ scope.row.esRecurrente ? 'Sí' : 'No' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="Días Restantes" width="120">
              <template #default="scope">
                <el-tag :type="getDiasRestantesType(scope.row.diasRestantes)">
                  {{ scope.row.diasRestantes }} días
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="Acciones" width="120">
              <template #default="scope">
                <el-button type="danger" size="small" @click="confirmDeleteRecordatorio(scope.row)">
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
      </el-col>
      
      <el-col :span="8">
        <el-card class="filtros-card">
          <template #header>
            <div class="card-header">
              <h3>Filtros</h3>
            </div>
          </template>
          
          <el-form :model="filtros" label-position="top">
            <el-form-item label="Rango de días">
              <el-select v-model="filtros.rangoDias" placeholder="Selecciona rango" style="width: 100%" @change="aplicarFiltros">
                <el-option label="Próximos 7 días" :value="7" />
                <el-option label="Próximos 30 días" :value="30" />
                <el-option label="Próximos 90 días" :value="90" />
                <el-option label="Próximo año" :value="365" />
                <el-option label="Todos" :value="-1" />
              </el-select>
            </el-form-item>
            
            <el-form-item label="Tipo de recordatorio">
              <el-select v-model="filtros.tipoRecordatorio" placeholder="Todos" style="width: 100%" @change="aplicarFiltros" clearable>
                <el-option label="Recurrentes" :value="true" />
                <el-option label="No recurrentes" :value="false" />
              </el-select>
            </el-form-item>
            
            <el-form-item label="Persona">
              <el-select
                v-model="filtros.personaId"
                filterable
                remote
                reserve-keyword
                placeholder="Buscar persona"
                :remote-method="searchPersonas"
                :loading="loadingPersonas"
                style="width: 100%"
                clearable
                @change="aplicarFiltros"
              >
                <el-option
                  v-for="persona in availablePersonas"
                  :key="persona.id"
                  :label="`${persona.nombre} ${persona.apellido}`"
                  :value="persona.id"
                />
              </el-select>
            </el-form-item>
            
            <el-form-item>
              <el-button type="primary" @click="aplicarFiltros" style="width: 100%">Aplicar Filtros</el-button>
            </el-form-item>
            
            <el-form-item>
              <el-button @click="resetFiltros" style="width: 100%">Limpiar Filtros</el-button>
            </el-form-item>
          </el-form>
        </el-card>
        
        <el-card class="estadisticas-card">
          <template #header>
            <div class="card-header">
              <h3>Estadísticas</h3>
            </div>
          </template>
          
          <div class="estadisticas-content">
            <div class="estadistica-item">
              <h4>Recordatorios próximos 7 días</h4>
              <div class="estadistica-valor">{{ estadisticas.proximos7Dias }}</div>
            </div>
            
            <div class="estadistica-item">
              <h4>Recordatorios próximos 30 días</h4>
              <div class="estadistica-valor">{{ estadisticas.proximos30Dias }}</div>
            </div>
            
            <div class="estadistica-item">
              <h4>Total recordatorios recurrentes</h4>
              <div class="estadistica-valor">{{ estadisticas.totalRecurrentes }}</div>
            </div>
            
            <div class="estadistica-item">
              <h4>Total recordatorios</h4>
              <div class="estadistica-valor">{{ estadisticas.total }}</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- Diálogo para añadir recordatorio -->
    <el-dialog
      v-model="recordatorioDialogVisible"
      title="Nuevo Recordatorio"
      width="500px"
    >
      <el-form :model="recordatorioForm" :rules="recordatorioRules" ref="recordatorioFormRef" label-position="top">
        <el-form-item label="Persona" prop="personaId">
          <el-select
            v-model="recordatorioForm.personaId"
            filterable
            remote
            reserve-keyword
            placeholder="Buscar persona"
            :remote-method="searchPersonasForm"
            :loading="loadingPersonasForm"
            style="width: 100%"
          >
            <el-option
              v-for="persona in availablePersonasForm"
              :key="persona.id"
              :label="`${persona.nombre} ${persona.apellido}`"
              :value="persona.id"
            />
          </el-select>
        </el-form-item>
        
        <el-form-item label="Fecha" prop="fecha">
          <el-date-picker
            v-model="recordatorioForm.fecha"
            type="date"
            placeholder="Selecciona fecha"
            format="DD/MM/YYYY"
            value-format="YYYY-MM-DD"
            style="width: 100%"
          />
        </el-form-item>
        
        <el-form-item label="Descripción" prop="descripcion">
          <el-input
            v-model="recordatorioForm.descripcion"
            placeholder="Descripción del recordatorio"
          />
        </el-form-item>
        
        <el-form-item label="Recurrente">
          <el-switch v-model="recordatorioForm.esRecurrente" />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="recordatorioDialogVisible = false">Cancelar</el-button>
          <el-button type="primary" @click="saveRecordatorio" :loading="savingRecordatorio">Crear</el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage, ElMessageBox } from 'element-plus';
import axios from 'axios';

export default {
  name: 'RecordatoriosList',
  setup() {
    const router = useRouter();
    const loading = ref(false);
    const currentPage = ref(1);
    const pageSize = ref(10);
    const total = ref(0);
    
    const proximosRecordatorios = ref([]);
    
    // Filtros
    const filtros = reactive({
      rangoDias: 30,
      tipoRecordatorio: null,
      personaId: null
    });
    
    // Estadísticas
    const estadisticas = reactive({
      proximos7Dias: 0,
      proximos30Dias: 0,
      totalRecurrentes: 0,
      total: 0
    });
    
    // Búsqueda de personas para filtros
    const availablePersonas = ref([]);
    const loadingPersonas = ref(false);
    
    // Recordatorio
    const recordatorioDialogVisible = ref(false);
    const savingRecordatorio = ref(false);
    const recordatorioFormRef = ref(null);
    
    // Búsqueda de personas para formulario
    const availablePersonasForm = ref([]);
    const loadingPersonasForm = ref(false);
    
    const recordatorioForm = reactive({
      personaId: null,
      fecha: '',
      descripcion: '',
      esRecurrente: false
    });
    
    const recordatorioRules = {
      personaId: [
        { required: true, message: 'Por favor selecciona la persona', trigger: 'change' }
      ],
      fecha: [
        { required: true, message: 'Por favor selecciona la fecha', trigger: 'change' }
      ],
      descripcion: [
        { required: true, message: 'Por favor ingresa la descripción', trigger: 'blur' },
        { min: 2, message: 'La descripción debe tener al menos 2 caracteres', trigger: 'blur' }
      ]
    };

    const loadRecordatorios = async () => {
      loading.value = true;
      try {
        const params = {
          page: currentPage.value,
          limit: pageSize.value
        };
        
        if (filtros.rangoDias > 0) {
          params.dias = filtros.rangoDias;
        }
        
        if (filtros.tipoRecordatorio !== null) {
          params.esRecurrente = filtros.tipoRecordatorio;
        }
        
        if (filtros.personaId) {
          params.personaId = filtros.personaId;
        }
        
        const response = await axios.get('/fechas-recordatorio', { params });
        proximosRecordatorios.value = response.data.items || response.data;
        total.value = response.data.total || proximosRecordatorios.value.length;
      } catch (error) {
        ElMessage.error('Error al cargar los recordatorios');
        console.error(error);
      } finally {
        loading.value = false;
      }
    };

    const loadEstadisticas = async () => {
      try {
        const response = await axios.get('/fechas-recordatorio/estadisticas');
        estadisticas.proximos7Dias = response.data.proximos7Dias;
        estadisticas.proximos30Dias = response.data.proximos30Dias;
        estadisticas.totalRecurrentes = response.data.totalRecurrentes;
        estadisticas.total = response.data.total;
      } catch (error) {
        console.error(error);
      }
    };

    const aplicarFiltros = () => {
      currentPage.value = 1;
      loadRecordatorios();
    };

    const resetFiltros = () => {
      filtros.rangoDias = 30;
      filtros.tipoRecordatorio = null;
      filtros.personaId = null;
      currentPage.value = 1;
      loadRecordatorios();
    };

    const handleSizeChange = (val) => {
      pageSize.value = val;
      loadRecordatorios();
    };

    const handleCurrentChange = (val) => {
      currentPage.value = val;
      loadRecordatorios();
    };

    const navigateToPersona = (id) => {
      router.push(`/personas/${id}`);
    };

    const searchPersonas = async (query) => {
      if (query.length < 2) return;
      
      loadingPersonas.value = true;
      try {
        const response = await axios.get('/personas', {
          params: { query }
        });
        availablePersonas.value = response.data;
      } catch (error) {
        console.error(error);
      } finally {
        loadingPersonas.value = false;
      }
    };

    const searchPersonasForm = async (query) => {
      if (query.length < 2) return;
      
      loadingPersonasForm.value = true;
      try {
        const response = await axios.get('/personas', {
          params: { query }
        });
        availablePersonasForm.value = response.data;
      } catch (error) {
        console.error(error);
      } finally {
        loadingPersonasForm.value = false;
      }
    };

    const showAddRecordatorioDialog = () => {
      recordatorioForm.personaId = null;
      recordatorioForm.fecha = '';
      recordatorioForm.descripcion = '';
      recordatorioForm.esRecurrente = false;
      recordatorioDialogVisible.value = true;
    };

    const saveRecordatorio = () => {
      if (!recordatorioFormRef.value) return;
      
      recordatorioFormRef.value.validate(async (valid) => {
        if (valid) {
          savingRecordatorio.value = true;
          
          try {
            await axios.post('/fechas-recordatorio', recordatorioForm);
            ElMessage.success('Recordatorio creado correctamente');
            recordatorioDialogVisible.value = false;
            loadRecordatorios();
            loadEstadisticas();
          } catch (error) {
            ElMessage.error('Error al crear el recordatorio');
            console.error(error);
          } finally {
            savingRecordatorio.value = false;
          }
        }
      });
    };

    const confirmDeleteRecordatorio = (recordatorio) => {
      ElMessageBox.confirm(
        `¿Estás seguro de eliminar este recordatorio?`,
        'Confirmar eliminación',
        {
          confirmButtonText: 'Eliminar',
          cancelButtonText: 'Cancelar',
          type: 'warning'
        }
      )
        .then(() => {
          deleteRecordatorio(recordatorio.id);
        })
        .catch(() => {
          // Cancelado
        });
    };

    const deleteRecordatorio = async (id) => {
      try {
        await axios.delete(`/fechas-recordatorio/${id}`);
        ElMessage.success('Recordatorio eliminado correctamente');
        loadRecordatorios();
        loadEstadisticas();
      } catch (error) {
        ElMessage.error('Error al eliminar el recordatorio');
        console.error(error);
      }
    };

    const formatDate = (dateString) => {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString();
    };

    const getDiasRestantesType = (dias) => {
      if (dias <= 7) return 'danger';
      if (dias <= 30) return 'warning';
      return 'info';
    };

    onMounted(() => {
      loadRecordatorios();
      loadEstadisticas();
    });

    return {
      proximosRecordatorios,
      loading,
      currentPage,
      pageSize,
      total,
      filtros,
      estadisticas,
      availablePersonas,
      loadingPersonas,
      recordatorioDialogVisible,
      savingRecordatorio,
      recordatorioFormRef,
      recordatorioForm,
      recordatorioRules,
      availablePersonasForm,
      loadingPersonasForm,
      loadRecordatorios,
      aplicarFiltros,
      resetFiltros,
      handleSizeChange,
      handleCurrentChange,
      navigateToPersona,
      searchPersonas,
      searchPersonasForm,
      showAddRecordatorioDialog,
      saveRecordatorio,
      confirmDeleteRecordatorio,
      formatDate,
      getDiasRestantesType
    };
  }
};
</script>

<style scoped>
.recordatorios-list {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.proximos-recordatorios-card,
.filtros-card,
.estadisticas-card {
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

.estadisticas-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
}

.estadistica-item {
  background-color: #f5f7fa;
  border-radius: 4px;
  padding: 15px;
  text-align: center;
}

.estadistica-item h4 {
  margin: 0 0 10px 0;
  font-size: 14px;
  color: #606266;
}

.estadistica-valor {
  font-size: 24px;
  font-weight: bold;
  color: #409eff;
}
</style>
