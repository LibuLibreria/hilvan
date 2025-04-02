<template>
  <div class="layout-container">
    <el-container>
      <el-aside width="250px">
        <el-menu
          :router="true"
          class="el-menu-vertical"
          background-color="#545c64"
          text-color="#fff"
          active-text-color="#ffd04b"
        >
          <el-menu-item index="/dashboard">
            <el-icon><HomeFilled /></el-icon>
            <span>Dashboard</span>
          </el-menu-item>
          <el-menu-item index="/personas">
            <el-icon><User /></el-icon>
            <span>Personas</span>
          </el-menu-item>
          <el-menu-item index="/relaciones">
            <el-icon><Connection /></el-icon>
            <span>Relaciones</span>
          </el-menu-item>
          <el-menu-item index="/recordatorios">
            <el-icon><Calendar /></el-icon>
            <span>Recordatorios</span>
          </el-menu-item>
          <el-menu-item index="/tags">
            <el-icon><Collection /></el-icon>
            <span>Tags/Membresías</span>
          </el-menu-item>
          <el-divider />
          <el-menu-item @click="logout">
            <el-icon><SwitchButton /></el-icon>
            <span>Cerrar sesión</span>
          </el-menu-item>
        </el-menu>
      </el-aside>
      
      <el-container>
        <el-header>
          <div class="header-content">
            <h2>Gestión de Relaciones Personales</h2>
            <div class="user-info">
              <span>{{ userEmail }}</span>
            </div>
          </div>
        </el-header>
        
        <el-main>
          <router-view></router-view>
        </el-main>
        
        <el-footer>
          <div class="footer-content">
            <p>&copy; 2025 - Aplicación de Gestión de Relaciones Personales</p>
          </div>
        </el-footer>
      </el-container>
    </el-container>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { ElMessage } from 'element-plus';

export default {
  name: 'Dashboard',
  setup() {
    const router = useRouter();
    const userEmail = ref('');
    
    onMounted(() => {
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      userEmail.value = user.email || 'Usuario';
    });
    
    const logout = () => {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      
      ElMessage({
        type: 'success',
        message: 'Sesión cerrada correctamente'
      });
      
      router.push('/login');
    };
    
    return {
      userEmail,
      logout
    };
  }
};
</script>

<style scoped>
.layout-container {
  height: 100vh;
}

.el-header {
  background-color: #f5f7fa;
  color: #333;
  line-height: 60px;
  border-bottom: 1px solid #e6e6e6;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.user-info {
  display: flex;
  align-items: center;
}

.el-aside {
  color: #333;
  background-color: #545c64;
}

.el-menu-vertical {
  height: 100%;
  border-right: none;
}

.el-main {
  background-color: #fff;
  color: #333;
  padding: 20px;
}

.el-footer {
  background-color: #f5f7fa;
  color: #333;
  text-align: center;
  line-height: 60px;
  border-top: 1px solid #e6e6e6;
}

.footer-content {
  font-size: 14px;
  color: #666;
}
</style>
