<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace GodsDev\AuthServicePhpClient;

/**
 * Description of AuthServicePhpClientCore
 *
 * @author Tomáš
 */
class AuthServicePhpClientCore {

    const ACCESS_TOKEN_PARAM_NAME = "at";

    protected $authServiceUrl; //authService URL
    protected $appId; //client application id, as appears in authService's allowed id list
    protected $logger;
    protected $headersArr;


    /**
     * creates an AuthServiceClient instance
     *
     * @param string $authServiceUrl
     * @param string $appId
     * @param Psr\Log\LoggerInterface $logger
     */
    public function __construct($authServiceUrl, $appId, \Psr\Log\LoggerInterface $logger) {
        $this->authServiceUrl = $authServiceUrl;
        $this->setAppId($appId);
        $this->logger = $logger;

        $this->logger->debug("AuthServiceClient CREATED");
    }


    protected function addHeader($name, $value) {
        $this->headersArr[] = $name . ": " . $value;
    }


    protected function execCurl($curl) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headersArr);
        return curl_exec($curl);
    }


        /**
     *
     * @param type $urlPart include leading slash
     * @return type
     */
    protected function getCurlCh($urlPart, $tokenId = null) {
        $this->headersArr = array();
        $curl = curl_init($this->authServiceUrl . $urlPart . "/" . $this->appId);
        //no response body is returned if uncommented
//        curl_setopt($curl, CURLOPT_FAILONERROR, true);
//
//        //return the transfer as a string
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        if ($tokenId) {
            $this->addHeader("Cookie", $this->getCookieName() . "=" . $tokenId);
        }
        $this->logger->debug("curl info=" . curl_getinfo($curl, CURLINFO_EFFECTIVE_URL));
        return $curl;
    }

    protected function setPostJson($curl, $jsonString) {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonString);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $this->addHeader("Content-Type", "application/json");
        $this->addHeader("Content-Length", strlen($jsonString));

        return $curl;
    }


    protected function handleCurlEnd($curl, $responseBody) {
        $errDescr = null;
        $isClientError = false;
        if (curl_errno($curl)) {
            $errDescr = 'Curl error: ' . curl_error($curl);
        } else {
            $lastHTTPCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if (self::isClientErrorHTTPCode($lastHTTPCode)) {
                $responseBodyArr = json_decode($responseBody, true);
                $errDescr = $responseBodyArr["errors"][0]["detail"];
                $isClientError = true;
            } else if (self::isServerErrorHTTPCode($lastHTTPCode)) {
                $errDescr = "Server error: " . $lastHTTPCode;
                $this->logger->error($errDescr);
            }
        }
        if ($curl) {
            curl_close($curl);
        }
        if ($errDescr) {
            if ($isClientError) {
                throw new AuthServicePhpClientException("AuthServiceClient: " . $errDescr);
            } else {
                throw new \Exception("AuthServiceClient: " . $errDescr);
            }
        }
    }


    /**
     * finds an user, by various keys available
     *
     * @param string $idKeyName one of "mail", "fbid", "msisdn", "subno"
     * @param string $idKeyValue
     * @param string $getExtendedInfo
     * @return array user info
     */
    public function findUserByKey($idKeyName, $idKeyValue, $getExtendedInfo = false) {
        $this->logger->debug("CALL findUserByKey idKeyName:[$idKeyName], idKeyValue:[$idKeyValue]");
        $ch = $this->getCurlCh("/user-key");
        if ($getExtendedInfo) {
            $this->addHeader('x-raw-json', true);
        }

        $jsonString = '{"key":{"%key%":"%value%"}}';

        $jsonString = str_replace('%key%', $idKeyName, $jsonString);
        $jsonString = str_replace('%value%', $idKeyValue, $jsonString);
        //$this->logger->debug("json=$jsonString");

        $this->setPostJson($ch, $jsonString);
        $output = $this->execCurl($ch);
//        $this->logger->debug(print_r($output, true));
        $this->handleCurlEnd($ch, $output);
        $arr = json_decode($output, true);
        if ($getExtendedInfo) {
            return $arr;
        } else {
            return $arr["data"];
        }
    }


    /**
     * makes a request for a new token
     *
     * @param string $accessTokenId
     * @return array token
     */
    public function createTokenFromAccessTokenId($accessTokenId) {
        $this->logger->debug("CALL getToken with $accessTokenId: [$accessTokenId]");
        $ch = $this->getCurlCh("/token");
        $this->addHeader("x-access-token", $accessTokenId);
        $output = $this->execCurl($ch);
//        $this->logger->debug(print_r($output, true));
        $this->handleCurlEnd($ch, $output);
        $arr = json_decode($output, true);
        return $arr["data"];
    }


    protected static function isClientErrorHTTPCode($code) {
        return ($code >= 400 && $code < 500);
    }

    protected static function isServerErrorHTTPCode($code) {
        return ($code >= 500 && $code < 600);
    }


    /**
     *
     * @return string client application id, as appears in authService's allowed id list
     */
    public function getAppId() {
        return $this->appId;
    }

    /**
     * Sets the client application id. Should be one of the authService's allowed id list
     * @return object self
     */
    public function setAppId($appId) {
        $this->appId = $appId;
        return $this;
    }


    /**
     *
     * @return string service url origin
     */
    public function getAuthServiceUrl() {
        return $this->authServiceUrl;
    }


    public function getCookieName() {
        return "svct" . $this->appId;
    }

}
