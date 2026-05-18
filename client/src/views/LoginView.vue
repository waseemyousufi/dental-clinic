<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { NCard, NForm, NFormItem, NInput, NButton, NH2, NP, useMessage } from 'naive-ui'

import userApi from '@api/user'
import type UserData from '@api/interfaces/User'

const router = useRouter()
const message = useMessage()
const { t } = useI18n()

const loading = ref(false)
const form = ref<Pick<UserData, 'email' | 'password'>>({
  email: '',
  password: '',
})

async function handleSubmit() {
  if (!form.value.email || !form.value.password) {
    message.warning(t('loginView.missingCredentials'))
    return
  }

  loading.value = true
  try {
    const { data } = await userApi.login({
      name: '', // backend ignores or derives this for login
      email: form.value.email,
      password: form.value.password,
    } as UserData)

    try {
      localStorage.setItem('user', JSON.stringify(data))
      window.location.reload() // Refresh to ensure all components read the new user data
    } catch {
      // ignore storage errors
    }

    message.success(t('loginView.loginSuccess'))
    router.push('/patients')
  } catch (error) {
    console.error(error)
    message.error(t('loginView.loginFailed'))
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-view">
    <n-card class="login-card" size="small" bordered>
      <div class="login-card__header">
        <n-h2>{{ t('loginView.title') }}</n-h2>
        <n-p depth="3"> {{ t('loginView.description') }} </n-p>
      </div>

      <n-form class="login-form" @submit.prevent="handleSubmit">
        <n-form-item :label="t('loginView.emailLabel')">
          <n-input v-model:value="form.email" type="text" :placeholder="t('loginView.emailPlaceholder')" autofocus />
        </n-form-item>

        <n-form-item :label="t('loginView.passwordLabel')">
          <n-input v-model:value="form.password" type="password" :placeholder="t('loginView.passwordPlaceholder')" show-password-on="click" />
        </n-form-item>

        <div class="login-form__actions">
          <n-button type="primary" attr-type="submit" :loading="loading" block> {{ t('loginView.signInButtonText') }} </n-button>
        </div>
      </n-form>
    </n-card>
  </div>
</template>

<style scoped>
.login-view {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px;
  background: #f3f4f6;
}

.login-card {
  width: 100%;
  max-width: 420px;
  box-shadow:
    0 10px 15px -3px rgba(15, 23, 42, 0.12),
    0 4px 6px -2px rgba(15, 23, 42, 0.08);
}

.login-card__header {
  margin-bottom: 16px;
}

.login-form__actions {
  margin-top: 8px;
}
</style>
