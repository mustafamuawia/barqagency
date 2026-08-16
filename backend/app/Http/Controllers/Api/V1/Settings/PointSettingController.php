<?php

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Settings\UpdatePointSettingRequest;
use App\Http\Resources\Api\V1\Settings\PointSettingResource;
use App\Services\Settings\PointSettingService;

class PointSettingController extends Controller
{
    public function __construct(private readonly PointSettingService $pointSettingService)
    {
    }

    public function show()
    {
        return response()->json(new PointSettingResource($this->pointSettingService->current()));
    }

    public function update(UpdatePointSettingRequest $request)
    {
        $setting = $this->pointSettingService->update(
            (float) $request->validated('money_amount'),
            (float) $request->validated('point_amount')
        );

        return response()->json([
            'message' => 'تم تحديث سعر تحويل النقاط بنجاح',
            'setting' => new PointSettingResource($setting),
        ]);
    }
}
