<template>
  <div class="receipt-shell">
    <div class="toolbar no-print">
      <button class="btn" type="button" @click="printReceipt">
        <Icon icon="solar:printer-linear" />
        Print
      </button>
      <button class="btn btn-primary" type="button" @click="$emit('save')">
        <Icon icon="solar:download-linear" />
        Save PDF
      </button>
      <button class="btn" type="button" @click="$emit('share')">
        <Icon icon="solar:share-linear" />
        Share
      </button>
    </div>

    <article class="sheet" ref="sheetRef">
      <header class="page-header">
        <div class="brand">
          <div class="brand-mark" aria-hidden="true">
            <Icon icon="solar:health-linear" />
          </div>
          <div class="title-block">
            <h1>{{ receipt.title }}</h1>
            <p>{{ receipt.subtitle }}</p>
          </div>
        </div>

        <div class="meta">
          <div><strong>Receipt No:</strong> {{ receipt.receiptNo }}</div>
          <div><strong>PO No:</strong> {{ receipt.poNo }}</div>
          <div><strong>Date:</strong> {{ receipt.createdAt }}</div>
        </div>
      </header>

      <section class="status-card">
        <div class="status-left">
          <div class="status-badge" :class="statusClass(receipt.status)">
            <Icon :icon="statusIcon(receipt.status)" />
          </div>
          <div>
            <h2>{{ receipt.statusTitle }}</h2>
            <p>{{ receipt.statusDescription }}</p>
          </div>
        </div>

        <div class="pill">
          <Icon :icon="statusPillIcon(receipt.status)" />
          {{ receipt.statusLabel }}
        </div>
      </section>

      <section class="info-grid">
        <div class="card">
          <h3>Supplier</h3>
          <div class="kv">
            <div class="kv-row">
              <div class="key">
                <Icon icon="solar:shop-2-linear" /> Name
              </div>
              <div class="value">{{ receipt.supplierName }}</div>
            </div>
            <div class="kv-row">
              <div class="key">
                <Icon icon="solar:map-point-linear" /> Branch
              </div>
              <div class="value">{{ receipt.branchName }}</div>
            </div>
            <div class="kv-row">
              <div class="key">
                <Icon icon="solar:user-check-linear" /> Received by
              </div>
              <div class="value">{{ receipt.receivedBy }}</div>
            </div>
          </div>
        </div>

        <div class="card">
          <h3>Status</h3>
          <div class="kv">
            <div class="kv-row">
              <div class="key">
                <Icon icon="solar:clipboard-list-linear" /> Ordered
              </div>
              <div class="value">{{ receipt.summary.orderedLineItems }} line items</div>
            </div>
            <div class="kv-row">
              <div class="key">
                <Icon icon="solar:box-linear" /> Received
              </div>
              <div class="value">{{ receipt.summary.receivedLineItems }} line items</div>
            </div>
            <div class="kv-row">
              <div class="key">
                <Icon icon="solar:danger-triangle-linear" /> Pending
              </div>
              <div class="value">{{ receipt.summary.pendingLineItems }} line item(s)</div>
            </div>
          </div>
        </div>
      </section>

      <section class="items-card">
        <div class="items-head">
          <div>Material</div>
          <div class="num">Ordered</div>
          <div class="num">Received</div>
          <div>Batch</div>
          <div>Expiry</div>
          <div>Status</div>
        </div>

        <div v-for="item in receipt.items" :key="item.id" class="item-row">
          <div class="product">
            <div class="thumb" aria-hidden="true">IMG</div>
            <div>
              <p class="name">{{ item.name }}</p>
              <p class="sub">{{ item.note }}</p>
            </div>
          </div>
          <div class="num">{{ item.ordered }}</div>
          <div class="num">{{ item.received }}</div>
          <div>{{ item.batch }}</div>
          <div>{{ item.expiry }}</div>
          <div>
            <span class="tag" :class="tagClass(item.status)">
              <Icon :icon="tagIcon(item.status)" />
              {{ item.statusLabel }}
            </span>
          </div>
        </div>
      </section>

      <section class="bottom-grid">
        <div class="notes-card">
          <h3>Receiving Notes</h3>
          <p>
            Use this layout for a clinic goods-received note. It keeps the form compact for A5 while still showing the
            details staff need for stock entry, batch traceability, and exceptions.
          </p>
          <ul>
            <li>Record shortages instead of overwriting the PO.</li>
            <li>Keep batch and expiry visible for clinical stock.</li>
            <li>Use the status field for partial or pending delivery.</li>
          </ul>
        </div>

        <div class="summary-card">
          <h3>Summary</h3>
          <div class="summary-grid">
            <div class="summary-row"><span>Line items</span><strong>{{ receipt.summary.lineItems }}</strong></div>
            <div class="summary-row"><span>Received</span><strong>{{ receipt.summary.receivedLineItems }}</strong></div>
            <div class="summary-row"><span>Pending</span><strong>{{ receipt.summary.pendingLineItems }}</strong></div>
            <div class="summary-row final"><span>Receipt status</span><strong>{{ receipt.summary.finalStatus }}</strong>
            </div>
          </div>
        </div>
      </section>

      <footer class="footer-grid">
        <div class="sig">
          <div class="label">
            <Icon icon="solar:user-linear" /> Received by
          </div>
          <div class="value">{{ receipt.receivedBy }}</div>
          <div class="line"></div>
        </div>
        <div class="sig">
          <div class="label">
            <Icon icon="solar:stethoscope-linear" /> QC / Pharmacy
          </div>
          <div class="value">{{ receipt.qcBy }}</div>
          <div class="line"></div>
        </div>
        <div class="sig">
          <div class="label">
            <Icon icon="solar:building-linear" /> Supplier
          </div>
          <div class="value">{{ receipt.supplierSignOff }}</div>
          <div class="line"></div>
        </div>
        <div class="footer-note">For internal clinic inventory control and audit trail only.</div>
      </footer>
    </article>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Icon } from '@iconify/vue';

type ReceiptStatus = 'received' | 'partial' | 'pending';
type ItemStatus = 'ok' | 'warn' | 'danger';

interface ReceiptItem {
  id: string;
  name: string;
  note: string;
  ordered: string;
  received: string;
  batch: string;
  expiry: string;
  status: ItemStatus;
  statusLabel: string;
}

interface ReceiptSummary {
  lineItems: number;
  orderedLineItems: number;
  receivedLineItems: number;
  pendingLineItems: number;
  finalStatus: string;
}

interface ReceiptData {
  title: string;
  subtitle: string;
  receiptNo: string;
  poNo: string;
  createdAt: string;
  supplierName: string;
  branchName: string;
  receivedBy: string;
  qcBy: string;
  supplierSignOff: string;
  status: ReceiptStatus;
  statusTitle: string;
  statusDescription: string;
  statusLabel: string;
  summary: ReceiptSummary;
  items: ReceiptItem[];
}

const props = withDefaults(
  defineProps<{
    receipt?: ReceiptData;
  }>(),
  {
    receipt: () => ({
      title: 'Clinic Supply Receipt',
      subtitle: 'Purchase order receiving record for branch inventory and audit tracking.',
      receiptNo: 'GRN-2025-00128',
      poNo: 'PO-2025-00491',
      createdAt: '15 Apr 2026 • 10:02',
      supplierName: 'MediCore Pharmaceuticals Ltd.',
      branchName: 'Bergen Central Clinic',
      receivedBy: 'A. Eriksen',
      qcBy: 'Verified',
      supplierSignOff: 'Pending sign-off',
      status: 'partial',
      statusTitle: 'Goods Received Successfully',
      statusDescription: 'Delivery checked against the PO and recorded into branch stock.',
      statusLabel: 'Partially Received',
      summary: {
        lineItems: 5,
        orderedLineItems: 5,
        receivedLineItems: 4,
        pendingLineItems: 1,
        finalStatus: 'Open',
      },
      items: [
        {
          id: '1',
          name: 'Disposable Syringe 5ml',
          note: 'Box of 100 • Room temp storage',
          ordered: '30',
          received: '30',
          batch: 'DS-5ML-24091',
          expiry: 'Nov 2027',
          status: 'ok',
          statusLabel: 'OK',
        },
        {
          id: '2',
          name: 'Paracetamol 500mg',
          note: 'Carton • Batch & expiry tracked',
          ordered: '20',
          received: '18',
          batch: 'PCM-500-77812',
          expiry: 'Aug 2026',
          status: 'warn',
          statusLabel: 'Short',
        },
        {
          id: '3',
          name: 'Sterile Gauze Pads',
          note: 'Pack of 50 • Sealed box',
          ordered: '40',
          received: '40',
          batch: 'SGP-50-33104',
          expiry: 'Feb 2028',
          status: 'ok',
          statusLabel: 'OK',
        },
        {
          id: '4',
          name: 'Nitrile Gloves Size M',
          note: 'Box of 100 • General exam use',
          ordered: '50',
          received: '50',
          batch: 'NG-M-00991',
          expiry: 'Jan 2028',
          status: 'ok',
          statusLabel: 'OK',
        },
        {
          id: '5',
          name: 'Insulin Pen Needles',
          note: 'Pack of 100 • Pending from supplier',
          ordered: '25',
          received: '0',
          batch: 'IPN-100-55218',
          expiry: 'Dec 2027',
          status: 'danger',
          statusLabel: 'Pend.',
        },
      ],
    }),
  }
);

const emit = defineEmits<{
  (e: 'save'): void;
  (e: 'share'): void;
}>();

const sheetRef = ref<HTMLElement | null>(null);

const printReceipt = (): void => {
  window.print();
};

const statusClass = (status: ReceiptStatus): string => {
  return `status-${status}`;
};

const statusIcon = (status: ReceiptStatus): string => {
  switch (status) {
    case 'received':
      return 'solar:check-circle-bold';
    case 'partial':
      return 'solar:clock-circle-linear';
    case 'pending':
      return 'solar:danger-triangle-linear';
    default:
      return 'solar:check-circle-bold';
  }
};

const statusPillIcon = (status: ReceiptStatus): string => {
  switch (status) {
    case 'received':
      return 'solar:check-circle-linear';
    case 'partial':
      return 'solar:clock-circle-linear';
    case 'pending':
      return 'solar:danger-triangle-linear';
    default:
      return 'solar:check-circle-linear';
  }
};

const tagClass = (status: ItemStatus): string => {
  return `tag-${status}`;
};

const tagIcon = (status: ItemStatus): string => {
  switch (status) {
    case 'ok':
      return 'solar:check-circle-bold';
    case 'warn':
      return 'solar:clock-circle-linear';
    case 'danger':
      return 'solar:close-circle-linear';
    default:
      return 'solar:check-circle-bold';
  }
};
</script>

<style scoped>
:global(*) {
  box-sizing: border-box;
}

:global(html),
:global(body) {
  margin: 0;
  padding: 0;
}

.receipt-shell {
  --paper: #f7fbf5;
  --ink: #203229;
  --muted: #64756c;
  --line: rgba(32, 50, 41, 0.14);
  --accent: #169a8a;
  --accent-soft: rgba(22, 154, 138, 0.10);
  --warn: #b87727;
  --danger: #bc5454;
  min-height: 100vh;
  padding: 18px;
  background:
    radial-gradient(circle at 9% 11%, rgba(82, 163, 255, 0.24) 0 5%, transparent 6%),
    radial-gradient(circle at 91% 10%, rgba(82, 163, 255, 0.24) 0 5%, transparent 6%),
    radial-gradient(circle at 10% 90%, rgba(82, 163, 255, 0.16) 0 4%, transparent 5%),
    radial-gradient(circle at 90% 89%, rgba(82, 163, 255, 0.16) 0 4%, transparent 5%),
    linear-gradient(180deg, #dff0e1 0%, #dbeadf 100%);
  color: var(--ink);
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

.toolbar {
  width: min(100%, 980px);
  margin: 0 auto 12px;
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.btn {
  border: none;
  border-radius: 999px;
  padding: 8px 11px;
  background: white;
  color: var(--ink);
  font-weight: 700;
  font-size: 11px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.10);
  cursor: pointer;
}

.btn-primary {
  background: linear-gradient(135deg, #1cb49e, #169a8a);
  color: white;
}

.sheet {
  width: 148mm;
  min-height: 210mm;
  margin: 0 auto;
  background: var(--paper);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 14px 44px rgba(0, 0, 0, 0.18);
  position: relative;
}

.sheet::before,
.sheet::after {
  content: "";
  position: absolute;
  top: 50%;
  width: 14px;
  height: 28px;
  background: #0f1112;
  transform: translateY(-50%);
  opacity: 0.95;
}

.sheet::before {
  left: -1px;
  border-radius: 0 999px 999px 0;
}

.sheet::after {
  right: -1px;
  border-radius: 999px 0 0 999px;
}

.page-header {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 12px;
  align-items: start;
  padding: 20px 18px 10px;
  border-bottom: 1px dashed var(--line);
}

.brand {
  display: flex;
  gap: 10px;
  align-items: center;
  min-width: 0;
}

.brand-mark {
  width: 42px;
  height: 42px;
  border-radius: 13px;
  background: linear-gradient(135deg, rgba(22, 154, 138, 0.16), rgba(43, 124, 255, 0.10));
  border: 1px solid rgba(22, 154, 138, 0.12);
  display: grid;
  place-items: center;
  flex: 0 0 auto;
}

.brand-mark :deep(svg),
.brand-mark :deep(i) {
  font-size: 24px;
  color: var(--accent);
}

.title-block h1 {
  margin: 0;
  font-size: 18px;
  line-height: 1.1;
  letter-spacing: -0.02em;
}

.title-block p {
  margin: 4px 0 0;
  font-size: 10.5px;
  color: var(--muted);
  line-height: 1.35;
}

.meta {
  text-align: right;
  font-size: 10.5px;
  color: var(--muted);
  line-height: 1.55;
  white-space: nowrap;
}

.meta strong {
  color: var(--ink);
}

.status-card {
  margin: 10px 18px 0;
  border-radius: 14px;
  border: 1px solid rgba(22, 154, 138, 0.16);
  background: var(--accent-soft);
  padding: 11px 12px;
  display: flex;
  justify-content: space-between;
  gap: 10px;
  align-items: center;
}

.status-left {
  display: flex;
  gap: 9px;
  align-items: center;
  min-width: 0;
}

.status-badge {
  width: 28px;
  height: 28px;
  border-radius: 999px;
  background: var(--accent);
  color: white;
  display: grid;
  place-items: center;
  flex: 0 0 auto;
}

.status-badge :deep(svg),
.status-badge :deep(i) {
  font-size: 16px;
}

.status-badge.status-received {
  background: #169a8a;
}

.status-badge.status-partial {
  background: #b87727;
}

.status-badge.status-pending {
  background: #bc5454;
}

.status-card h2 {
  margin: 0;
  font-size: 13.5px;
}

.status-card p {
  margin: 2px 0 0;
  font-size: 10.5px;
  color: var(--muted);
  line-height: 1.35;
}

.pill {
  flex: 0 0 auto;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 10px;
  border-radius: 999px;
  background: white;
  border: 1px solid var(--line);
  font-size: 10.5px;
  font-weight: 700;
  white-space: nowrap;
}

.info-grid {
  margin: 10px 18px 0;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.card,
.items-card,
.notes-card,
.summary-card {
  background: rgba(255, 255, 255, 0.50);
  border: 1px solid rgba(32, 50, 41, 0.08);
  border-radius: 14px;
}

.card {
  padding: 10px;
}

.card h3,
.notes-card h3,
.summary-card h3 {
  margin: 0 0 8px;
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--muted);
}

.kv {
  display: grid;
  gap: 7px;
  font-size: 10.5px;
}

.kv-row {
  display: grid;
  grid-template-columns: 1fr 1.1fr;
  gap: 8px;
  align-items: start;
}

.key {
  color: var(--muted);
  display: flex;
  align-items: center;
  gap: 6px;
  min-width: 0;
}

.key :deep(svg),
.key :deep(i) {
  color: var(--accent);
  font-size: 14px;
  flex: 0 0 auto;
}

.value {
  color: var(--ink);
  font-weight: 700;
  text-align: right;
}

.items-card {
  margin: 10px 18px 0;
  overflow: hidden;
}

.items-head,
.item-row {
  display: grid;
  grid-template-columns: 2.1fr 0.62fr 0.62fr 0.9fr 0.72fr 0.56fr;
  gap: 8px;
  align-items: center;
}

.items-head {
  padding: 9px 10px;
  background: rgba(22, 154, 138, 0.08);
  border-bottom: 1px solid rgba(32, 50, 41, 0.08);
  color: var(--muted);
  font-size: 9px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 800;
}

.item-row {
  padding: 9px 10px;
  border-bottom: 1px solid rgba(32, 50, 41, 0.08);
  font-size: 10.5px;
}

.item-row:last-child {
  border-bottom: none;
}

.product {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.thumb {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  border: 1px dashed rgba(32, 50, 41, 0.18);
  background: linear-gradient(135deg, rgba(22, 154, 138, 0.10), rgba(43, 124, 255, 0.08));
  color: var(--muted);
  display: grid;
  place-items: center;
  flex: 0 0 auto;
  font-size: 9px;
  text-align: center;
  line-height: 1;
  padding: 2px;
}

.product .name {
  margin: 0;
  font-weight: 800;
  font-size: 10.5px;
  line-height: 1.2;
}

.product .sub {
  margin: 2px 0 0;
  color: var(--muted);
  font-size: 9px;
  line-height: 1.25;
}

.num {
  text-align: right;
  font-variant-numeric: tabular-nums;
}

.tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 5px 7px;
  border-radius: 999px;
  font-size: 8.5px;
  font-weight: 800;
  white-space: nowrap;
}

.tag :deep(svg),
.tag :deep(i) {
  font-size: 11px;
}

.tag-ok {
  background: rgba(22, 154, 138, 0.10);
  color: var(--accent);
}

.tag-warn {
  background: rgba(184, 119, 39, 0.12);
  color: var(--warn);
}

.tag-danger {
  background: rgba(188, 84, 84, 0.10);
  color: var(--danger);
}

.bottom-grid {
  margin: 10px 18px 0;
  display: grid;
  grid-template-columns: 1.15fr 0.85fr;
  gap: 10px;
  align-items: start;
}

.notes-card,
.summary-card {
  padding: 10px;
}

.notes-card p {
  margin: 0;
  font-size: 10.2px;
  line-height: 1.5;
  color: var(--ink);
}

.notes-card ul {
  margin: 7px 0 0;
  padding-left: 16px;
  font-size: 9.8px;
  line-height: 1.45;
  color: var(--ink);
}

.notes-card li {
  margin: 2px 0;
}

.summary-grid {
  display: grid;
  gap: 7px;
  font-size: 10.2px;
}

.summary-row {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 10px;
  align-items: center;
}

.summary-row strong {
  font-size: 11px;
}

.summary-row.final {
  padding-top: 7px;
  margin-top: 4px;
  border-top: 1px solid rgba(32, 50, 41, 0.12);
  font-size: 11px;
  font-weight: 900;
}

.footer-grid {
  margin: 10px 18px 0;
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 8px;
  padding-bottom: 14px;
}

.sig {
  min-height: 44px;
  border: 1px solid rgba(32, 50, 41, 0.10);
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.46);
  padding: 8px;
  display: grid;
  align-content: end;
  gap: 3px;
}

.sig .label {
  font-size: 9px;
  color: var(--muted);
  display: flex;
  align-items: center;
  gap: 5px;
}

.sig .label :deep(svg),
.sig .label :deep(i) {
  color: var(--accent);
  font-size: 13px;
}

.sig .value {
  font-size: 10px;
  font-weight: 800;
}

.sig .line {
  border-top: 1px solid rgba(32, 50, 41, 0.16);
  margin-top: 10px;
}

.footer-note {
  grid-column: 1 / -1;
  text-align: center;
  font-size: 9px;
  color: var(--muted);
  margin-top: 2px;
}

@media (max-width: 820px) {
  .receipt-shell {
    padding: 12px;
  }

  .toolbar {
    justify-content: center;
    flex-wrap: wrap;
  }

  .sheet {
    width: 100%;
    max-width: 148mm;
  }
}

@media print {
  .receipt-shell {
    padding: 0;
    background: white;
  }

  .toolbar,
  .no-print {
    display: none !important;
  }

  .sheet {
    width: 148mm;
    height: 210mm;
    min-height: 210mm;
    margin: 0;
    border-radius: 0;
    box-shadow: none;
    overflow: hidden;
    transform: scale(0.96);
    transform-origin: top left;
  }

  @page {
    size: A5 portrait;
    margin: 6mm;
  }
}
</style>
