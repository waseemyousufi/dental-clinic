/**
 * Formats phone number for wa.me link (removes non-digit chars, ensures country code)
 */
export function formatWhatsAppPhone(phone: string): string {
  const cleaned = phone.replace(/[^\d+]/g, '');
  if (cleaned.startsWith('00')) return cleaned.replace('00', '');
  if (cleaned.startsWith('0') && !cleaned.startsWith('+')) {
    // Assume default country code if missing (customize for your region)
    return '55' + cleaned.slice(1); // Example: Brazil +55
  }
  return cleaned.startsWith('+') ? cleaned.slice(1) : cleaned;
}

export function generateWelcomePatientLink(phone: string, patientName: string, clinicName: string) {
  const formattedPhone = formatWhatsAppPhone(phone)

  const message = `Hello ${patientName}, welcome to ${clinicName}`

  const encoded = encodeURIComponent(message)

  return `https://wa.me/${formattedPhone}?text=${encoded}`;
}

export function generatePatientAppointmentReminderLink(phone: string, patientName: string, appointmentDate: string, appointmentReason: string, clinicName: string) {
  const formattedPhone = formatWhatsAppPhone(phone)

  const message = `Hello ${patientName}, You have an appointment in ${appointmentDate} for ${appointmentReason} in ${clinicName}. `

  const encoded = encodeURIComponent(message)

  return `https://wa.me/${formattedPhone}?text=${encoded}`;
}

/**
 * Generates wa.me link with pre-filled message for a new order
 */
export function generateWhatsAppOrderLink(
  phone: string,
  supplierName: string,
  items: { productId: string; productName: string; quantity: number; unit: string; notes?: string }[],
  clinicName = 'Your Dental Clinic'
): string {
  const formattedPhone = formatWhatsAppPhone(phone);

  const itemList = items
    .map((item, idx) => `${idx + 1}. *${item.productName}* - Qty: ${item.quantity} ${item.unit}${item.notes ? ` (${item.notes})` : ''}`)
    .join('\n');

  const message = `New Purchase Order\n\n` +
    `From: ${clinicName}\n` +
    `Supplier: ${supplierName}\n` +
    `Date: ${new Date().toLocaleDateString()}\n\n` +
    `Items:\n${itemList}\n\n` +
    `Total Items: ${items.reduce((sum, i) => sum + i.quantity, 0)}\n\n` +
    `Please confirm availability and delivery time.\n` +
    `_Sent via Dental Clinic Manager_`;

  const encoded = encodeURIComponent(message);
  return `https://wa.me/${formattedPhone}?text=${encoded}`;
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
  clinicName = 'Your Dental Clinic'
): string {
  const formattedPhone = formatWhatsAppPhone(phone);

  const itemList = items
    .map((item, idx) => `${idx + 1}. ${item.productName} (Qty: ${item.quantity} ${item.unit})`)
    .join('\n');

  const message = `Order Cancellation\n\n` +
    `From: ${clinicName}\n` +
    `To: ${supplierName}\n` +
    `Order ID: ${orderId}\n` +
    `Date: ${new Date().toLocaleDateString()}\n\n` +
    `Items to cancel:\n${itemList}\n\n` +
    `Reason: ${reason}\n\n` +
    `Please confirm cancellation. Thank you.\n` +
    `_Sent via Dental Clinic Manager_`;

  const encoded = encodeURIComponent(message);
  return `https://wa.me/${formattedPhone}?text=${encoded}`;
}

/**
 * Opens WhatsApp in new tab
 */
export function sendViaWhatsApp(link: string): void {
  window.open(link, '_blank', 'noopener,noreferrer');
}
