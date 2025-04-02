<template>
  <div class="register-container">
    <el-card class="register-card">
      <template #header>
        <div class="card-header">
          <h2>Registro de usuario</h2>
        </div>
      </template>
      
      <el-form :model="registerForm" :rules="rules" ref="registerFormRef" label-position="top">
        <el-form-item label="Email" prop="email">
          <el-input v-model="registerForm.email" placeholder="Introduce tu email" prefix-icon="Message" />
        </el-form-item>
        
        <el-form-item label="Contraseña" prop="password">
          <el-input v-model="registerForm.password" type="password" placeholder="Introduce tu contraseña" prefix-icon="Lock" show-password />
        </el-form-item>
        
        <el-form-item label="Confirmar contraseña" prop="confirmPassword">
          <el-input v-model="registerForm.confirmPassword" type="password" placeholder="Confirma tu contraseña" prefix-icon="Lock" show-password />
        </el-form-item>
        
        <el-form-item>
          <el-button type="primary" @click="submitForm" :loading="loading" style="width: 100%">
            Registrarse
          </el-button>
        </el-form-item>
      </el-form>
      
      <div class="login-link">
        ¿Ya tienes cuenta? <router-link to="/login">Inicia sesión</router-link>
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
  name: 'Register',
  setup() {
    const router = useRouter();
    const registerFormRef = ref(null);
    const loading = ref(false);
    
    const registerForm = reactive({
      email: '',
      password: '',
      confirmPassword: ''
    });
    
    const validatePass = (rule, value, callback) => {
      if (value === '') {
        callback(new Error('Por favor introduce la contraseña'));
      } else {
        if (registerForm.confirmPassword !== '') {
          registerFormRef.value.validateField('confirmPassword');
        }
        callback();
      }
    };
    
    const validatePass2 = (rule, value, callback) => {
      if (value === '') {
        callback(new Error('Por favor confirma la contraseña'));
      } else if (value !== registerForm.password) {
        callback(new Error('Las contraseñas no coinciden'));
      } else {
        callback();
      }
    };
    
    const rules = {
      email: [
        { required: true, message: 'Por favor introduce tu email', trigger: 'blur' },
        { type: 'email', message: 'Por favor introduce un email válido', trigger: 'blur' }
      ],
      password: [
        { required: true, message: 'Por favor introduce tu contraseña', trigger: 'blur' },
        { min: 6, message: 'La contraseña debe tener al menos 6 caracteres', trigger: 'blur' },
        { validator: validatePass, trigger: 'blur' }
      ],
      confirmPassword: [
        { required: true, message: 'Por favor confirma tu contraseña', trigger: 'blur' },
        { validator: validatePass2, trigger: 'blur' }
      ]
    };
    
    const submitForm = () => {
      if (!registerFormRef.value) return;
      
      registerFormRef.value.validate(async (valid) => {
        if (valid) {
          loading.value = true;
          
          try {
            const response = await axios.post('/auth/register', {
              email: registerForm.email,
              password: registerForm.password
            });
            
            ElMessage({
              type: 'success',
              message: 'Registro exitoso. Ahora puedes iniciar sesión.'
            });
            
            // Redireccionar a login
            router.push('/login');
          } catch (error) {
            ElMessage({
              type: 'error',
              message: error.response?.data?.message || 'Error al registrarse'
            });
          } finally {
            loading.value = false;
          }
        }
      });
    };
    
    return {
      registerFormRef,
      registerForm,
      rules,
      loading,
      submitForm
    };
  }
};
</script>

<style scoped>
.register-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  background-color: #f5f7fa;
}

.register-card {
  width: 400px;
  max-width: 90%;
}

.card-header {
  display: flex;
  justify-content: center;
  align-items: center;
}

.login-link {
  text-align: center;
  margin-top: 20px;
  font-size: 14px;
}

.login-link a {
  color: #409eff;
  text-decoration: none;
}
</style>
