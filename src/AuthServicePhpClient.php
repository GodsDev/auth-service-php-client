<?php

namespace GodsDev\AuthServicePhpClient;

/**
 * AuthService interface client
 *
 * userInfo array format example:
 *
 *   array(
 *     "type" => 'user-info',
 *     "id" => '58a4b42325b44aa2cf76ce9b',
 *     "attributes" => array(
 *        'app-id' => 'test-app',
 *        "mail" => 'mail@m.c',
 *        "data" => '{ "whatever": "value" }',
 *     )
 *   )
 *
 * @author Tomáš
 */
class AuthServicePhpClient {

    private $authServiceUrl; //authService URL
    private $appId; //client application id, as appears in authService's allowed id list
    private $logger;
    private $headersArr;


    /**
     * creates an AuthServiceClient instance
     *
     * @param string $authServiceUrl
     * @param string $appId
     * @param log4php-Logger $logger
     */
    public function __construct($authServiceUrl, $appId, $logger) {
        $this->authServiceUrl = $authServiceUrl;
        $this->setAppId($appId);
        $this->logger = $logger;

        $this->logger->debug("AuthServiceClient CREATED");
    }

    /**
     *
     * @return string service url origin
     */
    public function getAuthServiceUrl() {
        return $this->authServiceUrl;
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
    protected function setAppId($appId) {
        $this->appId = $appId;
        return $this;
    }


    private function getCookieName() {
        return "svct" . $this->appId;
    }

    private function addHeader($name, $value) {
        $this->headersArr[] = $name . ": " . $value;
    }

    /**
     *
     * @param type $urlPart include leading slash
     * @return type
     */
    private function getCurlCh($urlPart, $token = null) {
        $this->headersArr = array();
        if (!$token) {
            $tName = $this->getCookieName();
            if (isset($_COOKIE[$tName])) {
                $token = $_COOKIE[$tName];
                $this->logger->debug("getCurlCh: Token cookie [$tName] found. Value: [" . $_COOKIE[$tName] . "]");
            } else {
                $this->logger->debug("getCurlCh: Token cookie [$tName] not found.");
            }
        }
        $curl = curl_init($this->authServiceUrl . $urlPart . "/" . $this->appId);
        //no response body is returned if uncommented
//        curl_setopt($curl, CURLOPT_FAILONERROR, true);
//
//        //return the transfer as a string
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        if ($token) {
            $this->addHeader("Cookie", $this->getCookieName() . "=" . $token);
        }
        $this->logger->debug("curl info=" . curl_getinfo($curl, CURLINFO_EFFECTIVE_URL));
        return $curl;
    }

    private function setPostJson($curl, $jsonString) {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonString);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $this->addHeader("Content-Type", "application/json");
        $this->addHeader("Content-Length", strlen($jsonString));

        return $curl;
    }


    private function execCurl($curl) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headersArr);
        return curl_exec($curl);
    }

    private static function isClientErrorHTTPCode($code) {
        return ($code >= 400 && $code < 500);
    }

    private static function isServerErrorHTTPCode($code) {
        return ($code >= 500 && $code < 600);
    }

    private function handleCurlEnd($curl, $responseBody) {
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
                throw new AuthServiceClientException("AuthServiceClient: " . $errDescr);
            } else {
                throw new \Exception("AuthServiceClient: " . $errDescr);
            }
        }
    }

    /**
     *
     * @param string $token token id. If null, a token cookie (name: "svct" . $appId ) is used
     * @return array user info
     */
    public function getUserInfo($token = null) {
        $this->logger->debug("CALL getUserInfo with token: [$token]");
        $ch = $this->getCurlCh("/user-info", $token);
        $output = $this->execCurl($ch);
//        $this->logger->debug(print_r($output, true));
        $this->handleCurlEnd($ch, $output);
        $arr = json_decode($output, true);
        return $arr["data"];
    }

    /**
     * makes a request for a new access-token
     *
     * @param string $userId
     * @param string $tokenValidTo explicit token (not the access-token!) validTo (format: UTC unix time milliseconds). If null, token validTo will be computed at the time of token retrieval.
     * @return array access-token
     *
     * access-token array format example:
     *
     * array(
     *  type => 'access-token',
     *  id => '58a4b4232726f3642c0dd051',
     *   attributes => array(
     *     'user-id' => '58a4b42325b44aa2cf76ce9b',
     *     'valid-to' => '2017-02-15T20:04:17.311Z',
     *     'token-valid-to' => '1970-01-01T00:00:07.000Z', //ISO 8601 DateTime
     *   )
     * )
     */
    public function createAccessToken($userId, $tokenValidTo = null) {
        $this->logger->debug("CALL getAccessToken with userId: [$userId]");
        $ch = $this->getCurlCh("/access-token");
        $this->addHeader("x-user-id", $userId);
        if ($tokenValidTo) {
            $this->addHeader("x-token-valid-to", $tokenValidTo);
        }
        $output = $this->execCurl($ch);
//        $this->logger->debug(print_r($output, true));
        $this->handleCurlEnd($ch, $output);
        $arr = json_decode($output, true);
        return $arr["data"];
    }

    /**
     * makes a request for a new token
     *
     * @param string $accessTokenId
     * @return array token
     */
    public function createTokenFromAccessToken($accessTokenId) {
        $this->logger->debug("CALL getToken with $accessTokenId: [$accessTokenId]");
        $ch = $this->getCurlCh("/token");
        $this->addHeader("x-access-token", $accessTokenId);
        $output = $this->execCurl($ch);
//        $this->logger->debug(print_r($output, true));
        $this->handleCurlEnd($ch, $output);
        return json_decode($output, true);
    }

    /**
     * makes a request for a new token, without the need for an access-token id.
     *
     * @param string $userId
     * @return array token
     *
     *
     * token array format example:
     *
     * array(
     *  type => 'token',
     *  id => '58a4b4232726f3642c0dd051',
     *   attributes => array(
     *     'user-id' => '58a4b42325b44aa2cf76ce9b',
     *     'valid-to' => '2017-02-15T20:04:17.311Z',
     *   )
     * )
     *
     *
     */
    public function createToken($userId) {
        $this->logger->debug("CALL getTokenFromUserId with userId: [$userId]");
        $accessTokenArr = $this->createAccessToken($userId);

        $tokenArr = $this->createTokenFromAccessToken($accessTokenArr["data"]["id"]);
        return $tokenArr;
    }


    /**
     * sets a cookie from the token
     *
     * @param array $token token
     * @return setCookie status
     */
    public function setTokenCookie(array $token) {
        $expireDate = date("U",strtotime($token["data"]["attributes"]["valid-to"]));
        return setcookie($this->getCookieName(), $token["data"]["id"], $expireDate, "/");
    }


    /**
     * Makes a request for an user modification. If no such user exists, a new user is created.
     *
     * @param string $idKeyName one of "mail", "fbid", "msisdn", "subno"
     * @param string $idKeyValue
     * @param string $dataKey
     * @param string $dataValue  note: to enter a string literal, use quotes inside  (example: '"ABCD"')
     * @return array full user info
     *
     * full user info array example:
     *
     *   array(
     *     "id" => '58a4b42325b44aa2cf76ce9b',
     *     "app-id" => 'test-app',
     *     "mail" => 'mail@m.c',
     *     "data" => '{ "whatever": "value" }',
     *   )
     *
     *
     */
    public function createOrUpdateUser($idKeyName, $idKeyValue, $dataKey = null, $dataValue = null) {
        $this->logger->debug("CALL createOrUpdateUser idKeyName:[$idKeyName], idKeyValue:[$idKeyValue], with data: [$dataKey : $dataValue]");
        $ch = $this->getCurlCh("/update-user");

        $jsonString = '{"key":{"%key%":"%value%"}, "data":{"%dataKey%":%dataValue%}}';

        $jsonString = str_replace('%key%', $idKeyName, $jsonString);
        $jsonString = str_replace('%value%', $idKeyValue, $jsonString);
        if (!$dataKey) {
            $dataKey = "data";
        }
        if (!$dataValue) {
            $dataValue = "null";
        }
        $jsonString = str_replace('%dataKey%', $dataKey, $jsonString);
        $jsonString = str_replace('%dataValue%', $dataValue, $jsonString);
        $this->logger->debug("json=$jsonString");

        $this->setPostJson($ch, $jsonString);
        $output = $this->execCurl($ch);
//        $this->logger->debug(print_r($output, true));
        $this->handleCurlEnd($ch, $output);

        //success
        $userInfo = $this->findUserByKey($idKeyName, $idKeyValue, true);
        return $userInfo;
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

}

/**
 * if client error occured, i.e. HTTP communication is ok
 *
 * throws an \Exception if system error arises (HTTP 50x, cannot connect...)
 */
class AuthServiceClientException extends \Exception {
    public function __construct($message = "", $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}