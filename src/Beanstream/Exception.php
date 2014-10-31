<?php
namespace Beanstream;

/**
 * Beanstream specific exception type
 * 
 * Zero error code corresponds to PHP API specific errors
 * 
 * Positive error codes correspond to those of Beanstream API
 * @link http://developer.beanstream.com/documentation/analyze-payments/api-messages/
 * 
 * Negative error codes corresponde to those of cURL
 * @link http://curl.haxx.se/libcurl/c/libcurl-errors.html
 * 
 * @author Pavel Kulbakin <p.kulbakin@gmail.com>
 */
class Exception extends \Exception
{
}
