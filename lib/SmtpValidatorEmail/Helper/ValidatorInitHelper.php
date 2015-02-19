<?php
/**
 * Class ValidatorHelper used for building and setting up the validator
 */

namespace SmtpValidatorEmail\Helper;

use SmtpValidatorEmail\Entity\Email;
use SmtpValidatorEmail\Entity\Results;
use SmtpValidatorEmail\Helper\BagHelper;
use SmtpValidatorEmail\Helper\EmailHelper;

class ValidatorInitHelper{

    /**
     * @var array
     */
    protected $domains;
    /**
     * @var
     */
    protected $fromUser = 'user';
    /**
     * @var
     */
    protected $fromDomain = 'localhost';

    /**
     * @var Results
     */
    protected $results;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @param array|String $emails
     * @param $sender
     * @param array $options
     */
    public function init($emails = array(), $sender, $options = array()) {
        $defaultOptions = array(
            'domainMoreInfo' => false,
            'delaySleep' => array(0,1),
            'noCommIsValid' => 0,
            'catchAllIsValid' => 0,
            'catchAllEnabled' => 1,
            'sameDomainLimit' => 5,
        );

        $emails = is_array($emails) ? EmailHelper::sortEmailsByDomain($emails) : $emails;
        $this->options = array_merge($defaultOptions,$options);
        $this->setSender($sender);
        $this->setBags($emails);
        $this->results = new Results();
    }

    public function setBags($emails) {
        if (!empty($emails)) {
            $emailBag = new BagHelper();
            $emailBag->add($emails);
            $domainBag = $this->setEmailsDomains($emailBag);
            $this->domains = $domainBag->all();
        }
    }


    /**
     * Sets the email addresses that should be validated.
     *
     * @param BagHelper $emailBag
     *
     * @return BagHelper $domainBag
     */

    public function setEmailsDomains(BagHelper $emailBag)
    {
        $domainBag = new BagHelper();
        foreach ($emailBag as $key => $emails) {
            foreach ($emails as $email) {
                $mail = new Email($email);
                list($user, $domain) = $mail->parse();
                $domainBag->set($domain, $user, false);
            }
        }
        return $domainBag;
    }

    /**
     * Sets the email address to use as the sender/validator.
     *
     * @param string $email
     *
     * @return void
     */
    public function setSender($email)
    {
        $mail = new Email($email);
        $parts = $mail->parse();

        $this->fromUser = $parts[0];
        $this->fromDomain = $parts[1];
    }

}