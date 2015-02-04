<?php
/**
 * Created by JetBrains PhpStorm.
 * User: daviddjian
 * Date: 31/07/13
 * Time: 19:20
 * To change this template use File | Settings | File Templates.
 */

namespace SmtpValidatorEmail;

use SmtpValidatorEmail\Entity\Domain;
use SmtpValidatorEmail\Helper\TransportHelper;
use SmtpValidatorEmail\Helper\ValidatorInitHelper;
use SmtpValidatorEmail\Mx\Mx;
use SmtpValidatorEmail\Service\StatusManager;
use Symfony\Component\Config\Definition\Exception\Exception;


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
     *      'noCommIsValid' Being unable to communicate with the remote MTA could mean an address
     *                      is invalid, but it might not, depending on your use case, set the
     *                      value appropriately.
     */
    public function __construct($emails = array(), $sender, $options = array())
    {
        $this->init($emails,$sender,$options);
        $this->statManager = new StatusManager();
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

            $mx = new Mx();
            $mxs = $mx->getEntries($domain);

            $dom = new Domain($domain);
            $dom->addDescription(array('users' => $users));
            $dom->addDescription(array('mxs' => $mxs));

            $count = count($options['delaySleep']);
            $i = 0;
            $loopStop = 0;
            while ($i < $count && $loopStop != 1) {
                $transport = new TransportHelper($this->statManager,array('fromDomain' => $this->fromDomain, 'fromUser' => $this->fromUser ));
                $smtp = $transport->getSmtp();

                if (array_key_exists('timeout', $options)) {
                    $transport->setTimeout($options['timeout']);
                }

                try {
                    $result = $transport->connect($mxs);
                    $this->statManager->setStatus($users, $dom, 0, $result);
                } catch (Exception $e) {
                    $this->statManager->setStatus($users, $dom, 0, 0, 'could not connect to host '.$mxs);
                }

                // are we connected?
                if ($smtp->isConnect()) {

                    // TODO : Make a dynamic sleep timeout
                    sleep($options['delaySleep'][$i]);

                    // say helo, and continue if we can talk
                    if ($smtp->helo()) {

                        // try issuing MAIL FROM
                        if (!($smtp->mail($this->fromUser . '@' . $this->fromDomain))) {
                            // MAIL FROM not accepted, we can't talk
                            $this->statManager->setStatus($users, $dom, $options['noCommIsValid'],'MAIL FROM not accepted');
                        }

                        /**
                         * if we're still connected, proceed (cause we might get
                         * disconnected, or banned, or greylisted temporarily etc.)
                         * see mail() for more
                         */
                        if ( $smtp->isConnect()) {

                            $smtp->noop();

                            // Do a catch-all test for the domain .
                            // This increases checking time for a domain slightly,
                            // but doesn't confuse users.
                            if($options['catchAllEnabled']){
                                try{
                                    $isCatchallDomain = $smtp->acceptsAnyRecipient($dom);
                                }catch (\Exception $e) {
                                    $this->statManager->setStatus($users, $dom, $options['catchAllIsValid'], 'error while on CatchAll test: '.$e );
                                }
                                // if a catchall domain is detected, and we consider
                                // accounts on such domains as invalid, mark all the
                                // users as invalid and move on
                                if ($isCatchallDomain) {
                                    if (!$options['catchAllIsValid']) {
                                        $this->statManager->setStatus($users, $dom, $options['catchAllIsValid'],'catch all detected');
                                        continue;
                                    }
                                }
                            }

                            // if we're still connected, try issuing rcpts
                            if ($smtp->isConnect()) {
                                $smtp->noop();

                                // rcpt to for each user
                                foreach ($users as $user) {
                                    // TODO: An error from the SMTP couse a disconnect , need to implement a reconnect
                                    if(!$smtp->isConnect()){
                                        $transport->reconnect($this->fromUser . '@' . $this->fromDomain);
                                    }
                                    $address = $user . '@' . $dom->getDomain();
                                    // Sets the results to an integer 0 ( failure ) or 1 ( success )

                                    // TODO: Impliment setter
                                    $this->statManager->updateStatus($address,$smtp->rcpt($address));

                                    if ($this->statManager->getStatus($address) == 1) {
                                        $loopStop = 1;
                                    }

                                    // TODO: Dynamic sleep if to much users
//                                    if( count($users) >= $options["sameDomainLimit"] ){
//                                        sleep(count($users));
//                                        $userCounter = 0;
//                                    }
                                    $smtp->noop();
                                }
                            }
                            // saying buh-bye if we're still connected, cause we're done here
                            if ($smtp->isConnect()) {
                                $smtp->rset();
                                $smtp->disconnect();
                            }
                        }

                    } else {
                        // we didn't get a good response to helo and should be disconnected already
                        $this->statManager->setStatus($users, $dom, $options['noCommIsValid'],'bad response on helo');
                    }
                } else {
                    $this->statManager->setStatus($users, $dom, 0,'no connection ');
                }

                // TODO: Create setter for domainMoreInfo
//                if ($options['domainMoreInfo']) {
//                    $this->results->get[$dom->getDomain()] = $dom->getDescription();
//                }
                $i++;
            }
        }
    }
}
