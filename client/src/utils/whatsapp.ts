/**
 * WhatsApp link helpers.
 * Keeps existing helpers intact and adds generic/template-based appointment helpers.
 */

export type WhatsAppVars = Record<string, string | number | boolean | null | undefined>

const trimText = (value: unknown): string => String(value ?? '').trim()

/**
 * Formats phone number for wa.me link (removes non-digit chars, ensures country code)
 *
 * Notes:
 * - wa.me expects digits only, no plus sign.
 * - If the number starts with 00, it is converted to international format.
 * - If the number starts with 0 and a default country code is provided, it will be prefixed.
 */
export function formatWhatsAppPhone(phone: string, defaultCountryCode = '93'): string {
  const cleaned = String(phone || '').replace(/[^\d+]/g, '')

  if (!cleaned) return ''

  if (cleaned.startsWith('00')) return cleaned.replace(/^00/, '')

  if (cleaned.startsWith('+')) return cleaned.slice(1)

  if (cleaned.startsWith('0') && defaultCountryCode) {
    // Keep existing behavior, but allow the region to be customized by the caller.
    return `${defaultCountryCode}${cleaned.slice(1)}`
  }

  return cleaned
}

export function buildWhatsAppLink(phone: string, message: string, defaultCountryCode = '93'): string {
  const formattedPhone = formatWhatsAppPhone(phone, defaultCountryCode)
  const encoded = encodeURIComponent(String(message || ''))
  return `https://wa.me/${formattedPhone}?text=${encoded}`
}

export function sendViaWhatsApp(link: string): void {
  window.open(link, '_blank', 'noopener,noreferrer')
}

/**
 * Replace template placeholders like:
 *  - {{patient_name}}
 *  - {{clinic_name}}
 *  - {patient_name}
 *
 * Missing values are replaced with an empty string.
 */
export function fillWhatsAppTemplate(template: string, vars: WhatsAppVars = {}): string {
  const source = trimText(template)

  return source.replace(/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}|\{\s*([a-zA-Z0-9_]+)\s*\}/g, (_match, keyA, keyB) => {
    const key = (keyA || keyB) as string
    const value = vars[key]
    return value === null || value === undefined ? '' : String(value)
  })
}

function normalizeLineBreaks(text: string): string {
  return String(text || '').replace(/\r\n/g, '\n').replace(/\r/g, '\n').trim()
}

export function generateWhatsAppMessageLink(
  phone: string,
  message: string,
  defaultCountryCode = '93',
): string {
  return buildWhatsAppLink(phone, normalizeLineBreaks(message), defaultCountryCode)
}

/**
 * Generates wa.me link with a pre-filled welcome message.
 */
export function generateWelcomePatientLink(
  phone: string,
  patientName: string,
  clinicName: string,
  defaultCountryCode = '93',
) {
  const message = `Hello ${patientName}, welcome to ${clinicName}`
  return buildWhatsAppLink(phone, message, defaultCountryCode)
}

/**
 * Generates wa.me link with a pre-filled reminder message.
 */
export function generatePatientAppointmentReminderLink(
  phone: string,
  patientName: string,
  appointmentDate: string,
  appointmentReason: string,
  clinicName: string,
  defaultCountryCode = '93',
) {
  const message = `Hello ${patientName}, You have an appointment in ${appointmentDate} for ${appointmentReason} in ${clinicName}. `
  return buildWhatsAppLink(phone, message, defaultCountryCode)
}

/**
 * Generates wa.me link for appointment messages using a template from settings.
 * The template can contain placeholders such as:
 * patient_name, patient_phone, clinic_name, clinic_address, appointment_date,
 * appointment_time, appointment_datetime, doctor_name, description, message_line
 */
export function generatePatientAppointmentMessageLink(
  phone: string,
  template: string,
  vars: WhatsAppVars = {},
  defaultCountryCode = '93',
  fallbackMessage = '',
): string {
  const rendered = fillWhatsAppTemplate(template, vars).trim()

  const message = rendered || fallbackMessage || ''
  return buildWhatsAppLink(phone, message, defaultCountryCode)
}

export function generatePatientAppointmentCancelLink(
  phone: string,
  template: string,
  vars: WhatsAppVars = {},
  defaultCountryCode = '93',
): string {
  return generatePatientAppointmentMessageLink(
    phone,
    template,
    {
      ...vars,
      message_line: vars.message_line ?? 'Your appointment has been cancelled.',
    },
    defaultCountryCode,
    'Your appointment has been cancelled.',
  )
}

export function generatePatientAppointmentCompleteLink(
  phone: string,
  template: string,
  vars: WhatsAppVars = {},
  defaultCountryCode = '93',
): string {
  return generatePatientAppointmentMessageLink(
    phone,
    template,
    {
      ...vars,
      message_line: vars.message_line ?? 'Your appointment has been completed.',
    },
    defaultCountryCode,
    'Your appointment has been completed.',
  )
}

export function generatePatientAppointmentReminderLinkTemplate(
  phone: string,
  template: string,
  vars: WhatsAppVars = {},
  defaultCountryCode = '93',
): string {
  return generatePatientAppointmentMessageLink(
    phone,
    template,
    {
      ...vars,
      message_line: vars.message_line ?? 'This is a reminder for your upcoming appointment.',
    },
    defaultCountryCode,
    'This is a reminder for your upcoming appointment.',
  )
}

/**
 * Generates wa.me link with pre-filled message for a new order
 */
export function generateWhatsAppOrderLink(
  phone: string,
  supplierName: string,
  items: { productId: string; productName: string; quantity: number; unit: string; notes?: string }[],
  clinicName = 'Your Dental Clinic',
  defaultCountryCode = '93'
): string {
  const formattedPhone = formatWhatsAppPhone(phone, defaultCountryCode)

  const itemList = items
    .map((item, idx) => `${idx + 1}. *${item.productName}* - Qty: ${item.quantity} ${item.unit}${item.notes ? ` (${item.notes})` : ''}`)
    .join('\n')

  const message = `New Purchase Order\n\n` +
    `From: ${clinicName}\n` +
    `Supplier: ${supplierName}\n` +
    `Date: ${new Date().toLocaleDateString()}\n\n` +
    `Items:\n${itemList}\n\n` +
    `Total Items: ${items.reduce((sum, i) => sum + i.quantity, 0)}\n\n` +
    `Please confirm availability and delivery time.\n` +
    `_Sent via Dental Clinic Manager_`

  const encoded = encodeURIComponent(message)
  return `https://wa.me/${formattedPhone}?text=${encoded}`
}

/**
 * Generates wa.me link for order cancellation
 */
export function generateWhatsAppCancellationLink(
  phone: string,
  supplierName: string,
  orderId: string,
  items: { productName: string; quantity: number; unit: string }[],
  reason = 'Please cancel this order',
  clinicName = 'Your Dental Clinic',
  defaultCountryCode = '93'
): string {
  const formattedPhone = formatWhatsAppPhone(phone, defaultCountryCode)

  const itemList = items
    .map((item, idx) => `${idx + 1}. ${item.productName} (Qty: ${item.quantity} ${item.unit})`)
    .join('\n')

  const message = `Order Cancellation\n\n` +
    `From: ${clinicName}\n` +
    `To: ${supplierName}\n` +
    `Order ID: ${orderId}\n` +
    `Date: ${new Date().toLocaleDateString()}\n\n` +
    `Items to cancel:\n${itemList}\n\n` +
    `Reason: ${reason}\n\n` +
    `Please confirm cancellation. Thank you.\n` +
    `_Sent via Dental Clinic Manager_`

  const encoded = encodeURIComponent(message)
  return `https://wa.me/${formattedPhone}?text=${encoded}`
}
