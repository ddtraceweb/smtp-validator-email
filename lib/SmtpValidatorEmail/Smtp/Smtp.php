<?php
/**
 * Created by JetBrains PhpStorm.
 * User: daviddjian
 * Date: 31/07/13
 * Time: 21:24
 * To change this template use File | Settings | File Templates.
 */

namespace SmtpValidatorEmail\Smtp;

use SmtpValidatorEmail\Domain\Domain;
use SmtpValidatorEmail\Exception\ExceptionNoConnection;
use SmtpValidatorEmail\Exception\ExceptionNoHelo;
use SmtpValidatorEmail\Exception\ExceptionNoMailFrom;
use SmtpValidatorEmail\Exception\ExceptionNoResponse;
use SmtpValidatorEmail\Exception\ExceptionNoTimeout;
use SmtpValidatorEmail\Exception\ExceptionSendFailed;
use SmtpValidatorEmail\Exception\ExceptionSmtpValidatorEmail;
use SmtpValidatorEmail\Exception\ExceptionTimeout;
use SmtpValidatorEmail\Exception\ExceptionUnexpectedResponse;

/**
 * Class Smtp
 * @package SmtpValidatorEmail\Smtp
 */
class Smtp
{
    /**
     * default smtp port
     * @var int
     */
    public $port = 25;
    /**
     * @var
     */
    public $host;
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

    private $state = array(
        'helo' => false,
        'mail' => false,
        'rcpt' => false
    );

    /**
     * Timeout values for various commands (in seconds) per RFC 2821
     * @see expect()
     */
    protected $commandTimeouts = array(
        'ehlo' => 120,
        'helo' => 120,
        'tls'  => 180, // start tls
        'mail' => 300, // mail from
        'rcpt' => 300, // rcpt to,
        'rset' => 30,
        'quit' => 60,
        'noop' => 60
    );

    /**
     * some utils contants
     */
    const CRLF = "\r\n";

    /**
     * some smtp response codes
     */
    const SMTP_CONNECT_SUCCESS     = 220;
    const SMTP_QUIT_SUCCESS        = 221;
    const SMTP_GENERIC_SUCCESS     = 250;
    const SMTP_USER_NOT_LOCAL      = 251;
    const SMTP_CANNOT_VRFY         = 252;
    const SMTP_SERVICE_UNAVAILABLE = 421;

    /**
     * 450  Requested mail action not taken: mailbox unavailable
     * (e.g., mailbox busy or temporarily blocked for policy reasons)
     */
    const SMTP_MAIL_ACTION_NOT_TAKEN = 450;

    /**
     * 451  Requested action aborted: local error in processing
     */
    const SMTP_MAIL_ACTION_ABORTED = 451;

    /**
     * 452  Requested action not taken: insufficient system storage
     */
    const SMTP_REQUESTED_ACTION_NOT_TAKEN = 452;

    /**
     * 550  Requested action not taken: mailbox unavailable (e.g., mailbox
     * not found, no access, or command rejected for policy reasons)
     */
    const SMTP_MBOX_UNAVAILABLE = 550;

    /**
     * 554  Seen this from hotmail MTAs, in response to RSET
     */
    const SMTP_TRANSACTION_FAILED = 554;

    /**
     * list of codes considered as "greylisted"
     */
    private $greyListed = array(
        self::SMTP_MAIL_ACTION_NOT_TAKEN,
        self::SMTP_MAIL_ACTION_ABORTED,
        self::SMTP_REQUESTED_ACTION_NOT_TAKEN
    );

    public $options = array();


    /**
     *
     * must contains the variables from sender
     *
     * array ("fromDomain" => value, "fromUser" => value);
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }


    /**
     * Tries to connect to the specified host on the pre-configured port.
     *
     * @param string $host   The host to connect to
     *
     * @return void
     * @throws ExceptionNoConnection
     * @throws ExceptionNoTimeout
     */
    public function connect($host)
    {
        $remoteSocket = $host . ':' . $this->port;
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
            stream_context_create(array())
        );

        // connected?
        if (!$this->isConnect()) {
            throw new ExceptionNoConnection('Cannot open a connection to remote host (' . $this->host . ')');
        }

        $result = stream_set_timeout($this->socket, $this->timeout);

        if (!$result) {
            throw new ExceptionNoTimeout('Cannot set timeout');
        }
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
     * @todo Implement TLS
     * @return bool  True if successful, false otherwise
     */
    public function helo()
    {
        // don't try if it was already done
        if ($this->state['helo']) {
            return true;
        }

        try {

            $this->expect(self::SMTP_CONNECT_SUCCESS, $this->commandTimeouts['helo']);
            $this->ehlo();
            // session started
            $this->state['helo'] = true;

            //todo: are we going for a TLS connection?

            return true;
        } catch (ExceptionUnexpectedResponse $e) {
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
            $this->expect(self::SMTP_GENERIC_SUCCESS, $this->commandTimeouts['ehlo']);

        } catch (ExceptionUnexpectedResponse $e) {
            // legacy, timeout 5 minutes
            $this->send('HELO ' . $this->options['fromDomain']);
            $this->expect(self::SMTP_GENERIC_SUCCESS, $this->commandTimeouts['helo']);
        }
    }

    /**
     * Sends a MAIL FROM command to indicate the sender.
     *
     * @param string $from   The "From:" address
     *
     * @return bool          If MAIL FROM command was accepted or not
     * @throws ExceptionNoHelo
     */
    public function mail($from)
    {
        if (!$this->state['helo']) {
            throw new ExceptionNoHelo('Need HELO before MAIL FROM');
        }
        // issue MAIL FROM, 5 minute timeout
        $this->send('MAIL FROM:<' . $from . '>');
        try {

            $this->expect(self::SMTP_GENERIC_SUCCESS, $this->commandTimeouts['mail']);
            // set state flags
            $this->state['mail'] = true;
            $this->state['rcpt'] = false;

            return true;
        } catch (ExceptionUnexpectedResponse $e) {
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
     * @throws ExceptionNoMailFrom
     */
    public function rcpt($to)
    {
        // need to have issued MAIL FROM first
        if (!$this->state['mail']) {
            throw new ExceptionNoMailFrom('Need MAIL FROM before RCPT TO');
        }
        $isValid       = 0;
        $expectedCodes = array(
            self::SMTP_GENERIC_SUCCESS,
            self::SMTP_USER_NOT_LOCAL
        );
        if ($this->greyListedConsideredValid) {
            $expectedCodes = array_merge($expectedCodes, $this->greyListed);
        }
        // issue RCPT TO, 5 minute timeout
        try {
            $this->send('RCPT TO:<' . $to . '>');
            // process the response
            try {
                $this->expect($expectedCodes, $this->commandTimeouts['rcpt']);
                $this->state['rcpt'] = true;
                $isValid             = 1;
            } catch (ExceptionUnexpectedResponse $e) {
                //'Unexpected response to RCPT TO: ' . $e->getMessage();
            }
        } catch (ExceptionSmtpValidatorEmail $e) {
            //'Sending RCPT TO failed: ' . $e->getMessage()
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
            self::SMTP_GENERIC_SUCCESS,
            self::SMTP_CONNECT_SUCCESS,
            // hotmail returns this o_O
            self::SMTP_TRANSACTION_FAILED
        );
        $this->expect($expected, $this->commandTimeouts['rset']);
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
            // [TODO] might need a try/catch here to cover some edge cases...
            $this->send('QUIT');
            $this->expect(self::SMTP_QUIT_SUCCESS, $this->commandTimeouts['quit']);
        }
    }

    /**
     * Sends a NOOP command.
     * @return void
     */
    public function noop()
    {
        $this->send('NOOP');
        $this->expect(self::SMTP_GENERIC_SUCCESS, $this->commandTimeouts['noop']);
    }

    /**
     * Sends a command to the remote host.
     *
     * @param string $cmd    The cmd to send
     *
     * @return int|bool      Number of bytes written to the stream
     * @throws ExceptionNoConnection
     * @throws ExceptionSendFailed
     */
    public function send($cmd)
    {
        // must be connected
        if (!$this->isConnect()) {
            throw new ExceptionNoConnection('No connection');
        }

        // write the cmd to the connection stream
        $result = fwrite($this->socket, $cmd . self::CRLF);
        // did the send work?
        if ($result === false) {
            throw new ExceptionSendFailed('Send failed ' .
            'on: ' . $this->host );
        }

        return $result;
    }

    /**
     * Receives a response line from the remote host.
     *
     * @param int $timeout Timeout in seconds
     *
     * @return string
     * @throws ExceptionNoConnection
     * @throws ExceptionTimeout
     * @throws ExceptionNoResponse
     */
    public function recv($timeout = null)
    {
        if (!$this->isConnect()) {
            throw new ExceptionNoConnection('No connection');
        }
        // timeout specified?
        if ($timeout !== null) {
            stream_set_timeout($this->socket, $timeout);
        }
        // retrieve response
        $line = fgets($this->socket, 1024);

        // have we timed out?
        $info = stream_get_meta_data($this->socket);
        if (!empty($info['timed_out'])) {
            throw new ExceptionTimeout('Timed out in recv');
        }
        // did we actually receive anything?
        if ($line === false) {
            throw new ExceptionNoResponse('No response in recv');
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
     * @throws ExceptionUnexpectedResponse
     */
    public function expect($codes, $timeout = null)
    {
        if (!is_array($codes)) {
            $codes = (array)$codes;
        }
        $code = null;
        $text = '';
        try {

            $text = $line = $this->recv($timeout);
            while (preg_match("/^[0-9]+-/", $line)) {
                $line = $this->recv($timeout);
                $text .= $line;
            }
            sscanf($line, '%d%s', $code, $text);
            if ($code === null || !in_array($code, $codes)) {
                throw new ExceptionUnexpectedResponse($line);
            }

        } catch (ExceptionNoResponse $e) {

            // no response in expect() probably means that the
            // remote server forcibly closed the connection so
            // lets clean up on our end as well?
            $this->disconnect(false);

        }

        return $text;
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
