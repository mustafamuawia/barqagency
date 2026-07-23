# Backend Architecture

**Status:** Current, as of 2026-07-23 (updated same day to add Auth password-reset, the Report domain, and PDF generation) — describes the real code, not an aspirational plan.

The Laravel backend (`backend/app/`) is organized by domain and by layer:

```
app/
├── DTOs/{Domain}/          Typed data crossing from a Request into a Service
├── Enums/                  Reserved for new fixed-value concepts (see below)
├── Exceptions/             Domain exceptions, rendered centrally
├── Helpers/                Small static utilities with no state
├── Http/
│   ├── Controllers/Api/V1/{Domain}/   Thin: validate → build DTO → call Service → shape response
│   ├── Requests/Api/V1/{Domain}/      Form Requests — all validation rules live here
│   ├── Resources/Api/V1/{Domain}/     Response shaping
│   └── Middleware/                    Cross-cutting, not domain-specific
├── Models/                 Eloquent models (unchanged)
├── Notifications/          Mail notifications (password reset)
├── Services/{Domain}/      Business logic, transactions, throws domain exceptions
└── Traits/                 Reusable behavior shared across Services
```

Domains today: `Auth, Order, Wallet, Refund, Agency, ReferenceData, Admin, Report` — one per existing controller. `ReferenceData` is pure lookup-table reads, so it has no Request/Resource/Service/DTO, just a Controller. `Report` has no Resource (it returns a plain array shape, plus PDF/CSV downloads) and no Exception (validation alone covers its failure modes).

## Layer responsibilities

- **Controller** — resolves the authenticated user, calls exactly one Service method, wraps the result in a Resource or `response()->json()`. No validation, no business logic, no `DB::transaction`.
- **Request** (`Http\Requests\Api\V1\...`) — one per validated action, `authorize()` returns `true` (access is already gated by route `role:` middleware), `rules()` holds the validation.
- **DTO** (`DTOs\...`) — a `final readonly` class with a `fromRequest()` factory. Created **only** when more than one field crosses into a Service in a structured way. Single-scalar actions (e.g. reject-refund's `admin_remarks`, set-credit-limit's `credit_limit`) pass the scalar straight through — no DTO wrapper for its own sake.
- **Service** (`Services\...`) — owns business rules and `DB::transaction` blocks, throws `Exceptions\*` instead of returning error responses inline.
- **Exception** (`Exceptions\*`) — all extend `DomainException` (`message` + `statusCode`), rendered once in `bootstrap/app.php`'s `withExceptions()` instead of each call site building its own `response()->json([...], 422)`.
- **Resource** (`Http\Resources\Api\V1\...`) — response shaping. Currently thin pass-throughs (`return $this->resource->toArray();`) preserving the existing API response shape exactly; give them real transforms if/when the API contract is intentionally redesigned.
- **Trait** (`Traits\AuthorizesAgencyOwnership`) — the "does this agency own this resource" 403 check, shared by `OrderService` and `RefundService`.
- **Helper** (`Helpers\ReferenceNumberGenerator`) — the `ORD-`/`REF-` reference number format, previously duplicated inline in two controllers.

## Enums decision

`OrderStatus`, `QuoteStatus`, `RefundStatus`, `UserType`, `WalletTransactionType` stay as DB-backed Eloquent lookup models — they are not being converted to PHP backed enums. `app/Enums/` is reserved for genuinely new fixed-value concepts introduced in future work; it's intentionally empty today rather than populated with a contrived first enum.

## API versioning

Routes are served under `/api/v1/...` (`routes/api.php` wraps everything in `Route::prefix('v1')`). The frontend's `VITE_API_URL` points at `.../api/v1`.

## Password reset

Standard Laravel `Password` broker (`password_reset_tokens` table already existed from the base `users` migration). `PasswordResetService` wraps `Password::sendResetLink()`/`Password::reset()`, throwing `InvalidResetTokenException` on a bad/expired token. `User::sendPasswordResetNotification()` is overridden to send `App\Notifications\ResetPasswordNotification`, which links to the Vue SPA (`config('app.frontend_url')` / `FRONTEND_URL` env var) instead of a Laravel Blade page — there is no server-rendered reset-password view. `forgot-password` always returns the same generic message whether or not the email exists, to avoid user enumeration. `MAIL_MAILER=log` in `.env` — reset emails currently land in `storage/logs/laravel.log`, not a real inbox; swap the mailer when real SMTP credentials exist.

## PDF generation

`barryvdh/laravel-dompdf` renders both the financial-summary report and the on-demand credit note (`resources/views/reports/*.blade.php`, shared styling in `reports/partials/style.blade.php`). Arabic requires an embedded font — dompdf's bundled fonts have no Arabic glyphs, so `resources/fonts/Cairo-Regular.ttf` and `Cairo-Bold.ttf` (static instances extracted from Google's variable Cairo font via `fontTools.varLib.instancer`, since dompdf doesn't support variable-font weight axes) are declared via `@font-face` and embedded with `enable_font_subsetting => true` in `config/dompdf.php`. Both weights must be declared — a `@font-face` with only `font-weight: normal` silently drops to a non-Arabic fallback font for any bold text, rendering it as `?` characters instead of throwing.

Credit notes are generated on-demand from the `Refund` record (no `credit_notes` table) — `RefundService::creditNotePdf()` throws `InvalidRefundStatusException` unless `refund.status === 'credited'`. Reports support PDF and CSV export, scoped by date range and (for admins) an optional `agency_id` filter; agency users are always scoped to their own agency via `AuthorizesAgencyOwnership`.

## Note: guest-redirect fix for API-only auth

`bootstrap/app.php` sets `$middleware->redirectGuestsTo(fn () => null)`. Without it, Laravel's default `Authenticate` middleware tries to redirect non-JSON-Accept requests to a route named `login` (which doesn't exist in this API-only app), throwing a 500 `RouteNotFoundException` instead of a clean 401 for any unauthenticated request that doesn't send `Accept: application/json` (axios always does, so the frontend never hit this — but any bare `curl`/external client would).
