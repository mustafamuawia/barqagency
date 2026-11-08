<?php

namespace App\Services\FlightSearch;

class TicketDataFormatter
{

    public function format(array $duffelOrder, array $localContext): array
    {
        $passengers   = $this->formatPassengers($duffelOrder);
        $segments     = $this->formatSegments($duffelOrder);
        $baggage      = $this->formatBaggage($duffelOrder, $passengers);
        $seats        = $this->formatSeats($duffelOrder);
        $ticketStatus = $this->resolveTicketStatus($duffelOrder);
        $bookingStatus = $this->resolveBookingStatus($duffelOrder, $localContext);
        $docType      = $this->resolveDocType($ticketStatus, $bookingStatus);

        return [
            'doc_type'           => $docType,
            'booking_status'     => $bookingStatus,
            'ticket_status'      => $ticketStatus,
            'booking_reference'  => $duffelOrder['booking_reference'] ?? null,
            'booking_references' => $duffelOrder['booking_references'] ?? [],
            'duffel_order_id'    => $duffelOrder['id'] ?? null,
            'booked_at'          => $duffelOrder['created_at'] ?? null,
            'order_number'       => $localContext['order_number'],
            'passengers'         => $passengers,
            'segments'           => $segments,
            'baggage'            => $baggage,
            'seats'              => $seats,
            'has_baggage'        => !empty(array_filter($baggage, fn ($b) => $b['quantity'] > 0)),
            'has_seats'          => !empty($seats),
        ];
    }

    private function resolveTicketStatus(array $duffelOrder): string
    {
        foreach ($duffelOrder['documents'] ?? [] as $doc) {
            if (($doc['type'] ?? '') !== 'electronic_ticket') {
                continue;
            }
            $uid = $doc['unique_identifier'] ?? null;
            if (!empty($uid) && strlen((string) $uid) > 0) {
                return 'issued';
            }
        }
        return 'not_issued';
    }

    private function resolveBookingStatus(array $duffelOrder, array $localContext): string
    {
        $localStatus = $localContext['status'] ?? 'confirmed';

        if ($localStatus === 'cancelled') {
            return 'cancelled';
        }

        $paymentStatus = $duffelOrder['payment_status'] ?? null;
        if ($paymentStatus === 'paid') {
            return 'confirmed';
        }
        if ($paymentStatus === 'awaiting_payment') {
            return 'pending';
        }

        return match ($localStatus) {
            'confirmed', 'approved' => 'confirmed',
            'pending_pricing', 'pricing_sent', 'draft' => 'pending',
            default => 'confirmed',
        };
    }

    private function resolveDocType(string $ticketStatus, string $bookingStatus): string
    {
        return match (true) {
            $bookingStatus === 'pending' || $bookingStatus === 'cancelled' => 'booking_pending',
            $ticketStatus === 'issued' => 'electronic_ticket',
            default => 'booking_confirmation',
        };
    }

    private function formatPassengers(array $duffelOrder): array
    {
        // Index e-tickets by passenger_id for quick lookup.
        $eticketsByPassenger = [];
        foreach ($duffelOrder['documents'] ?? [] as $doc) {
            if (($doc['type'] ?? '') !== 'electronic_ticket') {
                continue;
            }
            $uid = $doc['unique_identifier'] ?? null;
            if (empty($uid)) {
                continue;
            }
            foreach ($doc['passenger_ids'] ?? [] as $pid) {
                $eticketsByPassenger[$pid] = (string) $uid;
            }
        }

        return collect($duffelOrder['passengers'] ?? [])
            ->map(function ($p) use ($eticketsByPassenger) {
                $pid = $p['id'];
                return [
                    'id'            => $pid,
                    'name'          => trim(($p['given_name'] ?? '') . ' ' . ($p['family_name'] ?? '')),
                    'type'          => $p['type'] ?? 'adult',
                    'ticket_number' => $eticketsByPassenger[$pid] ?? null,
                    'ticket_issued' => isset($eticketsByPassenger[$pid]),
                ];
            })
            ->values()
            ->all();
    }

    private function formatSegments(array $duffelOrder): array
    {
        $segments = [];

        foreach ($duffelOrder['slices'] ?? [] as $sliceIdx => $slice) {
            $segCount = count($slice['segments'] ?? []);
            foreach ($slice['segments'] ?? [] as $segIdx => $seg) {
                $marketing  = $seg['marketing_carrier'] ?? [];
                $operating  = $seg['operating_carrier'] ?? [];
                $opName     = $operating['name'] ?? null;
                $mktName    = $marketing['name'] ?? null;
                $isCodeshare = $opName && $opName !== $mktName;

                // Collect per-passenger baggage for this segment.
                $baggageAllowances = [];
                foreach ($seg['passengers'] ?? [] as $paxSeg) {
                    $pid = $paxSeg['passenger_id'] ?? null;
                    foreach ($paxSeg['baggages'] ?? [] as $bag) {
                        $baggageAllowances[] = [
                            'passenger_id' => $pid,
                            'type'         => $bag['type'] ?? 'checked',
                            'quantity'     => (int) ($bag['quantity'] ?? 0),
                        ];
                    }
                }

                $segments[] = [
                    'id'                => $seg['id'] ?? null,
                    'slice_index'       => $sliceIdx,
                    'segment_index'     => $segIdx,
                    'is_return'         => $sliceIdx > 0,
                    'is_connection'     => $segIdx > 0,
                    'has_next'          => $segIdx < $segCount - 1,
                    'flight_number'     => ($marketing['iata_code'] ?? '') . ($seg['marketing_carrier_flight_number'] ?? ''),
                    'airline'           => $mktName,
                    'airline_code'      => $marketing['iata_code'] ?? null,
                    'operating_airline' => $isCodeshare ? $opName : null,
                    'origin_code'       => $seg['origin']['iata_code'] ?? null,
                    'origin_name'       => $seg['origin']['name'] ?? null,
                    'origin_city'       => $seg['origin']['city']['name'] ?? ($seg['origin']['city_name'] ?? null),
                    'destination_code'  => $seg['destination']['iata_code'] ?? null,
                    'destination_name'  => $seg['destination']['name'] ?? null,
                    'destination_city'  => $seg['destination']['city']['name'] ?? ($seg['destination']['city_name'] ?? null),
                    'departing_at'      => $seg['departing_at'] ?? null,
                    'arriving_at'       => $seg['arriving_at'] ?? null,
                    'aircraft'          => $seg['aircraft']['name'] ?? null,
                    'duration_minutes'  => $this->parseDuration($seg['duration'] ?? ''),
                    'baggage'           => $baggageAllowances,
                ];
            }
        }

        return $segments;
    }

    // Build a per-passenger baggage summary across the full itinerary.
    // Takes the highest quantity seen per (passenger, type) pair across all segments.
    private function formatBaggage(array $duffelOrder, array $passengers): array
    {
        $summary = [];

        foreach ($duffelOrder['slices'] ?? [] as $slice) {
            foreach ($slice['segments'] ?? [] as $seg) {
                foreach ($seg['passengers'] ?? [] as $paxSeg) {
                    $pid = $paxSeg['passenger_id'] ?? null;
                    if (!$pid) {
                        continue;
                    }
                    foreach ($paxSeg['baggages'] ?? [] as $bag) {
                        $type = $bag['type'] ?? 'checked';
                        $qty  = (int) ($bag['quantity'] ?? 0);
                        $key  = $pid . '_' . $type;
                        $weight = $bag['maximum_weight_kg'] ?? ($bag['metadata']['maximum_weight_kg'] ?? null);
                        if (!isset($summary[$key]) || $summary[$key]['quantity'] < $qty) {
                            $pax = collect($passengers)->firstWhere('id', $pid);
                            $summary[$key] = [
                                'passenger_id'   => $pid,
                                'passenger_name' => $pax ? $pax['name'] : $pid,
                                'type'           => $type,
                                'quantity'       => $qty,
                                'weight_kg'      => $weight ? (int) $weight : null,
                                'purchased'      => false,
                            ];
                        } elseif ($weight && !$summary[$key]['weight_kg']) {
                            $summary[$key]['weight_kg'] = (int) $weight;
                        }
                    }
                }
            }
        }

        foreach ($duffelOrder['services'] ?? [] as $service) {
            if (($service['type'] ?? '') !== 'baggage') {
                continue;
            }
            $meta     = $service['metadata'] ?? [];
            $weight   = isset($meta['maximum_weight_kg']) ? (int) $meta['maximum_weight_kg'] : null;
            $qty      = (int) ($service['quantity'] ?? 1);
            $bagType  = $meta['type'] ?? 'checked';
            foreach ($service['passenger_ids'] ?? [] as $pid) {
                $key = $pid . '_service_' . $bagType;
                $pax = collect($passengers)->firstWhere('id', $pid);
                $summary[$key] = [
                    'passenger_id'   => $pid,
                    'passenger_name' => $pax ? $pax['name'] : $pid,
                    'type'           => $bagType,
                    'quantity'       => $qty,
                    'weight_kg'      => $weight,
                    'purchased'      => true,
                ];
            }
        }

        return array_values($summary);
    }

    private function formatSeats(array $duffelOrder): array
    {
        $seats = [];

        foreach ($duffelOrder['services'] ?? [] as $service) {
            if (($service['type'] ?? '') !== 'seat') {
                continue;
            }
            $designator = $service['metadata']['designator'] ?? null;
            if (!$designator) {
                continue;
            }
            foreach ($service['passenger_ids'] ?? [] as $pid) {
                foreach ($service['segment_ids'] ?? [] as $sid) {
                    $seats[] = [
                        'passenger_id' => $pid,
                        'segment_id'   => $sid,
                        'seat_number'  => $designator,
                    ];
                }
            }
        }

        return $seats;
    }

    private function parseDuration(string $iso8601): int
    {
        if (!$iso8601) {
            return 0;
        }
        preg_match('/P(?:(\d+)D)?T(?:(\d+)H)?(?:(\d+)M)?/', $iso8601, $m);
        return ((int) ($m[1] ?? 0) * 24 + (int) ($m[2] ?? 0)) * 60 + (int) ($m[3] ?? 0);
    }
}
