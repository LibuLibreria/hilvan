<template>
  <div class="busqueda">
    <div class="page-header">
      <h1>Búsqueda Avanzada</h1>
    </div>

    <el-card class="search-card">
      <el-form :model="searchForm" label-position="top">
        <el-row :gutter="20">
          <el-col :span="8">
            <el-form-item label="Nombre o Apellido">
              <el-input 
                v-model="searchForm.query" 
                placeholder="Buscar por nombre o apellido" 
                clearable 
                @keyup.enter="buscar"
              />
            </el-form-item>
          </el-col>
          
          <el-col :span="8">
            <el-form-item label="Fecha de Nacimiento">
              <el-date-picker
                v-model="searchForm.fechaNacimiento"
                type="date"
                placeholder="Selecciona fecha"
                format="DD/MM/YYYY"
                value-format="YYYY-MM-DD"
                style="width: 100%"
                clearable
              />
            </el-form-item>
          </el-col>
          
          <el-col :span="8">
            <el-form-item label="Tag / Membresía">
              <el-select
                v-model="searchForm.tagId"
                filterable
                placeholder="Selecciona tag"
                style="width: 100%"
                clearable
              >
                <el-option
                  v-for="tag in tags"
                  :key="tag.id"
                  :label="tag.nombre"
                  :value="tag.id"
                />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="20">
          <el-col :span="8">
            <el-form-item label="Tipo de Relación">
              <el-select
                v-model="searchForm.tipoRelacionId"
                filterable
                placeholder="Selecciona tipo de relación"
                style="width: 100%"
                clearable
              >
                <el-option
                  v-for="tipo in tiposRelacion"
                  :key="tipo.id"
                  :label="tipo.nombre"
                  :value="tipo.id"
                />
              </el-select>
            </el-form-item>
          </el-col>
          
          <el-col :span="8">
            <el-form-item label="Persona Relacionada">
              <el-select
                v-model="searchForm.personaRelacionadaId"
                filterable
                remote
                reserve-keyword
                placeholder="Buscar persona relacionada"
                :remote-method="searchPersonasRelacionadas"
                :loading="loadingPersonasRelacionadas"
                style="width: 100%"
                clearable
              >
                <el-option
                  v-for="persona in availablePersonasRelacionadas"
                  :key="persona.id"
                  :label="`${persona.nombre} ${persona.apellido}`"
                  :value="persona.id"
                />
              </el-select>
            </el-form-item>
          </el-col>
          
          <el-col :span="8">
            <el-form-item label="Próximo Recordatorio">
              <el-select
                v-model="searchForm.proximoRecordatorio"
                placeholder="Selecciona rango"
                style="width: 100%"
                clearable
              >
                <el-option label="Próximos 7 días" :value="7" />
                <el-option label="Próximos 30 días" :value="30" />
                <el-option label="Próximos 90 días" :value="90" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row>
          <el-col :span="24" style="text-align: center; margin-top: 20px;">
            <el-button type="primary" @click="buscar" :loading="loading" size="large">
              <el-icon><Search /></el-icon> Buscar
            </el-button>
            <el-button @click="resetForm" size="large">
              <el-icon><RefreshRight /></el-icon> Limpiar
            </el-button>
          </el-col>
        </el-row>
      </el-form>
    </el-card>

    <el-card class="results-card" v-if="resultadosBusqueda.length > 0 || hasSearched">
      <template #header>
        <div class="card-header">
          <h3>Resultados de la Búsqueda</h3>
          <span v-if="resultadosBusqueda.length > 0">{{ resultadosBusqueda.length }} resultados encontrados</span>
        </div>
      </template>
      
      <el-empty v-if="resultadosBusqueda.length === 0 && hasSearched" description="No se encontraron resultados" />
      
      <el-table
        v-else
        v-loading="loading"
        :data="resultadosBusqueda"
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
            {{ scope.row.nombre }} {{ scope.row.apellido }}
          </template>
        </el-table-column>
        <el-table-column label="Fecha Nacimiento" width="150">
          <template #default="scope">
            {{ formatDate(scope.row.fechaNacimiento) }}
          </template>
        </el-table-column>
        <el-table-column label="Tags" width="200">
          <template #default="scope">
            <div class="tag-list">
              <el-tag 
                v-for="tag in scope.row.tags" 
                :key="tag.id"
                size="small"
                style="margin: 2px"
              >
                {{ tag.nombre }}
              </el-tag>
              <span v-if="!scope.row.tags || scope.row.tags.length === 0">-</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="Próximo Recordatorio" width="200">
          <template #default="scope">
            <div v-if="scope.row.proximoRecordatorio">
              <div>{{ formatDate(scope.row.proximoRecordatorio.fecha) }}</div>
              <small>{{ scope.row.proximoRecordatorio.descripcion }}</small>
            </div>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column label="Acciones" width="120">
          <template #default="scope">
            <el-button type="primary" size="small" @click="navigateToPersona(scope.row.id)">
              <el-icon><View /></el-icon>
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage } from 'element-plus';
import axios from 'axios';

export default {
  name: 'Busqueda',
  setup() {
    const router = useRouter();
    const loading = ref(false);
    const hasSearched = ref(false);
    
    const resultadosBusqueda = ref([]);
    const tags = ref([]);
    const tiposRelacion = ref([]);
    
    // Búsqueda de personas relacionadas
    const availablePersonasRelacionadas = ref([]);
    const loadingPersonasRelacionadas = ref(false);
    
    const searchForm = reactive({
      query: '',
      fechaNacimiento: '',
      tagId: null,
      tipoRelacionId: null,
      personaRelacionadaId: null,
      proximoRecordatorio: null
    });

    const loadTags = async () => {
      try {
        const response = await axios.get('/tags');
        tags.value = response.data;
      } catch (error) {
        console.error(error);
      }
    };

    const loadTiposRelacion = async () => {
      try {
        const response = await axios.get('/tipos-relacion');
        tiposRelacion.value = response.data;
      } catch (error) {
        console.error(error);
      }
    };

    const searchPersonasRelacionadas = async (query) => {
      if (query.length < 2) return;
      
      loadingPersonasRelacionadas.value = true;
      try {
        const response = await axios.get('/personas', {
          params: { query }
        });
        availablePersonasRelacionadas.value = response.data;
      } catch (error) {
        console.error(error);
      } finally {
        loadingPersonasRelacionadas.value = false;
      }
    };

    const buscar = async () => {
      loading.value = true;
      hasSearched.value = true;
      
      try {
        const params = {};
        
        if (searchForm.query) {
          params.query = searchForm.query;
        }
        
        if (searchForm.fechaNacimiento) {
          params.fechaNacimiento = searchForm.fechaNacimiento;
        }
        
        if (searchForm.tagId) {
          params.tagId = searchForm.tagId;
        }
        
        if (searchForm.tipoRelacionId) {
          params.tipoRelacionId = searchForm.tipoRelacionId;
        }
        
        if (searchForm.personaRelacionadaId) {
          params.personaRelacionadaId = searchForm.personaRelacionadaId;
        }
        
        if (searchForm.proximoRecordatorio) {
          params.proximoRecordatorio = searchForm.proximoRecordatorio;
        }
        
        const response = await axios.get('/personas/busqueda', { params });
        resultadosBusqueda.value = response.data;
      } catch (error) {
        ElMessage.error('Error al realizar la búsqueda');
        console.error(error);
      } finally {
        loading.value = false;
      }
    };

    const resetForm = () => {
      searchForm.query = '';
      searchForm.fechaNacimiento = '';
      searchForm.tagId = null;
      searchForm.tipoRelacionId = null;
      searchForm.personaRelacionadaId = null;
      searchForm.proximoRecordatorio = null;
      
      resultadosBusqueda.value = [];
      hasSearched.value = false;
    };

    const navigateToPersona = (id) => {
      router.push(`/personas/${id}`);
    };

    const formatDate = (dateString) => {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString();
    };

    const getPhotoUrl = (photoPath) => {
      return `/uploads/fotos/${photoPath}`;
    };

    onMounted(() => {
      loadTags();
      loadTiposRelacion();
    });

    return {
      loading,
      hasSearched,
      resultadosBusqueda,
      tags,
      tiposRelacion,
      availablePersonasRelacionadas,
      loadingPersonasRelacionadas,
      searchForm,
      searchPersonasRelacionadas,
      buscar,
      resetForm,
      navigateToPersona,
      formatDate,
      getPhotoUrl
    };
  }
};
</script>

<style scoped>
.busqueda {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.search-card {
  margin-bottom: 20px;
}

.results-card {
  margin-bottom: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.tag-list {
  display: flex;
  flex-wrap: wrap;
}
</style>
