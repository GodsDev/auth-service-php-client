<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace GodsDev\AuthServicePhpClient;

/**
 * if client error occured, i.e. HTTP communication is ok
 *
 * throws an \Exception if system error arises (HTTP 50x, cannot connect...)
 */
class AuthServicePhpClientException extends \Exception {
    public function __construct($message = "", $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
