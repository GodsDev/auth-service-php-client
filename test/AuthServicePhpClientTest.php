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
            $this->setExpectedException('\GodsDev\AuthServicePhpClient\AuthServiceClientException');
            $this->asc->findUserByKey("mail", $this->nonexistent_mail);
    }


    public function test_createUpdateUser() {
        $data = $this->asc->createOrUpdateUser("mail", $this->mail1);
        var_dump($data);
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

}

