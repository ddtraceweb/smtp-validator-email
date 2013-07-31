#Smtp Validator Emails

* Smtp Validator mail can validate your email to send smtp mail and check your mx.

#Requirements

* PHP >= 5.3.3
* namespaces use
* smtp configuration PHP Ok.

*example :

```
use SmtpValidatorEmail\ValidatorEmail;

$from = 'xyz@xzzz.com'; // for SMTP FROM:<> command
$emails = array('toto@somewhererlse.com', 'totor@somewhererlse.com');

$validator = new ValidatorEmail($email, $from);

var_dump($validator->getResults());
```
