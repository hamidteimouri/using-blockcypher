<?php


namespace App\Libraries;


use App\Support\bitwasp\RawTransaction;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class BitcoinDischargeService
{
    private $token = '68b5431d8ace4297924f69b6b570b333';
    private $base_url = 'https://blockchain.info';
    private $client;
    private $status_error = 'error';
    private $status_error_code = '0';

    public function __construct()
    {
        $this->client = new Client(['verify' => false]);
    }


    public function createTransactionOld(array $fromAddresses, array $privateKeys, array $toAddressesAsKeyAmountAsValue, $changeAddress, $feeForBitcoin = 0.0)
    {
        if (count($toAddressesAsKeyAmountAsValue) == 0) {
            Log::channel('evacuatorBTC')->debug('Bitcoin autoPayment by Blockchain Failed because To address is empty');
            return $this->result(false, "To address is empty");
        }

        if ($feeForBitcoin == 0)
            $feeForBitcoin = $this->getFeeOnline(3, 2);

        if ($feeForBitcoin == 0) {
            Log::channel('evacuatorBTC')->debug('Bitcoin autoPayment by Blockchain Failed because Bitcoin fee is ZERO.');
            return $this->result(false, "zero fee not allowed");
        }

        $feeForBitcoin = number_format($feeForBitcoin, 8, ".", '');

        foreach ($toAddressesAsKeyAmountAsValue as $address => $amount)
            $toAddressesAsKeyAmountAsValue[$address] = number_format($amount, 8, ".", '');

        $url = "https://blockchain.info/unspent?confirmations=0&active=" . implode("|", $fromAddresses);

        $server_output = (@file_get_contents($url)) or Log::channel('evacuatorBTC')->debug('Error in get content from blockchain in bitcoin autoPayment.');

        $utxos = json_decode($server_output);

        if (!is_object($utxos)) {
            Log::channel('evacuatorBTC')->debug('Bitcoin autoPayment by Blockchain Failed because no UTXOS found.');
            return $this->result(false, "no utxos found!");
        }

        $totalAmountTo = 0;
        foreach ($toAddressesAsKeyAmountAsValue as $address => $amount)
            $totalAmountTo = bcadd($totalAmountTo, $amount, 8);
        $totalSpent = bcadd($totalAmountTo, $feeForBitcoin, 8);

        $totalAmountFrom = 0;
        $inputs = [];
        $utxos = $this->getBestUtxos($utxos->unspent_outputs, $totalSpent);
        if (count($utxos) > 0) {
            foreach ($utxos as $utxo) {
                $btcValue = $this->satoshiToBTC($utxo->value);
                $totalAmountFrom = bcadd($totalAmountFrom, $btcValue, 8);
                $inputs[] = [
                    "txid" => $utxo->tx_hash_big_endian,
                    "vout" => $utxo->tx_output_n,
                    "scriptPubKey" => $utxo->script
                ];
            }
        } else {
            Log::channel('evacuatorBTC')->debug('Bitcoin autoPayment by Blockchain Failed because no UTXO found in knapstack problem solving.');
            return $this->result(false, "no utxo found in knapstack problem solving.");
        }

        if ($totalAmountFrom < $totalSpent) {
            Log::channel('evacuatorBTC')->debug('Bitcoin autoPayment by Blockchain Failed because totalAmountFrom < totalSpent');
            return $this->result(false, "not enough balance. CurrentBalance=" . $totalAmountFrom . " NeededBalance=" . $totalSpent);
        }

        $changeAmount = bcsub($totalAmountFrom, $totalAmountTo, 8);
        $changeAmount = bcsub($changeAmount, $feeForBitcoin, 8);
        if ($changeAmount > 0)
            $toAddressesAsKeyAmountAsValue[$changeAddress] = $changeAmount;

        $json_inputs = json_encode($inputs);

        $wallet = [];
        RawTransaction::private_keys_to_wallet($wallet, $privateKeys, '00');

        $raw_transaction = RawTransaction::create($inputs, $toAddressesAsKeyAmountAsValue);

        $sign = RawTransaction::sign($wallet, $raw_transaction, $json_inputs);

        $server_output = json_decode($this->sendPostRequest("https://api.omniexplorer.info/v1/transaction/pushtx/", "signedTransaction=" . $sign['hex']));

        if (isset($server_output->status) && isset($server_output->pushed) && $server_output->status == "OK" && !empty($server_output->pushed == "success")) {
            Log::channel('evacuatorBTC')->debug('Bitcoin autoPayment by Blockchain Successful.');
            return $this->result(true, $server_output);
        }

        Log::channel('evacuatorBTC')->debug('Bitcoin autoPayment by Blockchain Failed and the output is --> ');
        return $this->result(false, $server_output);
    }

    public function createTransaction(string $privateKey, string $fromAddress, string $value, float $fee = 0.0, $toAddress = null, $confirmationNumber = 1)
    {

        if (is_null($toAddress)) {
            $toAddress = '16WvUJ4wamAxQFwvRcWvyTQoTvANQXBr6Y';  # this is for test   TODO
        }
        # get all unspent transaction with confirmation more than 0
        $url_for_check_wallet = "{$this->base_url}/unspent?confirmations={$confirmationNumber}&active={$fromAddress}";


        $res = [
            'status' => null,
            'status_code' => null,
            'msg' => null,
        ];
        if (empty($value) || $value == '0.0') {
            Log::channel('evacuatorBTC')->debug('Bitcoin discharge error : Value is empty or zero');
            $res['msg'] = 'Value is empty or zero';
            $res['status'] = 'error';
            $res['status_code'] = '0';
            return $res;
        }

        if ($fee == 0) {
            # get fee from a third party ...
            #$feeForBitcoin = $this->getFeeOnline(3, 2);
        }

        if ($fee <= 0) {
            Log::channel('evacuatorBTC')->debug('Bitcoin discharge error : fee is ZERO');
            $res['msg'] = 'Fee is zero';
            $res['status'] = 'error';
            $res['status_code'] = '0';
            return $res;
        }

        # check UTXOS of wallet
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_for_check_wallet);
        curl_setopt($ch, CURLOPT_POST, 0);
        if (app()->environment() != 'production')
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        $result = curl_exec($ch);
        $result = json_decode($result);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        # check result of blockchain.info
        if ($httpCode != 200) {
            Log::channel('evacuatorBTC')->debug("Bitcoin discharge error : wallet {$fromAddress} has no UTXOS");
            $res['msg'] = 'Wallet has no UTXOS';
            $res['status'] = 'error';
            $res['status_code'] = '0';
            return $res;
        }

        $utxosArray = $result->unspent_outputs;
        $utxosCollection = collect($utxosArray);
        $utxos = $utxosCollection->where('value', $this->btcToSatoshi($value))->first();
        if (empty($utxos)) {
            Log::channel('evacuatorBTC')->debug("Bitcoin discharge error : wallet {$fromAddress} has no transaction with value $value");
            $res['msg'] = "Wallet has no transaction with value {$value}";
            $res['status'] = 'error';
            $res['status_code'] = '0';
            return $res;
        }

        $fee = number_format($fee, 8, ".", '');
        $value = number_format($value, 8, ".", '');

        if ($value < $fee) {
            Log::channel('evacuatorBTC')->debug('Bitcoin discharge error : Fee is greater than value');
            $res['msg'] = 'Fee is greater than value';
            $res['status'] = 'error';
            $res['status_code'] = '0';
            return $res;
        }
        # prepare data to create transaction
        $total_amount = bcsub($value, $fee, 8);
        $private_keys = [
            $privateKey
        ];
        $inputs[] = [
            "txid" => $utxos->tx_hash_big_endian,
            "vout" => $utxos->tx_output_n,
            "scriptPubKey" => $utxos->script
        ];
        $json_inputs = json_encode($inputs);
        $amount[$toAddress] = $total_amount;


        $wallet = [];
        RawTransaction::private_keys_to_wallet($wallet, $private_keys, '00');
        $raw_transaction = RawTransaction::create($inputs, $amount);
        $sign = RawTransaction::sign($wallet, $raw_transaction, $json_inputs);


        if (empty($sign['hex'])) {
            Log::channel('evacuatorBTC')->debug('Bitcoin discharge error : hex is not defined');
            $res['msg'] = 'Hex is not defined';
            $res['status'] = 'error';
            $res['status_code'] = '0';
            return $res;
        }


        $hex = $sign['hex'];
        $url = "https://api.omniexplorer.info/v1/transaction/pushtx/signedTransaction={$hex}";

        $params = "signedTransaction=$hex";

        # push transaction via BlockChain.com
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        if (app()->environment() != 'production')
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded'
        ));
        $result = curl_exec($ch);
        $result = json_decode($result);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        dd($httpCode, $result);

        if ($httpCode == 200 && !empty($result->error)) {
            $res['msg'] = 'Something went wrong';
            if (!empty($result->error)) {
                $res['msg'] = $result->error;
            }
            $res['status_code'] = '0';
            $res['status'] = 'error';
            return $res;
        }
        $res['msg'] = 'Successfully done';
        $res['status'] = 'Done';

        dd($result, $res);
        return $res;

    }

    # ********************** Helper functions *************************

    public function satochiToBtc($valueInSatoshi)
    {
        $satoshi_decimal = 8;
        return number_format(bcdiv($valueInSatoshi, pow(10, $satoshi_decimal), $satoshi_decimal), $satoshi_decimal, ".", '');
    }

    public function btcToSatoshi($valueInReal)
    {
        $satoshi_decimal = 8;
        return $valueInReal * pow(10, $satoshi_decimal);
    }
}
