<?php
/**
 * Used to help to construct the ValidatorEmail
 */

namespace SmtpValidatorEmail\Helper;


class ValidatorEmailHelper extends SingleTone {

    public static function createBag( array $container, BagHelper $bag ) {
        $bag->add( (array) $container );
        return $bag;
    }

}