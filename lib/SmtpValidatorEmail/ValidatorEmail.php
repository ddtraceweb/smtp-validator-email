<?php
/**
 * Created by JetBrains PhpStorm.
 * User: daviddjian
 * Date: 31/07/13
 * Time: 19:20
 * To change this template use File | Settings | File Templates.
 */

namespace SmtpValidatorEmail;

use SmtpValidatorEmail\Helper\ValidationHelper;
use SmtpValidatorEmail\Helper\ValidatorInitHelper;
use SmtpValidatorEmail\Service\StatusManager;


/**
 * Class ValidatorEmail
 * @package SmtpValidatorEmail
 */
class ValidatorEmail extends ValidatorInitHelper
{
    /**
     * @var StatusManager;
     */
    private $statManager;

    /**
     * Constructs the validator
     *
     * @param array|string $emails
     * @param string $sender
     * @param array $options
     *
     * possible options :
     *
     *      'domainMoreInfo' (bool) for have more information on domains of users.
     *      'delaySleep' is an array of delay(s) possible after connected Server to send the request SMTP.
     *      'catchAllIsValid' (int) can be 0 for false or 1 for true . Are 'catch-all' accounts considered valid or not?
     *      'catchAllEnabled' (int) 0 off 1 on enables catchAll test , may take more time
     *      'sameDomainLimit' (int) how many users are allowed a same domain , if limit reached , dynamic timeout will enabled
     *      'noCommIsValid' Being unable to communicate with the remote MTA could mean an address
     *                      is invalid, but it might not, depending on your use case, set the
     *                      value appropriately.
     */
    public function __construct($emails = array(), $sender, $options = array())
    {
        $this->statManager = new StatusManager();
        $this->init($emails,$sender,$options);
    }

    /**
     * Returns results
     * @return array
     */
    public function getResults()
    {
        if(!$this->statManager->checkStatus()){
            $this->runValidation($this->options);
        }
        return $this->statManager->getStatus();
    }

    /**
     * @param $options array
     * @throws Exception\ExceptionNoHelo
     * @throws Exception\ExceptionNoMailFrom
     * @throws Exception\ExceptionNoTimeout
     */
    private function runValidation($options){

        // The foreach fires for each email in array
        foreach ($this->domains as $domain => $users) {

            //TODO:Start validiation
            $count = count($options['delaySleep']);
            $i = 0;
            $loopStop = 0;
            while ($i < $count && $loopStop != 1) {
                $validator = new ValidationHelper(
                    $this->statManager,
                    $users,
                    $this->options,
                    array("fromDomain" => $this->fromDomain, "fromUser" => $this->fromUser),
                    $domain
                );

                $validator->startValidation($domain,$users);
                $validator->establishConnection();

                $transport = $validator->getTransport();
                $smtp = $transport->getSmtp();
                // are we connected?
                if ($smtp->isConnect()) {
                    sleep($options['delaySleep'][$i]);

                    // say helo, and continue if we can talk
                    if ($smtp->helo()) {

                        // try issuing MAIL FROM
                        $validator->mailFrom($this->fromUser,$this->fromDomain);

                        /**
                         * if we're still connected, proceed (cause we might get
                         * disconnected, or banned, or greylisted temporarily etc.)
                         * see mail() for more
                         */
                        if ( $smtp->isConnect()) {

                            $transport->getSmtp()->noop();

                            if( $validator->catchAll() ){
                                $loopStop = 1;
                                continue;
                            }

                            // if we're still connected, try issuing rcpts
                            if ($smtp->isConnect()) {
                                $loopStop = $validator->rcptEachUser($this->fromUser,$this->fromDomain) || 0;
                            }
                            // saying buh-bye if we're still connected, cause we're done here
                            $validator->closeConnection();
                        }

                    } else {
                        // we didn't get a good response to helo and should be disconnected already
                        $this->statManager->setStatus($users, $validator->getDom(), $options['noCommIsValid'],'bad response on helo');
                    }
                } else {
                    $this->statManager->setStatus($users, $validator->getDom(), 0,'no connection');
                }
                //TODO: Finish method;
                $validator->getDomainInfo();
                $i++;
            }
        }
    }
}
