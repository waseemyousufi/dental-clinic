<script setup lang="ts">
import { defineProps, computed } from 'vue';
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
// Assuming PatientData interface exists.
// If not, you might need to create it or adjust types accordingly.
import type PatientData from '@api/interfaces/patient';

const props = defineProps<{
  show: boolean;
  patientData: PatientData | null;
}>();

const emit = defineEmits(['update:show']);

const showModal = computed({
  get: () => props.show,
  set: (value) => emit('update:show', value),
});

// Removed employee-specific functions like getPositionLabel and getExperienceTotalAmountDisplay.
// If specific formatting for dates or other patient data is needed, it can be added here.
// For example, to format birthDate:
// const getFormattedBirthDate = computed(() => {
//   if (props.patientData?.birthDate) {
//     return new Date(props.patientData.birthDate).toLocaleDateString();
//   }
//   return 'N/A';
// });

</script>

<template>
  <n-modal v-model:show="showModal" content-scrollable style="max-width: 600px; max-height: 90vh;" preset="card"
    title="Patient Profile" class="patient-profile-modal">
    <n-card :bordered="false" class="profile-card">
      <div v-if="patientData">
        <div class="profile-header">
          <!-- <n-avatar :size="96" round src="https://07akioni.oss-cn-beijing.aliyuncs.com/07akioni.jpeg">
            <Icon icon="mdi:account" width="48" height="48" />
          </n-avatar> -->
          <div class="header-details">
            <h2>{{ patientData.fName }} {{ patientData.lName }}</h2>
          </div>
        </div>

        <n-descriptions :bordered="false" :column="2" class="profile-details">
          <n-descriptions-item>
            <template #label>
              <n-space align="center">
                <Icon :size="16" icon="solar:phone-broken" />
                Contact Number
              </n-space>
            </template>
            <p class="indented">
              {{ patientData.phone || 'N/A' }}
            </p>
          </n-descriptions-item>
          <n-descriptions-item>
            <template #label>
              <n-space align="center">
                <Icon :size="16" icon="streamline-ultimate:gender-hetero" />
                Gender
              </n-space>
            </template>
            <p class="indented">
              {{ patientData.gender ? patientData.gender.charAt(0).toUpperCase() + patientData.gender.slice(1) : 'N/A'
              }}
            </p>
          </n-descriptions-item>
          <!-- <n-descriptions-item>
            <template #label>
              <n-space align="center">
                <Icon :size="16" icon="mdi:cake-variant-outline" />
                Age
              </n-space>
            </template>
            <p class="indented">
              {{ patientData?.age || 'N/A' }}
            </p>
          </n-descriptions-item> -->
          <!-- <n-descriptions-item>
            <template #label>
              <n-space align="center">
                <Icon :size="16" icon="mdi:map-marker-outline" />
                Address
              </n-space>
            </template>
            <p class="indented">
              {{ patientData.address || 'N/A' }}
            </p>
          </n-descriptions-item> -->
          <n-descriptions-item>
            <template #label>
              <n-space align="center">
                <Icon :size="16" icon="healthicons:blood-ab-p" />
                Blood Type
              </n-space>
            </template>
            <p class="indented">
              {{ patientData.bloodType || 'N/A' }}
            </p>
          </n-descriptions-item>
          <n-descriptions-item>
            <template #label>
              <n-space align="center">
                <Icon :size="16" icon="mdi:allergy-outline" />
                Allergies
              </n-space>
            </template>
            <p class="indented">
              {{ patientData.allergies && patientData.allergies.length > 0 ? patientData.allergies.join(', ') : 'None'
              }}
            </p>
          </n-descriptions-item>
          <!-- <n-descriptions-item>
            <template #label>
              <n-space align="center">
                <Icon :size="16" icon="streamline-ultimate:insurance-hand-bold" />
                Insurance Provider
              </n-space>
            </template>
            <p class="indented">
              {{ patientData.insuranceProvider || 'N/A' }}
            </p>
          </n-descriptions-item> -->
        </n-descriptions>
      </div>
      <div v-else>
        No patient data to display.
      </div>
    </n-card>
  </n-modal>
</template>
<style scoped>
.patient-profile-modal :deep(.n-card__content) {
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
  color: #777;
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
