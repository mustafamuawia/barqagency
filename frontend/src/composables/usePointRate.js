import { ref } from 'vue'
import api from '@/lib/api'

/**
 * The points/money exchange rate is admin-configurable ("X money = Y points") and is not
 * always 1:1, so any screen that shows a money figure the wallet will be charged for has to
 * read the rate from the server rather than assume the two are interchangeable.
 */
export function usePointRate() {
  const rate = ref({ money_amount: null, point_amount: null })

  const loadRate = async () => {
    try {
      const response = await api.get('/point-settings')
      rate.value = { money_amount: response.data.money_amount, point_amount: response.data.point_amount }
    } catch (err) {
      console.error('Failed to load point settings', err)
    }
  }

  const moneyToPoints = (money) => {
    if (!rate.value.money_amount) return 0
    return (Number(money) || 0) * (rate.value.point_amount / rate.value.money_amount)
  }

  return { rate, loadRate, moneyToPoints }
}
