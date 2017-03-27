<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace GodsDev\AuthServicePhpClient;

/**
 * Description of FakeLogger
 *
 * @author Tomáš
 */
class FakeLogger implements \Psr\Log\LoggerInterface {

    public function info($message, array $context = array()) {

    }

    public function debug($messsage) {
        //do nothing
    }

    public function alert($message, array $context = array()) {

    }

    public function critical($message, array $context = array()) {

    }

    public function emergency($message, array $context = array()) {

    }

    public function error($message, array $context = array()) {

    }

    public function log($level, $message, array $context = array()) {

    }

    public function notice($message, array $context = array()) {

    }

    public function warning($message, array $context = array()) {

    }

}
