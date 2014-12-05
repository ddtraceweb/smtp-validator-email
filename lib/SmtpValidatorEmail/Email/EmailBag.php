<?php
/**
 * Created by JetBrains PhpStorm.
 * User: daviddjian
 * Date: 31/07/13
 * Time: 20:37
 * To change this template use File | Settings | File Templates.
 */

namespace SmtpValidatorEmail\Email;


/**
 * Class EmailBag is a container for emails.
 *
 * @author  David DJIAN <david@traceweb.fr>
 *
 * @package SmtpValidatorEmail\Email
 */
class EmailBag implements \IteratorAggregate, \Countable
{

    /**
     * @var
     */
    protected $emails;

    /**
     * Constructor.
     *
     * @param array $emails An array of emails
     *
     */
    public function __construct(array $emails = array())
    {
        $this->emails = array();
        foreach ($emails as $key => $values) {
            $this->set($key, $values);
        }
    }

    /**
     * Returns the emails as a string.
     *
     * @return string The emails
     */
    public function __toString()
    {
        if (!$this->emails) {
            return '';
        }

        $max     = max(array_map('strlen', array_keys($this->emails))) + 1;
        $content = '';
        ksort($this->emails);
        foreach ($this->emails as $name => $values) {
            $name = implode('-', array_map('ucfirst', explode('-', $name)));
            foreach ($values as $value) {
                $content .= sprintf("%-{$max}s %s\r\n", $name . ':', $value);
            }
        }

        return $content;
    }

    /**
     * Returns the emails.
     *
     * @return array An array of emails
     *
     */
    public function all()
    {
        return $this->emails;
    }

    /**
     * Returns the parameter keys.
     *
     * @return array An array of parameter keys
     *
     */
    public function keys()
    {
        return array_keys($this->emails);
    }

    /**
     * Replaces the current emails by a new set.
     *
     * @param array $emails An array of emails
     *
     */
    public function replace(array $emails = array())
    {
        $this->emails = array();
        $this->add($emails);
    }

    /**
     * Adds new emails the current emails set.
     *
     * @param array $emails An array of emails
     *
     */
    public function add(array $emails)
    {
        foreach ($emails as $key => $values) {
            $this->set($key, $values);
        }
    }

    /**
     * Returns a email value by name.
     *
     * @param string  $key     The email name
     * @param mixed   $default The default value
     * @param Boolean $first   Whether to return the first value or all email values
     *
     * @return string|array The first email value if $first is true, an array of values otherwise
     *
     */
    public function get($key, $default = null, $first = true)
    {
        if(!is_string($key) && !is_int($key)){
            throw new \InvalidArgumentException('$key expected to be string or integer, got: '.gettype($key));
        }

        if (!array_key_exists($key, $this->emails)) {
            if (null === $default) {
                return $first ? null : array();
            }

            return $first ? $default : array($default);
        }

        if ($first) {
            return count($this->emails[$key]) ? $this->emails[$key][0] : $default;
        }

        return $this->emails[$key];
    }

    /**
     * Sets a email values.
     *
     * @param string|int   $key     The key
     * @param string|array $values  The value or an array of values
     * @param Boolean      $replace Whether to replace the actual value or not (true by default)
     *
     */
    public function set($key, $values, $replace = true)
    {
        if(!is_string($key) && !is_int($key)){
            throw new \InvalidArgumentException('$key expected to be string, got:'.gettype($key));
        }

        if( !is_string($values) && !is_array($values) ){
            throw new \InvalidArgumentException('$key expected to be string or array, got: '.gettype($key));
        }

        $values = array_values((array)$values);

        if (true === $replace || !isset($this->emails[$key])) {
            $this->emails[$key] = $values;
        } else {
            $this->emails[$key] = array_merge($this->emails[$key], $values);
        }
    }

    /**
     * Returns true if the email is defined.
     *
     * @param string $key The email
     *
     * @return Boolean true if the parameter exists, false otherwise
     *
     */
    public function has($key)
    {
        return array_key_exists($key, $this->emails);
    }

    /**
     * Returns true if the given email contains the given value.
     *
     * @param string $key   The email name
     * @param string $value The email value
     *
     * @return Boolean true if the value is contained in the email, false otherwise
     *
     */
    public function contains($key, $value)
    {
        return in_array($value, $this->get($key, null, false));
    }

    /**
     * Removes a email.
     *
     * @param string $key The email name
     *
     */
    public function remove($key)
    {
        unset($this->emails[$key]);
    }

    /**
     * Returns an iterator for emails.
     *
     * @return \ArrayIterator An \ArrayIterator instance
     */
    public function count()
    {
        return count($this->emails);
    }

    /**
     * Returns the number of emails.
     *
     * @return object The ArrayIterator object of emails
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->emails);
    }

}