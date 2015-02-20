<?php

namespace SmtpValidatorEmail\Helper;


use SmtpValidatorEmail\Helper\Interfaces\TransportInterface;
use SmtpValidatorEmail\Service\StatusManager;
use SmtpValidatorEmail\Smtp\Smtp;

class TransportHelper implements TransportInterface{
    private $smtp;

    private $host;

    private $connected = false;

    private $domain;

    /**
     * @param StatusManager $statusManager
     * @param $users array
     * @param array $options FromDomain and FromUsers keys
     * @param $domain
     */
    public function __construct (StatusManager $statusManager,$users,$options,$domain) {
        $this->smtp = new Smtp($statusManager,$users,$options);
        $this->domain = $domain;
    }

    /**
     * @param $mxs
     * @return int|null|String
     */
    public function connect ($mxs) {
        $status = null;
        foreach($mxs as $host=>$priority){
            $connection = $this->smtp->connect($host,$this->domain);
            if ( $connection == 'connected' ) {
                $this->setHost($host);
                $status = 1;
                $this->connected = true;
                $this->smtp->state['mail'] = true;
                break;
            }else{
                $status = $connection;
            }
        }
        return $status;
    }

    public function reconnect($from) {
        $status = null;
        if($connection = $this->smtp->connect($this->host,$this->domain)=='connected'){
            $this->smtp->helo();
            if(!$this->smtp->mail($from)) {
                return 'MAIL FROM not accepted';
            }else {
                $this->connected = true;
            }
        }else {
            $status = $connection;
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