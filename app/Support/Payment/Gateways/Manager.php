<?php
namespace App\Support\Payment\Gateways;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

interface Manager {
    public function getName() : String;
    // public function getResponse() : Array;
    public function authenticate(Array $user);
    public function payWithCard(Request $request, String $reference);
}
