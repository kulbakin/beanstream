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
$bm = new \Beanstream\Messenger(YOUR_MERCHANT_ID, YOUR_API_KEY);

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

## Tips

### Authentication

Beansteam defines separate API access keys for payment, profile and search requests. It is possible though
to use the same value for all of them, so one should either initialize seperate *\Beanstream\Messanger* instance
for each request type or configure API passcodes in Beansteam merchant panel to be the same, see 
[Generate API Keys](https://developer.beanstream.com/docs/guides/merchant_quickstart/#2.-generate-api-keys-c7a8d316a97aa5ac0136c1b6de755512).

### Billing Address Province

Beanstream requires *province* field submitted along with *billing* data to be two-letter code. It only requires it when
specified *country* is *US* or *CA*, for other country codes set it to *--* even if corresponding country does have states
or provinces.
