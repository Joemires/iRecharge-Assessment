<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PaymentTest extends TestCase
{

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_charge_customer_action_required()
    {
        $endpoint = 'api/customer/1/charge/card';
        $data = [
            "card_number" => "4556052704172643",
            "expiry_month" => "01",
            "expiry_year" => "23",
            "cvv" => "899",
            "amount" => 5000,
        ];

        $response = $this->postJson($endpoint, collect($data)->toArray());

        // if($response->assertUnprocessable()) {
        //     if($response['error']['code'] == 'aws_required') {
        //         $response = $this->postJson($endpoint, collect($data)->merge([
        //             'reference' => $response['error']['reference'],
        //             'authorization' => [
        //                 'city' => 'San Francisco',
        //                 'address' => '69 Fremont Street',
        //                 'state' => 'CA',
        //                 'country' => 'US',
        //                 'zipcode' => '94105'
        //             ]
        //         ])->toArray());
        //     }

        //     if($response['error']['code'] == 'redirect_required') {

        //         if(\Illuminate\Support\Facades\Http::get($response['error']['link'])->ok()) {

        //         }
        //     }
        // }

        $response->assertUnprocessable();
    }
}
