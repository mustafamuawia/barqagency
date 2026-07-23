<?php

namespace App\Http\Controllers\Api\V1\Report;

use App\DTOs\Report\ReportFilterData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Report\ReportFilterRequest;
use App\Services\Report\ReportService;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService)
    {
    }

    public function financialSummary(ReportFilterRequest $request)
    {
        $summary = $this->reportService->summary($request->user(), ReportFilterData::fromRequest($request));

        return response()->json($summary);
    }

    public function financialSummaryPdf(ReportFilterRequest $request)
    {
        return $this->reportService->renderPdf($request->user(), ReportFilterData::fromRequest($request));
    }

    public function financialSummaryCsv(ReportFilterRequest $request)
    {
        return $this->reportService->renderCsv($request->user(), ReportFilterData::fromRequest($request));
    }
}
