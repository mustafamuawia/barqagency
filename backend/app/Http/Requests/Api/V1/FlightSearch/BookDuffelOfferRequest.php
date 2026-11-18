<?php

namespace App\Http\Requests\Api\V1\FlightSearch;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Validator;

class BookDuffelOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Pricing (total_price_sar, duffel_total_amount, duffel_total_currency) is NOT
            // accepted from the client — it is read from the server-side offer cache.
            'offer_id'  => 'required|string',

            'passengers' => 'required|array|min:1',
            'passengers.*.duffel_passenger_id' => 'required|string',
            'passengers.*.title' => 'required|in:mr,ms,mrs,miss,dr',
            'passengers.*.given_name' => 'required|string|max:100',
            'passengers.*.family_name' => 'required|string|max:100',
            'passengers.*.born_on' => 'required|date_format:Y-m-d',
            'passengers.*.email' => 'required|email',
            'passengers.*.phone_number' => 'required|string',
            'passengers.*.gender' => 'required|in:m,f',
            'passengers.*.duffel_type' => 'nullable|in:adult,child,infant',

            // Identity documents are conditionally required (enforced below via withValidator).
            'passengers.*.identity_documents' => 'nullable|array',
            'passengers.*.identity_documents.*.type' => 'required_with:passengers.*.identity_documents|string',
            'passengers.*.identity_documents.*.unique_identifier' => 'required_with:passengers.*.identity_documents|string',
            'passengers.*.identity_documents.*.expires_on' => 'required_with:passengers.*.identity_documents|date_format:Y-m-d',
            'passengers.*.identity_documents.*.issuing_country_code' => 'required_with:passengers.*.identity_documents|string|size:2',
        ];
    }

    public function messages(): array
    {
        return [
            'offer_id.required' => 'معرّف العرض مطلوب',
            'passengers.required' => 'بيانات المسافرين مطلوبة',
            'passengers.min' => 'يجب إضافة مسافر واحد على الأقل',
            'passengers.*.duffel_passenger_id.required' => 'معرّف المسافر مطلوب',
            'passengers.*.title.required' => 'اللقب مطلوب',
            'passengers.*.title.in' => 'اللقب غير صالح',
            'passengers.*.given_name.required' => 'الاسم الأول مطلوب',
            'passengers.*.given_name.max' => 'الاسم الأول طويل جداً',
            'passengers.*.family_name.required' => 'اسم العائلة مطلوب',
            'passengers.*.family_name.max' => 'اسم العائلة طويل جداً',
            'passengers.*.born_on.required' => 'تاريخ الميلاد مطلوب',
            'passengers.*.born_on.date_format' => 'صيغة تاريخ الميلاد غير صحيحة',
            'passengers.*.email.required' => 'البريد الإلكتروني مطلوب',
            'passengers.*.email.email' => 'البريد الإلكتروني غير صالح',
            'passengers.*.phone_number.required' => 'رقم الهاتف مطلوب',
            'passengers.*.gender.required' => 'الجنس مطلوب',
            'passengers.*.gender.in' => 'الجنس غير صالح',
            'passengers.*.duffel_type.in' => 'نوع المسافر غير صالح',
            'passengers.*.identity_documents.*.type.required_with' => 'نوع وثيقة الهوية مطلوب',
            'passengers.*.identity_documents.*.unique_identifier.required_with' => 'رقم وثيقة الهوية مطلوب',
            'passengers.*.identity_documents.*.expires_on.required_with' => 'تاريخ انتهاء وثيقة الهوية مطلوب',
            'passengers.*.identity_documents.*.expires_on.date_format' => 'صيغة تاريخ انتهاء وثيقة الهوية غير صحيحة',
            'passengers.*.identity_documents.*.issuing_country_code.required_with' => 'دولة إصدار وثيقة الهوية مطلوبة',
            'passengers.*.identity_documents.*.issuing_country_code.size' => 'رمز دولة الإصدار يجب أن يكون حرفين',
        ];
    }

    /**
     * After the structural rules pass, enforce identity-document requirements
     * from the server-side offer cache. We never trust a client-supplied boolean
     * to determine whether documents are mandatory.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $offerId     = $this->input('offer_id');
            $cachedOffer = Cache::get('duffel_offer:' . $offerId);

            // If the offer isn't cached it's expired; DuffelOrderService will throw
            // DuffelOfferExpiredException — no need to duplicate that check here.
            if (!$cachedOffer) {
                return;
            }

            if (!($cachedOffer['passport_required'] ?? false)) {
                // Offer does not mandate identity documents — validate only when supplied.
                // The nullable|array rule above already handles that.
                return;
            }

            foreach ($this->input('passengers', []) as $index => $passenger) {
                $docs = $passenger['identity_documents'] ?? [];

                if (empty($docs)) {
                    $validator->errors()->add(
                        "passengers.{$index}.identity_documents",
                        'بيانات وثيقة هوية المسافر مطلوبة لهذه الرحلة'
                    );
                    continue;
                }

                // Also ensure the first document has the mandatory sub-fields.
                $doc = $docs[0];
                if (empty($doc['unique_identifier'])) {
                    $validator->errors()->add(
                        "passengers.{$index}.identity_documents.0.unique_identifier",
                        'رقم وثيقة الهوية مطلوب'
                    );
                }
                if (empty($doc['expires_on'])) {
                    $validator->errors()->add(
                        "passengers.{$index}.identity_documents.0.expires_on",
                        'تاريخ انتهاء وثيقة الهوية مطلوب'
                    );
                }
                if (empty($doc['issuing_country_code'])) {
                    $validator->errors()->add(
                        "passengers.{$index}.identity_documents.0.issuing_country_code",
                        'دولة إصدار وثيقة الهوية مطلوبة'
                    );
                }
            }
        });
    }
}
