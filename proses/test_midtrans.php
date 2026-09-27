<?php

header(
    'Content-Type: application/json; charset=utf-8'
);

require_once __DIR__ .
    '/midtrans_config.php';


/*
|--------------------------------------------------------------------------
| TEST TRANSAKSI
|--------------------------------------------------------------------------
*/

$orderId =
    'TEST-BY-' .
    date('YmdHis');


$grossAmount =
    10000;


/*
|--------------------------------------------------------------------------
| DATA TRANSAKSI
|--------------------------------------------------------------------------
*/

$payload = [

    'transaction_details' => [

        'order_id' =>
            $orderId,

        'gross_amount' =>
            $grossAmount

    ],

    'item_details' => [

        [

            'id' =>
                'TEST-001',

            'price' =>
                $grossAmount,

            'quantity' =>
                1,

            'name' =>
                'Test Pembayaran BELAJARYUK'

        ]

    ],

    'customer_details' => [

        'first_name' =>
            'Test User',

        'email' =>
            'test@belajaryuk.local'

    ]

];


/*
|--------------------------------------------------------------------------
| BASIC AUTH
|--------------------------------------------------------------------------
|
| Midtrans:
| Base64(Server Key + ":")
|--------------------------------------------------------------------------
*/

$authorization =
    base64_encode(
        MIDTRANS_SERVER_KEY . ':'
    );


/*
|--------------------------------------------------------------------------
| CURL
|--------------------------------------------------------------------------
*/

$ch =
    curl_init(
        MIDTRANS_API_URL
    );


curl_setopt_array(
    $ch,
    [

        CURLOPT_POST =>
            true,

        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_HTTPHEADER =>
            [

                'Accept: application/json',

                'Content-Type: application/json',

                'Authorization: Basic ' .
                $authorization

            ],

        CURLOPT_POSTFIELDS =>
            json_encode(
                $payload
            ),

        CURLOPT_TIMEOUT =>
            30

    ]
);


$response =
    curl_exec(
        $ch
    );


$curlError =
    curl_error(
        $ch
    );


$httpCode =
    curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


curl_close(
    $ch
);


/*
|--------------------------------------------------------------------------
| CURL ERROR
|--------------------------------------------------------------------------
*/

if (
    $response === false
) {

    echo json_encode(
        [

            'success' =>
                false,

            'message' =>
                'cURL gagal.',

            'curl_error' =>
                $curlError

        ],

        JSON_PRETTY_PRINT
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| RESPONSE MIDTRANS
|--------------------------------------------------------------------------
*/

$midtransData =
    json_decode(
        $response,
        true
    );


/*
|--------------------------------------------------------------------------
| HASIL
|--------------------------------------------------------------------------
*/

echo json_encode(
    [

        'success' =>
            $httpCode >= 200 &&
            $httpCode < 300,

        'http_code' =>
            $httpCode,

        'order_id' =>
            $orderId,

        'snap_token' =>
            $midtransData['token']
            ?? null,

        'midtrans_response' =>
            $midtransData

    ],

    JSON_PRETTY_PRINT |
    JSON_UNESCAPED_SLASHES
);