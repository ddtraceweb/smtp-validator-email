<?php
/**
 * Singletone helper
 */

namespace SmtpValidatorEmail\Helper;


class SingleTone {

    private function __construct(){ }
    private function __clone(){ }

    public static function getInstance () {
        static $instance = null;

        if(null === $instance){
            $instance = new static();
        }
        return $instance;
    }
}