<template>
  <div class="personas-list">
    <div class="page-header">
      <h1>Listado de Personas</h1>
      <el-button type="primary" @click="navigateToCreate">
        <el-icon><Plus /></el-icon> Nueva Persona
      </el-button>
    </div>

    <el-card class="filter-card">
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="Buscar">
          <el-input v-model="searchForm.query" placeholder="Nombre, apellido..." clearable @clear="loadPersonas" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="searchPersonas">Buscar</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-table
      v-loading="loading"
      :data="personas"
      style="width: 100%"
      border
    >
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column prop="nombre" label="Nombre" />
      <el-table-column prop="apellido" label="Apellido" />
      <el-table-column label="Fecha Nacimiento">
        <template #default="scope">
          {{ formatDate(scope.row.fechaNacimiento) }}
        </template>
      </el-table-column>
      <el-table-column label="Foto">
        <template #default="scope">
          <el-avatar v-if="scope.row.foto" :src="getPhotoUrl(scope.row.foto)" :size="40"></el-avatar>
          <el-avatar v-else :size="40" icon="el-icon-user"></el-avatar>
        </template>
      </el-table-column>
      <el-table-column label="Acciones" width="200">
        <template #default="scope">
          <el-button-group>
            <el-button type="primary" size="small" @click="viewPersona(scope.row.id)">
              <el-icon><View /></el-icon>
            </el-button>
            <el-button type="warning" size="small" @click="editPersona(scope.row.id)">
              <el-icon><Edit /></el-icon>
            </el-button>
            <el-button type="danger" size="small" @click="confirmDelete(scope.row)">
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
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage, ElMessageBox } from 'element-plus';
import axios from 'axios';

export default {
  name: 'PersonasList',
  setup() {
    const router = useRouter();
    const personas = ref([]);
    const loading = ref(false);
    const currentPage = ref(1);
    const pageSize = ref(10);
    const total = ref(0);

    const searchForm = reactive({
      query: ''
    });

    const loadPersonas = async () => {
      loading.value = true;
      try {
        const response = await axios.get('/personas', {
          params: {
            page: currentPage.value,
            limit: pageSize.value,
            query: searchForm.query
          }
        });
        personas.value = response.data.items || response.data;
        total.value = response.data.total || personas.value.length;
      } catch (error) {
        ElMessage.error('Error al cargar las personas');
        console.error(error);
      } finally {
        loading.value = false;
      }
    };

    const searchPersonas = () => {
      currentPage.value = 1;
      loadPersonas();
    };

    const handleSizeChange = (val) => {
      pageSize.value = val;
      loadPersonas();
    };

    const handleCurrentChange = (val) => {
      currentPage.value = val;
      loadPersonas();
    };

    const navigateToCreate = () => {
      router.push('/personas/nueva');
    };

    const viewPersona = (id) => {
      router.push(`/personas/${id}`);
    };

    const editPersona = (id) => {
      router.push(`/personas/${id}/editar`);
    };

    const confirmDelete = (persona) => {
      ElMessageBox.confirm(
        `¿Estás seguro de eliminar a ${persona.nombre} ${persona.apellido}?`,
        'Confirmar eliminación',
        {
          confirmButtonText: 'Eliminar',
          cancelButtonText: 'Cancelar',
          type: 'warning'
        }
      )
        .then(() => {
          deletePersona(persona.id);
        })
        .catch(() => {
          // Cancelado
        });
    };

    const deletePersona = async (id) => {
      loading.value = true;
      try {
        await axios.delete(`/personas/${id}`);
        ElMessage.success('Persona eliminada correctamente');
        loadPersonas();
      } catch (error) {
        ElMessage.error('Error al eliminar la persona');
        console.error(error);
      } finally {
        loading.value = false;
      }
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
      loadPersonas();
    });

    return {
      personas,
      loading,
      currentPage,
      pageSize,
      total,
      searchForm,
      loadPersonas,
      searchPersonas,
      handleSizeChange,
      handleCurrentChange,
      navigateToCreate,
      viewPersona,
      editPersona,
      confirmDelete,
      formatDate,
      getPhotoUrl
    };
  }
};
</script>

<style scoped>
.personas-list {
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
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}
</style>
