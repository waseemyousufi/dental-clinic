import { createRouter, createWebHistory } from 'vue-router'

const isAuthenticated = () => {
  const user = localStorage.getItem('user')
  return user
}
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: isAuthenticated() ? '/patients/?branchId=1' : '/login',
    },
    {
      path: '/overview',
      component: () => import('@views/OverviewView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: isAuthenticated() ? '/patients/?branchId=1' : '/login',
    },
    {
      path: '/register-user',
      component: () => import('@views/SignInView.vue'),
    },
    {
      path: '/login',
      component: () => import('@views/LoginView.vue'),
    },
    {
      path: '/reset-password',
      component: () => import('@views/ResetPasswordView.vue'),
    },
    {
      path: '/patients',
      component: () => import('@views/PatientView.vue'),
    },
    {
      path: '/dentist/patient/:id',
      component: () => import('@views/Dentist/PatientView.vue'),
    },
    {
      path: '/employees',
      component: () => import('@views/EmployeeView.vue'),
    },
    {
      path: '/accounts',
      component: () => import('@views/AccountView.vue'),
    },
    {
      path: '/transactions',
      component: () => import('@views/TransactionView.vue'),
    },
    {
      path: '/appointments',
      component: () => import('@views/AppointmentView.vue'),
    },
    {
      path: '/test',
      component: () => import('../components/PrimaryOdontogram.vue'),
    },
    {
      path: '/prescriptions',
      component: () => import('../components/PrescriptionAligner.vue'),
    },
    {
      path: '/xrays',
      component: () => import('@views/XrayView.vue'),
    },
    {
      path: '/voice-commands',
      component: () => import('@views/VoiceCommandsView.vue'),
    },
    {
      path: '/patient-doctor/:id',
      component: () => import('@views/Dentist/PatientView.vue'),
    }
    ,
    {
      path: '/inventory',
      component: () => import('@views/ShelfView.vue'),
    },
    {
      path: '/suppliers',
      name: 'Suppliers',
      component: () => import('@/views/SupplierView.vue'),
      meta: { requiresAuth: true }
    }
  ],
})

router.beforeEach((to, from, next) => {
  const user = localStorage.getItem('user')
  const isLoggedIn = user

  if (to.meta.requiresAuth && !isLoggedIn) {
    return next('/login')
  }

  if (to.meta.guestOnly && isLoggedIn) {
    return next('/')
  }

  next()
})

export default router
