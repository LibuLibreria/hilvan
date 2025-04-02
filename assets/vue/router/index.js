import { createRouter, createWebHistory } from 'vue-router';

// Importar componentes
import Login from '../views/auth/Login.vue';
import Register from '../views/auth/Register.vue';
import Dashboard from '../views/Dashboard.vue';
import PersonasList from '../views/personas/PersonasList.vue';
import PersonaForm from '../views/personas/PersonaForm.vue';
import PersonaDetail from '../views/personas/PersonaDetail.vue';
import RelacionesList from '../views/relaciones/RelacionesList.vue';
import TagsList from '../views/tags/TagsList.vue';
import RecordatoriosList from '../views/recordatorios/RecordatoriosList.vue';

// Función para verificar autenticación
const requireAuth = (to, from, next) => {
  const token = localStorage.getItem('token');
  if (!token) {
    next('/login');
  } else {
    next();
  }
};

const routes = [
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/login',
    name: 'Login',
    component: Login
  },
  {
    path: '/register',
    name: 'Register',
    component: Register
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    beforeEnter: requireAuth
  },
  {
    path: '/personas',
    name: 'PersonasList',
    component: PersonasList,
    beforeEnter: requireAuth
  },
  {
    path: '/personas/nueva',
    name: 'PersonaCreate',
    component: PersonaForm,
    beforeEnter: requireAuth
  },
  {
    path: '/personas/:id/editar',
    name: 'PersonaEdit',
    component: PersonaForm,
    props: true,
    beforeEnter: requireAuth
  },
  {
    path: '/personas/:id',
    name: 'PersonaDetail',
    component: PersonaDetail,
    props: true,
    beforeEnter: requireAuth
  },
  {
    path: '/relaciones',
    name: 'RelacionesList',
    component: RelacionesList,
    beforeEnter: requireAuth
  },
  {
    path: '/tags',
    name: 'TagsList',
    component: TagsList,
    beforeEnter: requireAuth
  },
  {
    path: '/recordatorios',
    name: 'RecordatoriosList',
    component: RecordatoriosList,
    beforeEnter: requireAuth
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

export default router;
