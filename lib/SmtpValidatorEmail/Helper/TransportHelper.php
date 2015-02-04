<?php

namespace SmtpValidatorEmail\Helper;


use SmtpValidatorEmail\Helper\Interfaces\TransportInterface;
use SmtpValidatorEmail\Service\StatusManager;
use SmtpValidatorEmail\Smtp\Smtp;
use SmtpValidatorEmail\Exception as Exception ;

class TransportHelper implements TransportInterface{
    private $smtp;

    private $host;

    private $connected = false;

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
                    $this->connected = true;
                    break;
                }

            } catch (Exception\ExceptionNoConnection $e) {
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