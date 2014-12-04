<?php


namespace SmtpValidatorEmail\Tests\Email;

use SmtpValidatorEmail\Email\Email;

class EmailTest extends \PHPUnit_Framework_TestCase {


    public function testConstructorExpectsString()
    {
        $this->setExpectedException(
            '\InvalidArgumentException',
            '/constructor expected string, got:/'
        );
        new Email(true);
    }

    public function testConstructorStringIsEmailException(){
        $this->setExpectedException(
            '\InvalidArgumentException',
            '/String should be an email, got:/'
        );
        new Email('notAnEmail');
    }
}
