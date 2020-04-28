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
    $private = "2121212121212";
    $fromAddress = "15ndrKTjcJNNEaK94hcurucYPmtKhcpu6z";   # local address
    $toAddress = "16WvUJ4wamAxQFwvRcWvyTQoTvANQXBr6Y";     # binance address
    $value = 0.000058;
    $fee = 0.000008;

    $bds = new \App\Libraries\BitcoinDischargeService();
    $res = $bds->createTransaction($private, $fromAddress, $value, $fee, $toAddress);
    return $res;

});
