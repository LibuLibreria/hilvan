<template>
  <div class="login-container">
    <el-card class="login-card">
      <template #header>
        <div class="card-header">
          <h2>Iniciar sesión</h2>
        </div>
      </template>
      
      <el-form :model="loginForm" :rules="rules" ref="loginFormRef" label-position="top">
        <el-form-item label="Email" prop="email">
          <el-input v-model="loginForm.email" placeholder="Introduce tu email" prefix-icon="Message" />
        </el-form-item>
        
        <el-form-item label="Contraseña" prop="password">
          <el-input v-model="loginForm.password" type="password" placeholder="Introduce tu contraseña" prefix-icon="Lock" show-password />
        </el-form-item>
        
        <el-form-item>
          <el-button type="primary" @click="submitForm" :loading="loading" style="width: 100%">
            Iniciar sesión
          </el-button>
        </el-form-item>
      </el-form>
      
      <div class="register-link">
        ¿No tienes cuenta? <router-link to="/register">Regístrate</router-link>
      </div>
    </el-card>
  </div>
</template>

<script>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage } from 'element-plus';
import axios from 'axios';

export default {
  name: 'Login',
  setup() {
    const router = useRouter();
    const loginFormRef = ref(null);
    const loading = ref(false);
    
    const loginForm = reactive({
      email: '',
      password: ''
    });
    
    const rules = {
      email: [
        { required: true, message: 'Por favor introduce tu email', trigger: 'blur' },
        { type: 'email', message: 'Por favor introduce un email válido', trigger: 'blur' }
      ],
      password: [
        { required: true, message: 'Por favor introduce tu contraseña', trigger: 'blur' },
        { min: 6, message: 'La contraseña debe tener al menos 6 caracteres', trigger: 'blur' }
      ]
    };
    
    const submitForm = () => {
      if (!loginFormRef.value) return;
      
      loginFormRef.value.validate(async (valid) => {
        if (valid) {
          loading.value = true;
          
          try {
            const response = await axios.post('/auth/login', loginForm);
            
            // Guardar token en localStorage
            localStorage.setItem('token', response.data.token);
            localStorage.setItem('user', JSON.stringify(response.data.user));
            
            ElMessage({
              type: 'success',
              message: 'Inicio de sesión exitoso'
            });
            
            // Redireccionar al dashboard
            router.push('/dashboard');
          } catch (error) {
            ElMessage({
              type: 'error',
              message: error.response?.data?.message || 'Error al iniciar sesión'
            });
          } finally {
            loading.value = false;
          }
        }
      });
    };
    
    return {
      loginFormRef,
      loginForm,
      rules,
      loading,
      submitForm
    };
  }
};
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  background-color: #f5f7fa;
}

.login-card {
  width: 400px;
  max-width: 90%;
}

.card-header {
  display: flex;
  justify-content: center;
  align-items: center;
}

.register-link {
  text-align: center;
  margin-top: 20px;
  font-size: 14px;
}

.register-link a {
  color: #409eff;
  text-decoration: none;
}
</style>
