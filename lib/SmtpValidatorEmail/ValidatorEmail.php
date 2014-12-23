<?php
/**
 * Created by JetBrains PhpStorm.
 * User: daviddjian
 * Date: 31/07/13
 * Time: 19:20
 * To change this template use File | Settings | File Templates.
 */

namespace SmtpValidatorEmail;

use SmtpValidatorEmail\Domain\Domain;
use SmtpValidatorEmail\Domain\DomainBag;
use SmtpValidatorEmail\Email\Email;
use SmtpValidatorEmail\Email\EmailBag;
use SmtpValidatorEmail\Exception\ExceptionNoConnection;
use SmtpValidatorEmail\Mx\Mx;
use SmtpValidatorEmail\Smtp\Smtp;


/**
 * Class ValidatorEmail
 * @package SmtpValidatorEmail
 */
class ValidatorEmail
{
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
     * @var array
     */
    protected $results = array();


    /**
     * @param array|string $emails
     * @param string $sender
     * @param array $options
     *
     * possible options :
     *
     *      'domainMoreInfo' (bool) for have more information on domains of users.
     *      'delaySleep' is an array of delay(s) possible after connected Server to send the request SMTP.
     *      'catchAllIsValid' (int) can be 0 for false or 1 for true . Are 'catch-all' accounts considered valid or not?
     *      'noCommIsValid' Being unable to communicate with the remote MTA could mean an address
     *                      is invalid, but it might not, depending on your use case, set the
     *                      value appropriately.
     */
    public function __construct($emails = array(), $sender = '', $options = array())
    {
        $defaultOptions = array(
            'domainMoreInfo' => false,
            'delaySleep' => array(0),
            'noCommIsValid' => 0,
            'catchAllIsValid' => 1,
        );

        $options = array_merge($defaultOptions,$options);

        if (!empty($emails)) {
            $emailBag = new EmailBag();
            $emailBag->add((array)$emails);
            $domainBag = $this->setEmailsDomains($emailBag);
            $this->domains = $domainBag->all();
        }

        if (!empty($sender)) {
            $this->setSender($sender);
        }

        if (!is_array($this->domains) || empty($this->domains)) {
            return $this->results;
        }

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

                $smtp = new Smtp(array('fromDomain' => $this->fromDomain, 'fromUser' => $this->fromUser));
                if (array_key_exists('timeout', $options)) {
                    $smtp->timeout = $options['timeout'];
                }

                // try each host
                while (list($host) = each($mxs)) {

                    // try connecting to the remote host
                    try {

                        $smtp->connect($host);

                        if ($smtp->isConnect()) {
                            break;
                        }

                    } catch (ExceptionNoConnection $e) {
                        // unable to connect to host, so these addresses are invalid?
                        $this->setDomainResults($users, $dom, 0,'unable to connect to host');
                    }
                }

                // are we connected?
                if ($smtp->isConnect()) {


                    sleep($options['delaySleep'][$i]);

                    // say helo, and continue if we can talk
                    if ($smtp->helo()) {

                        // try issuing MAIL FROM
                        if (!($smtp->mail($this->fromUser . '@' . $this->fromDomain))) {
                            // MAIL FROM not accepted, we can't talk
                            $this->setDomainResults($users, $dom, $options['noCommIsValid'],'MAIL FROM not accepted');
                        }

                        /**
                         * if we're still connected, proceed (cause we might get
                         * disconnected, or banned, or greylisted temporarily etc.)
                         * see mail() for more
                         */
                        if ( $smtp->isConnect()) {

                            $smtp->noop();

                            // Do a catch-all test for the domain always.
                            // This increases checking time for a domain slightly,
                            // but doesn't confuse users.
                            try{
                                $isCatchallDomain = $smtp->acceptsAnyRecipient($dom);
                            }catch (\Exception $e) {
                                $this->setDomainResults($users, $dom, $options['catchAllIsValid'], 'error while on CatchAll test: '.$e );
                            }


                            // if a catchall domain is detected, and we consider
                            // accounts on such domains as invalid, mark all the
                            // users as invalid and move on
                            if ($isCatchallDomain) {
                                if (!$options['catchAllIsValid']) {
                                    $this->setDomainResults($users, $dom, $options['catchAllIsValid'],'catch all detected');
                                    continue;
                                }
                            }

                            // if we're still connected, try issuing rcpts
                            if ($smtp->isConnect()) {
                                $smtp->noop();
                                // rcpt to for each user
                                foreach ($users as $user) {
                                    $address = $user . '@' . $dom->getDomain();
                                    $this->results[$address] = $smtp->rcpt($address);

                                    if ($this->results[$address] == 1) {
                                        $loopStop = 1;
                                    }
                                    $smtp->noop();
                                }
                            }

                            // saying buh-bye if we're still connected, cause we're done here
                            if ($smtp->isConnect()) {
                                // issue a rset for all the things we just made the MTA do
                                $smtp->rset();
                                // kiss it goodbye
                                $smtp->disconnect();
                            }

                        }

                    } else {
                        // we didn't get a good response to helo and should be disconnected already
                        $this->setDomainResults($users, $dom, $options['noCommIsValid'],'bad response on helo');
                    }
                } else {
                    $this->setDomainResults($users, $dom, 0,'no connection ');
                }

                if ($options['domainMoreInfo']) {
                    $this->results[$dom->getDomain()] = $dom->getDescription();
                }

                $i++;
            }
        }

        return $this->getResults();

    }

    /**
     *
     * @return array
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     *
     * Sets the email addresses that should be validated.
     *
     * @param EmailBag $emailBag
     *
     * @return DomainBag $domainBag
     */
    public function setEmailsDomains(EmailBag $emailBag)
    {
        $domainBag = new DomainBag();

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

    /**
     * Helper to set results for all the users on a domain to a specific value
     *
     * @param array $users    Array of users (usernames)
     * @param Domain $domain   The domain
     * @param int $val      Value to set
     * @param String $info  Optional , can be used to give additional information about the result
     */
    private function setDomainResults($users, Domain $domain, $val, $info='')
    {
        if (!is_array($users)) {
            $users = (array)$users;
        }

        foreach ($users as $user) {
            $this->results[$user . '@' . $domain->getDomain()] = array(
                'result' => $val,
                'info'   => $info
            );
        }
    }
}
