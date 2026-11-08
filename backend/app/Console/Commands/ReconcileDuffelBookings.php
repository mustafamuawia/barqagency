<?php

namespace App\Console\Commands;

use App\Models\DuffelBookingAttempt;
use App\Services\FlightSearch\DuffelOrderService;
use Illuminate\Console\Command;

/**
 * Reconcile pending Duffel booking attempts whose outcome was unknown due to a network timeout.
 *
 * Duffel idempotency guarantee (24-hour window):
 *   Re-sending POST /air/orders with the SAME Idempotency-Key either returns the
 *   original response (if Duffel processed it) or creates the booking fresh (if
 *   Duffel never received it). Either way no double-booking occurs.
 *
 * Schedule: run every 5 minutes via the Laravel scheduler so uncertain bookings
 * are resolved quickly within Duffel's idempotency window.
 *
 * Example scheduler entry in routes/console.php or Kernel.php:
 *   Schedule::command('duffel:reconcile-bookings')->everyFiveMinutes();
 */
class ReconcileDuffelBookings extends Command
{
    protected $signature   = 'duffel:reconcile-bookings';
    protected $description = 'Reconcile pending Duffel booking attempts using the original idempotency key';

    public function handle(DuffelOrderService $service): int
    {
        $pending = DuffelBookingAttempt::where('status', 'pending')
            ->where('created_at', '>=', now()->subHours(24))
            ->where(function ($q) {
                // Retry at most once per 5 minutes to avoid hammering Duffel.
                $q->whereNull('last_attempted_at')
                  ->orWhere('last_attempted_at', '<=', now()->subMinutes(5));
            })
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No pending Duffel booking attempts to reconcile.');
            return Command::SUCCESS;
        }

        $this->info("Reconciling {$pending->count()} pending attempt(s)...");
        $counts = ['reconciled' => 0, 'retry' => 0, 'failed' => 0, 'expired' => 0, 'already_reconciled' => 0, 'insufficient_balance' => 0];

        foreach ($pending as $attempt) {
            $result = $service->reconcileAttempt($attempt);
            $counts[$result] = ($counts[$result] ?? 0) + 1;
            $this->line("  [{$result}] attempt {$attempt->uuid}");
        }

        $this->table(['outcome', 'count'], collect($counts)->map(fn ($c, $k) => [$k, $c])->values()->toArray());

        return Command::SUCCESS;
    }
}
