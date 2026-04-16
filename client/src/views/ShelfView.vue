<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

import InventoryStockApi from '@/api/inventoryStock'
import ShelfApi from '@/api/shelf'

import type InventoryStockData from '@/api/interfaces/InventoryStock'
import type ShelfData from '@/api/interfaces/Shelf'

type ShelfTypeKey = 'glass' | 'wooden' | 'iron' | 'fridge'

const SHELF_TYPES: Record<ShelfTypeKey, { label: string; icon: string }> = {
  glass: { label: 'Glass case', icon: '⬚' },
  wooden: { label: 'Wood storage', icon: '▤' },
  iron: { label: 'Iron rack', icon: '▦' },
  fridge: { label: 'Cold storage', icon: '❄' },
}

/** Iconify icons for shelf storage types */
const SHELF_TYPE_ICONIFY: Record<ShelfTypeKey, string> = {
  glass: 'mdi:glass-fragile',
  wooden: 'mdi:wardrobe-outline',
  iron: 'mdi:warehouse',
  fridge: 'mdi:snowflake-thermometer',
}

interface ShelfItemView {
  name: string
  qty: number
  volume: number
  raw: InventoryRow
}

interface ShelfView {
  id: number
  name: string
  type: ShelfTypeKey
  locked: boolean
  pin: string
  capacity: number
  items: ShelfItemView[]
  raw: ShelfData
}

interface InventoryRow {
  id: number
  name: string
  quantity: number
  expiry: string
  category: string
  status: 'pending' | 'placed'
  stockableId: number
  stockableType: InventoryStockData['stockableType']
  shelfId?: number
  shelfName?: string
  shelfType?: ShelfTypeKey
  raw: InventoryStockData
}

const normalizePinDigits = (raw: string, maxLen = 4) =>
  raw.replace(/\D/g, '').slice(0, maxLen)

const searchWrapRef = ref<HTMLElement | null>(null)

const ui = reactive({
  shelfModal: false,
  pinModal: false,
  pinInput: '',
  pinMsg: '',
  onPinSuccess: null as null | (() => void | Promise<void>),
  searchFocused: false,
})

const form = reactive({
  name: '',
  type: 'glass' as ShelfTypeKey,
  capacity: 30,
  pin: '',
})

const search = ref('')
const notifications = ref<{ id: number; text: string }[]>([])

const shelfRows = ref<ShelfData[]>([])
const stockRows = ref<InventoryStockData[]>([])

const shelfLocks = reactive<Record<number, boolean>>({})
const selectedShelf = reactive<Record<number, number | undefined>>({})

const onVerifyPinInput = (e: Event) => {
  ui.pinInput = normalizePinDigits((e.target as HTMLInputElement).value)
}

const onFormPinInput = (e: Event) => {
  form.pin = normalizePinDigits((e.target as HTMLInputElement).value)
}

const notify = (text: string) => {
  const id = Date.now() + Math.floor(Math.random() * 1000)
  notifications.value.push({ id, text })
  setTimeout(() => {
    notifications.value = notifications.value.filter((n) => n.id !== id)
  }, 2800)
}

const formatDate = (date?: string) => {
  if (!date) return 'N/A'
  const parsed = new Date(date)
  if (Number.isNaN(parsed.getTime())) return date
  return parsed.toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

const resolveShelfType = (value: unknown): ShelfTypeKey => {
  const v = String(value || '').toLowerCase()
  if (v === 'glass' || v === 'wooden' || v === 'iron' || v === 'fridge') return v
  return 'glass'
}

const getStockable = (stock: InventoryStockData) => stock.stockable as Record<string, any> | undefined

const getStockName = (stock: InventoryStockData) => {
  const s = getStockable(stock)
  return (
    s?.name ||
    s?.materialName ||
    s?.assetName ||
    'Unknown item'
  )
}

const getStockCategory = (stock: InventoryStockData) => {
  const s = getStockable(stock)
  return String(s?.category || '')
}

const getStockShelf = (stock: InventoryStockData): ShelfData | undefined => {
  const shelfId = Number(stock.shelfId || 0)
  if (!shelfId) return undefined
  return shelfMap.value.get(shelfId)
}

const makeRow = (stock: InventoryStockData): InventoryRow => {
  const shelf = getStockShelf(stock)
  return {
    id: Number(stock.id || 0),
    name: getStockName(stock),
    quantity: Number(stock.quantity || 0),
    expiry: stock.expiryDate || '',
    category: getStockCategory(stock),
    status: stock.status,
    stockableId: Number(stock.stockableId || 0),
    stockableType: stock.stockableType,
    shelfId: Number(stock.shelfId || shelf?.id || 0) || undefined,
    shelfName: shelf?.shelfName,
    shelfType: resolveShelfType(shelf?.shelfType),
    raw: stock,
  }
}

const shelfMap = computed(() => {
  const map = new Map<number, ShelfData>()
  for (const shelf of shelfRows.value) {
    if (shelf.id != null) map.set(Number(shelf.id), shelf)
  }
  return map
})

const pendingRows = computed<InventoryRow[]>(() =>
  stockRows.value
    .filter((stock) => stock.status === 'pending')
    .map((stock) => makeRow(stock)),
)

const placedRows = computed<InventoryRow[]>(() =>
  stockRows.value
    .filter((stock) => stock.status === 'placed')
    .map((stock) => makeRow(stock)),
)

const inventory = computed(() => pendingRows.value)

const allRows = computed(() => [...pendingRows.value, ...placedRows.value])

const getItemVolume = (row: InventoryRow) => {
  const stockable = getStockable(row.raw)
  const width = Number(stockable?.width || 0)
  const height = Number(stockable?.height || 0)
  const depth = Number(stockable?.depth || 0)

  const perUnitVolume = width > 0 && height > 0 && depth > 0 ? width * height * depth : 1
  return perUnitVolume * Math.max(1, Number(row.quantity || 0))
}

const shelves = computed<ShelfView[]>(() => {
  return shelfRows.value
    .filter((shelf) => shelf.id != null)
    .map((rawShelf) => {
      const id = Number(rawShelf.id)
      const items = placedRows.value
        .filter((row) => row.shelfId === id)
        .map((row) => ({
          name: row.name,
          qty: row.quantity,
          volume: getItemVolume(row),
          raw: row,
        }))

      return {
        id,
        name: rawShelf.shelfName || `Shelf ${id}`,
        type: resolveShelfType(rawShelf.shelfType),
        locked: shelfLocks[id] ?? !!rawShelf.accessPin,
        pin: rawShelf.accessPin || '',
        capacity: Math.max(1, Number(rawShelf.totalCapacityCm3 || 0) || 1),
        items,
        raw: rawShelf,
      }
    })
})

const getShelfLoad = (shelf: ShelfView) => {
  return shelf.items.reduce((acc, cur) => acc + cur.volume, 0)
}

const filteredInventory = computed(() => {
  const q = search.value.toLowerCase().trim()
  if (!q) return inventory.value

  return inventory.value.filter((item) => {
    return (
      item.name.toLowerCase().includes(q) ||
      item.category.toLowerCase().includes(q)
    )
  })
})

function lookupMatchScore(
  name: string,
  shelfName: string | undefined,
  category: string | undefined,
  q: string,
): number {
  const n = name.toLowerCase()
  const s = (shelfName || '').toLowerCase()
  const c = (category || '').toLowerCase()

  if (n.startsWith(q)) return 0
  if (s.startsWith(q)) return 1
  if (c.startsWith(q)) return 2
  if (n.includes(q)) return 3
  if (s.includes(q)) return 4
  if (c.includes(q)) return 5
  return 6
}

const searchLookupResults = computed(() => {
  const q = search.value.toLowerCase().trim()
  if (!q) return []

  const matched = allRows.value.filter((r) => {
    const hay = [r.name, r.category || '', r.shelfName || ''].join(' ').toLowerCase()
    return hay.includes(q)
  })

  matched.sort(
    (a, b) =>
      lookupMatchScore(a.name, a.shelfName, a.category, q) -
        lookupMatchScore(b.name, b.shelfName, b.category, q) ||
      a.name.localeCompare(b.name),
  )

  return matched.slice(0, 14)
})

const showSearchLookup = computed(() => {
  return search.value.trim().length > 0 && ui.searchFocused
})

const onSearchWrapFocusIn = () => {
  ui.searchFocused = true
}

const onSearchWrapFocusOut = (e: FocusEvent) => {
  const next = e.relatedTarget as Node | null
  const wrap = searchWrapRef.value
  if (wrap && next && wrap.contains(next)) return
  ui.searchFocused = false
}

const onLookupSelect = (row: InventoryRow) => {
  ui.searchFocused = false
  ;(document.getElementById('shelf-inventory-search') as HTMLInputElement | null)?.blur()

  if (row.status === 'placed' && row.shelfId) {
    document.getElementById(`shelf-${row.shelfId}`)?.scrollIntoView({
      behavior: 'smooth',
      block: 'nearest',
    })
  } else {
    document.getElementById(`pool-${row.id}`)?.scrollIntoView({
      behavior: 'smooth',
      block: 'nearest',
    })
  }
}

const metrics = computed(() => {
  const pressureCount = shelves.value.filter((s) => getShelfLoad(s) > s.capacity * 0.8).length

  return [
    {
      label: 'Unassigned items',
      value: inventory.value.length,
      isAlert: false,
      icon: 'mdi:tray-arrow-up',
    },
    {
      label: 'Security locks',
      value: shelves.value.filter((s) => s.locked).length,
      isAlert: false,
      icon: 'mdi:lock-outline',
    },
    {
      label: 'Storage pressure',
      value: pressureCount,
      isAlert: pressureCount > 0,
      icon: 'mdi:gauge',
    },
  ]
})

const loadData = async () => {
  try {
    const [shelvesRes, stockRes] = await Promise.all([
      ShelfApi.getShelves(),
      InventoryStockApi.getInventoryStock(),
    ])

    shelfRows.value = Array.isArray(shelvesRes.data)
      ? shelvesRes.data
      : (shelvesRes.data?.data || [])

    stockRows.value = Array.isArray(stockRes.data)
      ? stockRes.data
      : (stockRes.data?.data || [])

    for (const shelf of shelfRows.value) {
      if (shelf.id == null) continue
      if (shelfLocks[Number(shelf.id)] === undefined) {
        shelfLocks[Number(shelf.id)] = !!shelf.accessPin
      }
    }
  } catch {
    notify('Failed to load inventory data')
  }
}

onMounted(loadData)

const transfer = async (item: InventoryRow) => {
  const shelfId = selectedShelf[item.id]
  if (!shelfId) return

  const shelf = shelves.value.find((s) => s.id === Number(shelfId))
  if (!shelf) return

  const performTransfer = async () => {
    const extraVolume = getItemVolume(item)
    if (getShelfLoad(shelf) + extraVolume > shelf.capacity) {
      notify('Exceeds capacity')
      return
    }

    try {
      const payload: InventoryStockData = {
        ...item.raw,
        shelfId: shelf.id,
        status: 'placed',
      }

      await InventoryStockApi.updateInventoryStock(item.id, payload)
      delete selectedShelf[item.id]
      notify(`${item.name} stored in ${shelf.name}`)
      await loadData()
    } catch {
      notify('Transfer failed')
    } finally {
      ui.pinModal = false
      ui.onPinSuccess = null
      ui.pinInput = ''
    }
  }

  if (shelf.locked) {
    ui.pinMsg = `Authorize transfer of ${item.name} to ${shelf.name}`
    ui.pinInput = ''
    ui.onPinSuccess = async () => {
      if (ui.pinInput === shelf.pin) {
        await performTransfer()
      } else {
        notify('Invalid PIN')
      }
    }
    ui.pinModal = true
  } else {
    await performTransfer()
  }
}

const toggleShelfLock = (shelf: ShelfView) => {
  if (shelf.locked) {
    if (!shelf.pin) {
      shelfLocks[shelf.id] = false
      notify('Shelf unlocked')
      return
    }

    ui.pinMsg = `Unlocking ${shelf.name}`
    ui.pinInput = ''
    ui.onPinSuccess = async () => {
      if (ui.pinInput === shelf.pin) {
        shelfLocks[shelf.id] = false
        ui.pinModal = false
        ui.onPinSuccess = null
        notify('Shelf unlocked')
      } else {
        notify('Invalid PIN')
      }
    }
    ui.pinModal = true
  } else {
    shelfLocks[shelf.id] = true
    notify('Shelf locked')
  }
}

const confirmPin = async () => {
  if (ui.onPinSuccess) {
    await ui.onPinSuccess()
  }
}

const cancelPin = () => {
  ui.pinModal = false
  ui.pinInput = ''
  ui.onPinSuccess = null
}

const saveShelf = async () => {
  if (!form.name.trim() || !form.pin) {
    notify('Missing configuration')
    return
  }

  try {
    await ShelfApi.postShelf({
      shelfName: form.name.trim(),
      shelfType: form.type,
      accessPin: form.pin,
      totalCapacityCm3: Math.max(1, Number(form.capacity) || 1),
    })

    form.name = ''
    form.pin = ''
    form.capacity = 30
    form.type = 'glass'
    ui.shelfModal = false

    notify('Shelf initialized')
    await loadData()
  } catch {
    notify('Failed to create shelf')
  }
}
</script>

<template>
  <div class="cms-inventory">
    <header class="top-bar">
      <div class="brand">
        <h1>Inventory pool</h1>
        <span class="badge badge--neutral">{{ inventory.length }} pending materials</span>
      </div>

      <div class="actions">
        <div
          ref="searchWrapRef"
          class="search-field-wrap"
          @focusin="onSearchWrapFocusIn"
          @focusout="onSearchWrapFocusOut"
        >
          <div class="search-input">
            <Icon icon="mdi:magnify" class="search-input__iconify" aria-hidden="true" />
            <input
              id="shelf-inventory-search"
              v-model="search"
              type="search"
              name="shelf-inventory-filter"
              class="search-input__field"
              placeholder="Search materials, shelves, locations…"
              autocomplete="off"
              autocorrect="off"
              autocapitalize="off"
              spellcheck="false"
              data-lpignore="true"
              data-1p-ignore="true"
              data-form-type="other"
              role="combobox"
              aria-autocomplete="list"
              :aria-expanded="showSearchLookup"
              aria-controls="shelf-search-lookup-panel"
            />
          </div>

          <div
            v-show="showSearchLookup"
            id="shelf-search-lookup-panel"
            class="search-lookup"
            role="listbox"
            aria-label="Search results"
            @mousedown.prevent
          >
            <template v-if="searchLookupResults.length">
              <button
                v-for="row in searchLookupResults"
                :key="row.id + '-' + row.status"
                type="button"
                class="search-lookup__row"
                role="option"
                @click="onLookupSelect(row)"
              >
                <span class="search-lookup__icon" aria-hidden="true">
                  <Icon
                    v-if="row.status === 'placed' && row.shelfType"
                    :icon="SHELF_TYPE_ICONIFY[row.shelfType]"
                    class="search-lookup__iconify"
                  />
                  <Icon
                    v-else
                    icon="mdi:tray-arrow-up"
                    class="search-lookup__iconify search-lookup__iconify--pending"
                  />
                </span>

                <span class="search-lookup__main">
                  <span class="search-lookup__name">{{ row.name }}</span>
                  <span class="search-lookup__meta">
                    <span v-if="row.status === 'pending'" class="location-pill location-pill--pending">
                      Pending
                    </span>
                    <template v-else>
                      <span class="location-pill location-pill--shelf">
                        <Icon
                          v-if="row.shelfType"
                          :icon="SHELF_TYPE_ICONIFY[row.shelfType]"
                          class="location-pill__icon"
                          aria-hidden="true"
                        />
                        {{ row.shelfName }}
                      </span>
                    </template>
                    <span class="search-lookup__qty">×{{ row.quantity }}</span>
                    <span v-if="row.category" class="search-lookup__cat">{{ row.category }}</span>
                  </span>
                </span>
              </button>
            </template>
            <p v-else class="search-lookup__empty">No matches for this search.</p>
          </div>
        </div>

        <button type="button" class="btn btn--primary" @click="ui.shelfModal = true">
          + Add shelf
        </button>
      </div>
    </header>

    <section class="metrics-grid" aria-label="Summary">
      <div v-for="stat in metrics" :key="stat.label" class="metric-card">
        <div class="metric-card__top">
          <span class="metric-card__label">{{ stat.label }}</span>
          <span class="metric-card__icon-wrap" aria-hidden="true">
            <Icon :icon="stat.icon" class="metric-card__iconify" />
          </span>
        </div>
        <div class="metric-card__value" :class="{ 'metric-card__value--alert': stat.isAlert }">
          {{ stat.value }}
        </div>
      </div>
    </section>

    <div class="view-grid">
      <main class="content-area">
        <header class="area-header">
          <h2>Pending distribution</h2>
          <p>Materials waiting to be assigned to clinical shelving.</p>
        </header>

        <div class="pool-list">
          <p v-if="!filteredInventory.length" class="empty-state">
            {{ search.trim() ? 'No materials match your filter.' : 'All materials are assigned to shelves.' }}
          </p>

          <transition-group v-else name="stagger" tag="div" class="pool-list__inner">
            <article
              v-for="item in filteredInventory"
              :id="'pool-' + item.id"
              :key="item.id"
              class="inventory-row inventory-row--pending"
            >
              <div class="item-details">
                <div class="item-details__tags">
                  <span class="category-tag">{{ item.category || 'Uncategorized' }}</span>
                  <span class="pending-badge">Pending</span>
                </div>
                <h3>{{ item.name }}</h3>
                <small>Expiry {{ formatDate(item.expiry) }}</small>
              </div>

              <div class="item-placement">
                <div class="qty-pill" aria-label="Quantity">×{{ item.quantity }}</div>
                <select v-model="selectedShelf[item.id]" class="shelf-select" aria-label="Assign shelf">
                  <option value="" disabled>Assign shelf…</option>
                  <option
                    v-for="s in shelves"
                    :key="s.id"
                    :value="s.id"
                    :disabled="getShelfLoad(s) + 1 > s.capacity"
                  >
                    {{ s.locked ? '🔒 ' : '' }}{{ s.name }} ({{ Math.max(0, s.capacity - getShelfLoad(s)) }} free)
                  </option>
                </select>
                <button
                  type="button"
                  class="btn btn--action"
                  :disabled="!selectedShelf[item.id]"
                  @click="transfer(item)"
                >
                  Place
                </button>
              </div>
            </article>
          </transition-group>
        </div>
      </main>

      <aside class="shelf-sidebar" aria-label="Clinical shelving">
        <div class="sidebar-header">
          <h2>Clinical shelving</h2>
        </div>

        <div class="shelf-scroll">
          <div
            v-for="shelf in shelves"
            :id="'shelf-' + shelf.id"
            :key="shelf.id"
            class="shelf-box"
            :class="{ 'shelf-box--locked': shelf.locked }"
          >
            <div class="shelf-top">
              <div class="shelf-meta">
                <span class="type-icon" aria-hidden="true">{{ SHELF_TYPES[shelf.type].icon }}</span>
                <h4>{{ shelf.name }}</h4>
              </div>
              <button type="button" class="lock-btn" @click="toggleShelfLock(shelf)">
                {{ shelf.locked ? 'Unlock' : 'Lock' }}
              </button>
            </div>

            <div class="shelf-progress">
              <div
                class="progress-bar"
                role="progressbar"
                :aria-valuenow="getShelfLoad(shelf)"
                :aria-valuemax="shelf.capacity"
                aria-label="Shelf capacity"
              >
                <div
                  class="progress-bar__fill"
                  :class="{ 'progress-bar__fill--warn': getShelfLoad(shelf) / shelf.capacity > 0.8 }"
                  :style="{ width: Math.min(100, (getShelfLoad(shelf) / shelf.capacity) * 100) + '%' }"
                />
              </div>
              <span class="shelf-progress__label">{{ getShelfLoad(shelf) }} / {{ shelf.capacity }} cm³</span>
            </div>

            <ul v-if="shelf.items.length" class="shelf-items">
              <li v-for="entry in shelf.items" :key="entry.name + entry.qty">
                <span class="shelf-items__name">{{ entry.name }}</span>
                <span class="shelf-items__qty">×{{ entry.qty }}</span>
              </li>
            </ul>
            <p v-else class="shelf-empty">Empty</p>
          </div>
        </div>
      </aside>
    </div>

    <teleport to="body">
      <transition name="shelf-modal-fade">
        <div v-if="ui.pinModal" class="shelf-overlay" role="presentation" @click.self="cancelPin">
          <form
            class="shelf-dialog shelf-dialog--security"
            role="dialog"
            aria-modal="true"
            aria-labelledby="pin-title"
            autocomplete="off"
            @submit.prevent="confirmPin"
            @click.stop
          >
            <div class="shelf-dialog__header shelf-dialog__header--security">
              <div class="shelf-dialog__icon-wrap" aria-hidden="true">
                <span class="shelf-dialog__icon">🔒</span>
              </div>
              <h3 id="pin-title" class="shelf-dialog__title">Authorization required</h3>
            </div>
            <div class="shelf-dialog__body">
              <p class="shelf-dialog__hint">{{ ui.pinMsg }}</p>
              <label class="visually-hidden" for="shelf-pin-verify">Shelf PIN</label>
              <input
                id="shelf-pin-verify"
                :value="ui.pinInput"
                type="text"
                inputmode="numeric"
                maxlength="4"
                class="shelf-dialog__pin shelf-pin-masked"
                placeholder="····"
                autocomplete="one-time-code"
                name="shelf-pin-verify"
                autofocus
                @input="onVerifyPinInput"
                @keyup.enter="confirmPin"
              />
            </div>
            <div class="shelf-dialog__footer">
              <button type="button" class="shelf-btn shelf-btn--ghost" @click="cancelPin">Cancel</button>
              <button type="submit" class="shelf-btn shelf-btn--primary">Verify</button>
            </div>
          </form>
        </div>
      </transition>

      <transition name="shelf-modal-fade">
        <div v-if="ui.shelfModal" class="shelf-overlay" role="presentation" @click.self="ui.shelfModal = false">
          <form
            class="shelf-dialog shelf-dialog--form"
            role="dialog"
            aria-modal="true"
            aria-labelledby="shelf-form-title"
            autocomplete="off"
            @submit.prevent="saveShelf"
            @click.stop
          >
            <div class="shelf-dialog__header">
              <h3 id="shelf-form-title" class="shelf-dialog__title">Register new shelf</h3>
              <p class="shelf-dialog__subtitle">Add a shelf to the clinical inventory map.</p>
            </div>
            <div class="shelf-dialog__body">
              <div class="shelf-form-grid">
                <div class="shelf-field shelf-field--full">
                  <label class="shelf-field__label" for="shelf-name">Shelf name</label>
                  <input
                    id="shelf-name"
                    v-model="form.name"
                    class="shelf-field__input"
                    type="text"
                    name="shelf-display-name"
                    placeholder="e.g. Surgery cabinet A"
                    autocomplete="off"
                    autocorrect="off"
                  />
                </div>

                <div class="shelf-field">
                  <label class="shelf-field__label" for="shelf-cap">Capacity (cm³)</label>
                  <input
                    id="shelf-cap"
                    v-model.number="form.capacity"
                    class="shelf-field__input"
                    type="number"
                    name="shelf-capacity"
                    min="1"
                    autocomplete="off"
                  />
                </div>

                <div class="shelf-field">
                  <label class="shelf-field__label" for="shelf-type">Storage type</label>
                  <select
                    id="shelf-type"
                    v-model="form.type"
                    class="shelf-field__input shelf-field__select"
                    name="shelf-storage-type"
                    autocomplete="off"
                  >
                    <option v-for="(v, k) in SHELF_TYPES" :key="k" :value="k">
                      {{ v.label }}
                    </option>
                  </select>
                </div>

                <div class="shelf-field shelf-field--full">
                  <label class="shelf-field__label" for="shelf-pin">Access PIN (4 digits)</label>
                  <input
                    id="shelf-pin"
                    :value="form.pin"
                    class="shelf-field__input shelf-pin-masked"
                    type="text"
                    name="shelf-access-code"
                    maxlength="4"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    autocorrect="off"
                    spellcheck="false"
                    @input="onFormPinInput"
                  />
                </div>
              </div>
            </div>
            <div class="shelf-dialog__footer">
              <button type="button" class="shelf-btn shelf-btn--ghost" @click="ui.shelfModal = false">
                Cancel
              </button>
              <button type="submit" class="shelf-btn shelf-btn--primary">Initialize</button>
            </div>
          </form>
        </div>
      </transition>
    </teleport>

    <div class="toast-stack" aria-live="polite">
      <div v-for="msg in notifications" :key="msg.id" class="toast-item">
        {{ msg.text }}
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
.cms-inventory {
  --slate-50: #f8fafc;
  --slate-100: #f1f5f9;
  --slate-200: #e2e8f0;
  --slate-400: #94a3b8;
  --slate-600: #475569;
  --slate-700: #334155;
  --slate-900: #0f172a;
  --clinical: #0ea5e9;
  --clinical-soft: #e0f2fe;
  --clinical-deep: #0369a1;
  --danger: #e11d48;
  --danger-soft: #fff1f2;
  --surface: #ffffff;
  --radius: 12px;
  --radius-sm: 8px;
  --shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
  --shadow-md: 0 4px 14px rgba(15, 23, 42, 0.1);

  background: var(--slate-50);
  color: var(--slate-900);
  min-height: 100%;
  box-sizing: border-box;
  font-family:
    system-ui,
    -apple-system,
    'Segoe UI',
    Roboto,
    sans-serif;
}

.cms-inventory *,
.cms-inventory *::before,
.cms-inventory *::after {
  box-sizing: border-box;
}

/* —— Top bar —— */
.top-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem 1.25rem;
  padding: 1rem clamp(1rem, 3vw, 1.75rem);
  background: var(--surface);
  border-bottom: 1px solid var(--slate-200);
  box-shadow: var(--shadow);
}

.brand {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem 0.75rem;
  min-width: 0;

  h1 {
    font-size: clamp(1.05rem, 2.5vw, 1.35rem);
    font-weight: 700;
    margin: 0;
    letter-spacing: -0.02em;
    line-height: 1.2;
  }
}

.badge {
  display: inline-flex;
  align-items: center;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  padding: 0.25rem 0.55rem;
  border-radius: 999px;
  white-space: nowrap;

  &--neutral {
    background: var(--slate-100);
    color: var(--slate-600);
    border: 1px solid var(--slate-200);
  }
}

.actions {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  gap: 0.65rem;
  width: 100%;
  max-width: 100%;

  @media (min-width: 640px) {
    width: auto;
    max-width: none;
    margin-left: auto;
  }
}

.search-field-wrap {
  position: relative;
  flex: 0 1 200px;
  min-width: 0;
  z-index: 5;

  @media (min-width: 640px) {
    flex: 1 1 220px;
    max-width: min(100%, 400px);
  }
}

.search-input {
  position: relative;
  width: 100%;

  &__iconify {
    position: absolute;
    left: 0.72rem;
    top: 50%;
    transform: translateY(-50%);
    width: 1.15rem;
    height: 1.15rem;
    color: var(--clinical-deep);
    opacity: 0.75;
    pointer-events: none;
  }

  &__field {
    width: 100%;
    padding: 0.55rem 0.85rem 0.55rem 2.5rem;
    border-radius: var(--radius-sm);
    border: 1px solid var(--slate-200);
    background: var(--slate-50);
    font-size: 0.875rem;
    transition:
      border-color 0.15s ease,
      box-shadow 0.15s ease;

    &::placeholder {
      color: var(--slate-400);
    }

    &:hover {
      border-color: var(--slate-400);
    }

    &:focus {
      outline: none;
      border-color: var(--clinical);
      box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.22);
      background: var(--surface);
    }
  }

}

.search-lookup {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  right: 0;
  max-height: min(55vh, 320px);
  overflow-y: auto;
  background: var(--surface);
  border: 1px solid var(--slate-200);
  border-radius: var(--radius-sm);
  box-shadow:
    0 10px 40px -12px rgba(15, 23, 42, 0.18),
    0 0 0 1px rgba(14, 165, 233, 0.06);
  -webkit-overflow-scrolling: touch;
}

.search-lookup__row {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  width: 100%;
  padding: 0.65rem 0.85rem;
  border: none;
  border-bottom: 1px solid var(--slate-100);
  background: transparent;
  text-align: left;
  cursor: pointer;
  color: inherit;
  font: inherit;
  transition: background 0.12s ease;

  &:last-child {
    border-bottom: none;
  }

  &:hover,
  &:focus-visible {
    background: var(--clinical-soft);
    outline: none;
  }
}

.search-lookup__icon {
  flex-shrink: 0;
  width: 2rem;
  height: 2rem;
  border-radius: 8px;
  background: var(--clinical-soft);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(14, 165, 233, 0.15);
}

.search-lookup__iconify {
  width: 1.15rem;
  height: 1.15rem;
  color: var(--clinical-deep);

  &--pending {
    color: #842029;
  }
}

.search-lookup__main {
  min-width: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.search-lookup__name {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--slate-900);
  line-height: 1.3;
}

.search-lookup__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.35rem 0.5rem;
  font-size: 0.75rem;
}

.search-lookup__qty {
  font-weight: 700;
  color: var(--slate-600);
  font-variant-numeric: tabular-nums;
}

.search-lookup__cat {
  color: var(--slate-400);
  font-weight: 500;
}

.search-lookup__empty {
  margin: 0;
  padding: 1rem 0.9rem;
  font-size: 0.8125rem;
  color: var(--slate-600);
  text-align: center;
}

.location-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.65rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  padding: 0.2rem 0.45rem;
  border-radius: 4px;
  line-height: 1.2;
}

.location-pill--pending {
  color: #842029;
  background: #f8d7da;
  border: 1px solid #f5c2c7;
}

.location-pill--shelf {
  color: var(--clinical-deep);
  background: var(--clinical-soft);
  border: 1px solid rgba(14, 165, 233, 0.25);
}

.location-pill__icon {
  width: 0.85rem;
  height: 0.85rem;
  flex-shrink: 0;
}

/* —— Metrics —— */
.metrics-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.75rem;
  padding: 1rem clamp(1rem, 3vw, 1.75rem);

  @media (min-width: 520px) {
    grid-template-columns: repeat(2, 1fr);
  }

  @media (min-width: 900px) {
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
  }
}

.metric-card {
  background: var(--surface);
  padding: 1rem 1.1rem;
  border-radius: var(--radius);
  border: 1px solid var(--slate-200);
  box-shadow: var(--shadow);
  position: relative;
  overflow: hidden;

  &::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--clinical) 0%, var(--clinical-soft) 100%);
    opacity: 0.85;
  }

  &__top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.4rem;
  }

  &__label {
    display: block;
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--slate-400);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    line-height: 1.3;
    max-width: 70%;
  }

  &__icon-wrap {
    flex-shrink: 0;
    width: 2.35rem;
    height: 2.35rem;
    border-radius: 10px;
    background: var(--clinical-soft);
    border: 1px solid rgba(14, 165, 233, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  &__iconify {
    width: 1.2rem;
    height: 1.2rem;
    color: var(--clinical-deep);
  }

  &__value {
    font-size: clamp(1.35rem, 4vw, 1.65rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.1;

    &--alert {
      color: var(--danger);
    }
  }
}

/* —— Main layout —— */
.view-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.25rem;
  padding: 0 clamp(1rem, 3vw, 1.75rem) clamp(1.5rem, 4vw, 2.25rem);

  @media (min-width: 1024px) {
    grid-template-columns: minmax(0, 1fr) min(360px, 32vw);
    align-items: start;
    gap: 1.5rem;
  }
}

.content-area {
  min-width: 0;
}

.area-header {
  margin-bottom: 1rem;

  h2 {
    margin: 0 0 0.35rem;
    font-size: clamp(1rem, 2.2vw, 1.15rem);
    font-weight: 700;
    letter-spacing: -0.02em;
  }

  p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--slate-600);
    line-height: 1.45;
    max-width: 42ch;
  }
}

.pool-list {
  position: relative;
}

.pool-list__inner {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.empty-state {
  margin: 0;
  padding: 2rem 1.25rem;
  text-align: center;
  font-size: 0.9rem;
  color: var(--slate-600);
  background: var(--surface);
  border: 1px dashed var(--slate-200);
  border-radius: var(--radius);
}

/* —— Inventory rows —— */
.inventory-row {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  background: var(--surface);
  border: 1px solid var(--slate-200);
  padding: 1rem 1.1rem;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  transition: box-shadow 0.2s ease;
  scroll-margin-top: 1.25rem;

  @media (min-width: 720px) {
    flex-direction: row;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem 1.25rem;
  }

  &:focus-within {
    box-shadow: var(--shadow-md);
    border-color: var(--slate-200);
  }

  h3 {
    margin: 0.2rem 0 0.2rem;
    font-size: 0.95rem;
    font-weight: 600;
    line-height: 1.3;
  }
}

.item-details {
  flex: 1 1 auto;
  min-width: 0;

  &__tags {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem;
    margin-bottom: 0.15rem;
  }
}

.category-tag {
  display: inline-block;
  font-size: 0.65rem;
  font-weight: 700;
  background: var(--clinical-soft);
  color: var(--clinical-deep);
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  border: 1px solid rgba(14, 165, 233, 0.2);
}

.pending-badge {
  display: inline-flex;
  align-items: center;
  font-size: 0.65rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  padding: 0.2rem 0.45rem;
  border-radius: 4px;
  color: #842029;
  background: #f8d7da;
  border: 1px solid #f5c2c7;
}

.item-details small {
  color: var(--slate-400);
  font-size: 0.8rem;
}

.item-placement {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  width: 100%;

  @media (min-width: 480px) {
    flex-direction: row;
    flex-wrap: wrap;
    align-items: center;
    width: auto;
    flex: 0 1 auto;
  }

  @media (min-width: 720px) {
    flex-wrap: nowrap;
    justify-content: flex-end;
  }
}

.qty-pill {
  font-weight: 700;
  background: var(--clinical-soft);
  color: var(--clinical-deep);
  padding: 0.35rem 0.65rem;
  border-radius: 8px;
  font-size: 0.8rem;
  align-self: flex-start;

  @media (min-width: 480px) {
    align-self: center;
  }
}

.shelf-select {
  flex: 0 1 180px;
  min-width: 0;
  padding: 0.45rem 0.55rem;
  border: 1px solid var(--slate-200);
  border-radius: var(--radius-sm);
  font-size: 0.8rem;
  background: var(--surface);
  cursor: pointer;

  @media (min-width: 480px) {
    flex: 1 1 160px;
    min-width: 140px;
    max-width: 220px;
  }

  &:focus {
    outline: none;
    border-color: var(--clinical);
    box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.22);
  }
}

/* —— Sidebar —— */
.shelf-sidebar {
  min-width: 0;
  display: flex;
  flex-direction: column;
  background: var(--surface);
  border: 1px solid var(--slate-200);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  overflow: hidden;
  max-height: none;

  @media (min-width: 1024px) {
    position: sticky;
    top: 0.75rem;
    max-height: calc(100vh - 1.5rem);
  }
}

.sidebar-header {
  padding: 1rem 1.1rem;
  border-bottom: 1px solid var(--slate-200);
  background: linear-gradient(180deg, var(--surface) 0%, var(--slate-50) 100%);

  h2 {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: -0.02em;
  }
}

.shelf-scroll {
  padding: 0.85rem;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;

  @media (min-width: 1024px) {
    flex: 1;
    min-height: 0;
  }
}

.shelf-box {
  background: var(--slate-50);
  border: 1px solid var(--slate-200);
  border-radius: var(--radius-sm);
  padding: 0.9rem 1rem;
  margin-bottom: 0.75rem;
  scroll-margin-top: 1.25rem;

  &:last-child {
    margin-bottom: 0;
  }

  &--locked {
    background: var(--danger-soft);
    border-color: #fecdd3;
  }
}

.shelf-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.5rem;
  margin-bottom: 0.85rem;

  h4 {
    margin: 0;
    font-size: 0.88rem;
    font-weight: 600;
    line-height: 1.3;
  }
}

.shelf-meta {
  display: flex;
  align-items: flex-start;
  gap: 0.45rem;
  min-width: 0;
}

.type-icon {
  flex-shrink: 0;
  font-size: 1rem;
  line-height: 1.2;
  opacity: 0.85;
}

.lock-btn {
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--clinical-deep);
  background: var(--surface);
  border: 1px solid var(--slate-200);
  padding: 0.35rem 0.55rem;
  border-radius: 6px;
  cursor: pointer;
  white-space: nowrap;
  transition:
    background 0.15s ease,
    border-color 0.15s ease;

  &:hover {
    background: var(--clinical-soft);
    border-color: rgba(14, 165, 233, 0.25);
  }

  &:focus-visible {
    outline: 2px solid var(--clinical);
    outline-offset: 2px;
  }
}

.shelf-progress {
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--slate-600);

  &__label {
    display: block;
    margin-top: 0.35rem;
  }
}

.progress-bar {
  height: 6px;
  background: var(--slate-200);
  border-radius: 999px;
  overflow: hidden;

  &__fill {
    height: 100%;
    background: var(--clinical);
    border-radius: 999px;
    transition: width 0.35s ease;

    &--warn {
      background: var(--danger);
    }
  }
}

.shelf-items {
  margin: 0.85rem 0 0;
  padding: 0.65rem 0 0;
  border-top: 1px solid var(--slate-200);
  list-style: none;

  li {
    font-size: 0.8rem;
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 0.5rem;
    padding: 0.3rem 0;
  }

  &__name {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  &__qty {
    flex-shrink: 0;
    font-weight: 700;
    color: var(--slate-700);
  }
}

.shelf-empty {
  margin: 0.65rem 0 0;
  padding: 0;
  font-size: 0.78rem;
  color: var(--slate-400);
  font-style: italic;
}

/* —— Teleported modals: self-contained tokens (teleport is outside .cms-inventory) —— */
.shelf-overlay {
  /* Explicit theme so popups work when appended to body */
  --shelf-modal-surface: #ffffff;
  --shelf-modal-elevated: #f8fafc;
  --shelf-modal-border: #e2e8f0;
  --shelf-modal-muted: #64748b;
  --shelf-modal-text: #0f172a;
  --shelf-modal-accent: #0ea5e9;
  --shelf-modal-accent-soft: #e0f2fe;
  --shelf-modal-accent-deep: #0369a1;
  --shelf-modal-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
  --shelf-modal-radius: 14px;
  --shelf-modal-radius-sm: 10px;

  position: fixed;
  inset: 0;
  z-index: 5000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: max(1rem, env(safe-area-inset-top)) max(1rem, env(safe-area-inset-right)) max(1rem, env(safe-area-inset-bottom)) max(1rem, env(safe-area-inset-left));
  background: rgba(15, 23, 42, 0.55);
  -webkit-backdrop-filter: blur(6px);
  backdrop-filter: blur(6px);
}

.shelf-dialog {
  width: 100%;
  max-width: min(420px, calc(100vw - 2rem));
  background: var(--shelf-modal-surface);
  color: var(--shelf-modal-text);
  border-radius: var(--shelf-modal-radius);
  border: 1px solid var(--shelf-modal-border);
  box-shadow: var(--shelf-modal-shadow);
  overflow: hidden;
  transform-origin: center center;
}

.shelf-dialog--security {
  max-width: min(380px, calc(100vw - 2rem));
  text-align: center;
}

.shelf-dialog--form {
  max-width: min(460px, calc(100vw - 2rem));
}

.shelf-dialog__header {
  padding: 1.15rem 1.25rem 0.85rem;
  background: linear-gradient(180deg, var(--shelf-modal-elevated) 0%, var(--shelf-modal-surface) 100%);
  border-bottom: 1px solid var(--shelf-modal-border);
}

.shelf-dialog__header--security {
  padding-top: 1.35rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.65rem;
}

.shelf-dialog__icon-wrap {
  width: 3.25rem;
  height: 3.25rem;
  border-radius: 50%;
  background: var(--shelf-modal-accent-soft);
  border: 1px solid rgba(14, 165, 233, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
}

.shelf-dialog__icon {
  font-size: 1.5rem;
  line-height: 1;
}

.shelf-dialog__title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  letter-spacing: -0.02em;
  line-height: 1.25;
}

.shelf-dialog__subtitle {
  margin: 0.35rem 0 0;
  font-size: 0.8125rem;
  color: var(--shelf-modal-muted);
  line-height: 1.45;
}

.shelf-dialog__body {
  padding: 1.15rem 1.25rem 1rem;
  background: var(--shelf-modal-surface);
}

.shelf-dialog__hint {
  margin: 0 0 1rem;
  font-size: 0.875rem;
  color: var(--shelf-modal-muted);
  line-height: 1.5;
}

/* Mask digits like a password without type=password (avoids login autofill pairing with search) */
.shelf-pin-masked {
  -webkit-text-security: disc;
}

.visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.shelf-dialog__pin {
  display: block;
  width: 100%;
  max-width: 11.5rem;
  margin: 0 auto;
  text-align: center;
  font-size: 1.375rem;
  letter-spacing: 0.35rem;
  padding: 0.65rem 0.75rem;
  border: 2px solid var(--shelf-modal-border);
  border-radius: var(--shelf-modal-radius-sm);
  background: var(--shelf-modal-elevated);
  color: var(--shelf-modal-text);
  font-variant-numeric: tabular-nums;

  &::placeholder {
    color: var(--shelf-modal-border);
    letter-spacing: 0.2rem;
  }

  &:focus {
    outline: none;
    border-color: var(--shelf-modal-accent);
    background: var(--shelf-modal-surface);
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.25);
  }
}

.shelf-dialog__footer {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 0.5rem;
  padding: 0.85rem 1.25rem 1.1rem;
  background: var(--shelf-modal-elevated);
  border-top: 1px solid var(--shelf-modal-border);
}

.shelf-form-grid {
  display: grid;
  gap: 0.9rem;

  @media (min-width: 400px) {
    grid-template-columns: 1fr 1fr;

    .shelf-field--full {
      grid-column: 1 / -1;
    }
  }
}

.shelf-field__label {
  display: block;
  font-size: 0.6875rem;
  font-weight: 700;
  color: var(--shelf-modal-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.35rem;
}

.shelf-field__input,
.shelf-field__select {
  width: 100%;
  padding: 0.55rem 0.7rem;
  border: 1px solid var(--shelf-modal-border);
  border-radius: var(--shelf-modal-radius-sm);
  font-size: 0.875rem;
  background: var(--shelf-modal-surface);
  color: var(--shelf-modal-text);

  &:hover {
    border-color: #cbd5e1;
  }

  &:focus {
    outline: none;
    border-color: var(--shelf-modal-accent);
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.2);
  }
}

.shelf-btn {
  padding: 0.5rem 1.1rem;
  border-radius: var(--shelf-modal-radius-sm);
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  border: 1px solid transparent;
  transition:
    background 0.15s ease,
    border-color 0.15s ease,
    transform 0.1s ease;

  &:active {
    transform: scale(0.98);
  }

  &:focus-visible {
    outline: 2px solid var(--shelf-modal-accent);
    outline-offset: 2px;
  }
}

.shelf-btn--primary {
  background: var(--shelf-modal-accent-deep);
  color: #fff;

  &:hover {
    filter: brightness(1.07);
  }
}

.shelf-btn--ghost {
  background: var(--shelf-modal-surface);
  color: var(--shelf-modal-muted);
  border-color: var(--shelf-modal-border);

  &:hover {
    background: var(--shelf-modal-elevated);
    color: var(--shelf-modal-text);
  }
}

.shelf-modal-fade-enter-active,
.shelf-modal-fade-leave-active {
  transition: opacity 0.2s ease;
}

.shelf-modal-fade-enter-active .shelf-dialog,
.shelf-modal-fade-leave-active .shelf-dialog {
  transition:
    opacity 0.2s ease,
    transform 0.22s cubic-bezier(0.34, 1.2, 0.64, 1);
}

.shelf-modal-fade-enter-from,
.shelf-modal-fade-leave-to {
  opacity: 0;
}

.shelf-modal-fade-enter-from .shelf-dialog,
.shelf-modal-fade-leave-to .shelf-dialog {
  opacity: 0;
  transform: scale(0.96) translateY(6px);
}

/* —— Buttons —— */
.btn {
  padding: 0.5rem 1rem;
  border-radius: var(--radius-sm);
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  border: 1px solid transparent;
  transition:
    background 0.15s ease,
    transform 0.1s ease;

  &:active:not(:disabled) {
    transform: scale(0.98);
  }

  &:focus-visible {
    outline: 2px solid var(--clinical);
    outline-offset: 2px;
  }

  &--primary {
    background: var(--clinical-deep);
    color: white;

    &:hover:not(:disabled) {
      filter: brightness(1.06);
    }
  }

  &--text {
    background: transparent;
    color: var(--slate-600);
    border-color: transparent;

    &:hover {
      color: var(--slate-900);
      background: var(--slate-100);
    }
  }

  &--action {
    background: var(--slate-900);
    color: white;
    white-space: nowrap;

    &:hover:not(:disabled) {
      background: var(--slate-700);
    }

    &:disabled {
      opacity: 0.35;
      cursor: not-allowed;
    }
  }
}

/* —— Toasts —— */
.toast-stack {
  position: fixed;
  bottom: max(1rem, env(safe-area-inset-bottom, 0px));
  right: max(1rem, env(safe-area-inset-right, 0px));
  left: max(1rem, env(safe-area-inset-left, 0px));
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.5rem;
  pointer-events: none;
  z-index: 6000;

  @media (min-width: 480px) {
    left: auto;
  }

  .toast-item {
    pointer-events: none;
    background: var(--slate-900);
    color: white;
    padding: 0.65rem 1rem;
    border-radius: var(--radius-sm);
    font-size: 0.82rem;
    box-shadow: var(--shadow-md);
    max-width: min(100%, 320px);
    animation: toast-in 0.25s ease;
  }
}

@keyframes toast-in {
  from {
    opacity: 0;
    transform: translateY(6px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* —— List transitions —— */
.stagger-move,
.stagger-enter-active,
.stagger-leave-active {
  transition:
    opacity 0.25s ease,
    transform 0.25s ease;
}

.stagger-enter-from,
.stagger-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

.stagger-leave-active {
  position: absolute;
  width: 100%;
  left: 0;
  pointer-events: none;
}

.btn.btn--primary {
  white-space: pre;
  z-index: 2000 !important;
}

#shelf-inventory-search {
  width: 300px;
}

.shelf-select {
  max-height: 35px !important;
}

@media (max-width: 600px) {
  .brand h1 {
    font-size: clamp(0.9rem, 2vw, 1.1rem);
  }

  .search-input__field {
    font-size: 0.8rem;
  }

  .inventory-row h3,
  .item-details h3 {
    font-size: 0.875rem;
  }

  .area-header h2 {
    font-size: clamp(0.9rem, 2vw, 1.05rem);
  }

  .metric-card__value {
    font-size: clamp(1.1rem, 3.5vw, 1.4rem);
  }

  .btn--primary {
    font-size: 0.8rem;
    padding: 0.4rem 0.8rem;
  }
}
</style>
