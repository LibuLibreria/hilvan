<template>
  <div class="persona-form">
    <div class="page-header">
      <h1>{{ isEditing ? 'Editar Persona' : 'Nueva Persona' }}</h1>
      <el-button @click="goBack">
        <el-icon><Back /></el-icon> Volver
      </el-button>
    </div>

    <el-card>
      <el-form 
        :model="personaForm" 
        :rules="rules" 
        ref="personaFormRef" 
        label-position="top"
        v-loading="loading"
      >
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="Nombre" prop="nombre">
              <el-input v-model="personaForm.nombre" placeholder="Nombre" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Apellido" prop="apellido">
              <el-input v-model="personaForm.apellido" placeholder="Apellido" />
            </el-form-item>
          </el-col>
        </el-row>

        <el-form-item label="Fecha de Nacimiento" prop="fechaNacimiento">
          <el-date-picker
            v-model="personaForm.fechaNacimiento"
            type="date"
            placeholder="Selecciona fecha"
            format="DD/MM/YYYY"
            value-format="YYYY-MM-DD"
            style="width: 100%"
          />
        </el-form-item>

        <el-form-item label="Observaciones" prop="observaciones">
          <el-input
            v-model="personaForm.observaciones"
            type="textarea"
            :rows="4"
            placeholder="Observaciones generales"
          />
        </el-form-item>

        <el-form-item label="Foto" prop="foto">
          <el-upload
            class="avatar-uploader"
            action="#"
            :http-request="uploadPhoto"
            :show-file-list="false"
            :before-upload="beforePhotoUpload"
          >
            <img v-if="imageUrl" :src="imageUrl" class="avatar" />
            <el-icon v-else class="avatar-uploader-icon"><Plus /></el-icon>
          </el-upload>
          <div class="upload-tip">Haz clic para subir una foto</div>
        </el-form-item>

        <el-divider>Relaciones</el-divider>

        <div class="relations-section">
          <el-button type="primary" @click="showAddRelationDialog">
            <el-icon><Plus /></el-icon> Añadir Relación
          </el-button>

          <el-table
            v-if="personaForm.relaciones && personaForm.relaciones.length > 0"
            :data="personaForm.relaciones"
            style="width: 100%; margin-top: 20px"
            border
          >
            <el-table-column prop="tipoRelacion.nombre" label="Tipo de Relación" />
            <el-table-column prop="personaDestino.nombre" label="Nombre" />
            <el-table-column prop="personaDestino.apellido" label="Apellido" />
            <el-table-column label="Acciones" width="120">
              <template #default="scope">
                <el-button type="danger" size="small" @click="removeRelation(scope.$index)">
                  <el-icon><Delete /></el-icon>
                </el-button>
              </template>
            </el-table-column>
          </el-table>
          <el-empty v-else description="No hay relaciones" />
        </div>

        <el-divider>Tags / Membresías</el-divider>

        <div class="tags-section">
          <el-select
            v-model="selectedTag"
            filterable
            remote
            reserve-keyword
            placeholder="Buscar tag"
            :remote-method="searchTags"
            :loading="loadingTags"
            style="width: 100%"
          >
            <el-option
              v-for="tag in availableTags"
              :key="tag.id"
              :label="tag.nombre"
              :value="tag.id"
            />
          </el-select>
          <el-button type="primary" @click="addTag" style="margin-top: 10px">
            <el-icon><Plus /></el-icon> Añadir Tag
          </el-button>

          <div class="tags-container" v-if="personaForm.tags && personaForm.tags.length > 0">
            <el-tag
              v-for="(tag, index) in personaForm.tags"
              :key="tag.id"
              closable
              @close="removeTag(index)"
              style="margin: 5px"
            >
              {{ tag.nombre }}
            </el-tag>
          </div>
          <el-empty v-else description="No hay tags asignados" style="margin-top: 20px" />
        </div>

        <el-divider>Fechas Recordatorio</el-divider>

        <div class="dates-section">
          <el-button type="primary" @click="showAddDateDialog">
            <el-icon><Plus /></el-icon> Añadir Fecha Recordatorio
          </el-button>

          <el-table
            v-if="personaForm.fechasRecordatorio && personaForm.fechasRecordatorio.length > 0"
            :data="personaForm.fechasRecordatorio"
            style="width: 100%; margin-top: 20px"
            border
          >
            <el-table-column label="Fecha">
              <template #default="scope">
                {{ formatDate(scope.row.fecha) }}
              </template>
            </el-table-column>
            <el-table-column prop="descripcion" label="Descripción" />
            <el-table-column label="Recurrente">
              <template #default="scope">
                <el-tag :type="scope.row.esRecurrente ? 'success' : 'info'">
                  {{ scope.row.esRecurrente ? 'Sí' : 'No' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="Acciones" width="120">
              <template #default="scope">
                <el-button type="danger" size="small" @click="removeDate(scope.$index)">
                  <el-icon><Delete /></el-icon>
                </el-button>
              </template>
            </el-table-column>
          </el-table>
          <el-empty v-else description="No hay fechas recordatorio" />
        </div>

        <el-form-item style="margin-top: 20px">
          <el-button type="primary" @click="submitForm" :loading="submitting">
            {{ isEditing ? 'Actualizar' : 'Crear' }} Persona
          </el-button>
          <el-button @click="goBack">Cancelar</el-button>
        </el-form-item>
      </el-form>
    </el-card>

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
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { ElMessage } from 'element-plus';
import axios from 'axios';

export default {
  name: 'PersonaForm',
  props: {
    id: {
      type: [String, Number],
      required: false
    }
  },
  setup(props) {
    const router = useRouter();
    const route = useRoute();
    const personaFormRef = ref(null);
    const loading = ref(false);
    const submitting = ref(false);
    const imageUrl = ref('');
    const photoFile = ref(null);
    
    // Relaciones
    const relationDialogVisible = ref(false);
    const tiposRelacion = ref([]);
    const availablePersonas = ref([]);
    const loadingPersonas = ref(false);
    
    // Tags
    const availableTags = ref([]);
    const selectedTag = ref(null);
    const loadingTags = ref(false);
    
    // Fechas recordatorio
    const dateDialogVisible = ref(false);

    const personaForm = reactive({
      nombre: '',
      apellido: '',
      fechaNacimiento: '',
      observaciones: '',
      foto: '',
      relaciones: [],
      tags: [],
      fechasRecordatorio: []
    });

    const newRelation = reactive({
      tipoRelacionId: null,
      personaDestinoId: null
    });

    const newDate = reactive({
      fecha: '',
      descripcion: '',
      esRecurrente: false
    });

    const isEditing = computed(() => !!props.id);

    const rules = {
      nombre: [
        { required: true, message: 'Por favor ingresa el nombre', trigger: 'blur' },
        { min: 2, message: 'El nombre debe tener al menos 2 caracteres', trigger: 'blur' }
      ],
      apellido: [
        { required: true, message: 'Por favor ingresa el apellido', trigger: 'blur' },
        { min: 2, message: 'El apellido debe tener al menos 2 caracteres', trigger: 'blur' }
      ],
      fechaNacimiento: [
        { required: false, message: 'Por favor selecciona la fecha de nacimiento', trigger: 'change' }
      ]
    };

    const loadPersona = async () => {
      if (!props.id) return;
      
      loading.value = true;
      try {
        const response = await axios.get(`/personas/${props.id}`);
        const persona = response.data;
        
        personaForm.nombre = persona.nombre;
        personaForm.apellido = persona.apellido;
        personaForm.fechaNacimiento = persona.fechaNacimiento;
        personaForm.observaciones = persona.observaciones;
        personaForm.foto = persona.foto;
        
        if (persona.foto) {
          imageUrl.value = `/uploads/fotos/${persona.foto}`;
        }
        
        // Cargar relaciones, tags y fechas recordatorio
        loadRelaciones();
        loadTags();
        loadFechasRecordatorio();
        
      } catch (error) {
        ElMessage.error('Error al cargar los datos de la persona');
        console.error(error);
      } finally {
        loading.value = false;
      }
    };

    const loadRelaciones = async () => {
      if (!props.id) return;
      
      try {
        const response = await axios.get(`/relaciones/persona/${props.id}`);
        personaForm.relaciones = response.data;
      } catch (error) {
        ElMessage.error('Error al cargar las relaciones');
        console.error(error);
      }
    };

    const loadTags = async () => {
      if (!props.id) return;
      
      try {
        const response = await axios.get(`/personas/${props.id}/tags`);
        personaForm.tags = response.data;
      } catch (error) {
        ElMessage.error('Error al cargar los tags');
        console.error(error);
      }
    };

    const loadFechasRecordatorio = async () => {
      if (!props.id) return;
      
      try {
        const response = await axios.get(`/fechas-recordatorio/persona/${props.id}`);
        personaForm.fechasRecordatorio = response.data;
      } catch (error) {
        ElMessage.error('Error al cargar las fechas recordatorio');
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

    const searchTags = async (query) => {
      if (query.length < 2) return;
      
      loadingTags.value = true;
      try {
        const response = await axios.get('/tags', {
          params: { query }
        });
        availableTags.value = response.data;
      } catch (error) {
        console.error(error);
      } finally {
        loadingTags.value = false;
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
        // Si estamos editando, enviamos la relación al servidor
        if (isEditing.value) {
          await axios.post(`/relaciones`, {
            personaOrigenId: props.id,
            personaDestinoId: newRelation.personaDestinoId,
            tipoRelacionId: newRelation.tipoRelacionId
          });
          
          // Recargar relaciones
          await loadRelaciones();
        } else {
          // Si estamos creando, añadimos la relación localmente
          const tipoRelacion = tiposRelacion.value.find(t => t.id === newRelation.tipoRelacionId);
          const personaDestino = availablePersonas.value.find(p => p.id === newRelation.personaDestinoId);
          
          personaForm.relaciones.push({
            tipoRelacion,
            personaDestino
          });
        }
        
        relationDialogVisible.value = false;
        ElMessage.success('Relación añadida correctamente');
      } catch (error) {
        ElMessage.error('Error al añadir la relación');
        console.error(error);
      }
    };

    const removeRelation = async (index) => {
      try {
        if (isEditing.value) {
          const relacion = personaForm.relaciones[index];
          await axios.delete(`/relaciones/${relacion.id}`);
          await loadRelaciones();
        } else {
          personaForm.relaciones.splice(index, 1);
        }
        
        ElMessage.success('Relación eliminada correctamente');
      } catch (error) {
        ElMessage.error('Error al eliminar la relación');
        console.error(error);
      }
    };

    const addTag = async () => {
      if (!selectedTag.value) {
        ElMessage.warning('Por favor selecciona un tag');
        return;
      }
      
      try {
        // Si estamos editando, enviamos el tag al servidor
        if (isEditing.value) {
          await axios.post(`/tags/persona/${props.id}/asignar`, {
            tagId: selectedTag.value
          });
          
          // Recargar tags
          await loadTags();
        } else {
          // Si estamos creando, añadimos el tag localmente
          const tag = availableTags.value.find(t => t.id === selectedTag.value);
          
          // Verificar que no esté ya añadido
          if (personaForm.tags.some(t => t.id === tag.id)) {
            ElMessage.warning('Este tag ya está asignado');
            return;
          }
          
          personaForm.tags.push(tag);
        }
        
        selectedTag.value = null;
        ElMessage.success('Tag añadido correctamente');
      } catch (error) {
        ElMessage.error('Error al añadir el tag');
        console.error(error);
      }
    };

    const removeTag = async (index) => {
      try {
        if (isEditing.value) {
          const tag = personaForm.tags[index];
          await axios.post(`/tags/persona/${props.id}/desasignar`, {
            tagId: tag.id
          });
          await loadTags();
        } else {
          personaForm.tags.splice(index, 1);
        }
        
        ElMessage.success('Tag eliminado correctamente');
      } catch (error) {
        ElMessage.error('Error al eliminar el tag');
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
        // Si estamos editando, enviamos la fecha al servidor
        if (isEditing.value) {
          await axios.post(`/fechas-recordatorio`, {
            fecha: newDate.fecha,
            descripcion: newDate.descripcion,
            esRecurrente: newDate.esRecurrente,
            personaId: props.id
          });
          
          // Recargar fechas
          await loadFechasRecordatorio();
        } else {
          // Si estamos creando, añadimos la fecha localmente
          personaForm.fechasRecordatorio.push({
            fecha: newDate.fecha,
            descripcion: newDate.descripcion,
            esRecurrente: newDate.esRecurrente
          });
        }
        
        dateDialogVisible.value = false;
        ElMessage.success('Fecha recordatorio añadida correctamente');
      } catch (error) {
        ElMessage.error('Error al añadir la fecha recordatorio');
        console.error(error);
      }
    };

    const removeDate = async (index) => {
      try {
        if (isEditing.value) {
          const fecha = personaForm.fechasRecordatorio[index];
          await axios.delete(`/fechas-recordatorio/${fecha.id}`);
          await loadFechasRecordatorio();
        } else {
          personaForm.fechasRecordatorio.splice(index, 1);
        }
        
        ElMessage.success('Fecha recordatorio eliminada correctamente');
      } catch (error) {
        ElMessage.error('Error al eliminar la fecha recordatorio');
        console.error(error);
      }
    };

    const beforePhotoUpload = (file) => {
      const isImage = file.type.startsWith('image/');
      const isLt2M = file.size / 1024 / 1024 < 2;

      if (!isImage) {
        ElMessage.error('Solo se permiten archivos de imagen');
        return false;
      }
      
      if (!isLt2M) {
        ElMessage.error('La imagen no puede superar los 2MB');
        return false;
      }
      
      photoFile.value = file;
      return false; // Evitar la carga automática
    };

    const uploadPhoto = () => {
      if (!photoFile.value) return;
      
      const reader = new FileReader();
      reader.onload = (e) => {
        imageUrl.value = e.target.result;
      };
      reader.readAsDataURL(photoFile.value);
    };

    const submitForm = () => {
      if (!personaFormRef.value) return;
      
      personaFormRef.value.validate(async (valid) => {
        if (valid) {
          submitting.value = true;
          
          try {
            const formData = new FormData();
            formData.append('nombre', personaForm.nombre);
            formData.append('apellido', personaForm.apellido);
            
            if (personaForm.fechaNacimiento) {
              formData.append('fechaNacimiento', personaForm.fechaNacimiento);
            }
            
            if (personaForm.observaciones) {
              formData.append('observaciones', personaForm.observaciones);
            }
            
            if (photoFile.value) {
              formData.append('foto', photoFile.value);
            }
            
            // Si estamos editando, actualizamos la persona
            if (isEditing.value) {
              await axios.post(`/personas/${props.id}`, formData, {
                headers: {
                  'Content-Type': 'multipart/form-data'
                }
              });
              
              ElMessage.success('Persona actualizada correctamente');
            } else {
              // Si estamos creando, creamos la persona
              const response = await axios.post('/personas', formData, {
                headers: {
                  'Content-Type': 'multipart/form-data'
                }
              });
              
              const personaId = response.data.id;
              
              // Crear relaciones, tags y fechas recordatorio
              if (personaForm.relaciones.length > 0) {
                for (const relacion of personaForm.relaciones) {
                  await axios.post('/relaciones', {
                    personaOrigenId: personaId,
                    personaDestinoId: relacion.personaDestino.id,
                    tipoRelacionId: relacion.tipoRelacion.id
                  });
                }
              }
              
              if (personaForm.tags.length > 0) {
                for (const tag of personaForm.tags) {
                  await axios.post(`/tags/persona/${personaId}/asignar`, {
                    tagId: tag.id
                  });
                }
              }
              
              if (personaForm.fechasRecordatorio.length > 0) {
                for (const fecha of personaForm.fechasRecordatorio) {
                  await axios.post('/fechas-recordatorio', {
                    fecha: fecha.fecha,
                    descripcion: fecha.descripcion,
                    esRecurrente: fecha.esRecurrente,
                    personaId: personaId
                  });
                }
              }
              
              ElMessage.success('Persona creada correctamente');
            }
            
            // Redireccionar al listado
            router.push('/personas');
          } catch (error) {
            ElMessage.error('Error al guardar la persona');
            console.error(error);
          } finally {
            submitting.value = false;
          }
        }
      });
    };

    const goBack = () => {
      router.push('/personas');
    };

    const formatDate = (dateString) => {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString();
    };

    onMounted(() => {
      loadTiposRelacion();
      if (props.id) {
        loadPersona();
      }
    });

    return {
      personaFormRef,
      personaForm,
      rules,
      loading,
      submitting,
      imageUrl,
      isEditing,
      relationDialogVisible,
      tiposRelacion,
      newRelation,
      availablePersonas,
      loadingPersonas,
      dateDialogVisible,
      newDate,
      availableTags,
      selectedTag,
      loadingTags,
      submitForm,
      goBack,
      showAddRelationDialog,
      searchPersonas,
      addRelation,
      removeRelation,
      showAddDateDialog,
      addDate,
      removeDate,
      searchTags,
      addTag,
      removeTag,
      beforePhotoUpload,
      uploadPhoto,
      formatDate
    };
  }
};
</script>

<style scoped>
.persona-form {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.avatar-uploader {
  text-align: center;
  border: 1px dashed #d9d9d9;
  border-radius: 6px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  width: 178px;
  height: 178px;
}

.avatar-uploader:hover {
  border-color: #409eff;
}

.avatar-uploader-icon {
  font-size: 28px;
  color: #8c939d;
  width: 178px;
  height: 178px;
  line-height: 178px;
  text-align: center;
}

.avatar {
  width: 178px;
  height: 178px;
  display: block;
  object-fit: cover;
}

.upload-tip {
  font-size: 12px;
  color: #606266;
  margin-top: 7px;
}

.relations-section,
.tags-section,
.dates-section {
  margin-top: 20px;
  margin-bottom: 20px;
}

.tags-container {
  margin-top: 10px;
}
</style>
