<?php
namespace SmtpValidatorEmail\Entity;

class Results {

    private  $results = array();

    /**
     * Helper to set results for all the users on a domain to a specific value
     *
     * @param array $users    Array of users (usernames)
     * @param Domain $domain   The domain
     * @param int $val      Value to set 1 or 0 ( Valid,Invalid )
     * @param String $info  Optional , can be used to give additional information about the result
     */
    public function setDomainResults($users, Domain $domain, $val, $info='')
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

    public function hasResults() {
        return $this->results || false;
    }

    public function getResults() {
        return $this->results;
    }

    public function getResultByAddress($address) {
        return $this->results[$address];
    }

    public function setResultByAddress ($address , $result) {
        $this->results[$address] = $result;
    }
}