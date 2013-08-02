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

$validator = new ValidatorEmail($email, $from);

var_dump($validator->getResults());
?>
```

* example with X emails with more informations on domain, mxs and priorities.

```php
<?php

use SmtpValidatorEmail\ValidatorEmail;

$from = 'xyz@xzzz.com'; // for SMTP FROM:<> command
$emails = array('toto@somewhererlse.com', 'titi@totitito.com');

//two loops in this example for difficult domains.
$options = array('domainMoreInfo' => true);

$validator = new ValidatorEmail($email, $from);

var_dump($validator->getResults());
?>
```

* example with X emails with more informations on domain, mxs and priorities. In example same domain for two email. This is a connection to domain and check all account emails.

```php
<?php

use SmtpValidatorEmail\ValidatorEmail;

$from = 'xyz@xzzz.com'; // for SMTP FROM:<> command
$emails = array('toto@somewhererlse.com', 'titi@somewhererlse.com');

//two loops in this example for difficult domains.
$options = array('domainMoreInfo' => true);

$validator = new ValidatorEmail($email, $from);

var_dump($validator->getResults());
?>
```
