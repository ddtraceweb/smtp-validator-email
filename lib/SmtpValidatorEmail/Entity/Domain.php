<?php
/**
 * Created by JetBrains PhpStorm.
 * User: daviddjian
 * Date: 31/07/13
 * Time: 19:55
 * To change this template use File | Settings | File Templates.
 */

namespace SmtpValidatorEmail\Entity;

/**
 * Class Domain
 * @package SmtpValidatorEmail
 */
class Domain
{

    /**
     * @var
     */
    protected $domain;
    /**
     * @var array
     */
    protected $description = array();

    /**
     * @param $domain
     */
    public function __construct($domain)
    {
        $this->setDomain($domain);
    }

    /**
     * @param mixed $description
     */
    public function addDescription(array $description)
    {
        $this->description = array_merge($this->getDescription(), $description);
    }

    /**
     * @return array
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $domain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * @return mixed
     */
    public function getDomain()
    {
        return $this->domain;
    }


}