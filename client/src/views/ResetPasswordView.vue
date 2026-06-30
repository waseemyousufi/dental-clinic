<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { NCard, NForm, NFormItem, NInput, NButton, NH2, NP, useMessage } from 'naive-ui'

import userApi from '@api/user'

const router = useRouter()
const message = useMessage()
const { t } = useI18n()

const loading = ref(false)

const form = ref({
  previousPassword: '',
  newPassword: '',
  confirmPassword: '',
})

async function handleSubmit() {
  if (!form.value.previousPassword || !form.value.newPassword || !form.value.confirmPassword) {
    message.warning(t('resetPasswordView.requiredFieldsError'))
    return
  }

  if (form.value.newPassword !== form.value.confirmPassword) {
    message.error(t('resetPasswordView.passwordMismatchError'))
    return
  }

  loading.value = true
  try {
    await userApi.resetPassword({
      current_password: form.value.previousPassword,
      password: form.value.newPassword,
      password_confirmation: form.value.confirmPassword,
    })

    message.success(t('resetPasswordView.successMessage'))
    router.push('/login')
  } catch (error) {
    console.error(error)
    message.error(t('resetPasswordView.failureMessage'))
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="reset-view">
    <n-card class="reset-card" size="small" bordered>
      <div class="reset-card__header">
        <n-h2>{{ t('resetPasswordView.title') }}</n-h2>
        <n-p depth="3"> {{ t('resetPasswordView.description') }} </n-p>
      </div>

      <n-form class="reset-form" @submit.prevent="handleSubmit">
        <n-form-item :label="t('resetPasswordView.currentPasswordLabel')">
          <n-input
            v-model:value="form.previousPassword"
            type="password"
            :placeholder="t('resetPasswordView.currentPasswordPlaceholder')"
            show-password-on="click"
          />
        </n-form-item>

        <n-form-item :label="t('resetPasswordView.newPasswordLabel')">
          <n-input
            v-model:value="form.newPassword"
            type="password"
            :placeholder="t('resetPasswordView.newPasswordPlaceholder')"
            show-password-on="click"
          />
        </n-form-item>

        <n-form-item :label="t('resetPasswordView.confirmPasswordLabel')">
          <n-input
            v-model:value="form.confirmPassword"
            type="password"
            :placeholder="t('resetPasswordView.confirmPasswordPlaceholder')"
            show-password-on="click"
          />
        </n-form-item>

        <div class="reset-form__actions">
          <n-button type="primary" attr-type="submit" :loading="loading" block>
            {{ t('resetPasswordView.resetButtonText') }}
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
