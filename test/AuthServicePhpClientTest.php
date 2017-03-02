<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace GodsDev\AuthServicePhpClient\Test;

use GodsDev\AuthServicePhpClient\AuthServicePhpClient;

/**
 * Description of PathTest
 *
 * @author Tomáš
 */
class AuthServicePhpClientTest extends \PHPUnit_Framework_TestCase {

    protected $asc;
    protected $mail1 = "pepa@aaa.cz";
    protected $nonexistent_mail = "pepa@nonexistent.mail";


    protected function setUp()
    {
        $this->asc = new AuthServicePhpClient("http://localhost:3500/auth", "AuthClientApp", \Logger::getLogger("AuthClientTest"));
    }

    public function test_1st() {
        $this->assertEquals("AuthClientApp", $this->asc->getAppId());
    }


    public function test_findUserByKey_no_user() {
            $this->setExpectedException('\GodsDev\AuthServicePhpClient\AuthServicePhpClientException');
            $this->asc->findUserByKey("mail", $this->nonexistent_mail);
    }


    public function test_createUpdateUser() {
        $data = $this->asc->createOrUpdateUser("mail", $this->mail1);
        var_dump($data);
    }


    public function test_findUserByKey_existing_user() {
            $existingUserInfo = $this->asc->findUserByKey("mail", $this->mail1);
            var_dump($existingUserInfo);
    }


    public function test_modifyUserData() {
        $data = $this->asc->createOrUpdateUser("mail", $this->mail1, "data", '{ "step" : "A"}');
        $this->assertArrayHasKey("data", $data);
        $this->assertEquals(array("step" => "A"), $data["data"]);
        var_dump($data);
    }

    public function test_modifyUserDataAgain() {
        $data = $this->asc->createOrUpdateUser("mail", $this->mail1, "data", '"0012"');
        $this->assertArrayHasKey("data", $data);
        $this->assertEquals("0012", $data["data"]);
        var_dump($data);
    }


    public function test_createAccessToken() {
        //$tokenValidToDate = '1980-01-01T00:00:00.000Z';
        //$tokenValidToTimestamp = strtotime($tokenValidToDate);
        $existingUserInfo = $this->asc->findUserByKey("mail", $this->mail1);
        var_dump($existingUserInfo);
        $at = $this->asc->createAccessToken($existingUserInfo["id"]);
        var_dump($at);
        //$this->assertArrayHasKey("token-valid-to", $at["attributes"]);
        //$this->assertEquals($tokenValidToDate, $at["attributes"]["token-valid-to"]);
    }

    public function test_createToken() {
        $existingUserInfo = $this->asc->findUserByKey("mail", $this->mail1);
        $t = $this->asc->createToken($existingUserInfo["id"]);
        var_dump($t);
    }

    public function test_createTokenFromAccessToken() {
        $existingUserInfo = $this->asc->findUserByKey("mail", $this->mail1);
        $at = $this->asc->createAccessToken($existingUserInfo["id"]);
        $t = $this->asc->createTokenFromAccessTokenId($at["id"]);
        var_dump($t);
    }


    public function test_getUserInfoFromTokenId() {
        $existingUserInfo = $this->asc->findUserByKey("mail", $this->mail1);
        $t = $this->asc->createToken($existingUserInfo["id"]);

        $userInfo2 = $this->asc->getUserInfoFromTokenId($t["id"]);
        var_dump($userInfo2);
    }


    public function test_obtainUserInfo_from_accessTokenId() {
        $existingUserInfo = $this->asc->findUserByKey("mail", $this->mail1);
        $accessToken = $this->asc->createAccessToken($existingUserInfo["id"]);
        //simulate request parameter
        $_REQUEST[\GodsDev\AuthServicePhpClient\AuthServicePhpClient::ACCESS_TOKEN_PARAM_NAME] = $accessToken["id"];

        $userInfo3 = $this->asc->obtainUserInfo();
        var_dump($userInfo3);
    }

    public function test_obtainUserInfo_from_cookie() {
        $existingUserInfo = $this->asc->findUserByKey("mail", $this->mail1);
        $token = $this->asc->createToken($existingUserInfo["id"]);
        //simulate cookie
        $_COOKIE[$this->asc->getCookieName()] = $token["id"];

        $userInfo4 = $this->asc->obtainUserInfo();
        var_dump($userInfo4);
    }


    public function test_obtainUserInfo_from_nothing() {
        $this->setExpectedException('\GodsDev\AuthServicePhpClient\AuthServicePhpClientException');
        $userInfo5 = $this->asc->obtainUserInfo();
    }

}

