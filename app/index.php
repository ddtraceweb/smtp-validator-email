<?php
require '../vendor/autoload.php';

use SmtpValidatorEmail\ValidatorEmail;

//$validator = new ValidatorEmail("rube1n@gmail.com","ruben@codeforges.com",
//    array(
//        'domainMoreInfo' => true,
//        'delaySleep' => array(0,1),
//        'noCommIsValid' => 0,
//        'catchAllIsValid' => 1,
//        'logPath' => null
//    )
//);
$conf = \SmtpValidatorEmail\Configs\ConfigReader::readConfigs('../lib/SmtpValidatorEmail/Configs/smtp.yml');

var_dump($conf['responseCodes']['SMTP_GENERIC_SUCCESS']);
//var_dump($validator->getResults());