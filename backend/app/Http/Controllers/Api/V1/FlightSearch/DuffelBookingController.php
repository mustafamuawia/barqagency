<?php

namespace App\Http\Controllers\Api\V1\FlightSearch;

use App\Exceptions\DuffelBookingUncertainException;
use App\Exceptions\DuffelDuplicateBookingException;
use App\Exceptions\DuffelOfferExpiredException;
use App\Exceptions\InsufficientWalletBalanceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FlightSearch\BookDuffelOfferRequest;
use App\Models\DuffelBookingAttempt;
use App\Models\Order;
use App\Services\FlightSearch\DuffelOrderService;
use App\Services\Pdf\PdfRenderer;
use App\Traits\AuthorizesAgencyOwnership;
use Illuminate\Http\Request;

class DuffelBookingController extends Controller
{
    use AuthorizesAgencyOwnership;

    public function __construct(
        private readonly DuffelOrderService $duffelOrderService,
        private readonly PdfRenderer $pdfRenderer,
    ) {}

    public function book(BookDuffelOfferRequest $request)
    {
        try {
            $result = $this->duffelOrderService->book($request->user(), $request->validated());
        } catch (DuffelDuplicateBookingException $e) {
            return response()->json([
                'message'   => $e->getMessage(),
                'duplicate' => true,
            ], 409);
        } catch (DuffelOfferExpiredException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'expired' => true,
            ], 409);
        } catch (DuffelBookingUncertainException $e) {
            // 202 ≠ failure. The booking status is UNKNOWN — Duffel may have confirmed it.
            // The frontend must NOT allow an immediate retry; the user should wait for
            // reconciliation (automated) or check the booking_attempt_id status endpoint.
            return response()->json([
                'status'              => 'uncertain',
                'message'             => $e->getMessage(),
                'booking_attempt_id'  => $e->getAttemptUuid(),
                'retry_after_seconds' => 30,
            ], 202);
        } catch (InsufficientWalletBalanceException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        return response()->json([
            'message' => 'تم الحجز بنجاح',
            'booking' => $result,
        ], 201);
    }

    /**
     * Poll the status of an uncertain booking attempt.
     * The frontend should call this endpoint after receiving a 202 response.
     */
    public function bookingAttemptStatus(Request $request, string $uuid)
    {
        $agencyId = $this->agencyIdFor($request->user());

        $attempt = DuffelBookingAttempt::where('uuid', $uuid)
            ->where('agency_id', $agencyId)
            ->firstOrFail();

        return response()->json([
            'uuid'           => $attempt->uuid,
            'status'         => $attempt->status,
            'order_number'   => $attempt->order?->order_number,
            'created_at'     => $attempt->created_at,
            'resolved_at'    => $attempt->resolved_at,
            'within_window'  => $attempt->isWithinIdempotencyWindow(),
        ]);
    }

    public function cancel(Request $request, Order $order)
    {
        $result = $this->duffelOrderService->cancel($request->user(), $order);

        return response()->json([
            'message' => 'تم إلغاء الحجز بنجاح',
            'refund' => $result,
        ]);
    }

    public function ticket(Request $request, Order $order)
    {
        if ($request->user()->isAgency()) {
            $agencyId = $request->user()->agencies()->first()?->id;
            if ($agencyId !== $order->agency_id) {
                abort(403);
            }
        }

        $duffelOrderId = $order->metadata['duffel_order_id'] ?? null;
        if (!$duffelOrderId) {
            return response()->json(['message' => 'هذا الطلب لم يتم حجزه عبر Duffel'], 422);
        }

        $ticket = $this->duffelOrderService->fetchTicket($duffelOrderId, $order);

        return response()->json($ticket);
    }

    public function ticketPdf(Request $request, Order $order)
    {
        if ($request->user()->isAgency()) {
            $agencyId = $request->user()->agencies()->first()?->id;
            if ($agencyId !== $order->agency_id) {
                abort(403);
            }
        }

        $duffelOrderId = $order->metadata['duffel_order_id'] ?? null;
        if (!$duffelOrderId) {
            abort(422, 'هذا الطلب لم يتم حجزه عبر Duffel');
        }

        $ticket = $this->duffelOrderService->fetchTicket($duffelOrderId, $order);

        $filename = 'ticket-' . ($ticket['booking_reference'] ?? $order->order_number) . '.pdf';

        return $this->pdfRenderer->render('reports.duffel-ticket', compact('ticket'), $filename);
    }
}
