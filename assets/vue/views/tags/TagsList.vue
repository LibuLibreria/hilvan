<template>
  <div class="tags-list">
    <div class="page-header">
      <h1>Gestión de Tags / Membresías</h1>
    </div>

    <el-row :gutter="20">
      <el-col :span="16">
        <el-card class="tags-card">
          <template #header>
            <div class="card-header">
              <h3>Listado de Tags</h3>
              <el-button type="primary" size="small" @click="showAddTagDialog">
                <el-icon><Plus /></el-icon> Nuevo Tag
              </el-button>
            </div>
          </template>
          
          <el-table
            v-loading="loading"
            :data="tags"
            style="width: 100%"
            border
          >
            <el-table-column prop="id" label="ID" width="80" />
            <el-table-column prop="nombre" label="Nombre" />
            <el-table-column prop="descripcion" label="Descripción" show-overflow-tooltip />
            <el-table-column label="Personas Asociadas" width="150">
              <template #default="scope">
                <el-tag>{{ scope.row.totalPersonas || 0 }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column label="Acciones" width="150">
              <template #default="scope">
                <el-button-group>
                  <el-button type="primary" size="small" @click="showPersonasTag(scope.row)">
                    <el-icon><View /></el-icon>
                  </el-button>
                  <el-button type="warning" size="small" @click="editTag(scope.row)">
                    <el-icon><Edit /></el-icon>
                  </el-button>
                  <el-button type="danger" size="small" @click="confirmDeleteTag(scope.row)">
                    <el-icon><Delete /></el-icon>
                  </el-button>
                </el-button-group>
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
            <el-form-item label="Buscar por nombre">
              <el-input 
                v-model="filtros.query" 
                placeholder="Nombre del tag" 
                clearable 
                @keyup.enter="aplicarFiltros"
                @clear="aplicarFiltros"
              />
            </el-form-item>
            
            <el-form-item>
              <el-button type="primary" @click="aplicarFiltros" style="width: 100%">Buscar</el-button>
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
              <h4>Total de Tags</h4>
              <div class="estadistica-valor">{{ estadisticas.totalTags }}</div>
            </div>
            
            <div class="estadistica-item">
              <h4>Tags más utilizados</h4>
              <div class="tag-list">
                <el-tag 
                  v-for="tag in estadisticas.tagsMasUtilizados" 
                  :key="tag.id"
                  style="margin: 5px"
                >
                  {{ tag.nombre }} ({{ tag.totalPersonas }})
                </el-tag>
              </div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- Diálogo para añadir/editar tag -->
    <el-dialog
      v-model="tagDialogVisible"
      :title="editingTag ? 'Editar Tag' : 'Nuevo Tag'"
      width="500px"
    >
      <el-form :model="tagForm" :rules="tagRules" ref="tagFormRef" label-position="top">
        <el-form-item label="Nombre" prop="nombre">
          <el-input v-model="tagForm.nombre" placeholder="Nombre del tag" />
        </el-form-item>
        
        <el-form-item label="Descripción" prop="descripcion">
          <el-input
            v-model="tagForm.descripcion"
            type="textarea"
            :rows="3"
            placeholder="Descripción del tag"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="tagDialogVisible = false">Cancelar</el-button>
          <el-button type="primary" @click="saveTag" :loading="savingTag">
            {{ editingTag ? 'Actualizar' : 'Crear' }}
          </el-button>
        </span>
      </template>
    </el-dialog>

    <!-- Diálogo para mostrar personas asociadas a un tag -->
    <el-dialog
      v-model="personasTagDialogVisible"
      :title="`Personas con tag: ${selectedTag.nombre}`"
      width="700px"
    >
      <el-table
        v-loading="loadingPersonasTag"
        :data="personasTag"
        style="width: 100%"
        border
      >
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="Nombre">
          <template #default="scope">
            {{ scope.row.nombre }} {{ scope.row.apellido }}
          </template>
        </el-table-column>
        <el-table-column label="Fecha Nacimiento">
          <template #default="scope">
            {{ formatDate(scope.row.fechaNacimiento) }}
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
    </el-dialog>

    <!-- Diálogo para asignar tag a personas -->
    <el-dialog
      v-model="asignarTagDialogVisible"
      title="Asignar Tag a Personas"
      width="700px"
    >
      <el-form :model="asignarTagForm" label-position="top">
        <el-form-item label="Tag">
          <el-select v-model="asignarTagForm.tagId" placeholder="Selecciona un tag" style="width: 100%">
            <el-option
              v-for="tag in tags"
              :key="tag.id"
              :label="tag.nombre"
              :value="tag.id"
            />
          </el-select>
        </el-form-item>
        
        <el-form-item label="Personas">
          <el-select
            v-model="asignarTagForm.personaIds"
            multiple
            filterable
            remote
            reserve-keyword
            placeholder="Buscar personas"
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
          <el-button @click="asignarTagDialogVisible = false">Cancelar</el-button>
          <el-button type="primary" @click="asignarTag" :loading="asignandoTag">Asignar</el-button>
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
  name: 'TagsList',
  setup() {
    const router = useRouter();
    const loading = ref(false);
    const currentPage = ref(1);
    const pageSize = ref(10);
    const total = ref(0);
    
    const tags = ref([]);
    
    // Filtros
    const filtros = reactive({
      query: ''
    });
    
    // Estadísticas
    const estadisticas = reactive({
      totalTags: 0,
      tagsMasUtilizados: []
    });
    
    // Tag
    const tagDialogVisible = ref(false);
    const editingTag = ref(false);
    const savingTag = ref(false);
    const tagFormRef = ref(null);
    
    const tagForm = reactive({
      id: null,
      nombre: '',
      descripcion: ''
    });
    
    const tagRules = {
      nombre: [
        { required: true, message: 'Por favor ingresa el nombre', trigger: 'blur' },
        { min: 2, message: 'El nombre debe tener al menos 2 caracteres', trigger: 'blur' }
      ]
    };
    
    // Personas asociadas a un tag
    const personasTagDialogVisible = ref(false);
    const loadingPersonasTag = ref(false);
    const personasTag = ref([]);
    const selectedTag = reactive({
      id: null,
      nombre: ''
    });
    
    // Asignar tag a personas
    const asignarTagDialogVisible = ref(false);
    const asignandoTag = ref(false);
    
    const asignarTagForm = reactive({
      tagId: null,
      personaIds: []
    });
    
    // Búsqueda de personas
    const availablePersonas = ref([]);
    const loadingPersonas = ref(false);

    const loadTags = async () => {
      loading.value = true;
      try {
        const params = {
          page: currentPage.value,
          limit: pageSize.value
        };
        
        if (filtros.query) {
          params.query = filtros.query;
        }
        
        const response = await axios.get('/tags', { params });
        tags.value = response.data.items || response.data;
        total.value = response.data.total || tags.value.length;
      } catch (error) {
        ElMessage.error('Error al cargar los tags');
        console.error(error);
      } finally {
        loading.value = false;
      }
    };

    const loadEstadisticas = async () => {
      try {
        const response = await axios.get('/tags/estadisticas');
        estadisticas.totalTags = response.data.totalTags;
        estadisticas.tagsMasUtilizados = response.data.tagsMasUtilizados;
      } catch (error) {
        console.error(error);
      }
    };

    const aplicarFiltros = () => {
      currentPage.value = 1;
      loadTags();
    };

    const resetFiltros = () => {
      filtros.query = '';
      currentPage.value = 1;
      loadTags();
    };

    const handleSizeChange = (val) => {
      pageSize.value = val;
      loadTags();
    };

    const handleCurrentChange = (val) => {
      currentPage.value = val;
      loadTags();
    };

    const navigateToPersona = (id) => {
      router.push(`/personas/${id}`);
      personasTagDialogVisible.value = false;
    };

    // Gestión de tags
    const showAddTagDialog = () => {
      editingTag.value = false;
      tagForm.id = null;
      tagForm.nombre = '';
      tagForm.descripcion = '';
      tagDialogVisible.value = true;
    };

    const editTag = (tag) => {
      editingTag.value = true;
      tagForm.id = tag.id;
      tagForm.nombre = tag.nombre;
      tagForm.descripcion = tag.descripcion || '';
      tagDialogVisible.value = true;
    };

    const saveTag = () => {
      if (!tagFormRef.value) return;
      
      tagFormRef.value.validate(async (valid) => {
        if (valid) {
          savingTag.value = true;
          
          try {
            if (editingTag.value) {
              await axios.put(`/tags/${tagForm.id}`, tagForm);
              ElMessage.success('Tag actualizado correctamente');
            } else {
              await axios.post('/tags', tagForm);
              ElMessage.success('Tag creado correctamente');
            }
            
            tagDialogVisible.value = false;
            loadTags();
            loadEstadisticas();
          } catch (error) {
            ElMessage.error('Error al guardar el tag');
            console.error(error);
          } finally {
            savingTag.value = false;
          }
        }
      });
    };

    const confirmDeleteTag = (tag) => {
      ElMessageBox.confirm(
        `¿Estás seguro de eliminar el tag "${tag.nombre}"?`,
        'Confirmar eliminación',
        {
          confirmButtonText: 'Eliminar',
          cancelButtonText: 'Cancelar',
          type: 'warning'
        }
      )
        .then(() => {
          deleteTag(tag.id);
        })
        .catch(() => {
          // Cancelado
        });
    };

    const deleteTag = async (id) => {
      try {
        await axios.delete(`/tags/${id}`);
        ElMessage.success('Tag eliminado correctamente');
        loadTags();
        loadEstadisticas();
      } catch (error) {
        ElMessage.error('Error al eliminar el tag');
        console.error(error);
      }
    };

    // Personas asociadas a un tag
    const showPersonasTag = async (tag) => {
      selectedTag.id = tag.id;
      selectedTag.nombre = tag.nombre;
      loadingPersonasTag.value = true;
      personasTagDialogVisible.value = true;
      
      try {
        const response = await axios.get(`/tags/${tag.id}/personas`);
        personasTag.value = response.data;
      } catch (error) {
        ElMessage.error('Error al cargar las personas asociadas al tag');
        console.error(error);
      } finally {
        loadingPersonasTag.value = false;
      }
    };

    // Asignar tag a personas
    const showAsignarTagDialog = () => {
      asignarTagForm.tagId = null;
      asignarTagForm.personaIds = [];
      asignarTagDialogVisible.value = true;
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

    const asignarTag = async () => {
      if (!asignarTagForm.tagId || asignarTagForm.personaIds.length === 0) {
        ElMessage.warning('Por favor selecciona un tag y al menos una persona');
        return;
      }
      
      asignandoTag.value = true;
      
      try {
        await axios.post(`/tags/${asignarTagForm.tagId}/asignar-personas`, {
          personaIds: asignarTagForm.personaIds
        });
        
        ElMessage.success('Tag asignado correctamente');
        asignarTagDialogVisible.value = false;
        loadTags();
        loadEstadisticas();
      } catch (error) {
        ElMessage.error('Error al asignar el tag');
        console.error(error);
      } finally {
        asignandoTag.value = false;
      }
    };

    const formatDate = (dateString) => {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString();
    };

    onMounted(() => {
      loadTags();
      loadEstadisticas();
    });

    return {
      tags,
      loading,
      currentPage,
      pageSize,
      total,
      filtros,
      estadisticas,
      tagDialogVisible,
      editingTag,
      savingTag,
      tagFormRef,
      tagForm,
      tagRules,
      personasTagDialogVisible,
      loadingPersonasTag,
      personasTag,
      selectedTag,
      asignarTagDialogVisible,
      asignandoTag,
      asignarTagForm,
      availablePersonas,
      loadingPersonas,
      loadTags,
      aplicarFiltros,
      resetFiltros,
      handleSizeChange,
      handleCurrentChange,
      navigateToPersona,
      showAddTagDialog,
      editTag,
      saveTag,
      confirmDeleteTag,
      showPersonasTag,
      showAsignarTagDialog,
      searchPersonas,
      asignarTag,
      formatDate
    };
  }
};
</script>

<style scoped>
.tags-list {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.tags-card,
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
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.estadistica-item {
  background-color: #f5f7fa;
  border-radius: 4px;
  padding: 15px;
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
  text-align: center;
}

.tag-list {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}
</style>
