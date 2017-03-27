<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace GodsDev\AuthServicePhpClient;

/**
 * Description of AuthServicePhpClientCreator
 *
 * @author Tomáš
 */
class AuthServicePhpClientCreator extends AuthServicePhpClientCore {

    /**
     * creates an AuthServiceClient instance
     *
     * @param string $authServiceUrl
     * @param string $appId
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct($authServiceUrl, $appId, \Psr\Log\LoggerInterface $logger) {
        parent::__construct($authServiceUrl, $appId, $logger);

        $this->logger->debug("AuthServiceClientCreator CREATED");
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
     * makes a request for a new access-token
     *
     * @param string $userId userId
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
    public function createTokenFromUserId($userId) {
        $this->logger->debug("CALL getTokenFromUserId with userId: [$userId]");
        $accessTokenArr = $this->createAccessToken($userId);

        $tokenArr = $this->createTokenFromAccessTokenId($accessTokenArr["id"]);
        return $tokenArr;
    }

}
