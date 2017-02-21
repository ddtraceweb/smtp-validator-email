#Smtp Validator Emails
[![Build Status](https://travis-ci.org/rubenCodeforges/smtp-validator-email.svg)](https://travis-ci.org/rubenCodeforges/smtp-validator-email)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/rubenCodeforges/smtp-validator-email/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/rubenCodeforges/smtp-validator-email/?branch=master)
* Smtp Validator mail can validate your email to send smtp mail and check your mx.

#Requirements

* PHP >= 5.3.3
* namespaces use
* smtp configuration PHP Ok.

#Installation
For installation you can use composer.

Simple add `"ddtraceweb/smtp-validator-email": "dev-master"` 
in your composer.json to install the latest version

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

* example with 1 email with using a specific interface with debug mode ON:


```php
<?php

use SmtpValidatorEmail\ValidatorEmail;

$from = 'xyz@xzzz.com'; // for SMTP FROM:<> command
$emails = 'toto@somewhererlse.com';

$validator = new ValidatorEmail($email, $from, array('debug' => true, 'context' => 'socket' => array('bindto' => '0.0.0.0')));

var_dump($validator->getResults());
var_dump($validator->getDebug());
?>
```

* Available default options

```php
array(
  'domainMoreInfo' => false,
  'delaySleep' => array(0),
  'noCommIsValid' => 0,
  'catchAllIsValid' => 0,
  'catchAllEnabled' => 1,
  'timeout' => null, // ini_get("default_socket_timeout")
  'context' => array(),
  'detailResults' => false, // Instead of returning 0 for invalid and 1 for valid, it will return an array. array('result' => $isValid /* 0 or 1 */, 'info' => "<SMTP response like: 250 2.1.5 Ok>")
  'debug' => false
);
```

[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/ddtraceweb/smtp-validator-email/trend.png)](https://bitdeli.com/free "Bitdeli Badge")

