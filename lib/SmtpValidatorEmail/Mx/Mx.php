<?php
/**
 * Created by JetBrains PhpStorm.
 * User: daviddjian
 * Date: 31/07/13
 * Time: 19:25
 * To change this template use File | Settings | File Templates.
 */

namespace SmtpValidatorEmail\Mx;

class Mx
{
    public $mxs = array();
    /**
     * Queries the DNS server for domain MX entries
     *
     * @param string $domain The domain MX entries
     *
     * @return array         MX hosts and their weights
     */
    public function getEntries($domain)
    {
        $hosts  = array();
        $weight = array();

        getmxrr($domain, $hosts, $weight);

        // sort MX priorities
        foreach ($hosts as $k => $host) {
            $this->mxs[$host] = $weight[$k];
        }


        asort($this->mxs);

        // add the hostname with 0 weight (RFC 2821)
        $this->mxs[$domain] = 0;

        return $this->mxs;
    }
}