<?php

namespace App\Http\Controllers;

use App\Models\TravelAgency;
use Illuminate\Http\Request;

class AgencyController extends Controller
{
    public function index()
    {
        $agencies = TravelAgency::with(['wallet', 'agencyType'])
            ->orderBy('name')
            ->get();

        return response()->json($agencies);
    }

    public function show(TravelAgency $agency)
    {
        return response()->json($agency->load(['wallet', 'agencyType', 'orders']));
    }

    public function update(Request $request, TravelAgency $agency)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string',
            'address' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:active,suspended,inactive',
            'credit_limit' => 'sometimes|numeric|min:0',
        ]);

        $agency->update($validated);

        // If credit limit changed, sync wallet credit limit too
        if (isset($validated['credit_limit']) && $agency->wallet) {
            $agency->wallet->update(['credit_limit' => $validated['credit_limit']]);
        }

        return response()->json([
            'message' => 'Agency updated',
            'agency' => $agency->fresh(['wallet']),
        ]);
    }
}
