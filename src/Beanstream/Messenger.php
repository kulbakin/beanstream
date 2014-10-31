<?php
namespace Beanstream;

/**
 * Main class to communicate with Beanstream gateway,
 * supports API ver. 1
 * 
 * @author Pavel Kulbakin <p.kulbakin@gmail.com>
 */
class Messenger
{
    /**
     * Base API URL
     * 
     * @var string
     */
    const BASE = 'https://www.beanstream.com/api/v1';
    
    /**
     * Auth string
     * 
     * @var string
     */
    protected $_auth;
    
    /**
     * Constructor
     * 
     * @param string $mid Merchant ID
     * @param string $passcode API Access Passcode
     */
    public function __construct($mid, $passcode)
    {
        $this->_auth = base64_encode($mid.':'.$passcode);
    }
    
    /**
     * Send request to a gateway
     * 
     * @param string $action Gateway action (e.g. 'payments')
     * @param array[optional] $data Data to user with POST request, if not set GET request is used
     * @return array Decoded API response
     */
    public function request($action, $data = null)
    {
        if ( ! extension_loaded('curl')) {
            throw new Exception('The curl extension is required', 0);
        }
        
        $req = curl_init(self::BASE.'/'.$action);
        
        curl_setopt($req, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Passcode '.$this->_auth,
        ));
        
        curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($req, CURLOPT_FAILONERROR, true);
        curl_setopt($req, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($req, CURLOPT_TIMEOUT, 30);
        
        if ( ! is_null($data)) {
            curl_setopt($req, CURLOPT_POST, true);
            curl_setopt($req, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $result = curl_exec($req);
        if (false === $result) {
            throw new Exception(curl_error($req), -curl_errno($req));
        }
        
        $result = json_decode($result, true);
        if (is_null($result)) {
            throw new Exception('Unexpected response format', 0);
        }
        
        return $result;
    }
}
