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

    public function  testParseReturnNotEmptyUser(){
        $model = new Email('some@email.com');
        $results = $model->parse();
        $this->assertNotEmpty($results[0]);
    }

    public function  testParseReturnNotEmptyDomain(){
        $model = new Email('some@email.com');
        $results = $model->parse();
        $this->assertNotEmpty($results[1]);
    }
}
