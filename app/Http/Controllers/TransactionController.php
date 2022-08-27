<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class TransactionController extends Controller
{

    public function __construct(\App\Support\Payment\Gateways\Manager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(User $user = null)
    {
        return responder()->success($user->transactions()->paginate(25))->respond();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, User $user)
    {
        info($request->all());
        $this->paymentManager->authenticate($user->toArray());

        $method = Str::of($request->method)->title()->prepend('payWith')->toString();

        if(method_exists($this->paymentManager, $method)) {
            $response = app()->call([$this->paymentManager, $method], [
                'reference' => Str::random(10)
            ]);

            $code = data_get($response, 'code');
            abort_unless($code, 503, "We are sorry but an error occurred during payment, please try again later or contact support");

            if(! $code->is(\App\Enums\PaymentStatus::PENDING)) {
                return responder()
                    ->error(Str::lower($code->name), $code->message())
                    ->data(collect(data_get($response, 'data'))->toArray())
                    ->respond(422);
            }

            return $this->commit_transaction($user, data_get($data, 'amount'), $reference);
        }

    }

    public function webhook(Request $request)
    {
        if($request->isMethod('GET')) {

            $data = json_decode(request()->response, true);

            $user = User::where('email', data_get($data, 'customer.email'))->firstOrFail();

            return $this->commit_transaction($user, data_get($data, 'amount'), data_get(json_decode(request()->response, true), 'txRef'));
        }
    }

    public function commit_transaction($user, $amount, $reference)
    {
        // Verify transaction status
        $response = $this->paymentManager->verify($reference);
        $code = data_get($response, 'code');

        // Create Transaction
        if(! $user->transactions()->where('meta->reference', $reference)->exists()) {
            $transaction = $user->deposit(
                $amount,
                ['reference' => $reference],
                $code->is(\App\Enums\PaymentStatus::CHARGED)
            );
        } else {
            $transaction = Transaction::where('meta->reference', $reference)->first();
            if($transaction && ! $transaction->confirmed && $code->is(\App\Enums\PaymentStatus::CHARGED)) $transaction->confirm();
        }

        if($code->is(\App\Enums\PaymentStatus::PENDING)) {
            // Setup event to automatically check transaction status and confirm transaction
        }

        return $code->is(\App\Enums\PaymentStatus::CHARGED) ?
                responder()->success($transaction)->respond(201) :
                responder()->error('transaction_'.Str::lower($code->name))->data(['data' => $transaction])->respond(400);
    }
}
