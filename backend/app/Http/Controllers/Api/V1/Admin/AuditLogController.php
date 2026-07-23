<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AuditLogService;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    public function index(Request $request)
    {
        return response()->json($this->auditLogService->logs($request->query('event')));
    }

    public function loginHistory()
    {
        return response()->json($this->auditLogService->loginHistory());
    }
}
