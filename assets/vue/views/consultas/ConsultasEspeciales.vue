<template>
  <div class="consultas-especiales">
    <div class="page-header">
      <h1>Consultas Especiales</h1>
    </div>

    <el-row :gutter="20">
      <el-col :span="16">
        <el-card class="proximos-cumpleanos-card">
          <template #header>
            <div class="card-header">
              <h3>Próximos Cumpleaños</h3>
            </div>
          </template>
          
          <el-table
            v-loading="loadingCumpleanos"
            :data="proximosCumpleanos"
            style="width: 100%"
            border
          >
            <el-table-column label="Foto" width="80">
              <template #default="scope">
                <el-avatar v-if="scope.row.persona.foto" :src="getPhotoUrl(scope.row.persona.foto)" :size="40"></el-avatar>
                <el-avatar v-else :size="40" icon="el-icon-user"></el-avatar>
              </template>
            </el-table-column>
            <el-table-column label="Nombre">
              <template #default="scope">
                <el-button type="text" @click="navigateToPersona(scope.row.persona.id)">
                  {{ scope.row.persona.nombre }} {{ scope.row.persona.apellido }}
                </el-button>
              </template>
            </el-table-column>
            <el-table-column label="Fecha Cumpleaños" width="150">
              <template #default="scope">
                {{ formatDate(scope.row.fecha) }}
              </template>
            </el-table-column>
            <el-table-column label="Días Restantes" width="120">
              <template #default="scope">
                <el-tag :type="getDiasRestantesType(scope.row.diasRestantes)">
                  {{ scope.row.diasRestantes }} días
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="Edad a Cumplir" width="120">
              <template #default="scope">
                {{ scope.row.edadACumplir }} años
              </template>
            </el-table-column>
          </el-table>
          
          <div class="filter-container">
            <el-select v-model="filtrosCumpleanos.dias" placeholder="Rango de días" @change="cargarProximosCumpleanos">
              <el-option label="Próximos 7 días" :value="7" />
              <el-option label="Próximos 30 días" :value="30" />
              <el-option label="Próximos 90 días" :value="90" />
              <el-option label="Próximo año" :value="365" />
            </el-select>
          </div>
        </el-card>
        
        <el-card class="consultas-preguntas-card">
          <template #header>
            <div class="card-header">
              <h3>Preguntas Frecuentes</h3>
            </div>
          </template>
          
          <div class="preguntas-container">
            <div class="pregunta-item">
              <h4>¿Cuándo es el cumpleaños de una persona?</h4>
              <el-form :inline="true" class="pregunta-form">
                <el-form-item>
                  <el-select
                    v-model="preguntaCumpleanos.personaId"
                    filterable
                    remote
                    reserve-keyword
                    placeholder="Buscar persona"
                    :remote-method="searchPersonasCumpleanos"
                    :loading="loadingPersonasCumpleanos"
                    style="width: 250px"
                  >
                    <el-option
                      v-for="persona in availablePersonasCumpleanos"
                      :key="persona.id"
                      :label="`${persona.nombre} ${persona.apellido}`"
                      :value="persona.id"
                    />
                  </el-select>
                </el-form-item>
                <el-form-item>
                  <el-button type="primary" @click="consultarCumpleanos" :disabled="!preguntaCumpleanos.personaId">
                    Consultar
                  </el-button>
                </el-form-item>
              </el-form>
              
              <div v-if="resultadoCumpleanos" class="resultado-pregunta">
                <el-alert
                  :title="resultadoCumpleanos.mensaje"
                  :type="resultadoCumpleanos.tipo"
                  :description="resultadoCumpleanos.descripcion"
                  show-icon
                />
              </div>
            </div>
            
            <el-divider />
            
            <div class="pregunta-item">
              <h4>¿Quién es el/la [relación] de una persona?</h4>
              <el-form :inline="true" class="pregunta-form">
                <el-form-item>
                  <el-select
                    v-model="preguntaRelacion.tipoRelacionId"
                    placeholder="Tipo de relación"
                    style="width: 150px"
                  >
                    <el-option
                      v-for="tipo in tiposRelacion"
                      :key="tipo.id"
                      :label="tipo.nombre"
                      :value="tipo.id"
                    />
                  </el-select>
                </el-form-item>
                <el-form-item>
                  <span>de</span>
                </el-form-item>
                <el-form-item>
                  <el-select
                    v-model="preguntaRelacion.personaId"
                    filterable
                    remote
                    reserve-keyword
                    placeholder="Buscar persona"
                    :remote-method="searchPersonasRelacion"
                    :loading="loadingPersonasRelacion"
                    style="width: 250px"
                  >
                    <el-option
                      v-for="persona in availablePersonasRelacion"
                      :key="persona.id"
                      :label="`${persona.nombre} ${persona.apellido}`"
                      :value="persona.id"
                    />
                  </el-select>
                </el-form-item>
                <el-form-item>
                  <el-button 
                    type="primary" 
                    @click="consultarRelacion" 
                    :disabled="!preguntaRelacion.personaId || !preguntaRelacion.tipoRelacionId"
                  >
                    Consultar
                  </el-button>
                </el-form-item>
              </el-form>
              
              <div v-if="resultadoRelacion" class="resultado-pregunta">
                <el-alert
                  :title="resultadoRelacion.mensaje"
                  :type="resultadoRelacion.tipo"
                  show-icon
                />
                
                <div v-if="resultadoRelacion.personas && resultadoRelacion.personas.length > 0" class="personas-relacionadas">
                  <el-table
                    :data="resultadoRelacion.personas"
                    style="width: 100%"
                    border
                  >
                    <el-table-column label="Foto" width="80">
                      <template #default="scope">
                        <el-avatar v-if="scope.row.foto" :src="getPhotoUrl(scope.row.foto)" :size="40"></el-avatar>
                        <el-avatar v-else :size="40" icon="el-icon-user"></el-avatar>
                      </template>
                    </el-table-column>
                    <el-table-column label="Nombre">
                      <template #default="scope">
                        <el-button type="text" @click="navigateToPersona(scope.row.id)">
                          {{ scope.row.nombre }} {{ scope.row.apellido }}
                        </el-button>
                      </template>
                    </el-table-column>
                    <el-table-column label="Fecha Nacimiento" width="150">
                      <template #default="scope">
                        {{ formatDate(scope.row.fechaNacimiento) }}
                      </template>
                    </el-table-column>
                  </el-table>
                </div>
              </div>
            </div>
            
            <el-divider />
            
            <div class="pregunta-item">
              <h4>¿Cuáles son las membresías de una persona?</h4>
              <el-form :inline="true" class="pregunta-form">
                <el-form-item>
                  <el-select
                    v-model="preguntaMembresías.personaId"
                    filterable
                    remote
                    reserve-keyword
                    placeholder="Buscar persona"
                    :remote-method="searchPersonasMembresías"
                    :loading="loadingPersonasMembresías"
                    style="width: 250px"
                  >
                    <el-option
                      v-for="persona in availablePersonasMembresías"
                      :key="persona.id"
                      :label="`${persona.nombre} ${persona.apellido}`"
                      :value="persona.id"
                    />
                  </el-select>
                </el-form-item>
                <el-form-item>
                  <el-button type="primary" @click="consultarMembresías" :disabled="!preguntaMembresías.personaId">
                    Consultar
                  </el-button>
                </el-form-item>
              </el-form>
              
              <div v-if="resultadoMembresías" class="resultado-pregunta">
                <el-alert
                  :title="resultadoMembresías.mensaje"
                  :type="resultadoMembresías.tipo"
                  show-icon
                />
                
                <div v-if="resultadoMembresías.tags && resultadoMembresías.tags.length > 0" class="tags-list">
                  <el-table
                    :data="resultadoMembresías.tags"
                    style="width: 100%"
                    border
                  >
                    <el-table-column prop="nombre" label="Nombre" />
                    <el-table-column prop="descripcion" label="Descripción" show-overflow-tooltip />
                  </el-table>
                </div>
              </div>
            </div>
          </div>
        </el-card>
      </el-col>
      
      <el-col :span="8">
        <el-card class="estadisticas-card">
          <template #header>
            <div class="card-header">
              <h3>Estadísticas</h3>
            </div>
          </template>
          
          <div class="estadisticas-content">
            <div class="estadistica-item">
              <h4>Total de Personas</h4>
              <div class="estadistica-valor">{{ estadisticas.totalPersonas }}</div>
            </div>
            
            <div class="estadistica-item">
              <h4>Total de Relaciones</h4>
              <div class="estadistica-valor">{{ estadisticas.totalRelaciones }}</div>
            </div>
            
            <div class="estadistica-item">
              <h4>Cumpleaños este mes</h4>
              <div class="estadistica-valor">{{ estadisticas.cumpleañosMes }}</div>
            </div>
            
            <div class="estadistica-item">
              <h4>Recordatorios próximos 7 días</h4>
              <div class="estadistica-valor">{{ estadisticas.recordatoriosProximos }}</div>
            </div>
          </div>
        </el-card>
        
        <el-card class="relaciones-populares-card">
          <template #header>
            <div class="card-header">
              <h3>Tipos de Relación más Comunes</h3>
            </div>
          </template>
          
          <el-table
            :data="relacionesPopulares"
            style="width: 100%"
            border
          >
            <el-table-column prop="nombre" label="Tipo de Relación" />
            <el-table-column prop="cantidad" label="Cantidad" width="100" />
          </el-table>
        </el-card>
        
        <el-card class="tags-populares-card">
          <template #header>
            <div class="card-header">
              <h3>Membresías más Comunes</h3>
            </div>
          </template>
          
          <el-table
            :data="tagsPopulares"
            style="width: 100%"
            border
          >
            <el-table-column prop="nombre" label="Membresía" />
            <el-table-column prop="cantidad" label="Cantidad" width="100" />
          </el-table>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage } from 'element-plus';
import axios from 'axios';

export default {
  name: 'ConsultasEspeciales',
  setup() {
    const router = useRouter();
    
    // Próximos cumpleaños
    const loadingCumpleanos = ref(false);
    const proximosCumpleanos = ref([]);
    const filtrosCumpleanos = reactive({
      dias: 30
    });
    
    // Estadísticas
    const estadisticas = reactive({
      totalPersonas: 0,
      totalRelaciones: 0,
      cumpleañosMes: 0,
      recordatoriosProximos: 0
    });
    
    // Relaciones y tags populares
    const relacionesPopulares = ref([]);
    const tagsPopulares = ref([]);
    
    // Pregunta cumpleaños
    const preguntaCumpleanos = reactive({
      personaId: null
    });
    const resultadoCumpleanos = ref(null);
    const availablePersonasCumpleanos = ref([]);
    const loadingPersonasCumpleanos = ref(false);
    
    // Pregunta relación
    const tiposRelacion = ref([]);
    const preguntaRelacion = reactive({
      tipoRelacionId: null,
      personaId: null
    });
    const resultadoRelacion = ref(null);
    const availablePersonasRelacion = ref([]);
    const loadingPersonasRelacion = ref(false);
    
    // Pregunta membresías
    const preguntaMembresías = reactive({
      personaId: null
    });
    const resultadoMembresías = ref(null);
    const availablePersonasMembresías = ref([]);
    const loadingPersonasMembresías = ref(false);

    const cargarProximosCumpleanos = async () => {
      loadingCumpleanos.value = true;
      try {
        const response = await axios.get('/fechas-recordatorio/cumpleanos', {
          params: { dias: filtrosCumpleanos.dias }
        });
        proximosCumpleanos.value = response.data;
      } catch (error) {
        ElMessage.error('Error al cargar los próximos cumpleaños');
        console.error(error);
      } finally {
        loadingCumpleanos.value = false;
      }
    };

    const cargarEstadisticas = async () => {
      try {
        const response = await axios.get('/estadisticas');
        estadisticas.totalPersonas = response.data.totalPersonas;
        estadisticas.totalRelaciones = response.data.totalRelaciones;
        estadisticas.cumpleañosMes = response.data.cumpleañosMes;
        estadisticas.recordatoriosProximos = response.data.recordatoriosProximos;
      } catch (error) {
        console.error(error);
      }
    };

    const cargarRelacionesPopulares = async () => {
      try {
        const response = await axios.get('/relaciones/populares');
        relacionesPopulares.value = response.data;
      } catch (error) {
        console.error(error);
      }
    };

    const cargarTagsPopulares = async () => {
      try {
        const response = await axios.get('/tags/populares');
        tagsPopulares.value = response.data;
      } catch (error) {
        console.error(error);
      }
    };

    const cargarTiposRelacion = async () => {
      try {
        const response = await axios.get('/tipos-relacion');
        tiposRelacion.value = response.data;
      } catch (error) {
        console.error(error);
      }
    };

    // Búsqueda de personas para preguntas
    const searchPersonasCumpleanos = async (query) => {
      if (query.length < 2) return;
      
      loadingPersonasCumpleanos.value = true;
      try {
        const response = await axios.get('/personas', {
          params: { query }
        });
        availablePersonasCumpleanos.value = response.data;
      } catch (error) {
        console.error(error);
      } finally {
        loadingPersonasCumpleanos.value = false;
      }
    };

    const searchPersonasRelacion = async (query) => {
      if (query.length < 2) return;
      
      loadingPersonasRelacion.value = true;
      try {
        const response = await axios.get('/personas', {
          params: { query }
        });
        availablePersonasRelacion.value = response.data;
      } catch (error) {
        console.error(error);
      } finally {
        loadingPersonasRelacion.value = false;
      }
    };

    const searchPersonasMembresías = async (query) => {
      if (query.length < 2) return;
      
      loadingPersonasMembresías.value = true;
      try {
        const response = await axios.get('/personas', {
          params: { query }
        });
        availablePersonasMembresías.value = response.data;
      } catch (error) {
        console.error(error);
      } finally {
        loadingPersonasMembresías.value = false;
      }
    };

    // Consultas específicas
    const consultarCumpleanos = async () => {
      if (!preguntaCumpleanos.personaId) return;
      
      try {
        const response = await axios.get(`/personas/${preguntaCumpleanos.personaId}/cumpleanos`);
        
        if (response.data.fecha) {
          resultadoCumpleanos.value = {
            tipo: 'success',
            mensaje: `El cumpleaños es el ${formatDate(response.data.fecha)}`,
            descripcion: response.data.diasRestantes > 0 
              ? `Faltan ${response.data.diasRestantes} días para su cumpleaños número ${response.data.edadACumplir}`
              : response.data.diasRestantes === 0
                ? `¡Hoy es su cumpleaños número ${response.data.edadACumplir}!`
                : `Su próximo cumpleaños será el número ${response.data.edadACumplir}`
          };
        } else {
          resultadoCumpleanos.value = {
            tipo: 'warning',
            mensaje: 'No se ha registrado fecha de cumpleaños para esta persona'
          };
        }
      } catch (error) {
        resultadoCumpleanos.value = {
          tipo: 'error',
          mensaje: 'Error al consultar el cumpleaños'
        };
        console.error(error);
      }
    };

    const consultarRelacion = async () => {
      if (!preguntaRelacion.personaId || !preguntaRelacion.tipoRelacionId) return;
      
      try {
        const response = await axios.get(`/personas/${preguntaRelacion.personaId}/relaciones/${preguntaRelacion.tipoRelacionId}`);
        
        const tipoRelacion = tiposRelacion.value.find(t => t.id === preguntaRelacion.tipoRelacionId);
        const nombreTipoRelacion = tipoRelacion ? tipoRelacion.nombre : 'relación';
        
        if (response.data && response.data.length > 0) {
          resultadoRelacion.value = {
            tipo: 'success',
            mensaje: response.data.length === 1
              ? `Se encontró 1 ${nombreTipoRelacion}`
              : `Se encontraron ${response.data.length} ${nombreTipoRelacion}es`,
            personas: response.data
          };
        } else {
          resultadoRelacion.value = {
            tipo: 'info',
            mensaje: `No se encontraron ${nombreTipoRelacion}es para esta persona`,
            personas: []
          };
        }
      } catch (error) {
        resultadoRelacion.value = {
          tipo: 'error',
          mensaje: 'Error al consultar las relaciones'
        };
        console.error(error);
      }
    };

    const consultarMembresías = async () => {
      if (!preguntaMembresías.personaId) return;
      
      try {
        const response = await axios.get(`/personas/${preguntaMembresías.personaId}/tags`);
        
        if (response.data && response.data.length > 0) {
          resultadoMembresías.value = {
            tipo: 'success',
            mensaje: response.data.length === 1
              ? 'Se encontró 1 membresía'
              : `Se encontraron ${response.data.length} membresías`,
            tags: response.data
          };
        } else {
          resultadoMembresías.value = {
            tipo: 'info',
            mensaje: 'No se encontraron membresías para esta persona',
            tags: []
          };
        }
      } catch (error) {
        resultadoMembresías.value = {
          tipo: 'error',
          mensaje: 'Error al consultar las membresías'
        };
        console.error(error);
      }
    };

    const navigateToPersona = (id) => {
      router.push(`/personas/${id}`);
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

    const getPhotoUrl = (photoPath) => {
      return `/uploads/fotos/${photoPath}`;
    };

    onMounted(() => {
      cargarProximosCumpleanos();
      cargarEstadisticas();
      cargarRelacionesPopulares();
      cargarTagsPopulares();
      cargarTiposRelacion();
    });

    return {
      loadingCumpleanos,
      proximosCumpleanos,
      filtrosCumpleanos,
      estadisticas,
      relacionesPopulares,
      tagsPopulares,
      tiposRelacion,
      preguntaCumpleanos,
      resultadoCumpleanos,
      availablePersonasCumpleanos,
      loadingPersonasCumpleanos,
      preguntaRelacion,
      resultadoRelacion,
      availablePersonasRelacion,
      loadingPersonasRelacion,
      preguntaMembresías,
      resultadoMembresías,
      availablePersonasMembresías,
      loadingPersonasMembresías,
      cargarProximosCumpleanos,
      searchPersonasCumpleanos,
      searchPersonasRelacion,
      searchPersonasMembresías,
      consultarCumpleanos,
      consultarRelacion,
      consultarMembresías,
      navigateToPersona,
      formatDate,
      getDiasRestantesType,
      getPhotoUrl
    };
  }
};
</script>

<style scoped>
.consultas-especiales {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.proximos-cumpleanos-card,
.consultas-preguntas-card,
.estadisticas-card,
.relaciones-populares-card,
.tags-populares-card {
  margin-bottom: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.filter-container {
  margin-top: 15px;
  display: flex;
  justify-content: flex-end;
}

.preguntas-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.pregunta-item {
  margin-bottom: 10px;
}

.pregunta-item h4 {
  margin-bottom: 15px;
  color: #409eff;
}

.pregunta-form {
  margin-bottom: 15px;
}

.resultado-pregunta {
  margin-top: 15px;
}

.personas-relacionadas,
.tags-list {
  margin-top: 15px;
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
