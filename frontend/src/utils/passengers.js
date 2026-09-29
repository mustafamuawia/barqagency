// The passenger_types reference table is seeded in English (Adult/Child/Infant) because it
// mirrors the IATA codes, but every screen is Arabic — translate on the way out.
const LABELS = {
  Adult: 'بالغ',
  Child: 'طفل',
  Infant: 'رضيع',
}

export function passengerTypeLabel(type) {
  const name = typeof type === 'string' ? type : type?.name
  return LABELS[name] || name || 'غير محدد'
}

// A passenger row is created with only its type; the traveller details are filled in when
// the agency approves the quote.
export function isPassengerIncomplete(passenger) {
  return !passenger?.first_name || !passenger?.last_name || !passenger?.passport_number
}
