<?php
/**
 * Created by JetBrains PhpStorm.
 * User: daviddjian
 * Date: 31/07/13
 * Time: 20:36
 * To change this template use File | Settings | File Templates.
 */

namespace SmtpValidatorEmail\Email;

/**
 * Class Email
 * @package SmtpValidatorEmail\Email
 */
class Email
{

    /**
     * @var
     */
    protected $email;

    /**
     * @param $email
     */
    public function __construct($email)
    {
        $this->setEmail($email);
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Parses an email string into respective user and domain parts and
     * returns those as an array.
     *
     * @return array        ['user', 'domain']
     */
    public function parse()
    {
        $parts  = explode('@', $this->getEmail());
        $domain = array_pop($parts);
        $user   = implode('@', $parts);

        return array($user, $domain);
    }
}