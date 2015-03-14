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
     * @param $users array
     * @param array $options
     * @param array $from Domain and User
     * @param $domain
     */
    public function __construct(StatusManager $statusManager,$users,$options,$from,$domain){

        $this->statusManager = $statusManager;
        $this->options = $options;
        $this->users = $users;
        $this->transport = new TransportHelper($this->statusManager, $users,$from,$domain);
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
    public function startValidation($domain){
        $mx = new Mx();
        $this->mxs = $mx->getEntries($domain);
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
            $this->statusManager->setStatus($this->users, $this->dom, 0, 'could not connect to host ');
        }
    }

    /**
     * Do a catch-all test for the domain .
     * This increases checking time for a domain slightly,
     * but doesn't confuse users.
     */
    public function catchAll() {

        $isCatchallDomain = null;
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
        var_dump("user list :");
        var_dump($this->users);
        $iterator = 0;
        $dynamicTimeout = 0;

        if(count($this->users) >= $this->options['sameDomainLimit']){
            $dynamicTimeout = count($this->users);
        }

        foreach ($this->users as $user) {
            var_dump("Checking user :".$user);

            if(!$this->transport->getSmtp()->isConnect()){
                var_dump("Connection lost. Reconnect.");
                $this->establishConnection();
            }

            $address = $user . '@' . $this->dom->getDomain();

            // Sets the results to an integer 0 ( failure ) or 1 ( success )
            $result = $this->transport->getSmtp()->rcpt($address);
            var_dump("The adress $address, was checked , result: $result");
            $this->statusManager->updateStatus($address,$result);

            if ($iterator == count($this->users)) {
                // stop the loop
                return 1;
            }

            $this->transport->getSmtp()->noop();

//            if( $iterator > 0 && $iterator % 4 == 0 ){
//                $this->transport->disconnect();
//                sleep($dynamicTimeout);
//            }

            $iterator++;
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
    }
}