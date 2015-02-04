<?php
namespace SmtpValidatorEmail\Service;

use SmtpValidatorEmail\Entity\Domain;
use SmtpValidatorEmail\Entity\Results;

class StatusManager {

    /**
     * @var Results
     */
    private $results;

    public function __construct(){
        $this->model = new Results();
    }

    public function setStatus($users, Domain $domain, $val, $info='') {
        $this->results->setDomainResults($users,$domain, $val,$info);
    }

    public function updateStatus($address , $result){
        $this->results->setResultByAddress($address , $result);
    }

    /**
     * @param null $address optional , if set gets the status by address
     * @return array
     */
    public function getStatus( $address = null ) {
        if($address){
            return $this->results->getResultByAddress($address);
        }else {
            return $this->results->getResults();
        }

    }

    // TODO: Create loger method
}