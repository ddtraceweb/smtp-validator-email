<?php

namespace SmtpValidatorEmail\Helper;


use SmtpValidatorEmail\Exception\ExceptionNoConnection;
use SmtpValidatorEmail\Helper\Interfaces\TransportInterface;
use SmtpValidatorEmail\Service\StatusManager;
use SmtpValidatorEmail\Smtp\Smtp;

class TransportHelper implements TransportInterface{
    private $smtp;

    private $host;

    private $connected = false;

    /**
     * @param StatusManager $statusManager
     * @param array $options FromDomain and FromUsers keys
     */
    public function __construct (StatusManager $statusManager,$options) {
        $this->smtp = new Smtp($statusManager,$options);
    }

    public function connect ($mxs) {

        $status = null;

        foreach($mxs as $host=>$priority){
            // try connecting to the remote host
            try {

                $this->smtp->connect($host);

                if ( $this->smtp->isConnect() ) {
                    $this->setHost($host);
                    $status = 1;
                    $this->connected = true;
                    break;
                }

            } catch (ExceptionNoConnection $e) {
                // unable to connect to host, so these addresses are invalid?
                $status = 'unable to connect to host';
            }
        }
        return $status;
    }

    public function reconnect($from) {
        $status = null;
        try {
            $this->smtp->connect($this->host);

            if($this->smtp->isConnect()){
                $this->smtp->helo();
                if(!$this->smtp->mail($from)) {
                    return 'MAIL FROM not accepted';
                }else {
                    $this->connected = true;
                }
            }
        } catch (ExceptionNoConnection $e) {
            // unable to connect to host, so these addresses are invalid?
            $status = 'unable to connect to host';
        }
        return $status;
    }

    public function disconnect() {
        $this->smtp->disconnect(true);
        $this->host = null;
        $this->connected = false;
    }

    public function isConnected () {
        return $this->connected;
    }

    /**
     * @return Smtp
     */
    public function getSmtp () {
        return $this->smtp;
    }

    public function getHost() {
        return $this->host;
    }

    public function setTimeout ( $timeout ) {
        $this->smtp->timeout = $timeout;
    }

    private function setHost( $host ) {
        $this->host = $host;
    }

}