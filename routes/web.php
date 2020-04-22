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

    /*
    # After Step 1
    $token = env('BLOCKCYPHER_TOKEN');

    $blockCypherHelper = new \App\Libraries\BlockCypherHelper();
    $tx = "01000000011935b41d12936df99d322ac8972b74ecff7b79408bbccaf1b2eb8015228beac8000000006b483045022100921fc36b911094280f07d8504a80fbab9b823a25f102e2bc69b14bcd369dfc7902200d07067d47f040e724b556e5bc3061af132d5a47bd96e901429d53c41e0f8cca012102152e2bb5b273561ece7bbe8b1df51a4c44f5ab0bc940c105045e2cc77e618044ffffffff0240420f00000000001976a9145fb1af31edd2aa5a2bbaa24f6043d6ec31f7e63288ac20da3c00000000001976a914efec6de6c253e657a9d5506a78ee48d89762fb3188ac00000000";
    $res = $blockCypherHelper->createNewTransaction($tx);
    */


    $bw = new \BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory();
    $privateKey = $bw->fromWif('5Hwig3iZrm6uxS6Ch1egmJGyC89Q76X5tgVgtbEcLTPTx3aW5Zi');
    $txOut = new \BitWasp\Bitcoin\Transaction\TransactionOutput(
        1501000,
        \BitWasp\Bitcoin\Script\ScriptFactory::scriptPubKey()->payToPubKeyHash($privateKey->getPubKeyHash())
    );

    // Create a spend transaction
    $addressCreator = new \BitWasp\Bitcoin\Address\AddressCreator();
    $transaction = \BitWasp\Bitcoin\Transaction\TransactionFactory::build()
        ->input('87f7b7639d132e9817f58d3fe3f9f65ff317dc780107a6c10cba5ce2ad1e4ea1', 0)
        ->payToAddress(1500000, $addressCreator->fromString('1DUzqgG31FvNubNL6N1FVdzPbKYWZG2Mb6'))
        ->get();

    $signer = new  BitWasp\Bitcoin\Transaction\Factory\Signer($transaction);
    $input = $signer->input(0, $txOut);
    $input->sign($privateKey);
    $signed = $signer->get();

    dd($signed->getTxId()->getHex());

    // https://github.com/Bit-Wasp/bitcoin-php/blob/1.0/examples/doc/tx/007_sign_p2pkh_tx.php
});
