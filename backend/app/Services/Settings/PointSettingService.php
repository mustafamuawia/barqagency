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

    public function update(float $moneyAmount, float $pointAmount): PointSetting
    {
        $setting = $this->current();
        $previous = ['money_amount' => (float) $setting->money_amount, 'point_amount' => (float) $setting->point_amount];

        $setting->update([
            'money_amount' => $moneyAmount,
            'point_amount' => $pointAmount,
            'updated_by' => auth()->id(),
        ]);

        $this->audit->log('point_setting.updated', $setting, $previous, ['money_amount' => $moneyAmount, 'point_amount' => $pointAmount]);

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
}
