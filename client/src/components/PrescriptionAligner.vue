<!-- BUG the prescription aligner should work with backend data -->

<template>
  <div class="prescription-aligner">
    <aside class="toolbox">
      <div class="section">
        <h3>Template</h3>
        <label class="control">
          <span>Upload prescription image</span>
          <input type="file" accept="image/*" @change="onUploadImage" />
        </label>

        <label class="control">
          <span>Paper size</span>
          <select v-model="paperSizeMode" @change="applyPaperSizeMode">
            <option value="image">Image size (default)</option>
            <option value="a5">A5</option>
            <option value="a4p">A4 Portrait</option>
            <option value="a4l">A4 Landscape</option>
            <option value="custom">Custom</option>
          </select>
        </label>

        <div class="size-grid">
          <label class="control">
            <span>Width (px)</span>
            <input v-model.number="paperWidth" type="number" min="100" step="1"
              :disabled="paperSizeMode !== 'custom'" />
          </label>
          <label class="control">
            <span>Height (px)</span>
            <input v-model.number="paperHeight" type="number" min="100" step="1"
              :disabled="paperSizeMode !== 'custom'" />
          </label>
        </div>

        <div class="button-row">
          <button type="button" @click="resetPaperToImageSize">Use image size</button>
          <button type="button" class="print-btn" @click="printNow">Print</button>
        </div>
      </div>

      <!-- CALIBRATION -->
      <div class="section">
        <h3>Calibration</h3>
        <input v-model.number="calibration.offsetX" type="number" step="0.1" placeholder="Offset X mm" />
        <input v-model.number="calibration.offsetY" style="margin-top: .4em;" type="number" step="0.1"
          placeholder="Offset Y mm" />
        <button @click="saveCalibration" style="margin-top: .8em;">Save</button>
      </div>

      <div class="section">
        <div class="section-header">
          <h3>Fields</h3>
          <button type="button" @click="addField">Add field</button>
        </div>

        <div class="field-list">
          <button v-for="field in fields" :key="field.id" type="button" class="field-list-item"
            :class="{ active: selectedFieldId === field.id }" @click="selectField(field.id)">
            {{ field.label }}
          </button>
        </div>
      </div>

      <div v-if="selectedField" class="section">
        <h3>Selected field</h3>

        <label class="control">
          <span>Field label</span>
          <input v-model="selectedField.label" type="text" />
        </label>

        <label class="control">
          <span>Text</span>
          <textarea v-model="selectedField.text" rows="4"></textarea>
        </label>

        <div class="size-grid">
          <label class="control">
            <span>Font size</span>
            <input v-model.number="selectedField.fontSize" type="number" min="6" max="120" step="1" />
          </label>

          <label class="control">
            <span>Direction</span>
            <select v-model="selectedField.direction">
              <option value="ltr">Left to right</option>
              <option value="rtl">Right to left</option>
            </select>
          </label>
        </div>

        <label class="control">
          <span>Font family</span>
          <select v-model="selectedField.fontFamily">
            <optgroup label="English fonts">
              <option v-for="font in englishFonts" :key="font.value" :value="font.value">
                {{ font.label }}
              </option>
            </optgroup>
            <optgroup label="Persian fonts">
              <option v-for="font in persianFonts" :key="font.value" :value="font.value">
                {{ font.label }}
              </option>
            </optgroup>
          </select>
        </label>

        <div class="size-grid">
          <label class="control">
            <span>X</span>
            <input v-model.number="selectedField.x" type="number" min="0" step="1" />
          </label>
          <label class="control">
            <span>Y</span>
            <input v-model.number="selectedField.y" type="number" min="0" step="1" />
          </label>
        </div>

        <div class="size-grid">
          <label class="control">
            <span>Width</span>
            <input v-model.number="selectedField.width" type="number" min="40" step="1" />
          </label>
          <label class="control">
            <span>Height</span>
            <input v-model.number="selectedField.height" type="number" min="24" step="1" />
          </label>
        </div>

        <div class="button-row">
          <button type="button" class="danger" @click="removeSelectedField">Remove field</button>
        </div>
      </div>
    </aside>

    <main class="workspace">
      <div ref="paperRef" class="paper" :style="paperStyle" @mousedown.self="clearSelection">
        <div v-for="field in fields" :key="field.id" class="field-box"
          :class="{ selected: selectedFieldId === field.id }" :style="fieldStyle(field)"
          @mousedown.stop.prevent="startDrag($event, field)" @click.stop="selectField(field.id)">
          <div class="field-preview">{{ field.text }}</div>
          <span class="resize-handle" @mousedown.stop.prevent="startResize($event, field)"></span>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue';

const paperRef = ref(null);
const paperImageSrc = ref('');
const paperSizeMode = ref('image');
const paperWidth = ref(0);
const paperHeight = ref(0);
const naturalImageSize = reactive({ width: 0, height: 0 });
const calibration = reactive({ offsetX: 0, offsetY: 0 })
const selectedFieldId = ref('');

const englishFonts = [
  { label: 'Arial', value: 'Arial, sans-serif' },
  { label: 'Georgia', value: 'Georgia, serif' },
  { label: 'Times New Roman', value: '"Times New Roman", serif' },
  { label: 'Verdana', value: 'Verdana, sans-serif' },
  { label: 'Courier New', value: '"Courier New", monospace' },
];

const persianFonts = [
  { label: 'Tahoma', value: 'Tahoma, sans-serif' },
  { label: 'Vazirmatn', value: 'Vazirmatn, Tahoma, sans-serif' },
  { label: 'Shabnam', value: 'Shabnam, Tahoma, sans-serif' },
  { label: 'Sahel', value: 'Sahel, Tahoma, sans-serif' },
  { label: 'B Nazanin', value: '"B Nazanin", Tahoma, serif' },
];

const fields = ref([
  createField({
    label: 'Patient name',
    text: 'Patient name',
    x: 60,
    y: 60,
    width: 220,
    height: 48,
    fontSize: 18,
    fontFamily: 'Arial, sans-serif',
    direction: 'ltr',
  }),
  createField({
    label: 'Clinic address',
    text: 'Clinic address',
    x: 60,
    y: 130,
    width: 300,
    height: 72,
    fontSize: 16,
    fontFamily: 'Georgia, serif',
    direction: 'ltr',
  }),
  createField({
    label: 'Clinic contact',
    text: 'Clinic contact',
    x: 60,
    y: 220,
    width: 240,
    height: 48,
    fontSize: 16,
    fontFamily: 'Verdana, sans-serif',
    direction: 'ltr',
  }),
]);

const selectedField = computed(() => fields.value.find((field) => field.id === selectedFieldId.value) || null);

const paperStyle = computed(() => ({
  width: `${paperWidth.value}px`,
  height: `${paperHeight.value}px`,
  backgroundImage: paperImageSrc.value ? `url(${paperImageSrc.value})` : 'none',
}));

let dragState = null;

function createField(overrides = {}) {
  return {
    id: `field_${Math.random().toString(36).slice(2, 10)}`,
    label: 'New field',
    text: '',
    x: 40,
    y: 40,
    width: 200,
    height: 48,
    fontSize: 16,
    fontFamily: 'Arial, sans-serif',
    direction: 'ltr',
    ...overrides,
  };
}

function selectField(id) {
  selectedFieldId.value = id;
}

function clearSelection() {
  selectedFieldId.value = '';
}

function addField() {
  const field = createField({
    label: `Field ${fields.value.length + 1}`,
    text: 'New text',
    x: 80,
    y: 80,
    width: 220,
    height: 48,
  });
  fields.value.push(field);
  selectedFieldId.value = field.id;
}

function removeSelectedField() {
  if (!selectedField.value) return;
  fields.value = fields.value.filter((field) => field.id !== selectedFieldId.value);
  selectedFieldId.value = fields.value[0]?.id || '';
}

function onUploadImage(event) {
  const file = event.target.files?.[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = (e) => {
    const src = String(e.target?.result || '');
    const img = new Image();
    img.onload = async () => {
      paperImageSrc.value = src;
      naturalImageSize.width = img.naturalWidth;
      naturalImageSize.height = img.naturalHeight;
      paperSizeMode.value = 'image';
      paperWidth.value = img.naturalWidth;
      paperHeight.value = img.naturalHeight;
      await nextTick();
    };
    img.src = src;
  };
  reader.readAsDataURL(file);
}

function applyPaperSizeMode() {
  switch (paperSizeMode.value) {
    case 'image':
      resetPaperToImageSize();
      break;
    case 'a5':
      paperWidth.value = 559;
      paperHeight.value = 794;
      break;
    case 'a4p':
      paperWidth.value = 794;
      paperHeight.value = 1123;
      break;
    case 'a4l':
      paperWidth.value = 1123;
      paperHeight.value = 794;
      break;
    case 'custom':
      if (!paperWidth.value || !paperHeight.value) {
        paperWidth.value = naturalImageSize.width || 800;
        paperHeight.value = naturalImageSize.height || 1100;
      }
      break;
    default:
      break;
  }
}

function resetPaperToImageSize() {
  if (naturalImageSize.width && naturalImageSize.height) {
    paperWidth.value = naturalImageSize.width;
    paperHeight.value = naturalImageSize.height;
    paperSizeMode.value = 'image';
  }
}

function mmToPx(mm) { return mm * 3.78 }


function fieldStyle(f) {
  return {
    left: f.x + mmToPx(calibration.offsetX) + 'px',
    top: f.y + mmToPx(calibration.offsetY) + 'px',
    width: f.width + 'px',
    height: f.height + 'px',
    fontSize: f.fontSize + 'px',
    fontFamily: f.fontFamily,
    direction: f.direction,
    position: 'absolute'
  }
}

function startDrag(event, field) {
  if (event.button !== 0) return;
  selectField(field.id);
  dragState = {
    type: 'drag',
    field,
    startX: event.clientX,
    startY: event.clientY,
    originalX: field.x,
    originalY: field.y,
  };
}

function startResize(event, field) {
  if (event.button !== 0) return;
  selectField(field.id);
  dragState = {
    type: 'resize',
    field,
    startX: event.clientX,
    startY: event.clientY,
    originalWidth: field.width,
    originalHeight: field.height,
  };
}

function handleMouseMove(event) {
  if (!dragState || !paperRef.value) return;

  const paperRect = paperRef.value.getBoundingClientRect();
  const field = dragState.field;

  if (dragState.type === 'drag') {
    const nextX = dragState.originalX + (event.clientX - dragState.startX);
    const nextY = dragState.originalY + (event.clientY - dragState.startY);
    field.x = clamp(nextX, 0, Math.max(0, paperRect.width - field.width));
    field.y = clamp(nextY, 0, Math.max(0, paperRect.height - field.height));
  }

  if (dragState.type === 'resize') {
    const nextWidth = dragState.originalWidth + (event.clientX - dragState.startX);
    const nextHeight = dragState.originalHeight + (event.clientY - dragState.startY);
    field.width = clamp(nextWidth, 40, Math.max(40, paperRect.width - field.x));
    field.height = clamp(nextHeight, 24, Math.max(24, paperRect.height - field.y));
  }
}

function handleMouseUp() {
  dragState = null;
}

function clamp(value, min, max) {
  return Math.min(Math.max(value, min), max);
}

function printNow() {
  const paper = paperRef.value;
  if (!paper) return;

  // Clone paper
  const clone = paper.cloneNode(true);

  // Remove background image (only print fields)
  clone.style.backgroundImage = 'none';

  // Clean UI elements
  clone.querySelectorAll('.resize-handle').forEach(el => el.remove());
  clone.querySelectorAll('.field-box').forEach(el => {
    el.style.border = 'none';
    el.style.background = 'transparent';
    el.style.boxShadow = 'none';
  });

  // Convert px → mm (approx 96 DPI)
  const pxToMm = (px) => px * 0.264583;
  const widthMM = pxToMm(paperWidth.value);
  const heightMM = pxToMm(paperHeight.value);

  // Create iframe instead of document.write
  const iframe = document.createElement('iframe');
  iframe.style.position = 'fixed';
  iframe.style.right = '0';
  iframe.style.bottom = '0';
  iframe.style.width = '0';
  iframe.style.height = '0';
  iframe.style.border = '0';
  document.body.appendChild(iframe);

  const doc = iframe.contentDocument || iframe.contentWindow.document;

  // Build document safely
  doc.open();
  doc.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <style>
        @page {
          size: ${widthMM}mm ${heightMM}mm;
          margin: 0;
        }

        html, body {
          margin: 0;
          padding: 0;
          overflow: hidden;
        }

        .paper {
          position: relative;
          width: ${widthMM}mm;
          height: ${heightMM}mm;
          page-break-after: avoid;
          page-break-inside: avoid;
        }

        .field-box {
          position: absolute;
          white-space: pre-wrap;
        }
      </style>
    </head>
    <body>
      ${clone.outerHTML}
    </body>
    </html>
  `);
  doc.close();

  iframe.onload = () => {
    iframe.contentWindow.focus();
    iframe.contentWindow.print();

    setTimeout(() => {
      document.body.removeChild(iframe);
    }, 1000);
  };
}

function saveCalibration() { localStorage.setItem('calibration', JSON.stringify(calibration)) }


onMounted(() => {
  if (!paperWidth.value || !paperHeight.value) {
    paperWidth.value = 800;
    paperHeight.value = 1100;
  }
  selectedFieldId.value = fields.value[0]?.id || '';
  window.addEventListener('mousemove', handleMouseMove);
  window.addEventListener('mouseup', handleMouseUp);

  const saved = localStorage.getItem('calibration')
  if (saved) Object.assign(calibration, JSON.parse(saved))
});

onBeforeUnmount(() => {
  window.removeEventListener('mousemove', handleMouseMove);
  window.removeEventListener('mouseup', handleMouseUp);
});
</script>

<style scoped>
.prescription-aligner {
  display: grid;
  grid-template-columns: 360px minmax(0, 1fr);
  gap: 16px;
  width: 100%;
  min-height: 100vh;
  background: #f4f7f6;
  color: #1f2937;
}

.toolbox {
  position: sticky;
  top: 0;
  /* height: 100vh; */
  overflow: auto;
  padding: 16px;
  background: #ffffff;
  border-right: 1px solid #e5e7eb;
  /* overflow: visible; */
}

.workspace {
  padding: 24px;
  overflow: auto;
}

.paper {
  position: relative;
  background-color: #fff;
  background-repeat: no-repeat;
  background-position: center center;
  background-size: 100% 100%;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
  border: 1px solid #ddd;
  margin: 0 auto;
}

.section {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 14px;
  margin-bottom: 14px;
}

.section h3 {
  margin: 0 0 12px;
  font-size: 15px;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 12px;
}

.section-header h3 {
  margin: 0;
}

.control {
  display: grid;
  gap: 6px;
  margin-bottom: 10px;
  font-size: 13px;
}

.control span {
  font-weight: 600;
  color: #374151;
}

input,
select,
textarea,
button {
  font: inherit;
}

input,
select,
textarea {
  width: 100%;
  box-sizing: border-box;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 10px;
  background: #fff;
  color: #111827;
}

textarea {
  resize: vertical;
  min-height: 92px;
}

button {
  border: none;
  border-radius: 10px;
  padding: 10px 14px;
  cursor: pointer;
  background: #2563eb;
  color: #fff;
  transition: transform 0.15s ease, opacity 0.15s ease;
}

button:hover {
  transform: translateY(-1px);
}

button.danger {
  background: #dc2626;
}

button.print-btn {
  background: #16a34a;
}

.button-row {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.size-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.field-list {
  display: grid;
  gap: 8px;
}

.field-list-item {
  text-align: left;
  background: #fff;
  color: #111827;
  border: 1px solid #d1d5db;
}

.field-list-item.active {
  border-color: #2563eb;
  box-shadow: inset 0 0 0 1px #2563eb;
}

.field-box {
  position: absolute;
  box-sizing: border-box;
  padding: 6px 8px;
  overflow: hidden;
  border: 1px dashed rgba(37, 99, 235, 0.45);
  background: rgba(255, 255, 255, 0.15);
  cursor: move;
  user-select: none;
}

.field-box.selected {
  border-color: #2563eb;
  box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.25);
}

.field-preview {
  width: 100%;
  height: 100%;
  white-space: pre-wrap;
  word-break: break-word;
  line-height: 1.35;
}

.resize-handle {
  position: absolute;
  right: 0;
  bottom: 0;
  width: 12px;
  height: 12px;
  background: #2563eb;
  cursor: nwse-resize;
}

@media print {

  .toolbox,
  .resize-handle,
  .field-list,
  .section-header button,
  .button-row,
  .field-box.selected {
    display: none !important;
  }

  .prescription-aligner {
    display: block;
    background: #fff;
  }

  .workspace {
    padding: 0;
  }

  .paper {
    margin: 0 !important;
    box-shadow: none !important;
    border: none !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  .field-box {
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
    cursor: default !important;
  }

  @page {
    margin: 0;
  }
}
</style>









































<!--

<template>
  <div class="prescription-aligner">

    <aside class="toolbox">


      <div class="section">
        <h3>Template</h3>

        <input type="file" @change="onUploadImage" />

        <label>Paper size</label>
        <select v-model="paperSizeMode" @change="applyPaperSizeMode">
          <option value="image">Image size</option>
          <option value="a5">A5</option>
          <option value="a4p">A4 Portrait</option>
          <option value="a4l">A4 Landscape</option>
          <option value="custom">Custom</option>
        </select>

        <div>
          <input v-model.number="paperWidth" type="number" :disabled="paperSizeMode !== 'custom'" />
          <input v-model.number="paperHeight" type="number" :disabled="paperSizeMode !== 'custom'" />
        </div>

        <button @click="printNow">Print</button>
      </div>


      <div class="section">
        <h3>Calibration</h3>
        <input v-model.number="calibration.offsetX" type="number" step="0.1" placeholder="Offset X mm" />
        <input v-model.number="calibration.offsetY" type="number" step="0.1" placeholder="Offset Y mm" />
        <button @click="saveCalibration">Save</button>
      </div>

      <div class="section">
        <h3>Fields</h3>
        <button @click="addField">Add</button>

        <div v-for="f in fields" :key="f.id" @click="selectField(f.id)">
          {{ f.label }}
        </div>
      </div>

      <div v-if="selectedField" class="section">
        <h3>Edit Field</h3>

        <input v-model="selectedField.label" placeholder="Label" />
        <textarea v-model="selectedField.text" />

        <input v-model.number="selectedField.fontSize" type="number" placeholder="Font size" />

        <select v-model="selectedField.fontFamily">
          <optgroup label="English">
            <option v-for="f in englishFonts" :value="f">{{ f }}</option>
          </optgroup>
          <optgroup label="Persian">
            <option v-for="f in persianFonts" :value="f">{{ f }}</option>
          </optgroup>
        </select>

        <select v-model="selectedField.direction">
          <option value="ltr">LTR</option>
          <option value="rtl">RTL</option>
        </select>
      </div>

    </aside>

    <main class="workspace">
      <div ref="paperRef" class="paper" :style="paperStyle">

        <div v-for="f in fields" :key="f.id" class="field-box" :style="fieldStyle(f)" @mousedown="startDrag($event, f)">

          {{ f.text }}

          <div class="resize" @mousedown.stop="startResize($event, f)"></div>

        </div>

      </div>
    </main>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'

const paperRef = ref(null)
const paperWidth = ref(800)
const paperHeight = ref(1100)
const paperImageSrc = ref('')
const paperSizeMode = ref('image')

const calibration = reactive({ offsetX: 0, offsetY: 0 })

const englishFonts = ['Arial','Georgia','Times New Roman','Verdana','Courier New']
const persianFonts = ['Tahoma','Vazirmatn','Shabnam','Sahel','B Nazanin']

const fields = ref([
  { id:1,label:'Patient',text:'Patient',x:50,y:50,width:200,height:50,fontSize:16,fontFamily:'Arial',direction:'ltr' }
])

const selectedFieldId = ref(1)
const selectedField = computed(()=>fields.value.find(f=>f.id===selectedFieldId.value))

function selectField(id){selectedFieldId.value=id}
function addField(){fields.value.push({id:Date.now(),label:'New',text:'Text',x:80,y:80,width:200,height:50,fontSize:16,fontFamily:'Arial',direction:'ltr'})}

function mmToPx(mm){return mm*3.78}

function fieldStyle(f){
  return {
    left: f.x + mmToPx(calibration.offsetX) + 'px',
    top: f.y + mmToPx(calibration.offsetY) + 'px',
    width: f.width+'px',
    height: f.height+'px',
    fontSize: f.fontSize+'px',
    fontFamily: f.fontFamily,
    direction: f.direction,
    position:'absolute'
  }
}

const paperStyle = computed(()=>({
  width: paperWidth.value+'px',
  height: paperHeight.value+'px',
  backgroundImage: paperImageSrc.value ? `url(${paperImageSrc.value})`:'none',
  backgroundSize:'100% 100%'
}))

function onUploadImage(e){
  const file=e.target.files[0]
  const r=new FileReader()
  r.onload=ev=>{
    const img=new Image()
    img.onload=()=>{
      paperWidth.value=img.width
      paperHeight.value=img.height
      paperImageSrc.value=ev.target.result
    }
    img.src=ev.target.result
  }
  r.readAsDataURL(file)
}

function applyPaperSizeMode(){
  if(paperSizeMode.value==='a5'){paperWidth.value=559;paperHeight.value=794}
  if(paperSizeMode.value==='a4p'){paperWidth.value=794;paperHeight.value=1123}
  if(paperSizeMode.value==='a4l'){paperWidth.value=1123;paperHeight.value=794}
}

function saveCalibration(){localStorage.setItem('calibration',JSON.stringify(calibration))}

onMounted(()=>{
  const saved=localStorage.getItem('calibration')
  if(saved) Object.assign(calibration,JSON.parse(saved))
})

let drag=null
function startDrag(e,f){drag={type:'move',f,startX:e.clientX,startY:e.clientY,ox:f.x,oy:f.y}}
function startResize(e,f){drag={type:'resize',f,startX:e.clientX,startY:e.clientY,ow:f.width,oh:f.height}}

window.addEventListener('mousemove',e=>{
  if(!drag) return
  if(drag.type==='move'){
    drag.f.x = drag.ox + (e.clientX-drag.startX)
    drag.f.y = drag.oy + (e.clientY-drag.startY)
  }
  if(drag.type==='resize'){
    drag.f.width = drag.ow + (e.clientX-drag.startX)
    drag.f.height = drag.oh + (e.clientY-drag.startY)
  }
})
window.addEventListener('mouseup',()=>drag=null)

function printNow(){
  const clone = paperRef.value.cloneNode(true)
  clone.style.backgroundImage='none'

  const iframe=document.createElement('iframe')
  document.body.appendChild(iframe)

  const doc=iframe.contentDocument
  const pxToMm=px=>px*0.264583

  doc.open()
  doc.write(`
    <style>
      @page{size:${pxToMm(paperWidth.value)}mm ${pxToMm(paperHeight.value)}mm;margin:0}
      body{margin:0}
      .field-box{border:none!important;background:transparent!important}
    </style>
    ${clone.outerHTML}
  `)
  doc.close()

  iframe.onload=()=>{
    iframe.contentWindow.print()
    setTimeout(()=>document.body.removeChild(iframe),500)
  }
}
</script>

<style scoped>
.prescription-aligner {
  display: flex
}

.toolbox {
  width: 280px
}

.paper {
  position: relative
}

.field-box {
  border: 1px dashed red
}

.resize {
  width: 10px;
  height: 10px;
  background: blue;
  position: absolute;
  right: 0;
  bottom: 0;
  cursor: nwse-resize
}

@media print {
  .resize {
    display: none !important
  }

  .field-box {
    border: none !important
  }
}
</style> -->
