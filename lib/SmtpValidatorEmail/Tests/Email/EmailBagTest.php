<?php


namespace SmtpValidatorEmail\Tests\Email;

use SmtpValidatorEmail\Email\EmailBag;

class EmailBagTest extends \PHPUnit_Framework_TestCase {

    public function testConstructorExpectsArray(){
        $this->setExpectedException(
            '\PHPUnit_Framework_Error',
            '/must be of the type array/'
        );

        new EmailBag("notAnArray");
    }

}
