Beanstream PHP API
==================

Composer ready PHP wrapper for [Beanstream API](http://developer.beanstream.com/documentation/).

## Installation

The recommended way to install the library is using [Composer](https://getcomposer.org).

```json
{
    "require": {
        "propa/beanstream": "dev-master"
    }
}
```

## Handling Exceptions

If server returns an unexpected response or error, PHP API throws *\Beanstream\Exception*.
Positive error codes correspond to Beanstream API errors, see
[Take Payment Errors](http://developer.beanstream.com/documentation/take-payments/errors/),
[Analyze Payment Errors](http://developer.beanstream.com/documentation/analyze-payments/errors/),
[Tokenize Payments Errors](http://developer.beanstream.com/documentation/tokenize-payments/errors/).
Negative codes correspond to [cURL errors](http://curl.haxx.se/libcurl/c/libcurl-errors.html)
(original cURL error codes are positive, in *\Beanstream\Exception* those are just reversed).
Exception with zero error code are PHP API specific, e.g. *The curl extension is required* or
*Unexpected response format*.

Generally, any unsuccessful request, e.g. insufficient data or declined transaction, results in *\Beanstream\Exception*,
thus *try..catch* is recommended for intercepting and handling them, see example below.

## Your First Integration

The sample below is an equivalent of original [example](http://developer.beanstream.com/documentation/your-first-integration/)
from Beanstream.

```php
<?php
$bm = new \Beanstream\Messanger(YOUR_MERCHANT_ID, YOUR_API_PASSCODE);

try {
    $ts = $bm->payment(array(
        'order_number' => '100001234',
        'amount' => 100.00,
        'payment_method' => 'card',
        'card' => array(
            'name' => 'John Doe',
            'number' => '5100000010001004',
            'expiry_month' => '02',
            'expiry_year' => '17',
            'cvd' => '123'
        )
    ));
    
    /*
     * Handle successful transaction, payment method returns
     * transaction details as result, so $ts contains that data
     * in the form of associative array.
     */
} catch (\Beanstream\Exception $e) {
    /*
     * Handle transaction error, $e->code can be checked for a
     * specific error, e.g. 211 corresponds to transaction being
     * DECLINED, 314 - to missing or invalid payment information
     * etc.
     */
}
```

## Authentication

Beansteam defines separate API access passcodes for payment, profile and search requests. It is possible though
to use same value for all of them, so one should either initialize seperate *\Beanstream\Messanger* instance
for each request type or configure API passcodes in Beansteam merchant panel to be the same (see
*administration -> account settings -> order settings* for payment and search passcodes,
*configuration -> payment profile configuration* for profile passcode).
