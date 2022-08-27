<?php
namespace App\Support\Payment\Gateways;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class Flutterwave implements Manager {
    private Array $user;

    public function getName() : String
    {
        return 'flutterwave';
    }

    public function authenticate(Array $user) {
        $this->user = [
            'fullname' => data_get($user, 'name'),
            'email' => data_get($user, 'email'),
            'phone_number' => data_get($user, 'mobile'),
        ];

        return $this;
    }

    public function payWithCard(Request $request, String $reference = null) {
        $request->validate([
            'card_number' => 'required',
            'expiry_month' => 'required',
            'expiry_year' => 'required',
            'reference' => 'sometimes',
        ]);

        //The data variable holds the payload
        $data = collect($request->toArray())->merge($this->user);
        $client = Http::acceptJson()->timeout(100)->withToken(config('payment.flutterwave.secret_key'))->baseUrl('https://api.flutterwave.com/v3/');
        $response = null;

        if($request->reference) {
            if($res = cache($request->reference)) {
                $reference = $request->reference;
                $data = $data->mergeRecursive(['authorization' => ['mode' => $res['meta']['authorization']['mode']]]);

                switch (data_get($res, 'meta.authorization.mode')) {
                    case 'pin':
                        $request->validate([
                            'authorization.pin' => 'required'
                        ]);
                        break;
                    case 'avs_noauth':
                        $request->validate([
                            'authorization.city' => 'required',
                            'authorization.address' => 'required',
                            'authorization.state' => 'required',
                            'authorization.country' => 'required',
                            'authorization.zipcode' => 'required'
                        ]);
                        break;
                    case 'otp':
                        $request->validate([
                            'authorization.otp' => 'required'
                        ]);

                        $response = $client->post(data_get($res, 'meta.authorization.endpoint'), [
                            'otp' => data_get($data->toArray(), 'authorization.otp'),
                            'flw_ref' => data_get($res, 'data.flw_ref')
                        ]);

                        break;
                    case 'redirect':
                        return [
                            'code' => \App\Enums\PaymentStatus::REDIRECT_REQUIRED,
                            'data' => ['link' => data_get($res, 'meta.authorization.redirect')]
                        ];
                        break;
                    default:
                        abort(\Exception::class);
                        break;
                }
            }
        }

        if(! $response) {
            $data->put('tx_ref', $reference);
            $data->put('redirect_url', route('payment.webhook'));
            $data->put('currency', config('payment.currency', 'NGN'));
            $data->put('amount', config('payment.amount', 2000));

            $response = $client->post('charges?type=card', [
                'client' => base64_encode(openssl_encrypt($data->toJson(), 'DES-EDE3', config('payment.flutterwave.encryption_key'), OPENSSL_RAW_DATA))
            ]);

        }

        cache([$reference => $response->json()], now()->addMinutes(30));

        $mode = data_get($response->json(), 'meta.authorization.mode');

        $mode = $mode ? \App\Enums\PaymentStatus::tryFrom($mode) : \App\Enums\PaymentStatus::PENDING;

        if($response->successful() && ! $mode->is(\App\Enums\PaymentStatus::PENDING)) {
            if(data_get($response->json(), 'meta.authorization.fields')) {
                return [
                    'code' => $mode,
                    'data' => [
                        'fields' => data_get($response->json(), 'meta.authorization.fields'),
                        'reference' => $reference
                    ]
                ];
            }
            return [
                'code' => $mode,
                'data' => [
                    'link' => data_get($response, 'meta.authorization.redirect')
                ]
            ];
        }


        return [
            'code' => $mode,
            'data' => [
                'reference' => $reference,
                'amount' => $data->get('amount'),
                'currency' => $data->get('currency')
            ]
        ];
    }

    public function verify($reference)
    {
        $response = Http::acceptJson()
                        ->timeout(100)
                        ->withToken(config('payment.flutterwave.secret_key'))
                        ->get("https://api.flutterwave.com/v3/transactions/$reference/verify")
                        ->json();
        return [
            'code' => data_get($response, 'status') == 'success' && data_get($response, 'data.status') == 'successful' ?
                    \App\Enums\PaymentStatus::CHARGED :
                    (data_get($response, 'status') == 'success' && data_get($response, 'data.status') == 'successful' ?
                    \App\Enums\PaymentStatus::PENDING :
                        \App\Enums\PaymentStatus::FAILED
                    ),
            'amount' => data_get($response, 'data.amount'),
            'reference' => $reference
        ];
    }
}
