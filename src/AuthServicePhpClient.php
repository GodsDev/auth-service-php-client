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
class AuthServicePhpClient extends AuthServicePhpClientCore {


    /**
     * creates an AuthServiceClient instance
     *
     * @param string $authServiceUrl
     * @param string $appId
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct($authServiceUrl, $appId, \Psr\Log\LoggerInterface $logger) {
        parent::__construct($authServiceUrl, $appId, $logger);

        $this->logger->debug("AuthServiceClient CREATED");
    }

    /**
     * tries several ways to get user info
     * (first looks for access-token in HTTP GET parameter, if not present then tries a token cookie)
     * if unsuccessfull, user info cannot be obtained
     *
     * After a constructor, this is the only method client typically needs to use.
     *
     * @return array user info
     *
     * @throws \Exception
     * @throws \GodsDev\AuthServicePhpClient\AuthServicePhpClientException
     */
    public function obtainUserInfo() {
        $tokenId = null;
        if (isset($_REQUEST[self::ACCESS_TOKEN_PARAM_NAME])) {
            $accessTokenId = $_REQUEST[self::ACCESS_TOKEN_PARAM_NAME];
            $token = $this->createTokenFromAccessTokenId($accessTokenId);
            $this->setTokenCookie($token, "/");
            $tokenId = $token["id"];
        }
        if ($tokenId == null) {
            $tokenId = $this->getTokenIdFromCookie();
        }
        if ($tokenId == null) {
            throw new \GodsDev\AuthServicePhpClient\AuthServicePhpClientException("cannot obtain AuthService user info");
        }

        $userInfo = $this->getUserInfoFromTokenId($tokenId);
        return $userInfo;
    }


    /**
     *
     *
     * @param string $cookieName if null, uses a standard, appId-related name
     *
     * @return string tokenId
     */
    private function getTokenIdFromCookie($cookieName = null) {
        if (!$cookieName) {
            $cookieName = $this->getCookieName();
        }
        if (isset($_COOKIE[$cookieName])) {
            $tokenId = $_COOKIE[$cookieName];
            $this->logger->debug("getTokenFromCookie: Token cookie [$cookieName] found. Value: [" . $_COOKIE[$cookieName] . "]");
        } else {
            $this->logger->debug("getTokenFromCookie: Token cookie [$cookieName] not found.");
            $tokenId = null;
        }
        return $tokenId;
    }




    /**
     *
     * @param string $tokenId token id. If null, a token cookie (name: "svct" . $appId ) is used
     * @return array user info
     */
    public function getUserInfoFromTokenId($tokenId) {
//        if ($tokenId == null) {
//            $tokenId = $this->getTokenIdFromCookie();
//        }
        $this->logger->debug("CALL getUserInfoFromTokenId with token id: [$tokenId]");
        $ch = $this->getCurlCh("/user-info", $tokenId);
        $output = $this->execCurl($ch);
//        $this->logger->debug(print_r($output, true));
        $this->handleCurlEnd($ch, $output);
        $arr = json_decode($output, true);
        return $arr["data"];
    }


    /**
     * sets a cookie from the token id
     *
     * @param string $tokenId token id value
     * @return setCookie status
     */

    /**
     * sets a cookie from the token id
     *
     * @param array $token token array
     * @param string $path The path on the server in which the cookie will be available on. If set to '/', the cookie will be available within the entire domain
     * @param string $domain The (sub)domain that the cookie is available to.
     * @param boolean $secure Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client.
     * @param boolean $httponly When TRUE the cookie will be made accessible only through the HTTP protocol.
     * @return boolean php setcookie status
     *
     * @see setcookie
     */
    public function setTokenCookie(array $token, $path = "", $domain = "", $secure = false, $httponly = true) {
        $expireDate = date("U",strtotime($token["attributes"]["valid-to"]));
        return setcookie($this->getCookieName(), $token["id"], $expireDate, $path, $domain, $secure, $httponly);
    }


}

