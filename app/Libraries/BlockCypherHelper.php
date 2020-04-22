<?php


namespace App\Libraries;


use GuzzleHttp\Client;

class BlockCypherHelper
{
    private $token = '68b5431d8ace4297924f69b6b570b333';
    public $base_url = 'https://api.blockcypher.com';
    public $client;

    public function __construct()
    {
        $this->client = new Client(['verify' => false]);
    }

    public function createNewTransaction($tx = null)
    {
        $url = "{$this->base_url}/v1/bcy/test/txs/new";

        $params = [
            'token' => $this->token,
        ];
        $params = http_build_query($params);
        $url = $url . '?' . $params;
        $result = $this->client->post($url,
            [
                'form_params' => [
                    'tx' => $tx
                ]
            ]);
        $result = $result->getBody()->getContents();
        $result = json_decode($result, true);
        return $result;

    }

    public function sendTransaction()
    {
        $inputs = [
            'addresses' => 'CEztKBAYNoUEEaPYbkyFeXC5v8Jz9RoZH9',
        ];
        $output = [
            'addresses' => 'C1rGdt7QEPGiwPMFhNKNhHmyoWpa5X92pn',
        ];
        $value = 1000000;
        $url = "{$this->base_url}/v1/bcy/test/txs/send";


    }


    public function pushTransaction($tx = null)
    {
        /*
        $url = "{$this->base_url}/v1/bcy/test/txs/push";

        $params = [
            'token' => $this->token,
        ];
        $params = http_build_query($params);
        $url = $url . '?' . $params;
        $result = $this->client->post($url,
            [
                'form_params' => [
                    'tx' => $tx
                ]

            ]);
        $result = $result->getBody()->getContents();
        $result = json_decode($result, true);
        return $result;
        */
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.blockcypher.com/v1/bcy/test/txs/push?token={$this->token}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ));
        $response = curl_exec($curl);
        //echo $err = curl_error($curl);
        curl_close($curl);
        dd($response);
    }

    public function decode($tx = null)
    {

        /*
        $url = "{$this->base_url}/v1/bcy/test/txs/push";

        $params = [
            'tx' => $tx,
        ];
        $params = http_build_query($params);
        //$params = json_encode($params);
        //dd($params);
        //$url = $url . '?' . $params;
        $result = $this->client->post($url,
            [
                'form_params' => [
                    $params
                ]

            ]);
        $result = $result->getBody()->getContents();
        $result = json_decode($result, true);
        dd($result);
        return $result;
        */

        /*
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.blockcypher.com/v1/bcy/test/txs/decode?token={$this->token}",
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        dd($response, $err);
        */


        $params = [
            'tx' => $tx,
        ];
        $params = http_build_query($params);
        $params = json_encode($params);


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.blockcypher.com/v1/bcy/test/txs/decode?token={$this->token}");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json'
                #'Content-Length: ' . strlen($params))
        ));
        $result = curl_exec($ch);
        $result = json_decode($result);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        dd($result);


    }
}
