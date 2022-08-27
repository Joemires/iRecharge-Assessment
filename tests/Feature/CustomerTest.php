<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_create_customer_successfully()
    {
        $faker = \Faker\Factory::create();

        $response = $this->postJson('/api/customer', [
            'name' => $faker->name(),
            'email' => $faker->email(),
            'password' => '12345678'
        ]);

        $response->assertStatus(201);
    }

    public function test_create_customer_validation_error()
    {
        $response = $this->postJson('/api/customer', [
            'name' => '',
            'email' => '',
            'password' => ''
        ]);

        $response->assertStatus(422);
    }
}
