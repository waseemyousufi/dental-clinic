<script setup lang="js">
import { ref } from 'vue'
import {
  NDatePicker,
  NH3,
  NH2,
  NTimePicker,
  NInputNumber,
  NSelect,
  NButton,
  NP,
  NTreeSelect,
  NInputGroup,
  NInput,
} from 'naive-ui'

const form = ref({
  fName: '',
  lName: '',
  gender: '',
  email: '',
  hireDate: null,
  workStartTime: null,
  workEndTime: null,
  speciality: '',
  qualification: '',
  midLicenseNum: '',
  positionId: null,
  experience: {
    workspace: '',
    position: '',
    totalAmount: null,
  },
})

const handleSubmit = () => {
  // form.value.email = 'bro';
  console.log(form)
}
</script>

<template>
  <div class="sign-in">
    <br />
    <n-h2 class="title">Register New User.</n-h2>

    <n-p>Email</n-p>
    <n-input
      type="email"
      v-model:value="form.email"
      required
      autofocus
      placeholder="e.g. jhondoe@mail.com"
    ></n-input>

    <n-p>full name</n-p>
    <n-input-group>
      <n-input required v-model:value="form.fName" placeholder="e.g. Jhon"></n-input>
      <n-input required v-model:value="form.lName" placeholder="e.g. Doe"></n-input>
    </n-input-group>
    <br /><br />
    <n-tree-select
      placeholder="gender"
      required
      v-model:value="form.gender"
      :options="[
        { label: 'male', key: 1 },
        { label: 'female', key: 2 },
      ]"
    ></n-tree-select>
    <br />
    <n-date-picker required v-model:value="form.hireDate" placeholder="hire date"></n-date-picker>
    <n-p>specialty</n-p>
    <n-input required v-model:value="form.speciality" placeholder="e.g. Dental Doctor"></n-input>
    <n-p>qualification</n-p>
    <n-input required v-model:value="form.qualification" placeholder="e.g. Bachelor"></n-input>
    <n-p>medical license number</n-p>
    <n-input required v-model:value="form.midLicenseNum" placeholder="e.g. AFG-DEN-1023"></n-input>
    <br /><br />

    <n-tree-select
      placeholder="Position"
      required
      v-model:value="form.positionId"
      :options="[
        { label: 'Doctor', key: 1 },
        { label: 'Doctor Assistant', key: 2 },
      ]"
    ></n-tree-select>
    <br />
    <n-input-group>
      <n-time-picker
        required
        v-model:value="form.workStartTime"
        placeholder="work start time"
      ></n-time-picker>
      <n-time-picker
        required
        v-model:value="form.workEndTime"
        placeholder="work end time"
      ></n-time-picker>
    </n-input-group>

    <n-h3>Experience</n-h3>

    <n-input placeholder="workspace" required v-model:value="form.experience.workspace"></n-input>
    <br /><br />
    <n-input placeholder="position" required v-model:value="form.experience.position"></n-input>
    <br /><br />
    <n-input-number
      placeholder="total amount"
      required
      v-model:value="form.experience.totalAmount"
    ></n-input-number>

    <n-button @click="handleSubmit">Create User</n-button>
  </div>
</template>
