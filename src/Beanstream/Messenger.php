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
     * Send request to a gateway.
     * 
     * This is a generic method, in most cases a specific
     * one should be used, e.g. Messenger::createProfile()
     * 
     * @param string $action Gateway action (e.g. 'payments')
     * @param array[optional] $data Data to user with POST request, if not set GET request is used
     * @param string[optional] $method HTTP method to use, by default is GET if there is no $data and PUT when there is $data set
     * @return array Decoded API response
     */
    public function request($action, $data = null, $method = null)
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
        curl_setopt($req, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($req, CURLOPT_TIMEOUT, 30);
        
        if (is_null($method)) {
            if (is_null($data)) {
                $method = 'GET';
            } else {
                $method = 'POST';
            }
        }
        curl_setopt($req, CURLOPT_CUSTOMREQUEST, $method);
        
        if ( ! is_null($data)) {
            curl_setopt($req, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $raw = curl_exec($req);
        if (false === $raw) {
            throw new Exception(curl_error($req), -curl_errno($req));
        }
        
        $res = json_decode($raw, true);
        if (is_null($res)) {
            throw new Exception('Unexpected response format', 0);
        }
        
        if (isset($res['code']) and 1 < $res['code']) {
            throw new Exception($res['message'], $res['code']);
        }
        
        return $res;
    }
    
    /**
     * Purchases and pre-authorization
     * @link http://developer.beanstream.com/documentation/take-payments/purchases/card/
     * @link http://developer.beanstream.com/documentation/legato/take-payment-legato-token/
     * @link http://developer.beanstream.com/documentation/take-payments/purchases/cash/
     * @link http://developer.beanstream.com/documentation/take-payments/purchases/cheque-purchases/
     * 
     * @param array Payment data, by default 'payment_method' is 'card'
     * @return array Transaction details
     */
    public function payment($data)
    {
        $res = $this->request('payments', $data + array(
            'payment_method' => 'card',
        ), 'POST');
        
        return $res;
    }
    
    /**
     * Pre-authorization completion
     * @link http://developer.beanstream.com/documentation/take-payments/pre-authorization-completion/
     * 
     * @param string $oid Transaction Id
     * @param mixed $amount Order amount
     * @param string[optional] $order_number
     * @return array Transaction details
     */
    public function complete($tid, $amount, $order_number = null)
    {
        $data = array('amount' => $amount);
        if ( ! is_null($order_number)) {
            $data['order_number'] = $order_number;
        }
        $res = $this->request('payments/'.$tid.'/completions', $data, 'POST');
        
        return $res;
    }
    
    /**
     * Void (aka cancel)
     * @link http://developer.beanstream.com/documentation/take-payments/voids/
     * 
     * @param string $tid Transaction Id
     * @param mixed $amount Order amount
     * @return array Transaction details
     */
    public function void($tid, $amount)
    {
        $res = $this->request('payments/'.$tid.'/void', array('amount' => $amount), 'POST');
        
        return $res;
    }
    
    /**
     * Return (aka refund)
     * @link http://developer.beanstream.com/documentation/take-payments/return/
     * 
     * @param string $oid Transaction Id
     * @param mixed $amount Order amount
     * @param string[optional] $order_number
     * @return array Transaction details
     */
    public function refund($tid, $amount, $order_number = null)
    {
        $data = array('amount' => $amount);
        if ( ! is_null($order_number)) {
            $data['order_number'] = $order_number;
        }
        $res = $this->request('payments/'.$tid.'/returns', $data, 'POST');
        
        return $res;
    }
    
    /**
     * Take payment - profile
     * @link http://developer.beanstream.com/documentation/tokenize-payments/take-payment-profiles/
     * 
     * @param string $pid Profile Id
     * @param int $cid Card Id
     * @param array $data Order data
     * @param bool[optional] $complete Set to false for pre-auth
     * @return array Transaction details
     */
    public function paymentProfile($pid, $cid, $data, $complete = true)
    {
        $res = $this->request('payments', $data + array(
            'payment_method' => 'payment_profile',
            'payment_profile' => array(
                'complete' => $complete,
                'customer_code' => $pid,
                'card_id' => $cid,
            )
        ), 'POST');
        
        return $res;
    }
    
    /**
     * Create a new profile
     * @link http://developer.beanstream.com/documentation/tokenize-payments/create-new-profile/
     * 
     * @param array $data Profile data
     * @return string Profile Id (aka customer_code)
     */
    public function createProfile($data)
    {
        $res = $this->request('profiles', $data, 'POST');
        
        return $res['customer_code'];
    }
    
    /**
     * Retrieve a profile
     * @link http://developer.beanstream.com/documentation/tokenize-payments/retrieve-profile/
     * 
     * @param string $pid Profile Id
     * @return array Profile data
     */
    public function retrieveProfile($pid)
    {
        $res = $this->request('profiles/'.$pid);
        unset($res['code'], $res['message']);
        
        return $res;
    }
    
    /**
     * Update a profile
     * @link http://developer.beanstream.com/documentation/tokenize-payments/update-profile/
     * 
     * @param string $pid Profile Id
     * @param array $data Profile data
     * @return TRUE
     */
    public function updateProfile($pid, $data)
    {
        $this->request('profiles/'.$pid, $data, 'PUT');
        
        return true;
    }
    
    /**
     * Delete a profile
     * @link http://developer.beanstream.com/documentation/tokenize-payments/delete-profile/
     * 
     * @param string $id Profile Id
     * @return TRUE
     */
    public function deleteProfile($pid)
    {
        $this->request('profiles/'.$pid, null, 'DELETE');
        
        return true;
    }
    
    /**
     * Add card to a profile
     * @link http://developer.beanstream.com/documentation/tokenize-payments/add-card-profile/
     * 
     * @param string $pid Profile Id
     * @param array $data Card data
     * @return TRUE
     */
    public function addCard($pid, $data)
    {
        $this->request('profiles/'.$pid.'/cards', array('card' => $data), 'POST');
        
        /*
         * XXX it would be more appropriate to return newly added card id,
         * but Beanstream for some reason does not return it in result
         */
        return true;
    }
    
    /**
     * Retrieve cards in a profile
     * @link http://developer.beanstream.com/documentation/tokenize-payments/retrieve-cards-profile/
     * 
     * @param string $pid Profile Id
     * @return array Cards data
     */
    public function retrieveCards($pid)
    {
        $res = $this->request('profiles/'.$pid.'/cards');
        
        return $res['card'];
    }
    
    /**
     * Update card in a profile
     * @link http://developer.beanstream.com/documentation/tokenize-payments/update-card-profile/
     * 
     * @param string $pid Profile Id
     * @param int $cid Card Id
     * @param array $data Card data
     * @return TRUE
     */
    public function updateCard($pid, $cid, $data)
    {
        $this->request('profiles/'.$pid.'/cards/'.$cid, array('card' => $data), 'PUT');
        
        return true;
    }
    
    /**
     * Delete card from a profile
     * @link http://developer.beanstream.com/documentation/tokenize-payments/delete-card-profile/
     * 
     * @param string $pid Profile Id
     * @param int $cid Card Id
     * @return TRUE
     */
    public function deleteCard($pid, $cid)
    {
        $this->request('profiles/'.$pid.'/cards/'.$cid, null, 'DELETE');
        
        return true;
    }
    
    /**
     * Search transactions
     * @link http://developer.beanstream.com/documentation/analyze-payments/search-specific-criteria/
     * 
     * @param array[optional] $data Filtering data, the default values
     *  name: 'Search'
     *  start_date: now - 1 day OR end_date - 1 day
     *  end_date: start_date + 1 day
     *  start_row: 1
     *  end_row: start_row + 9
     *  i.e. by default function returns first 10 transactions for the past 24h
     * @return array Transactions found
     */
    public function search($data = array())
    {
        // compose default values for date filter
        $start_dt = new \DateTime(isset($data['end_date']) ? $data['end_date'] : null);
        $start_dt->modify('-1 day +1 second');
        $start_date = $start_dt->format('c');
        $end_dt = new \DateTime(isset($data['start_date']) ? $data['start_date'] : $start_date);
        $end_dt->modify('+1 day');
        $end_date = $end_dt->format('c');
        
        $res = $this->request('reports', $data + array(
            'name' => 'Search',
            'start_date' => $start_date,
            'end_date' => $end_date,
            'start_row' => 1,
            'end_row' => 9 + (isset($data['start_row']) ? $data['start_row'] : 1),
        ), 'POST');
        
        return $res['records'];
    }
}
