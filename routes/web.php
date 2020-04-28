<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    $private = "L4XnkvPsa5HJ62HZjzRZYuZ6K9C1sP2G3adkYfUn6ziCxT5sXeiJ";
    $fromAddress = "15ndrKTjcJNNEaK94hcurucYPmtKhcpu6z";   # local address
    $toAddress = "16WvUJ4wamAxQFwvRcWvyTQoTvANQXBr6Y";     # binance address
    $value = 0.000058;
    $fee = 0.000008;

    $bds = new \App\Libraries\BitcoinDischargeService();
    $res = $bds->createTransaction($private, $fromAddress, $value, $fee, $toAddress);
    return $res;

});
