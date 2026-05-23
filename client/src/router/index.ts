import { createRouter, createWebHistory } from 'vue-router'

const isAuthenticated = () => {
  const user = localStorage.getItem('user')
  const token = localStorage.getItem('token')
  if (user) return user
  if (token) return token
  return false;
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: () => isAuthenticated() ? '/patients/?branchId=1' : '/login',
    },
    {
      path: '/overview',
      component: () => import('@views/OverviewView.vue'),
      meta: { requiresAuth: true }
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
      redirect: '/accounts',
    },
    {
      path: '/appointments',
      component: () => import('@views/AppointmentView.vue'),
    },
    {
      path: '/prescriptions',
      component: () => import('../components/PrescriptionAligner.vue'),
    },
    {
      path: '/patient-doctor/:id',
      component: () => import('@views/Dentist/PatientView.vue'),
    },
    {
      path: '/inventory',
      component: () => import('@views/ShelfView.vue'),
    },
    {
      path: '/suppliers',
      name: 'Suppliers',
      component: () => import('@/views/SupplierView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/clinic-assets',
      name: 'ClinicAssets',
      component: () => import('@/views/ClinicAssetsView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/dashboard',
      name: 'Dashboard',
      component: () => import('@views/DashboardView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/expenses',
      name: 'Expenses',
      component: () => import('@views/ExpenseView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/treatments',
      name: 'Treatments',
      component: () => import('@views/Dentist/TreatmentView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/settings',
      name: 'Settings',
      component: () => import('@views/SettingsView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/reports',
      name: 'Reports',
      component: () => import('@views/ReportsView.vue'),
      meta: { requiresAuth: true }
    },

    // FIX 1: EXPLICIT ROUTE ABOVE CATCH-ALL (REMOVED REQUIRING GENERAL AUTH)
    {
      path: '/hyper-controls',
      name: 'HyperControls',
      component: () => import('@views/HyperControlsView.vue'),
      meta: { requiresAuth: false } // Bypasses the global router guard completely!
    },

    // FIX 2: CATCH-ALL DROPPED TO THE VERY BOTTOM
    {
      path: '/:pathMatch(.*)*',
      redirect: () => {
        if (isAuthenticated()) {
          return '/patients/?branchId=1';
        } else {
          return '/login';
        }
      },
    },
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
