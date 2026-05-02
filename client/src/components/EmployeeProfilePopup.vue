<script setup lang="ts">
import { defineProps, computed, watchEffect } from 'vue';
import {
  NModal,
  NCard,
  NDescriptions,
  NDescriptionsItem,
  NAvatar,
  NSpace,
  NTag,
} from 'naive-ui';
import { Icon } from '@iconify/vue';
import type EmployeeData from '@api/interfaces/employee';

const props = defineProps<{
  show: boolean;
  employeeData: EmployeeData | null;
}>();

const emit = defineEmits(['update:show']);

const showModal = computed({
  get: () => props.show,
  set: (value) => emit('update:show', value),
});

const getExperienceTotalAmountDisplay = computed(() => {
  if (props.employeeData?.experience?.totalAmount) {
    const months = props.employeeData.experience.totalAmount;
    if (months >= 12) {
      const years = Math.floor(months / 12);
      const remainingMonths = months % 12;
      return `${years} year${years > 1 ? 's' : ''}${remainingMonths > 0 ? ` and ${remainingMonths} month${remainingMonths > 1 ? 's' : ''}` : ''}`;
    }
    return `${months} month${months > 1 ? 's' : ''}`;
  }
  return 'N/A';
});
</script>

<template>
  <n-modal v-model:show="showModal" content-scrollable style="max-width: 600px; max-height: 90vh;" preset="card"
    title="Employee Profile" class="employee-profile-modal">
    <n-card :bordered="false" class="profile-card">
      <div v-if="employeeData" style="text-transform: capitalize;">
        <div class="profile-header">
          <!-- {{ employeeData.profile_image_url }} -->
          <img :src="employeeData.profile_image_url"
            style="width: 96px; height: 96px; border-radius: 50%; object-fit: cover;"
            @error="(e) => console.log('Image failed to load:', e)"
            @load="() => console.log('Image loaded successfully!')" />
          <div class="header-details">
            <h2>{{ employeeData.fName }} {{ employeeData.lName }}</h2>
            <n-space size="small">
              <Icon :size="18" icon="mdi:briefcase-outline" />
              <n-tag type="info" round size="small">
                {{ employeeData.position }}
              </n-tag>
            </n-space>
            <n-space size="small" v-if="employeeData.speciality">
              <Icon width="20" height="20" icon="mdi:medical-bag" />
              <span>{{ employeeData.speciality }}</span>
            </n-space>
          </div>
        </div>

        <n-descriptions :bordered="false" :column="2" class="profile-details">
          <n-descriptions-item>
            <template #label>
              <n-space>
                <div class="icon-aligner">
                  <Icon :size="16" icon="mdi:email-outline" />
                  <p>Email</p>
                </div>
              </n-space>
            </template>
            <p class="indented">
              {{ employeeData.email || 'N/A' }}
            </p>
          </n-descriptions-item>
          <n-descriptions-item>
            <template #label>
              <n-space>
                <div class="icon-aligner">
                  <Icon :size="16" :icon="employeeData.gender === 'male' ? 'mdi:gender-male' : 'mdi:gender-female'" />
                  Gender
                </div>
              </n-space>
            </template>
            <p class="indented">
              {{ employeeData.gender || 'N/A' }}
            </p>
          </n-descriptions-item>
          <n-descriptions-item>
            <template #label>
              <n-space>
                <div class="icon-aligner">
                  <Icon :size="16" icon="mdi:calendar-account-outline" />
                  Hire Date
                </div>
              </n-space>
            </template>
            <p class="indented">
              {{ employeeData.hireDate || 'N/A' }}
            </p>
          </n-descriptions-item>
          <n-descriptions-item>
            <template #label>
              <n-space>
                <div class="icon-aligner">
                  <Icon :size="16" icon="mdi:certificate-outline" />
                  Qualification
                </div>
              </n-space>
            </template>
            <p class="indented">
              {{ employeeData.qualification || 'N/A' }}
            </p>
          </n-descriptions-item>
          <n-descriptions-item>
            <template #label>
              <n-space>
                <div class="icon-aligner">
                  <Icon :size="16" icon="clarity:license-outline-alerted" />
                  Medical License #
                </div>
              </n-space>
            </template>
            <p class="indented">
              {{ employeeData.midLicenseNum || 'N/A' }}
            </p>
          </n-descriptions-item>
          <n-descriptions-item>
            <template #label>
              <n-space>
                <div class="icon-aligner">
                  <Icon :size="16" icon="mdi:clock-outline" />
                  Work Hours
                </div>
              </n-space>
            </template>
            <p class="indented">
              {{ employeeData.workStartTime || 'N/A' }} - {{ employeeData.workEndTime || 'N/A' }}
            </p>
          </n-descriptions-item>
          <!-- <n-descriptions-item>
            <template #label>
              <n-space>
                <div class="icon-aligner">
                  <Icon :size="16" icon="mdi:office-building-outline" />
                  Workplace
                </div>
              </n-space>
            </template>
            <p class="indented">
              {{ employeeData.experience?.workplace || 'N/A' }}
            </p>
          </n-descriptions-item> -->
          <!-- <n-descriptions-item>
            <template #label>
              <n-space>
                <div class="icon-aligner">
                  <Icon :size="16" icon="hugeicons:doctor-02" />
                  Position
                </div>
              </n-space>
            </template>
            <p class="indented">
              {{ employeeData.experience?.position || 'N/A' }}
            </p>
          </n-descriptions-item> -->
          <!-- <n-descriptions-item>
            <template #label>
              <n-space>
                <div class="icon-aligner">
                  <Icon :size="16" icon="tdesign:user-time" />
                  Total Experience
                </div>
              </n-space>
            </template>
            <p class="indented">
              {{ getExperienceTotalAmountDisplay }}
            </p>
          </n-descriptions-item> -->
        </n-descriptions>
      </div>
      <div v-else>
        No employee data to display.
      </div>
    </n-card>
  </n-modal>
</template>
<style scoped>
.employee-profile-modal :deep(.n-card__content) {
  padding-top: 0;
}

.profile-details .iconify {
  font-size: 1.8em;
}

.profile-details .icon-aligner {
  display: flex;
  align-items: center;
  gap: 5px;
  margin-bottom: 3px;
  color: #555;
  opacity: .9;
  cursor: pointer;
}

.profile-details .icon-aligner:hover {
  opacity: 1;
}

.profile-details .indented {
  margin-left: .8em;
}

.profile-card {
  box-shadow: none;
}

.profile-header {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 16px 0;
  border-bottom: 1px solid #eee;
  /* Light separator */
  margin-bottom: 20px;
}

.header-details h2 {
  margin: 0 0 8px 0;
  font-size: 1.8em;
  color: #333;
}

.header-details .n-space {
  margin-bottom: 4px;
}

.header-details span {
  font-size: 0.9em;
  color: #555;
}

.profile-details :deep(.n-descriptions-item__label) {
  display: flex;
  align-items: center;
  gap: 5px;
  margin-bottom: 8px;
  color: #666;
  font-weight: bold;
}

.profile-details :deep(.n-descriptions-item__content) {
  font-size: 1em;
  color: #333;
}

.avatar-mimic {
  width: 96px;
  height: 96px;
  border-radius: 50%;
  object-fit: cover;
  display: block;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .profile-header {
    flex-direction: column;
    text-align: center;
    padding-bottom: 10px;
  }

  .header-details {
    margin-top: 15px;
  }

  .n-descriptions {
    grid-template-columns: 1fr !important;
    /* Stack descriptions on small screens */
  }
}
</style>
