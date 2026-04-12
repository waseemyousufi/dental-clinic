<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { NCard, NForm, NFormItem, NInput, NButton, NH2, NP, useMessage } from 'naive-ui'

import userApi from '@api/user'
import type UserData from '@api/interfaces/User'

const route = useRoute()
const router = useRouter()
const message = useMessage()

const loading = ref(false)

const form = ref({
  email: '',
  password: '',
  confirmPassword: '',
})

const token = computed(() => {
  const t = route.query.token
  return typeof t === 'string' ? t : ''
})

async function handleSubmit() {
  if (!token.value) {
    message.error('Reset token is missing or invalid.')
    return
  }

  if (!form.value.email || !form.value.password || !form.value.confirmPassword) {
    message.warning('Please fill in all fields.')
    return
  }

  if (form.value.password !== form.value.confirmPassword) {
    message.error('Passwords do not match.')
    return
  }

  loading.value = true
  try {
    await userApi.resetPassword({
      name: '',
      email: form.value.email,
      password: form.value.password,
      token: token.value,
    } as UserData)

    message.success('Password has been reset. You can now log in.')
    router.push('/login')
  } catch (error) {
    console.error(error)
    message.error('Failed to reset password. The link may be invalid or expired.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="reset-view">
    <n-card class="reset-card" size="small" bordered>
      <div class="reset-card__header">
        <n-h2>Set a new password</n-h2>
        <n-p depth="3"> Enter your email and a new password for your account. </n-p>
      </div>

      <n-form class="reset-form" @submit.prevent="handleSubmit">
        <n-form-item label="Email">
          <n-input v-model:value="form.email" type="email" placeholder="you@example.com" />
        </n-form-item>

        <n-form-item label="New password">
          <n-input
            v-model:value="form.password"
            type="password"
            placeholder="••••••••"
            show-password-on="click"
          />
        </n-form-item>

        <n-form-item label="Confirm password">
          <n-input
            v-model:value="form.confirmPassword"
            type="password"
            placeholder="Repeat new password"
            show-password-on="click"
          />
        </n-form-item>

        <div class="reset-form__actions">
          <n-button type="primary" attr-type="submit" :loading="loading" block>
            Reset password
          </n-button>
        </div>
      </n-form>
    </n-card>
  </div>
</template>

<style scoped>
.reset-view {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background: #f3f4f6;
}

.reset-card {
  width: 100%;
  max-width: 420px;
  box-shadow:
    0 10px 15px -3px rgba(15, 23, 42, 0.12),
    0 4px 6px -2px rgba(15, 23, 42, 0.08);
}

.reset-card__header {
  margin-bottom: 16px;
}

.reset-form__actions {
  margin-top: 8px;
}
</style>
