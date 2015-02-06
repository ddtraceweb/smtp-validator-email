<?php

namespace SmtpValidatorEmail\Helper;


use SmtpValidatorEmail\Entity\Domain;
use SmtpValidatorEmail\Mx\Mx;
use SmtpValidatorEmail\Service\StatusManager;

class ValidationHelper {

    /**
     * @var TransportHelper
     */
    private $transport;

    /**
     * @var StatusManager
     */
    private $statusManager;

    private $options;

    /**
     * @var Mx
     */
    private $mxs;
    /**
     * @var Domain;
     */
    private $dom;

    /**
     * @var
     */
    private $users;

    /**
     * @param StatusManager $statusManager
     * @param array $options
     * @param array $from Domain and User
     */
    public function __construct(StatusManager $statusManager,$options,$from){
        $this->statusManager = $statusManager;
        $this->options = $options;
        $this->transport = new TransportHelper($this->statusManager, $from);

    }

    /**
     * @return TransportHelper
     */
    public function getTransport(){
        return $this->transport;
    }

    /**
     * @return Domain
     */
    public function getDom(){
        return $this->dom;
    }

    /**
     * @param $domain
     * @param $users
     */
    public function startValidation($domain,$users){
        $mx = new Mx();
        $this->mxs = $mx->getEntries($domain);
        $this->users = $users;
        $this->dom = new Domain($domain);
        $this->dom->addDescription(array('users' => $this->users));
        $this->dom->addDescription(array('mxs' =>  $this->mxs));
    }


    public function establishConnection(){
        if (array_key_exists('timeout',  $this->options)) {
            $this->transport->setTimeout( $this->options['timeout']);
        }

        try {
            $this->transport->connect($this->mxs);
        } catch (\Exception $e) {
            $this->statusManager->setStatus($this->users, $this->dom, 0, 'could not connect to host '.$this->mxs);
        }
    }

    /**
     * Do a catch-all test for the domain .
     * This increases checking time for a domain slightly,
     * but doesn't confuse users.
     */
    public function catchAll() {
        $this->getTransport()->getSmtp()->noop();
        if($this->options['catchAllEnabled']){
            try{
                $isCatchallDomain = $this->transport->getSmtp()->acceptsAnyRecipient($this->dom);

            }catch (\Exception $e) {
                $this->statusManager->setStatus($this->users, $this->dom, $this->options['catchAllIsValid'], 'error while on CatchAll test: '.$e );
            }

            // if a catchall domain is detected, and we consider
            // accounts on such domains as invalid, mark all the
            // users as invalid and move on
            if ($isCatchallDomain) {
                if (!$this->options['catchAllIsValid']) {
                    $this->statusManager->setStatus($this->users, $this->dom, $this->options['catchAllIsValid'],'catch all detected');
                    return true;
                }
            }
        }
    }

    /**
     * MAIL FROM
     * @param $fromUser
     * @param $fromDomain
     * @throws \SmtpValidatorEmail\Exception\ExceptionNoHelo
     */
    public function mailFrom($fromUser,$fromDomain) {
        if (!($this->transport->getSmtp()->mail($fromUser. '@' . $fromDomain))) {
            // MAIL FROM not accepted, we can't talk
            $this->statusManager->setStatus($this->users, $this->dom, $this->options['noCommIsValid'],'MAIL FROM not accepted');
        }
    }

    /**
     * @param $fromUser
     * @param $fromDomain
     * @return bool
     * @throws \SmtpValidatorEmail\Exception\ExceptionNoMailFrom
     */
    public function rcptEachUser($fromUser,$fromDomain){
        $this->transport->getSmtp()->noop();
        // rcpt to for each user
        foreach ($this->users as $user) {
            // TODO: An error from the SMTP couse a disconnect , need to implement a reconnect
            if(!$this->transport->getSmtp()->isConnect()){
                $this->transport->reconnect($fromUser . '@' . $fromDomain);
            }
            $address = $user . '@' . $this->dom->getDomain();
            // Sets the results to an integer 0 ( failure ) or 1 ( success )

            // TODO: Impliment setter
            $this->statusManager->updateStatus($address,$this->transport->getSmtp()->rcpt($address));

            if ($this->statusManager->getStatus($address) == 1) {
                // stop the loop
                return true;
            }

            $this->transport->getSmtp()->noop();
        }
    }

    /**
     * Close connection
     */
    public function closeConnection () {
        if ( $this->transport->getSmtp()->isConnect()) {
            $this->transport->getSmtp()->rset();
            $this->transport->getSmtp()->disconnect();
        }
    }

    public function getDomainInfo() {
        // TODO: Create setter for domainMoreInfo
        //                if ($options['domainMoreInfo']) {
        //                    $this->results->get[$dom->getDomain()] = $dom->getDescription();
        //                }
    }
}