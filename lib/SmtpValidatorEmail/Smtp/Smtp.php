<?php
/**
 * Created by JetBrains PhpStorm.
 * User: daviddjian
 * Date: 31/07/13
 * Time: 21:24
 * To change this template use File | Settings | File Templates.
 */

namespace SmtpValidatorEmail\Smtp;

use SmtpValidatorEmail\Entity\Domain;
use SmtpValidatorEmail\Exception as Exception ;
use SmtpValidatorEmail\Configs\ConfigReader;
use SmtpValidatorEmail\Service\StatusManager;

/**
 * Class Smtp
 * @package SmtpValidatorEmail\Smtp
 */
class Smtp
{
    /**
     * @var
     */
    public $host;

    /**
     * @var
     */
    private $domain;
    
    /**
     * @var
     */
    public $socket;
    /**
     * @var int
     */
    public $timeout = 10;

    /**
     * do we consider "greylisted" responses as valid or invalid addresses
     */
    public $greyListedConsideredValid = true;

    public $state = array(
        'helo' => false,
        'mail' => false,
        'rcpt' => false
    );

    /**
     * @var array
     */
    private $debug = array();

    /**
     * @var mixed configs loaded from yml
     */
    private $config;

    /**
     * some utils constants
     */
    const CRLF = "\r\n";

    /**
     * list of codes considered as "greylisted"
     */
    private $greyListed;


    /**
     * @var StatusManager
     */
    private $statusManager;

    private $users ;

    public $options = array();
    public $validationOptions = array();
    /**
     * @param StatusManager $statusManager
     * @param $users array
     * @param array $options ("fromDomain" => value, "fromUser" => value);
     * must contains the variables from sender
     */
    public function __construct(StatusManager $statusManager, $users, array $options, array $validationOptions)
    {

        $this->config = ConfigReader::readConfigs(__DIR__.'/../Configs/smtp.yml');
        $this->statusManager = $statusManager;
        $this->greyListed = array(
            $this->config['responseCodes']['SMTP_MAIL_ACTION_NOT_TAKEN'],
            $this->config['responseCodes']['SMTP_MAIL_ACTION_ABORTED'],
            $this->config['responseCodes']['SMTP_REQUESTED_ACTION_NOT_TAKEN'],
        );
        $this->users = $users;
        $this->options = $options;
        $this->validationOptions = $validationOptions;
    }


    /**
     * Tries to connect to the specified host on the pre-configured port.
     *
     * @param string $host   The host to connect to
     *
     * @return String weather a error string or success string
     * @throws Exception\ExceptionNoConnection
     * @throws Exception\ExceptionNoTimeout
     */
    public function connect($host,$domain)
    {
        $remoteSocket = $host . ':' . $this->config['port'];
        $this->domain = $domain;
        $errnum       = 0;
        $errstr       = '';
        $this->host   = $remoteSocket;
        // open connection

        $this->socket = @stream_socket_client(
            $this->host,
            $errnum,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            stream_context_create($this->validationOptions['context'])
        );

        // connected?
        if (!$this->isConnect()) {
            return 'no connection';
        }

        $result = stream_set_timeout($this->socket, $this->timeout);

        if (!$result) {
            return 'Cannot set timeout';
        }
        return 'connected';
    }

    /**
     * Returns true if we're connected to an MTA
     * @return bool
     */
    public function isConnect()
    {
        return is_resource($this->socket);
    }

    /**
     * Returns smtp logs
     * @return array
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * @param Domain $domain
     *
     * @return bool
     */
    public function acceptsAnyRecipient(Domain $domain)
    {
        $test     = 'catch-all-test-' . time();
        $accepted = $this->rcpt($test . '@' . $domain->getDomain());
        if ($accepted) {
            $domain->addDescription(array('catchall' => 1));
            // success on a non-existing address is a "catch-all"
            return 1;
        }
        // log the case in which we get disconnected
        // while trying to perform a catchall detect
        $this->noop();
        if (!($this->isConnect())) {

        }
        // nb: disconnects are considered as a non-catch-all case this way
        // this might not be true always
        return 0;
    }

    /**
     * Sends a HELO/EHLO sequence
     * @todo Implement TLS, add logs
     * @return bool  True if successful, false otherwise
     */
    public function helo()
    {
        // don't try if it was already done
        if ($this->state['helo']) {
            return true;
        }

        try {
            $this->expect(
                $this->config['responseCodes']['SMTP_CONNECT_SUCCESS'],
                $this->config['commandTimeouts']['helo']
            );
            $this->ehlo();
            // session started
            $this->state['helo'] = true;

            //todo: are we going for a TLS connection?

            return true;
        } catch (Exception\ExceptionUnexpectedResponse $e) {
            // connected, but received an unexpected response, so disconnect
            $this->disconnect(false);

            return false;
        }
    }


    /**
     * Send EHLO or HELO, depending on what's supported by the remote host.
     * @return void
     */

    protected function ehlo()
    {
        try {
            // modern, timeout 5 minutes
            $this->send('EHLO ' . $this->options['fromDomain']);
            $this->expect($this->config['responseCodes']['SMTP_GENERIC_SUCCESS'], $this->config['commandTimeouts']['ehlo']);

        } catch (Exception\ExceptionUnexpectedResponse $e) {
            // legacy, timeout 5 minutes
            $this->send('HELO ' . $this->options['fromDomain']);
            $this->expect($this->config['responseCodes']['SMTP_GENERIC_SUCCESS'], $this->config['commandTimeouts']['helo']);
        }
    }

    /**
     * Sends a MAIL FROM command to indicate the sender.
     *
     * @param string $from   The "From:" address
     *
     * @return bool          If MAIL FROM command was accepted or not
     * @throws Exception\ExceptionNoHelo
     */
    public function mail($from)
    {
        if (!$this->state['helo']) {
            throw new Exception\ExceptionNoHelo('Need HELO before MAIL FROM');
        }
        try {
            // issue MAIL FROM, 5 minute timeout
            $this->send('MAIL FROM:<' . $from . '>');
            $this->expect($this->config['responseCodes']['SMTP_GENERIC_SUCCESS'], $this->config['commandTimeouts']['mail']);
            // set state flags
            $this->state['mail'] = true;
            $this->state['rcpt'] = false;

            return true;
        } catch (Exception\ExceptionUnexpectedResponse $e) {
            // got something unexpected in response to MAIL FROM
            // hotmail is know to do this, and is closing the connection
            // forcibly on their end, so I'm killing the socket here too
            $this->disconnect(false);

            return false;
        }
    }

    /**
     * Sends a RCPT TO command to indicate a recipient.
     *
     * @param string $to Recipient's email address
     *
     * @return bool      Is the recipient accepted
     * @throws Exception\ExceptionNoMailFrom
     */
    public function rcpt($to)
    {

        // need to have issued MAIL FROM first
        if (!$this->state['mail']) {
            $this->statusManager->setStatus($this->users,new Domain($this->domain),0,'Need MAIL FROM before RCPT TO');
            throw new Exception\ExceptionNoMailFrom('Need MAIL FROM before RCPT TO');
        }
        $expectedCodes = array(
            $this->config['responseCodes']['SMTP_GENERIC_SUCCESS'],
            $this->config['responseCodes']['SMTP_USER_NOT_LOCAL']
        );

        if ($this->greyListedConsideredValid) {
            $expectedCodes = array_merge($expectedCodes, $this->greyListed);
        }

        // issue RCPT TO, 5 minute timeout
        try {
            $this->send('RCPT TO:<' . $to . '>');
            // process the response
            try {
                $response = $this->expect($expectedCodes, $this->config['commandTimeouts']['rcpt']);
                $this->state['rcpt'] = true;
                $isValid             = 1;

                $this->statusManager->updateStatus($to, array(
                    'result' => $isValid,
                    'info' => "OK: {$response}"
                ));
            } catch (Exception\ExceptionUnexpectedResponse $e) {
                $this->statusManager->setStatus($this->users, new Domain($this->domain), 0, 'UnexpectedResponse: ' . $e->getMessage());
                $isValid = 0;
            }
        } catch (Exception\ExceptionSmtpValidatorEmail $e) {
            $this->statusManager->setStatus($this->users, new Domain($this->domain), 0, 'Sending RCPT TO failed: ' . $e->getMessage());
            $isValid = 0;
        }

        return $isValid;
    }

    /**
     * Sends a RSET command and resets our internal state.
     * @return void
     */
    public function rset()
    {
        $this->send('RSET');
        // MS ESMTP doesn't follow RFC according to ZF tracker, see [ZF-1377]
        $expected = array(
            $this->config['responseCodes']['SMTP_GENERIC_SUCCESS'],
            $this->config['responseCodes']['SMTP_CONNECT_SUCCESS'],
            // hotmail returns this o_O
            $this->config['responseCodes']['SMTP_TRANSACTION_FAILED']
        );
        $this->expect($expected, $this->config['commandTimeouts']['rset']);
        $this->state['mail'] = false;
        $this->state['rcpt'] = false;
    }

    /**
     * Sends a QUIT command.
     * @return void
     */
    public function quit()
    {
        // although RFC says QUIT can be issued at any time, we won't
        if ($this->state['helo']) {
            try {
                $this->send('QUIT');
                $this->expect($this->config['responseCodes']['SMTP_QUIT_SUCCESS'], $this->config['commandTimeouts']['quit']);
            } catch (Exception\ExceptionUnexpectedResponse $e) {}
        }
    }

    /**
     * Sends a NOOP command.
     * @return void
     */
    public function noop()
    {
        $this->send('NOOP');
        $this->expect($this->config['responseCodes']['SMTP_GENERIC_SUCCESS'], $this->config['commandTimeouts']['noop']);
    }

    /**
     * Sends a command to the remote host.
     *
     * @param string $cmd    The cmd to send
     *
     * @return int|bool      Number of bytes written to the stream
     * @throws Exception\ExceptionNoConnection
     * @throws Exception\ExceptionSendFailed
     */
    public function send($cmd)
    {
        // must be connected
        if (!$this->isConnect()) {
            $this->statusManager->setStatus($this->users,new Domain($this->domain),0,'No connection');
            return false;
        }

        $result = false;
        // write the cmd to the connection stream
        try {
            if ($this->validationOptions['debug'] === true) {
                $this->debug[] = ["timestamp" => microtime(true), "message" => "send> {$cmd}"];
            }

            $result = fwrite($this->socket, $cmd . self::CRLF);
        } catch (\Exception $e) {
            // did the send work?
            if ($result === false) {
                $this->statusManager->setStatus($this->users,new Domain($this->domain),0,'Send failed on: '. $this->host );
                return $result;
            }
        }

        return $result;
    }

    /**
     * Receives a response line from the remote host.
     *
     * @param int $timeout Timeout in seconds
     *
     * @return string
     * @throws Exception\ExceptionNoConnection
     * @throws Exception\ExceptionTimeout
     * @throws Exception\ExceptionNoResponse
     */
    public function recv($timeout = null)
    {
        // timeout specified?
        if ($timeout !== null) {
            stream_set_timeout($this->socket, $timeout);
        }
        // retrieve response
        $line = fgets($this->socket, 1024);

        if ($this->validationOptions['debug'] === true) {
            $this->debug[] = ["timestamp" => microtime(true), "message" => "received> {$line}"];
        }

        // have we timed out?
        $info = stream_get_meta_data($this->socket);
        if (!empty($info['timed_out'])) {
            throw new Exception\ExceptionTimeout('Timed out in recv');
        }
        // did we actually receive anything?
        if ($line === false) {
            throw new Exception\ExceptionNoResponse('No response in recv');
        }

        return $line;
    }

    /**
     * Receives lines from the remote host and looks for expected response codes.
     *
     * @param string|array $codes     A list of one or more expected response codes
     * @param int          $timeout   The timeout for this individual command, if any
     *
     * @return string        The last text message received
     * @throws Exception\ExceptionUnexpectedResponse
     */
    public function expect($codes, $timeout = null)
    {
        if (!is_array($codes)) {
            $codes = (array)$codes;
        }
        $code = null;
        $text = $line = '';
        try {

            $text = $line = $this->recv($timeout);
            while (preg_match("/^[0-9]+-/", $line)) {
                $line = $this->recv($timeout);
                $text .= $line;
            }

            sscanf($line, '%d%s', $code, $text);
            if ($code === null || !in_array($code, $codes)) {
                throw new Exception\ExceptionUnexpectedResponse($line);
            }

        } catch (Exception\ExceptionNoResponse $e) {

            // no response in expect() probably means that the
            // remote server forcibly closed the connection so
            // lets clean up on our end as well?
            $this->disconnect(false);

        } catch (Exception\ExceptionTimeout $e) {
            $this->disconnect(false);
        }

        return $line;
    }

    /**
     * Disconnects the currently connected MTA.
     *
     * @param bool $quit Issue QUIT before closing the socket on our end.
     *
     * @return void
     */
    public function disconnect($quit = true)
    {

        if ($quit) {
            $this->quit();
        }

        if ($this->isConnect()) {

            fclose($this->socket);
        }

        $this->host = null;
        $this->resetState();
    }

    /**
     * Resets internal state flags to defaults
     */
    private function resetState()
    {
        $this->state['helo'] = false;
        $this->state['mail'] = false;
        $this->state['rcpt'] = false;
    }


}
