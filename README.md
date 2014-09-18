#Smtp Validator Emails

* Smtp Validator mail can validate your email to send smtp mail and check your mx.

#Requirements

* PHP >= 5.3.3
* namespaces use
* smtp configuration PHP Ok.

#examples :

* example with 1 email :


```php
<?php

use SmtpValidatorEmail\ValidatorEmail;

$from = 'xyz@xzzz.com'; // for SMTP FROM:<> command
$emails = 'toto@somewhererlse.com';

$validator = new ValidatorEmail($email, $from);

var_dump($validator->getResults());
?>
```

* example with X emails :

```php
<?php

use SmtpValidatorEmail\ValidatorEmail;

$from = 'xyz@xzzz.com'; // for SMTP FROM:<> command
$emails = array('toto@somewhererlse.com', 'titi@totitito.com');

$validator = new ValidatorEmail($email, $from);

var_dump($validator->getResults());
?>
```

* example with X emails and have custom delays time when connection and send HELO, with domain need time to respond.

```php
<?php

use SmtpValidatorEmail\ValidatorEmail;

$from = 'xyz@xzzz.com'; // for SMTP FROM:<> command
$emails = array('toto@somewhererlse.com', 'titi@totitito.com');

//two loops in this example for difficult domains.
$options = array('delaySleep' => array(0, 6));

//Handle $options to the constructor as third parameter
$validator = new ValidatorEmail($email, $from, $options);

var_dump($validator->getResults());
?>
```

* example with X emails with more informations on domain, mxs and priorities.

```php
<?php

use SmtpValidatorEmail\ValidatorEmail;

$from = 'xyz@xzzz.com'; // for SMTP FROM:<> command
$emails = array('toto@somewhererlse.com', 'titi@totitito.com');

//more informations option activate
$options = array('domainMoreInfo' => true);

//Handle $options to the constructor as third parameter
$validator = new ValidatorEmail($email, $from, $options);

var_dump($validator->getResults());
?>
```

* example with X emails with more informations on domain, mxs and priorities. In example same domain for two email. This is a connection to domain and check all account emails.

```php
<?php

use SmtpValidatorEmail\ValidatorEmail;

$from = 'xyz@xzzz.com'; // for SMTP FROM:<> command
$emails = array('toto@somewhererlse.com', 'titi@somewhererlse.com');

//more informations option activate
$options = array('domainMoreInfo' => true);

//Handle $options to the constructor as third parameter
$validator = new ValidatorEmail($email, $from, $options);

var_dump($validator->getResults());
?>
```


[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/ddtraceweb/smtp-validator-email/trend.png)](https://bitdeli.com/free "Bitdeli Badge")

