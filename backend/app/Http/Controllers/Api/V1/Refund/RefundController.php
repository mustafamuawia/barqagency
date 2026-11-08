<?php

namespace App\Http\Controllers\Api\V1\Refund;

use App\DTOs\Refund\CreateRefundData;
use App\DTOs\Refund\DecideRefundData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Refund\ApproveRefundRequest;
use App\Http\Requests\Api\V1\Refund\RejectRefundRequest;
use App\Http\Requests\Api\V1\Refund\StoreRefundRequest;
use App\Http\Resources\Api\V1\Refund\RefundResource;
use App\Models\Refund;
use App\Services\Refund\RefundService;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function __construct(private readonly RefundService $refundService)
    {
    }

    public function index(Request $request)
    {
        $refunds = $this->refundService->listForUser($request->user());

        return response()->json($refunds->toArray() + [
            'pending_count' => $this->refundService->pendingCountForUser($request->user()),
        ]);
    }

    public function show(Request $request, Refund $refund)
    {
        $refund = $this->refundService->findForUser($request->user(), $refund);

        return response()->json($refund);
    }

    public function store(StoreRefundRequest $request)
    {
        $refund = $this->refundService->create($request->user(), CreateRefundData::fromRequest($request));

        return response()->json([
            'message' => 'تم إرسال طلب الاسترداد',
            'refund' => new RefundResource($refund),
        ], 201);
    }

    public function approve(ApproveRefundRequest $request, Refund $refund)
    {
        try {
            $refund = $this->refundService->approve($refund, DecideRefundData::fromRequest($request));
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => 'لا تسمح شركة الطيران بإلغاء هذا الحجز عبر API.',
            ], 422);
        }

        return response()->json([
            'message' => 'تمت الموافقة على الاسترداد وإضافة المبلغ للمحفظة',
            'refund' => new RefundResource($refund),
        ]);
    }

    public function reject(RejectRefundRequest $request, Refund $refund)
    {
        $refund = $this->refundService->reject($refund, $request->validated('admin_remarks'));

        return response()->json([
            'message' => 'تم رفض طلب الاسترداد',
            'refund' => new RefundResource($refund),
        ]);
    }

    public function creditNote(Request $request, Refund $refund)
    {
        return $this->refundService->creditNotePdf($request->user(), $refund);
    }
}
