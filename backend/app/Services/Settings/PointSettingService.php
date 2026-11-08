<?php

namespace App\Services\Settings;

use App\Models\PointSetting;
use App\Services\Audit\AuditLogger;

class PointSettingService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function current(): PointSetting
    {
        return PointSetting::first() ?? PointSetting::create(['money_amount' => 1, 'point_amount' => 1]);
    }

    public function update(float $moneyAmount, float $pointAmount, ?float $commissionAmount = null, ?float $usdToSarRate = null): PointSetting
    {
        $setting = $this->current();
        $previous = [
            'money_amount'             => (float) $setting->money_amount,
            'point_amount'             => (float) $setting->point_amount,
            'agency_commission_amount' => (float) $setting->agency_commission_amount,
            'usd_to_sar_rate'          => (float) $setting->usd_to_sar_rate,
        ];

        $data = [
            'money_amount' => $moneyAmount,
            'point_amount' => $pointAmount,
            'updated_by'   => auth()->id(),
        ];

        if ($commissionAmount !== null) {
            $data['agency_commission_amount'] = $commissionAmount;
        }
        if ($usdToSarRate !== null) {
            $data['usd_to_sar_rate'] = $usdToSarRate;
        }

        $setting->update($data);

        $this->audit->log('point_setting.updated', $setting, $previous, $data);

        return $setting->fresh();
    }

    /** money_amount units of money = point_amount units of points. */
    public function moneyToPoints(float $money): float
    {
        $setting = $this->current();
        $moneyAmount = (float) $setting->money_amount;

        return $moneyAmount > 0 ? round($money * ((float) $setting->point_amount / $moneyAmount), 3) : 0.0;
    }

    public function pointsToMoney(float $points): float
    {
        $setting = $this->current();
        $pointAmount = (float) $setting->point_amount;

        return $pointAmount > 0 ? round($points * ((float) $setting->money_amount / $pointAmount), 3) : 0.0;
    }

    public function usdToSar(float $usdAmount): float
    {
        $rate = (float) $this->current()->usd_to_sar_rate;
        return round($usdAmount * ($rate > 0 ? $rate : 3.75), 3);
    }

    public function currencyToSar(float $amount, string $currency): float
    {
        if ($currency === 'SAR') {
            return $amount;
        }
        $rate = (float) $this->current()->usd_to_sar_rate;
        $rate = $rate > 0 ? $rate : 3.75;
        $rates = ['USD' => $rate, 'GBP' => $rate * 1.26, 'EUR' => $rate * 1.08];
        return round($amount * ($rates[$currency] ?? $rate), 3);
    }
}
