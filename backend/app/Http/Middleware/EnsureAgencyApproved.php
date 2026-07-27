<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAgencyApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $agency = $user->agencies()->first();

        if (!$agency || $agency->status !== 'active') {
            if ($agency && $agency->status === 'rejected') {
                abort(403, 'تم رفض طلب تسجيل الوكالة'.($agency->rejection_reason ? ': '.$agency->rejection_reason : ''));
            }

            if (!$user->email_verified_at) {
                abort(403, 'يرجى تفعيل بريدك الإلكتروني أولاً');
            }

            abort(403, 'حسابك بانتظار موافقة الإدارة');
        }

        return $next($request);
    }
}
