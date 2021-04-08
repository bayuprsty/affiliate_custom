<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Affiliate extends Model
{
    public static function postCurl($url, $headers, $body = NULL) {
        $client = curl_init();

        curl_setopt($client, CURLOPT_URL, $url);
        curl_setopt($client, CURLOPT_POST, 1);
        curl_setopt($client, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($client, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($client, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($client, CURLOPT_HTTPHEADER, $headers);

        if (!is_null($body)) {
            curl_setopt($client, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($client);

        curl_close($client);

        return json_decode($response, true);
    }

    public static function getToken($secretID) {
        $body = [
            'username' => 'affiliateapi',
            'password' => 'affiliatedvnt101112',
            'secret_id' => $secretID
        ];

        $headers = [
            'Cache-Control: no-cache',
            'Connection: keep-alive',
            'Content-Type: multipart/form-data'
        ];

        $url = 'https://share.tradingnyantai.com/api/getAccessToken';

        $result = Self::postCurl($url, $headers, $body);

        Log::info($result);

        if ($result['data']['token']) {
            return $result['data']['token'];
        }
    }

    public static function setLeadApi($client, $leadID) {
        $secretID = 'nofdB3nmknTYa7u8HQRzwjLfkw4RhT';
        $token = Self::getToken($secretID);

        $body = [
            'lead_id' => $leadID,
            'customer_name' => $client['first_name']." ".$client['last_name'],
            'email' => $client['email'],
            'no_telepon' => $client['phone']
        ];

        $headers = [
            'Cache-Control: no-cache',
            'Connection: keep-alive',
            'Content-Type: multipart/form-data',
            'X-TOKEN-ID: '.$token,
            'SECRET-ID: '.$secretID
        ];

        $url = 'https://share.tradingnyantai.com/api/setLeadData';

        $result = Self::postCurl($url, $headers, $body);
    }
}
