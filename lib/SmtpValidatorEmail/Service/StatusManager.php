<?php
namespace SmtpValidatorEmail\Service;

use SmtpValidatorEmail\Entity\Domain;
use SmtpValidatorEmail\Entity\Results;

class StatusManager {

    /**
     * @var Results
     */
    private $results = null;

    public function __construct(){
        $this->results = new Results();
    }

    /**
     * @param array $users    Array of users (usernames)
     * @param Domain $domain   The domain
     * @param int $val      Value to set
     * @param String $info  Optional , can be used to give additional information about the result
     */

    public function setStatus($users, Domain $domain, $val, $info='') {
        $this->results->setDomainResults($users,$domain, $val,$info);
    }

    public function updateStatus($address , $result){
        $this->results->setResultByAddress($address , $result);
    }

    /**
     * @param String $address optional , if set gets the status by address
     * @return array
     */
    public function getStatus( $address = '' ) {
        if($address){
            return $this->results->getResultByAddress($address);
        }else {
            return $this->results->getResults();
        }

    }

    public function checkStatus() {
        return $this->results->hasResults();
    }
    // TODO: Create loger method
}