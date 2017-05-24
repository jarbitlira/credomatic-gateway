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
     * Client constructor.
     * @param $userName
     * @param $privateKey
     * @param $publicKey
     */
    public function __construct($userName, $privateKey, $publicKey)
    {
        $this->userName = $userName;
        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
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
        $GuzzleClient = new GuzzleClient();

        $data = array_merge($this->makeBasicRequestParams('auth', $orderId, $amount), [
            'ccnumber' => $ccnumber, //card number
            'cvv' => $cvv, //security code
            'ccexp' => str_replace(["/", "-"], "", $ccexp),//expiration date in format mmyy
        ]);

        $requestResponse = $GuzzleClient->post($this->credomaticWebservice, ['body' => $data]);

        $res = str_replace("?", "", $requestResponse->getBody()); //drop symbol '?' in response
        parse_str($res, $response); //convert to array

        $result = (object)$response; //convert to object
        $this->result = $result; //convert to object

        return [
            'response' => $result->response,
            'response_text' => $result->responsetext,
            'authcode' => $result->authcode,
            'transactionid' => $result->transactionid,
            'avsresponse' => $result->avsresponse,
            'cvvresponse' => $result->cvvresponse,
            'response_code' => $result->response_code,
            'amount' => $result->amount,
            'orderid' => $result->orderid,
            'type_transaction' => $result->type
        ];
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
        if (isset($result->authcode))
            return $result->authcode;
        return false;
    }

    /**
     * @return object
     */
    public function getResult()
    {
        $result = $this->result;
        return $result;
    }

    public function getMessage()
    {
        $result = $this->result;
        $error = array("code" => 0, "message" => "transaccion no realizada");
        if (isset($result->response_code)) {
            $message = isset($this->responsesCodes[$result->response_code]) ?
                $this->responsesCodes[$result->response_code] : $result->responsetext;

            $error = array("code" => $result->response_code, "message" => $message);
            if (isset($result->transactionid)) {
                $error["transactionid"] = $result->transactionid;
            }
        }
        return $error;
    }

    /**
     * @param string("auth", "sale") $type
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
}