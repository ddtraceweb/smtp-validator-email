#Smtp Validator Emails

example :

use SmtpValidatorEmail\ValidatorEmail;

$from = 'xyz@xzzz.com'; // for SMTP FROM:<> command
$emails = array('toto@somewhererlse.com', 'totor@somewhererlse.com');

$validator = new ValidatorEmail($email, $from);

var_dump($validator->getResults());
