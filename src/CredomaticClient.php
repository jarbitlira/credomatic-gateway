<?php
/**
 * Created by PhpStorm.
 * User: jarbitlira
 * Date: 05-21-2017
 * Time: 11:48 PM
 */

namespace JarbitLira\Credomatic;

use GuzzleHttp\Client as GuzzleClient;

class CredomaticClient
{
    private $result;
    private $credomaticWebservice = "https://paycom.credomatic.com/PayComBackEndWeb/common/requestPaycomService.go";

    private $userName;
    private $privateKey;
    private $publicKey;

    /**
     * CredomaticClient constructor.
     * @param string $userName
     * @param string $privateKey
     * @param string $publicKey
     * @param null|string $credomaticWebservice
     */
    public function __construct($userName, $privateKey, $publicKey, $credomaticWebservice = null)
    {
        $this->userName = $userName;
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
        $this->credomaticWebservice = is_null($credomaticWebservice) ?
            $this->credomaticWebservice :
            $credomaticWebservice;
    }

    /**
     * @param $orderId
     * @param $amount
     * @param $ccnumber
     * @param $cvv
     * @param $ccexp
     * @return array
     */
    public function processPayment($orderId, $amount, $ccnumber, $cvv, $ccexp)
    {
        $data = array_merge($this->makeBasicRequestParams('auth', $orderId, $amount), [
            'ccnumber' => $ccnumber, //card number
            'cvv' => $cvv, //security code
            'ccexp' => str_replace(["/", "-"], "", $ccexp),//expiration date in format mmyy
        ]);

        $result = $this->credomacticRequest($data);

        return $this->parseResult($result);
    }

    /**
     * @param $orderId
     * @param $transactionId
     * @param $amount
     * @return array
     */
    public function authorizeTransaction($orderId, $transactionId, $amount)
    {
        $data = $this->makeBasicRequestParams('sale', $orderId, $amount);
        array_push($data, "transaction_id", $transactionId);

        $result = $this->credomacticRequest($data);

        return $this->parseResult($result);
    }

    /**
     * @param $orderId
     * @param $transactionId
     * @return array
     */
    public function cancelTransaction($orderId, $transactionId)
    {
        $data = $this->makeBasicRequestParams('void', $orderId);
        array_push($data, "transaction_id", $transactionId);

        $result = $this->credomacticRequest($data);

        return $this->parseResult($result);
    }

    /**
     * @return bool
     */
    public function succeeded()
    {
        $result = $this->result;
        if (isset($result->response) && $result->response == 1)
            return true;
        return false;
    }

    public function getAuthCode()
    {
        $result = $this->result;
        if (isset($result->auth_code))
            return $result->auth_code;
        return false;
    }

    /**
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param $result
     */
    private function setResult($result)
    {
        $result = [
            'response' => $result->response,
            'response_text' => $result->responsetext,
            'auth_code' => $result->authcode,
            'transaction_id' => $result->transactionid,
            'avs_response' => $result->avsresponse,
            'cvv_response' => $result->cvvresponse,
            'response_code' => $result->response_code,
            'amount' => $result->amount,
            'order_id' => $result->orderid,
            'type_transaction' => $result->type
        ];

        $this->result = $result;
    }

    private function parseResult($result)
    {
        $this->setResult($result);
        return $this->getResult();
    }

    /**
     * @param string("auth", "sale", "void") $type
     * @param $orderId
     * @param null $amount
     * @return array
     */
    private function makeBasicRequestParams($type, $orderId, $amount = null)
    {
        $time = time();

        $hash = array(
            'orderid' => $orderId,
            'amount' => $amount,
            'time' => $time,
            'key' => $this->privateKey, // bac_key or private_key
        );

        $hashCode = md5(implode("|", $hash));

        return
            $data = [
                'username' => $this->userName, // bac_username
                'type' => $type,
                'key_id' => $this->publicKey, // bac_key_id or public_key
                'hash' => $hashCode,
                'time' => $time,
                'orderid' => $orderId,
                'amount' => $amount,
            ];
    }

    /**
     * @param array $data
     * @return object
     */
    private function credomacticRequest($data)
    {
        $GuzzleClient = new GuzzleClient();
        $requestResponse = $GuzzleClient->post($this->credomaticWebservice,
            ['form_params' => $data]
        );

        $res = str_replace("?", "", $requestResponse->getBody()); //drop symbol '?' in response
        parse_str($res, $response); //convert string params to array

        return (object)$response; //convert to object
    }
}
