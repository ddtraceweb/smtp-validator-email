<?php

namespace SmtpValidatorEmail\Tests;

use SmtpValidatorEmail\ValidatorEmail;

class ValidationEmailTest extends \PHPUnit_Framework_TestCase{


    public function testGetResults()
    {
        $emails = array(
            'ruben@codeforges.com',
            'ruben2@codeforges.com'
        );

        $args = array(
            'noComIsValid' => 1
        );

        $validator = new ValidatorEmail($emails,'ruben@codeforges.com',$args);
        $results  = $validator->getResults();
        $this->assertTrue(is_array($results));

        var_dump($results);
    }
}